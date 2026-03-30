---
name: ERP Batidos Pitaya Development
description: Comprehensive skill for developing modules and tools in the Batidos Pitaya ERP system following established standards and architecture
---

# ERP Batidos Pitaya Development Skill

Esta skill te guÃ­a en el desarrollo de mÃ³dulos y herramientas para el Sistema ERP de Batidos Pitaya, siguiendo los estÃ¡ndares establecidos en la documentaciÃ³n del proyecto.

## ðŸŽ¯ Contexto del Proyecto

**Sistema**: ERP modular para Batidos Pitaya  
**Stack**: PHP + MySQL  
**Arquitectura**: Componentes globales compartidos con estructura estandarizada  
**Ritmo**: ~1 herramienta completa por dÃ­a  

## ðŸ”„ Sistema de SincronizaciÃ³n PitayaCore (Iron Sync v6)

El ecosistema de Batidos Pitaya utiliza un sistema de **SincronizaciÃ³n de Hierro (Iron Sync)** descentralizado y de alta estabilidad para mantener la coherencia del cÃ³digo compartido entre todos los subdominios (ERP, API, Talento, etc.).

### ðŸ“ Fuente de Verdad: `PitayaCore`
- **Repositorio Central**: `MiguelGotea/PitayaCore`
- **Carpetas Gestionadas**: `/core`, `/docs`, `/.agent`
- **Regla de Oro**: Todo cambio en componentes globales o documentaciÃ³n debe nacer o consolidarse en `PitayaCore`.

### ðŸš€ Flujo de Trabajo del Desarrollador (IA)
1. **Desarrollo Centralizado**: Realiza siempre los cambios globales en el repositorio `PitayaCore`.
2. **Despliegue Maestro**: Usa el script de push para disparar la sincronizaciÃ³n en la nube:
   ```powershell
   # Desde c:\...\VisualCode\PitayaCore
   .\.scripts\gitpush.ps1
   ```
3. **SincronizaciÃ³n Local**: Para actualizar tus otros repositorios locales (`api`, `erp`, `talento`), usa el script unificado en la raÃ­z:
   ```powershell
   # Desde c:\...\VisualCode
   .\gitsync-local.ps1
   ```

### ðŸ›¡ï¸ Iron Sync v6: Estabilidad y Seguridad
El sistema de GitHub Actions estÃ¡ blindado con las siguientes protecciones:
- **ClonaciÃ³n Fresca**: No usa cachÃ©; descarga una copia limpia de `PitayaCore` para evitar cÃ³digo obsoleto.
- **ValidaciÃ³n Flexible de SHA**: Verifica que el commit recibido sea el correcto o uno mÃ¡s reciente, con un bucle de reintento (5 intentos x 10s) para combatir el retardo de GitHub.
- **Cola de Seguridad (Concurrency)**: Si realizas varios pushes seguidos, GitHub los encola ordenadamente para evitar que las actualizaciones choquen entre sÃ­.
- **Escudo de Credenciales**: Valida automÃ¡ticamente que las llaves de Hostinger estÃ©n configuradas antes de intentar cualquier despliegue.

### ðŸ› ï¸ Herramientas de Control
- `PitayaCore/.scripts/gitpush.ps1`: Dispara la actualizaciÃ³n global en GitHub.
- `VisualCode/gitsync-local.ps1`: Mantiene todos tus repositorios locales al dÃ­a de forma quirÃºrgica y segura.

## ðŸ“‹ Antes de Empezar

Cuando el usuario solicite crear una nueva herramienta, **SIEMPRE pregunta**:

1. **Nombre del mÃ³dulo** (ej: cupones, auditorÃ­as, vacaciones)
2. **Ãrea/Carpeta** (marketing, rrhh, operaciones, gerencia, etc.)
3. **Funcionalidades especÃ­ficas** requeridas
4. **Permisos necesarios** (ademÃ¡s del obligatorio `vista`)
5. **Sufijo para archivos y tablas** (ej: `cupones_`, `vacaciones_`)

## ðŸ—ï¸ Arquitectura Obligatoria

### Estructura de Archivos por MÃ³dulo

