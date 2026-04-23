# 📡 Sistema de Alertas WhatsApp — Documentación Técnica

> **Ubicación:** `api.batidospitaya.com/api/alertas/`  
> **Versión inicial:** Abril 2026  
> **Alertas implementadas:** `alerta_conexion_pc`, `alerta_anulacion_web`

---

## 1. Visión General

El sistema de alertas permite que **PitayaBot** (servidor DigitalOcean) envíe mensajes automáticos de WhatsApp a colaboradores cuando se detectan eventos críticos en el ERP.

### Flujo de triangulación

```
[ERP / Base de datos]
        ↑ consulta SQL
[api.batidospitaya.com/api/alertas/check_all.php]
        ↑ GET cada 1 min (X-WSP-Token)
[PitayaBot — DigitalOcean]
        ↓ sendMessage por cada destinatario
[Colaboradores — WhatsApp]
```

### Características del sistema

| Característica | Detalle |
|---|---|
| **Frecuencia de polling** | Cada 1 minuto (cron `* * * * *` en `scheduler.js`) |
| **Anti-spam** | Tabla `alertas_wsp_estado` — UNIQUE por `(tipo_alerta, key_unica)` |
| **Autenticación** | Header `X-WSP-Token` — mismo token usado por todo el bot |
| **Sin destinatarios** | La alerta se omite y reintenta el próximo minuto |
| **Horario** | 24/7, sin restricción |
| **Envío** | El bot envía directamente (no delega a PHP) |
| **Delay anti-ban** | 1.5 segundos entre cada mensaje enviado |

---

## 2. Estructura de Archivos

```
api/alertas/
├── README.md                    ← Este archivo
├── check_all.php                ← Orquestador (endpoint que llama PitayaBot)
├── alerta_conexion_pc.php       ← Alerta: PC offline ≥ 60 min
├── alerta_anulacion_web.php     ← Alerta: Anulación web pendiente del día
└── alerta_[nombre_nuevo].php    ← (Futuras alertas)
```

---

## 3. Tablas de Base de Datos

### `alertas_wsp_estado` — Control anti-spam

```sql
CREATE TABLE alertas_wsp_estado (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    tipo_alerta  VARCHAR(100)  NOT NULL COMMENT 'Ej: conexion_pc, anulacion_web',
    key_unica    VARCHAR(255)  NOT NULL COMMENT 'Identificador único del evento',
    datos_json   JSON          NULL     COMMENT 'Contexto extra al momento del envío',
    enviado_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_alerta (tipo_alerta, key_unica)
);
```

**Regla fundamental:** Si `(tipo_alerta, key_unica)` ya existe → nunca se vuelve a alertar ese evento.

> **Diseño de `key_unica` por tipo:**
> - **PC offline:** `"{sucursal_codigo}-{pc_nombre}-{ping_at}"` → incluye timestamp del último ping para auto-reset natural cuando la PC se reconecta y vuelve a caer.
> - **Anulación web:** `"{CodAnulacionHost}"` → una sola alerta por solicitud, sin reseteo.
> - **Futuras alertas:** usar el ID del registro disparador como key, o una combinación que identifique unívocamente el evento.

### `tools_erp` — Registro de la alerta

Cada alerta debe estar registrada en `tools_erp` con `tipo_componente = 'alerta'`:

```sql
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, icono, orden, activo)
VALUES (
    'alerta_nombre',          -- snake_case, debe coincidir con el nombre del archivo PHP
    'Título de la Alerta',
    'alerta',
    'sistemas',               -- o el módulo correspondiente
    'Descripción de cuándo y por qué se envía',
    'api.batidospitaya.com/api/alertas/alerta_nombre.php',
    'fas fa-icon',
    30,                       -- orden en el sidebar de gestion_permisos
    1
);
```

### `acciones_tools_erp` — Acción `recibir`

Cada alerta tiene exactamente **una acción**: `recibir`.