```
ðŸ“ modulos/{area}/
â”œâ”€â”€ {herramienta}.php              # Archivo principal
â”œâ”€â”€ uploads/                        # Archivos subidos (max 10MB)
â”œâ”€â”€ css/
â”‚   â””â”€â”€ {herramienta}.css          # CSS personalizado
â”œâ”€â”€ js/
â”‚   â””â”€â”€ {herramienta}.js           # JavaScript personalizado
â””â”€â”€ ajax/
    â”œâ”€â”€ {herramienta}_guardar.php
    â”œâ”€â”€ {herramienta}_get_datos.php
    â”œâ”€â”€ {herramienta}_get_opciones_filtro.php
    â”œâ”€â”€ {herramienta}_get_{item}.php
    â””â”€â”€ {herramienta}_eliminar.php
```

### Ãreas del Sistema

- `ventas/` - Historial de ventas, cupones
- `rh/` - Recursos humanos
- `operaciones/` - GestiÃ³n de sucursales
- `marketing/` - Cupones, promociones
- `supervision/` - AuditorÃ­as y control
- `sucursales/` - Herramientas de punto de venta
- `sistemas/` - Control de permisos
- `mantenimiento/` - GestiÃ³n de activos
- `gerencia/` - DirecciÃ³n general
- `compras/` - GestiÃ³n de OC, facturas
- `contabilidad/` - Descarga de datos
- Y mÃ¡s... (ver docs/00_Instrucciones_Generales.md lÃ­neas 31-54)

## ðŸŽ¨ Identidad Visual

### Colores Corporativos

```css
/* Color principal */
--color-principal: #51B8AC;

/* Encabezado de tablas */
--color-header-tabla: #0E544C;

/* Botones de acciÃ³n */
--btn-nuevo: #218838;
--btn-nuevo-hover: #1d6f42;
--btn-principal: #51B8AC;
```

### TipografÃ­a

```css
font-family: 'Calibri', sans-serif;
font-size: clamp(12px, 2vw, 18px);
```

### Principios de DiseÃ±o

- âŒ **NO usar degradados**
- âœ… **Estilo minimalista y limpio**
- âœ… **Mobile-first responsive**
- âœ… **Modales Premium:** Efecto glassmorphism (blur), animaciones suaves y bordes redondeados.

## ðŸ” Sistema de Permisos

### ImplementaciÃ³n Obligatoria en Archivo Principal

```php
<?php
require_once '../../core/auth/auth.php';
require_once '../../core/permissions/permissions.php';

$usuario = obtenerUsuarioActual();
$cargoOperario = $usuario['CodNivelesCargos'];

// Verificar acceso (SIEMPRE debe existir permiso 'vista')
if (!tienePermiso('nombre_herramienta', 'vista', $cargoOperario)) {
    header('Location: /login.php');
    exit();
}
?>
```

### FunciÃ³n de Permisos

```php
tienePermiso($nombreHerramienta, $nombreAccion, $codNivelCargo)
```

### Permisos TÃ­picos por MÃ³dulo

- `vista` - **OBLIGATORIO** para todas las herramientas
- `nuevo_registro` - Crear registros
- `editar` - Modificar registros
- `eliminar` - Eliminar registros
- `shortcut` - Acceso rÃ¡pido en index del mÃ³dulo
- `aprobar_gerencia` - Aprobaciones de nivel gerencial
- `exportar_{modulo}` - Exportar a Excel

### Permisos EspecÃ­ficos por MÃ³dulo

#### Mantenimiento (`historial_solicitudes_mantenimiento`)
- `vista` - Ver el historial de solicitudes.
- `nuevo_registro` - Acceder a formularios de creaciÃ³n de tickets y equipos.
- `vista_todas_sucursales` - Ver y filtrar por todas las sucursales en el historial.

#### Reglas de Sucursales en Mantenimiento
- Usuarios sin `vista_todas_sucursales` solo verÃ¡n su sucursal asignada.
- En formularios de creaciÃ³n, el selector de sucursal se elimina para forzar la asignaciÃ³n automÃ¡tica basada en el cargo del usuario (toma la primera sucursal activa encontrada).

### Cargos Frecuentes