```sql
SET @tool_id = (SELECT id FROM tools_erp WHERE nombre = 'alerta_nombre' LIMIT 1);

INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
VALUES (@tool_id, 'recibir', 'El cargo recibe la alerta por WhatsApp cuando se cumple la condición.');
```

### `permisos_tools_erp` — Qué cargos reciben la alerta

Se gestiona desde la UI en `gestion_permisos.php → pestaña Alertas`.  
También se puede insertar directamente:

```sql
SET @accion_id = (SELECT ac.id FROM acciones_tools_erp ac
                  JOIN tools_erp t ON t.id = ac.tool_erp_id
                  WHERE t.nombre = 'alerta_nombre' AND ac.nombre_accion = 'recibir' LIMIT 1);

INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
VALUES
    (@accion_id, 16, 'allow'),   -- Gerencia General
    (@accion_id, 49, 'allow');   -- Gerencia Proyectos
```

---

## 4. Contrato de Cada Archivo de Alerta

Cada archivo de alerta (ej: `alerta_nombre.php`) **no es un endpoint directo** — es incluido por `check_all.php` mediante `require`. Debe:

1. **Incluir** `require_once __DIR__ . '/../../core/database/conexion.php';`
2. **Definir** `obtenerDestinatariosAlerta()` con `if (!function_exists(...))` (evita conflicto al ser incluido junto a otros)
3. **Detectar** los registros que cumplen la condición de alerta
4. **Verificar** si ya existe en `alertas_wsp_estado` (`NOT EXISTS` en el SQL principal)
5. **Verificar** que haya destinatarios → si no hay, `return ['alertas' => []]`
6. **Registrar** en `alertas_wsp_estado` con `INSERT IGNORE` **antes** de retornar
7. **Retornar** el array con formato estándar:

```php
return ['alertas' => $alertas];
```

### Formato del array retornado

```php
[
    'alertas' => [
        [
            'tipo'          => 'nombre_alerta',        // snake_case, igual que tools_erp.nombre
            'key_unica'     => 'identificador-evento', // lo que se guardó en alertas_wsp_estado
            'mensaje'       => "🔔 *Título*\n...",     // texto WhatsApp con emojis y *bold*
            'destinatarios' => ['88001234', '88005678'] // sin código de país (se agrega 505 en el bot)
        ],
        // ... más alertas del mismo tipo si hay múltiples eventos
    ]
]
```

---

## 5. Query de Destinatarios (Reutilizable)

```php
function obtenerDestinatariosAlerta(PDO $conn, string $nombreAlerta): array
{
    $stmt = $conn->prepare("
        SELECT DISTINCT o.telefono_corporativo
        FROM Operarios o
        INNER JOIN AsignacionNivelesCargos anc
            ON anc.CodOperario = o.CodOperario
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            AND anc.Fecha <= CURDATE()
        INNER JOIN permisos_tools_erp p
            ON p.CodNivelesCargos = anc.CodNivelesCargos
            AND p.permiso = 'allow'
        INNER JOIN acciones_tools_erp ac
            ON ac.id = p.accion_tool_erp_id
            AND ac.nombre_accion = 'recibir'
        INNER JOIN tools_erp t
            ON t.id = ac.tool_erp_id
            AND t.tipo_componente = 'alerta'
            AND t.nombre = :nombre
        WHERE o.telefono_corporativo IS NOT NULL
          AND o.telefono_corporativo != ''
    ");
    $stmt->execute([':nombre' => $nombreAlerta]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
```

> **Importante:** `telefono_corporativo` está guardado sin código de país (8 dígitos Nicaragua).  
> El bot agrega `505` automáticamente al armar el JID: `505{numero}@c.us`.

---

## 6. `check_all.php` — Cómo agregar una nueva alerta

Abrir `check_all.php` y agregar un bloque en la sección de "futuras alertas":

```php
// ── Alerta N: Nombre descriptivo ──────────────────────────────
try {
    $resultado = require __DIR__ . '/alerta_nombre_nuevo.php';
    if (!empty($resultado['alertas'])) {
        $todasLasAlertas = array_merge($todasLasAlertas, $resultado['alertas']);
    }
} catch (Throwable $e) {
    error_log('[check_all] alerta_nombre_nuevo falló: ' . $e->getMessage());
}
```