| CodNivelesCargos | Nombre |
|------------------|--------|
| 2 | Vendedor |
| 5 | LÃ­der de Tienda |
| 8 | Jefe de Contabilidad |
| 11 | Jefe de Operaciones |
| 13 | Gerente de Desarrollo Humano |
| 15 | LÃ­der de TI |
| 16 | Gerencia General |
| 49 | Gerencia Proyectos |

(Ver lista completa en docs/00_Instrucciones_Generales.md lÃ­neas 82-127)

## ðŸ“¦ Componentes Globales

### Includes Obligatorios

```php
<?php
// SIEMPRE en este orden
require_once '../../core/auth/auth.php';                    // Incluye funciones.php y conexion.php
require_once '../../core/layout/menu_lateral.php';          // MenÃº lateral
require_once '../../core/layout/header_universal.php';      // Header universal
require_once '../../core/permissions/permissions.php';      // Sistema de permisos
?>
```

### Servicios Disponibles

#### ConexiÃ³n a Base de Datos
```php
require_once '../../core/database/conexion.php';
// Variable $conn disponible globalmente â€” es un objeto PDO
// Charset: UTF-8
// Zona horaria: America/Managua
```

> âš ï¸ **`$conn` es una instancia de `PDO`, NO de `mysqli`.** Ver secciÃ³n crÃ­tica abajo.

---

## ðŸš¨ CRÃTICO: Usar PDO â€” NUNCA MySQLi

> **Este es el error mÃ¡s frecuente que causa HTTP 500.**  
> El archivo `core/database/conexion.php` crea una conexiÃ³n **PDO**.  
> Usar mÃ©todos de MySQLi (`$conn->query()` sin parÃ¡metros, `mysqli_fetch_assoc()`, `bind_param()`, etc.) **rompe la aplicaciÃ³n**.

### âŒ PROHIBIDO â€” CÃ³digo MySQLi (causa errores 500)

```php
// âŒ JAMÃS usar esto:
$resultado = $conn->query("SELECT * FROM tabla");       // MySQLi estilo
$fila = mysqli_fetch_assoc($resultado);                  // FunciÃ³n MySQLi
$stmt = $conn->prepare($sql);                            // Hasta aquÃ­ igual...
$stmt->bind_param("si", $nombre, $id);                  // â† bind_param() es MySQLi
$stmt->execute();
$resultado = $stmt->get_result();                        // â† get_result() es MySQLi
```

### âœ… CORRECTO â€” CÃ³digo PDO (obligatorio)

```php
// âœ… Consulta simple (sin parÃ¡metros externos)
$stmt = $conn->query("SELECT * FROM tabla");
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// âœ… Consulta con parÃ¡metros (prepared statement PDO)
$sql = "SELECT * FROM tabla WHERE id = :id AND estado = :estado";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $id, ':estado' => 'activo']);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// âœ… Obtener una sola fila
$stmt = $conn->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $id]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);

// âœ… INSERT / UPDATE / DELETE
$sql = "INSERT INTO tabla (nombre, fecha) VALUES (:nombre, :fecha)";
$stmt = $conn->prepare($sql);
$stmt->execute([':nombre' => $nombre, ':fecha' => $fecha]);
$ultimoId = $conn->lastInsertId();

// âœ… Contar filas afectadas
$filasAfectadas = $stmt->rowCount();

// âœ… Transacciones
$conn->beginTransaction();
try {
    $stmt = $conn->prepare("UPDATE tabla SET campo = :valor WHERE id = :id");
    $stmt->execute([':valor' => $valor, ':id' => $id]);
    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
```

### ðŸ“‹ Tabla de equivalencias MySQLi â†’ PDO

| MySQLi âŒ | PDO âœ… |
|-----------|--------|
| `$conn->query($sql)` (sin parÃ¡metros es igual) | `$conn->query($sql)` âœ… igual en PDO |
| `$stmt->bind_param("si", $a, $b)` | `$stmt->execute([':a' => $a, ':b' => $b])` |
| `$stmt->get_result()` | `$stmt->fetchAll(PDO::FETCH_ASSOC)` |
| `mysqli_fetch_assoc($res)` | `$stmt->fetch(PDO::FETCH_ASSOC)` |
| `$conn->insert_id` | `$conn->lastInsertId()` |
| `$stmt->num_rows` | `$stmt->rowCount()` |
| `mysqli_real_escape_string()` | âŒ no necesario â€” usar parÃ¡metros `:nombre` |