> Cada bloque está aislado en `try/catch` — si una alerta falla, las demás no se ven afectadas.

---

## 7. `scheduler.js` — Sin cambios necesarios

El cron de alertas ya está registrado y llama a `check_all.php`:

```js
cron.schedule('* * * * *', () => {
    ejecutarAlertas(clienteWA);
}, { timezone: TZ });
```

**No es necesario modificar `scheduler.js`** al agregar nuevas alertas. Solo se modifica `check_all.php` y los archivos de la alerta nueva.

---

## 8. `gestion_permisos.php` — Sin cambios necesarios

La pestaña **Alertas** ya existe y carga automáticamente cualquier alerta registrada en `tools_erp` con `tipo_componente = 'alerta'`. Al insertar la nueva alerta en la BD, aparecerá en el sidebar para configurar cargos.

---

## 9. Plantilla Completa para Nueva Alerta

### Paso 1 — SQL

```sql
-- 1. Registrar en tools_erp
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, icono, orden, activo)
VALUES (
    'alerta_nombre_nuevo',
    'Título Legible',
    'alerta',
    'sistemas',
    'Descripción de cuándo se dispara.',
    'api.batidospitaya.com/api/alertas/alerta_nombre_nuevo.php',
    'fas fa-icon',
    30,
    1
) ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), activo = 1;

-- 2. Registrar acción recibir
SET @tool_id = (SELECT id FROM tools_erp WHERE nombre = 'alerta_nombre_nuevo' LIMIT 1);
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
VALUES (@tool_id, 'recibir', 'El cargo recibe la alerta por WhatsApp.')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);
```

### Paso 2 — Archivo PHP

```php
<?php
/**
 * alerta_nombre_nuevo.php — [Descripción breve]
 *
 * Condiciones: [Describir cuándo se dispara]
 * key_unica:   [Describir cómo se forma]
 * Reset:       [Describir si se resetea o es permanente]
 *
 * Llamado por: api/alertas/check_all.php
 */

require_once __DIR__ . '/../../core/database/conexion.php';

if (!function_exists('obtenerDestinatariosAlerta')) {
    function obtenerDestinatariosAlerta(PDO $conn, string $nombreAlerta): array
    {
        // ... (copiar de alerta_conexion_pc.php)
    }
}

try {
    // 1. Detectar eventos que cumplen la condición Y no han sido alertados
    $stmt = $conn->prepare("
        SELECT
            campo1,
            campo2,
            CONCAT(...) AS key_unica
        FROM tabla_fuente t
        WHERE condicion = valor
          AND NOT EXISTS (
              SELECT 1 FROM alertas_wsp_estado
              WHERE tipo_alerta = 'nombre_alerta'
                AND key_unica = CONCAT(...)
          )
    ");
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($registros)) {
        return ['alertas' => []];
    }

    // 2. Verificar destinatarios
    $destinatarios = obtenerDestinatariosAlerta($conn, 'alerta_nombre_nuevo');
    if (empty($destinatarios)) {
        return ['alertas' => []]; // reintenta el próximo minuto
    }

    // 3. Construir alertas y registrar
    $alertas    = [];
    $stmtInsert = $conn->prepare("
        INSERT IGNORE INTO alertas_wsp_estado (tipo_alerta, key_unica, datos_json)
        VALUES ('nombre_alerta', :key, :datos)
    ");

    foreach ($registros as $r) {
        $mensaje = "🔔 *Alerta: Título*\n" .
                   "📍 Campo: {$r['campo1']}\n" .
                   "🔗 https://erp.batidospitaya.com/modulos/.../pagina.php";

        $stmtInsert->execute([
            ':key'   => $r['key_unica'],
            ':datos' => json_encode($r, JSON_UNESCAPED_UNICODE),
        ]);

        $alertas[] = [
            'tipo'          => 'nombre_alerta',
            'key_unica'     => $r['key_unica'],
            'mensaje'       => $mensaje,
            'destinatarios' => $destinatarios,
        ];
    }

    return ['alertas' => $alertas];

} catch (Exception $e) {
    error_log('[alerta_nombre_nuevo] ' . $e->getMessage());
    return ['alertas' => []];
}
```