### Checklist antes de entregar cÃ³digo PHP

- [ ] Â¿NingÃºn mÃ©todo es de MySQLi? (`bind_param`, `get_result`, `mysqli_*`)
- [ ] Â¿Todos los inputs van como parÃ¡metros (`:nombre`)?
- [ ] Â¿Se usa `fetchAll(PDO::FETCH_ASSOC)` o `fetch(PDO::FETCH_ASSOC)`?
- [ ] Â¿Las transacciones usan `$conn->beginTransaction()` / `commit()` / `rollBack()`?

---

#### Funciones de Usuarios
```php
require_once '../../core/helpers/funciones.php';

// Funciones disponibles:
obtenerNombreCompleto($id_empleado)
obtenerCargo($id_empleado)
obtenerSucursal($id_empleado)
verificarPermiso($permiso)
```

#### EnvÃ­o de Correos
```php
require_once '../../core/email/EmailService.php';

// Funciones disponibles:
obtenerEmailPorCargo($codNivelCargo)
enviarCorreo($remitenteId, $destinatarios, $asunto, $cuerpoHtml, $archivos = [])
obtenerCredencialesUsuario($codOperario)
```

## ðŸ“ Reglas de CodificaciÃ³n

### PHP

- ðŸš¨ **CRÃTICO: Usar EXCLUSIVAMENTE PDO** â€” La variable `$conn` es PDO, nunca MySQLi
- ðŸš¨ **JAMÃS usar** `bind_param()`, `get_result()`, `mysqli_fetch_assoc()` ni funciones `mysqli_*`
- âœ… **SIEMPRE** usar parÃ¡metros nombrados (`:nombre`) en prepared statements PDO
- âœ… **SIEMPRE** usar `fetchAll(PDO::FETCH_ASSOC)` o `fetch(PDO::FETCH_ASSOC)` para obtener resultados
- âœ… Validar y sanitizar **TODOS** los inputs
- âœ… Usar `try-catch` con `$conn->rollBack()` en transacciones
- âœ… **MANDATORIO:** Todo registro de fecha en base de datos debe ser siempre en horario **Nicaragua (America/Managua)**
- âœ… Comentar cÃ³digo complejo
- âœ… Nombres de variables en espaÃ±ol descriptivos

### JavaScript

- âœ… Funciones con nombres descriptivos en espaÃ±ol
- âœ… Usar `async/await` para AJAX
- âœ… Validar formularios antes de enviar
- âœ… Mostrar loaders durante operaciones
- âœ… Mensajes claros con SweetAlert2

### CSS

- âœ… Mobile-first responsive
- âœ… Usar variables CSS para colores
- âœ… Clases descriptivas con prefijo del mÃ³dulo
- âœ… Consistencia con estilos globales

### SQL y Base de Datos

- ðŸš¨ **MANDATORIO:** Para proyectos y herramientas que requieran cambiar la estructura de la base de datos o crear nuevas tablas, **SE DEBE CREAR UN ARCHIVO `.sql`** dentro de una carpeta `sql/` del mÃ³dulo correspondiente (ej: `modulos/gerencia/sql/setup_tablas.sql`).
- âŒ **ESTÃ ESTRICTAMENTE PROHIBIDO** crear archivos PHP ejecutables (ej: `setup_db.php`) para alterar o crear tablas. Toda migraciÃ³n o estructura debe entregarse como `.sql` para ejecuciÃ³n manual por el administrador.
- âœ… Nombres de tablas: `{herramienta}_`
- âœ… Campos de auditorÃ­a: `fecha_creacion`, `usuario_creacion` (siempre en hora **Nicaragua**)
- âœ… IDs auto-increment
- âœ… Foreign keys con ON DELETE/UPDATE apropiados

## ðŸŽ¨ Estructura HTML EstÃ¡ndar