### Paso 3 — Agregar a `check_all.php`

```php
try {
    $resultado = require __DIR__ . '/alerta_nombre_nuevo.php';
    if (!empty($resultado['alertas'])) {
        $todasLasAlertas = array_merge($todasLasAlertas, $resultado['alertas']);
    }
} catch (Throwable $e) {
    error_log('[check_all] alerta_nombre_nuevo falló: ' . $e->getMessage());
}
```

### Paso 4 — Configurar cargos en ERP

Ir a `gestion_permisos.php → pestaña Alertas → seleccionar la nueva alerta → acción "recibir" → activar cargos`.

---

## 10. Guía de Formato de Mensajes WhatsApp

WhatsApp soporta formato básico en mensajes de texto:

| Formato | Sintaxis | Ejemplo |
|---|---|---|
| **Negrita** | `*texto*` | `*Alerta Crítica*` |
| _Cursiva_ | `_texto_` | `_hace 2 horas_` |
| ~~Tachado~~ | `~texto~` | `~resuelto~` |
| Monoespaciado | ` ```texto``` ` | códigos |
| Salto de línea | `\n` | entre secciones |

### Estructura recomendada de mensaje

```
{emoji_tipo} *Alerta: {Título}*
{emoji} {Campo 1}: {Valor}
{emoji} {Campo 2}: {Valor}
{emoji} {Campo 3}: {Valor}
🔗 https://erp.batidospitaya.com/modulos/.../pagina.php
```

### Emojis sugeridos por contexto

| Contexto | Emoji |
|---|---|
| Error / Crítico | 🔴 |
| Advertencia | ⚠️ |
| Información | 🔵 |
| Éxito / Resuelto | ✅ |
| Sucursal / Ubicación | 📍 |
| PC / Computadora | 💻 |
| Tiempo / Hora | 🕐 ⏱ |
| Pedido / Paquete | 📦 |
| Dinero | 💰 |
| Persona | 👤 |
| Link | 🔗 |
| Motivo / Nota | 📋 |

---

## 11. Alertas Implementadas

| Nombre | Archivo | Condición | key_unica | Reset |
|---|---|---|---|---|
| `alerta_conexion_pc` | `alerta_conexion_pc.php` | PC offline ≥ 60 min | `{sucursal}-{pc_nombre}-{ping_at}` | Auto (cambio de ping_at al reconectarse) |
| `alerta_anulacion_web` | `alerta_anulacion_web.php` | Anulación web pendiente del día en VentasGlobalesAccessCSV | `{CodAnulacionHost}` | Nunca (una sola notificación por solicitud) |

---

## 12. Notas Técnicas Importantes

- **`HoraSolicitada` en Access:** Tiene fecha ~1988 (año cero de Access). Siempre usar `TIME(HoraSolicitada)` para extraer solo la hora.
- **`v.local` en `VentasGlobalesAccessCSV`:** Es VARCHAR numérico (ej: `'13'`). El formato `'S13'` está desfasado — no usarlo.
- **Teléfono corporativo:** Se guarda sin código de país (8 dígitos). El bot agrega `505` al armar `{numero}@c.us`.
- **`INSERT IGNORE`:** Se usa en lugar de `INSERT` normal para prevenir errores de duplicate key en condiciones de carrera (2 ciclos simultáneos del bot).
- **Sin `activo` en `alertas_wsp_estado`:** El UNIQUE key es la única barrera. No se necesita columna de estado activo/inactivo — el diseño de `key_unica` maneja los reinicios.
- **Función `obtenerDestinatariosAlerta`:** Siempre definirla con `if (!function_exists(...))` porque múltiples archivos la definen y `check_all.php` los requiere en el mismo request.