### Head Section

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nombre de la Herramienta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" href="../../assets/img/icon12.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/global_tools.css?v=<?php echo mt_rand(1, 10000); ?>">
    <link rel="stylesheet" href="css/{herramienta}.css?v=<?php echo mt_rand(1, 10000); ?>">
</head>
```

### Body Structure

```html
<body>
    <?php echo renderMenuLateral($cargoOperario); ?>
    
    <div class="main-container">
        <div class="sub-container">
            <?php echo renderHeader($usuario, false, 'TÃ­tulo de la Herramienta'); ?>
            
            <div class="container-fluid p-3">
                <!-- Contenido aquÃ­ -->
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/{herramienta}.js?v=<?php echo mt_rand(1, 10000); ?>"></script>
</body>
```

## ðŸ“˜ Modal de Ayuda Universal (OBLIGATORIO)

**TODAS las herramientas deben incluir un modal de ayuda** con ID estÃ¡ndar `pageHelpModal`. El header universal incluye un botÃ³n de ayuda (Ã­cono "i" turquesa) que detecta automÃ¡ticamente este modal.

## ðŸ—„ï¸ Database Schema Research

When researching database tables, fields, or relations:
- **Primary Source**: ALWAYS use `docs/u839374897_erp.sql` as the single source of truth for the database schema.
- **Verification**: Do not assume table structures based on code alone. Search for the `CREATE TABLE` and `ALTER TABLE` statements within the SQL dump to confirm data types, enums, and foreign key constraints.
- **Updates**: If you identify a discrepancy between the code and the SQL dump, notify the user and prioritize the SQL dump structure.

## ðŸ“˜ Modal de Ayuda Universal (OBLIGATORIO)

### ImplementaciÃ³n Requerida

**TODAS las herramientas deben incluir un modal de ayuda** con ID estÃ¡ndar `pageHelpModal`. El header universal incluye un botÃ³n de ayuda (Ã­cono "i" turquesa) que detecta automÃ¡ticamente este modal.

### Estructura del Modal

Agregar **antes del cierre de `</body>`**:

```html
<!-- Modal de Ayuda -->
<div class="modal fade" id="pageHelpModal" tabindex="-1" 
     aria-labelledby="pageHelpModalLabel" aria-hidden="true" 
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pageHelpModalLabel">
                    <i class="fas fa-info-circle me-2"></i>
                    GuÃ­a de {Nombre de la Herramienta}
                </h5>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- CONTENIDO PERSONALIZADO -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body">
                                <h6 class="text-primary border-bottom pb-2 fw-bold">
                                    <i class="fas fa-check me-2"></i> SecciÃ³n 1
                                </h6>
                                <p class="small text-muted mb-0">
                                    DescripciÃ³n de funcionalidad...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Z-index para evitar que backdrop cubra el modal */
    #pageHelpModal {
        z-index: 1060 !important;
    }
    .modal-backdrop {
        z-index: 1050 !important;
    }
</style>
```

### Contenido del Modal

El modal debe documentar:

1. **Funcionalidades principales** - QuÃ© hace la herramienta
2. **Permisos y roles** - QuiÃ©n puede hacer quÃ©
3. **Flujo de trabajo** - CÃ³mo usar la herramienta
4. **Reglas de negocio** - Validaciones y restricciones
5. **Casos especiales** - Situaciones importantes a considerar

### Elementos Recomendados

```html
<!-- Cards con iconos de colores -->
<div class="card border-0 bg-light">
    <div class="card-body">
        <h6 class="text-warning border-bottom pb-2 fw-bold">
            <i class="fas fa-exclamation-triangle me-2"></i> Importante
        </h6>
        <p class="small text-muted mb-0">InformaciÃ³n crÃ­tica...</p>
    </div>
</div>

<!-- Alertas informativas -->
<div class="alert alert-info py-2 px-3 small">
    <strong><i class="fas fa-info-circle me-1"></i> Nota:</strong>
    <br>
    InformaciÃ³n adicional relevante.
</div>
```

### Reglas Obligatorias

- âœ… **ID**: Siempre `id="pageHelpModal"`
- âœ… **Backdrop**: Incluir `data-bs-backdrop="static"`
- âœ… **Z-index**: Incluir CSS de z-index
- âœ… **TamaÃ±o**: MÃ­nimo `modal-lg`
- âœ… **Contenido**: DocumentaciÃ³n Ãºtil y completa
- âŒ **No**: Modales vacÃ­os o sin informaciÃ³n relevante

## ðŸŽ­ Modales Premium (EstÃ¡ndar de DiseÃ±o)

Para una experiencia de usuario superior, todos los modales nuevos deben seguir el estÃ¡ndar "Premium":

### Modales Premium (EstÃ¡ndar de DiseÃ±o)
UbicaciÃ³n del CSS: `/core/assets/css/modales_premium.css`

Este CSS debe incluirse en cualquier pÃ¡gina que requiera modales modernos con efecto glassmorphism y animaciones.
```css
/* Backdrop con desenfoque (Glassmorphism) */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Contenido del Modal Moderno */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: slideUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

/* AnimaciÃ³n de entrada */
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Header estilizado */
.modal-header {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 20px 25px;
}

/* Botones Modernos */
.btn-modern {
    padding: 10px 25px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}
.btn-modern-primary { background: #0E544C; color: white; }
.btn-modern-secondary { background: #6c757d; color: white; }
.btn-modern:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
```

### 2. Estructura HTML

```html
<div class="modal fade" id="miModal" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">TÃ­tulo Moderno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Contenido -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-modern btn-modern-primary">Confirmar</button>
            </div>
        </div>
    </div>
</div>
```


## ðŸ”˜ BotÃ³n de AcciÃ³n Flotante (FAB) â€” ESTÃNDAR OBLIGATORIO

### Regla

**TODA pÃ¡gina de historial o listado que permita crear un registro nuevo DEBE usar el botÃ³n circular flotante `.btn-floating-pitaya`.**  
Nunca usar botones pill o inline (`btn btn-success`, `btn btn-primary rounded-pill`, etc.) para esta acciÃ³n.

### Fuente del CSS

El estilo estÃ¡ en `/core/assets/css/fab_button.css` â€” **no copiar el CSS a los archivos del mÃ³dulo**.

Incluir en el `<head>` de la herramienta:

```html
<link rel="stylesheet" href="/core/assets/css/fab_button.css">
```

### ImplementaciÃ³n

```php
<?php if (tienePermiso('nombre_herramienta', 'nuevo_registro', $cargoOperario)): ?>
<a href="nueva_herramienta.php" class="btn-floating-pitaya" title="Nuevo Registro">
    <i class="fas fa-plus"></i>
</a>
<?php endif; ?>
```

### Comportamiento Visual

- â­• Circular (65Ã—65px), **posiciÃ³n fija** â€” esquina inferior derecha (`bottom: 35px; right: 35px`)
- ðŸŽ¨ Color verde Pitaya `#51B8AC` en reposo Â· oscurece a `#0E544C` en hover
- âœ¨ Al hacer hover: escala 1.15Ã— + rotaciÃ³n 90Â° con animaciÃ³n cÃºbica
- ðŸ”² Borde blanco de 3px Â· sombra verde suave

### Requisitos

- âœ… El Ã­cono dentro SIEMPRE es `<i class="fas fa-plus"></i>` (Font Awesome 5)
- âœ… Incluir atributo `title` descriptivo (ej: `title="Nueva Solicitud"`)
- âœ… Envolver en bloque de permiso PHP con `tienePermiso()`
- âœ… La pÃ¡gina DEBE cargar `/core/assets/css/global_tools.css` en el `<head>`
- âŒ **PROHIBIDO** redefinir `.btn-floating-pitaya` en CSS del mÃ³dulo o en `<style>` inline

## ðŸ“Š Sistema de Filtros para Tablas

### Tipos de Filtro

1. **Texto Libre** (`data-type="text"`)
   - Para textos grandes o cÃ³digos autogenerados
   - Input de bÃºsqueda libre

2. **NÃºmero con Rango** (`data-type="number"`)
   - Para cantidades
   - Inputs min y max

3. **Rango de Fechas** (`data-type="daterange"`)
   - Un solo calendario inteligente: desde/hasta
   - **LÃ³gica de SelecciÃ³n**:
     - El panel **no se cierra** automÃ¡ticamente tras la selecciÃ³n.
     - 1er clic: Define el inicio.
     - 2do clic: Define el fin (con auto-intercambio si es menor al inicio).
     - 3er clic en adelante: 
       - Si es antes del inicio: actualiza `desde`.
       - Si es despuÃ©s del fin o dentro del rango: actualiza `hasta`.
   - Incluir mensaje informativo: "Haz clic en dos fechas para definir el rango."

4. **Lista Definida** (`data-type="list"`)
   - Para datos con enum o consultas limitadas
   - Checkboxes con bÃºsqueda
   - Aplica para: sucursales, cargos, tipos, estados, etc.

### Estructura de Encabezado de Tabla

```html
<thead>
    <tr>
        <th data-column="nombre_columna" data-type="text">
            Nombre Columna
            <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
        </th>
        <th data-column="monto" data-type="number">
            Monto
            <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
        </th>
        <th data-column="fecha" data-type="daterange">
            Fecha
            <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
        </th>
        <th data-column="estado" data-type="list">
            Estado
            <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
        </th>
        <th style="width: 150px;">Acciones</th>
    </tr>
</thead>
```

## ðŸŽ¯ Columnas EstÃ¡ndar en Tablas

### Columna de Estado (Badges)

```html
<td>
    <span class="badge bg-success">Activo</span>
    <span class="badge bg-warning text-dark">Pendiente</span>
    <span class="badge bg-danger">Inactivo</span>
</td>
```

### Columna de Acciones

```html
<td>
    <?php if (tienePermiso('herramienta', 'editar', $cargoOperario)): ?>
    <button class="btn-accion btn-editar" onclick="editar(id)" title="Editar">
        <i class="bi bi-pencil"></i>
    </button>
    <?php endif; ?>
    
    <?php if (tienePermiso('herramienta', 'eliminar', $cargoOperario)): ?>
    <button class="btn-accion btn-eliminar" onclick="eliminar(id)" title="Eliminar">
        <i class="bi bi-trash"></i>
    </button>
    <?php endif; ?>
</td>
```

## ðŸ“„ PaginaciÃ³n EstÃ¡ndar

### HTML

```html
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="d-flex align-items-center gap-2">
        <label class="mb-0">Mostrar:</label>
        <select class="form-select form-select-sm" id="registrosPorPagina" 
                style="width: auto;" onchange="cambiarRegistrosPorPagina()">
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span class="mb-0">registros</span>
    </div>
    <div id="paginacion"></div>
</div>
```

### JavaScript

```javascript
const elementosPorPagina = [10, 25, 50, 100, 500];
let paginaActual = 1;
let registrosPorPagina = 25;
```

## ðŸ“š LibrerÃ­as Disponibles

- **jQuery 3.x** - DOM manipulation
- **Bootstrap 5.x** - UI framework
- **SweetAlert2** - Alertas bonitas
- **DataTables** (opcional) - Tablas avanzadas
- **Select2** (opcional) - Dropdowns mejorados
- **Chart.js** (opcional) - GrÃ¡ficas

## ðŸ—„ï¸ Esquema de Base de Datos

### Tablas Principales del Sistema

#### Operarios
```sql
-- Todos los colaboradores del sistema
-- Campos clave: CodOperario, Nombre, Apellido, email_trabajo, CodNivelesCargos
```

#### NivelesCargos
```sql
-- Todos los cargos con permisos
-- Campos clave: CodNivelesCargos, Nombre, Area, Peso
```

#### AsignacionNivelesCargos
```sql
-- AsignaciÃ³n histÃ³rica de cargos a operarios
-- LÃ³gica: Fin IS NULL OR Fin >= CURDATE() AND Fecha <= CURDATE()
```

#### Sucursales
```sql
-- InformaciÃ³n de sucursales
-- Campos clave: id, codigo, nombre, activa, sucursal (boolean)
```

#### tools_erp
```sql
-- Registro de herramientas del sistema
-- Campos: id, nombre, titulo, tipo_componente ('herramienta','indicador','balance'), grupo, descripcion, url_real, url_alias(para mascara de url), icono(icono relacionado a herramienta)
```

#### acciones_tools_erp
```sql
-- Acciones disponibles por herramienta
-- Campos: id, tool_erp_id, nombre_accion, descripcion
```

#### permisos_tools_erp
```sql
-- Permisos por cargo para cada acciÃ³n
-- Campos: id, accion_tool_erp_id, CodNivelesCargos, permiso (allow/deny)
```

(Ver esquema completo en docs/03_Esquema_BaseDatos.md)

## ðŸš€ Proceso de GeneraciÃ³n de Nueva Herramienta

### 1. Recopilar InformaciÃ³n

Preguntar al usuario:
- Nombre del mÃ³dulo
- Ãrea (carpeta)
- Funcionalidades especÃ­ficas
- Permisos requeridos
- Sufijo de archivos/tablas

### 2. Generar Estructura Completa

- âœ… Archivo PHP principal
- âœ… CSS especÃ­fico
- âœ… JavaScript con todas las funciones
- âœ… Archivos AJAX necesarios
- âœ… **NUEVO:** Archivo(`.sql`) guardado siempre bajo una subcarpeta `sql/` (ej: `sql/migracion.sql`) para crear o alterar tablas. Nunca uses archivos `.php`.
- âœ… Carpeta uploads (si aplica, max 10MB)

### 3. Incluir DocumentaciÃ³n

- âœ… Lista de herramientas (nombre/cÃ³digo) a crear manualmente
- âœ… Lista de permisos a crear manualmente
- âœ… Instrucciones de implementaciÃ³n
- âœ… Consideraciones especiales

### 4. Validar Contra Patrones

- âœ… Â¿Usa header_universal?
- âœ… Â¿Implementa permisos?
- âœ… Â¿Sigue estructura de carpetas?
- âœ… Â¿Colores corporativos correctos?
- âœ… Â¿AJAX devuelve JSON?
- âœ… Â¿El botÃ³n "Nuevo Registro" usa `.btn-floating-pitaya` (no un botÃ³n pill/inline)?
- âœ… Â¿Carga `/core/assets/css/global_tools.css` en el `<head>`?

## ðŸ“¦ Entregables Esperados

Para cada mÃ³dulo nuevo:

1. âœ… Todos los archivos de cÃ³digo
2. âœ… Script SQL completo y probado
3. âœ… Lista de herramientas a crear en `tools_erp`
4. âœ… Lista de permisos necesarios
5. âœ… Instrucciones de implementaciÃ³n
6. âœ… Notas sobre configuraciÃ³n especial

**Formato**: Archivos separados listos para copiar/pegar

## ðŸ’¡ Recordatorios Importantes

- ðŸš¨ **Base de datos = PDO siempre.** Si el cÃ³digo usa MySQLi provocarÃ¡ error 500.
- Siempre seguir herramienta de ejemplo como referencia (docs/04_Plantilla_Modulo_Referencia.md)
- Mantener consistencia con mÃ³dulos existentes
- CÃ³digo limpio, comentado y profesional
- Pensar en escalabilidad y mantenimiento
- Validar en frontend **Y** backend
- Responsive design obligatorio
- Usar permisos granulares
- Formato de fechas: `dia-mes-aÃ±o` (01-Ene-25)

## ðŸ“– Referencias

- **Instrucciones Generales**: `docs/00_Instrucciones_Generales.md`
- **EstÃ¡ndares UI/UX**: `docs/01_Estandares_UI_UX.md`
- **Core Global**: `docs/02_Core_Global_Docs.md`
- **Esquema BD**: `docs/03_Esquema_BaseDatos.md`
- **Plantilla Referencia**: `docs/04_Plantilla_Modulo_Referencia.md`

---

## ðŸŽ¯ Uso de Esta Skill

Cuando trabajes en el ERP de Batidos Pitaya:

1. **Lee esta skill** antes de comenzar cualquier desarrollo
2. **Sigue los estÃ¡ndares** establecidos aquÃ­
3. **Consulta las referencias** para detalles especÃ­ficos
4. **Valida tu cÃ³digo** contra los patrones definidos
5. **Genera documentaciÃ³n completa** para cada entregable

