---
name: ERP Batidos Pitaya Development
description: Comprehensive skill for developing modules and tools in the Batidos Pitaya ERP system following established standards and architecture
---

# ERP Batidos Pitaya Development Skill

Esta skill te guía en el desarrollo de módulos y herramientas para el Sistema ERP de Batidos Pitaya, siguiendo los estándares establecidos en la documentación del proyecto.

## 🎯 Contexto del Proyecto

**Sistema**: ERP modular para Batidos Pitaya  
**Stack**: PHP + MySQL  
**Arquitectura**: Componentes globales compartidos con estructura estandarizada  
**Ritmo**: ~1 herramienta completa por día  

## 🔄 Sistema de Sincronización PitayaCore (Iron Sync v3)

El ecosistema de Batidos Pitaya utiliza un sistema de **Sincronización de Hierro (Iron Sync)** descentralizado y de alta estabilidad para mantener la coherencia del código compartido entre todos los subdominios (ERP, API, Talento, etc.).

### 📍 Fuente de Verdad: `PitayaCore`
- **Repositorio Central**: `MiguelGotea/PitayaCore`
- **Carpetas Gestionadas**: `/core`, `/docs`, `/.agent`
- **Regla de Oro**: Todo cambio en componentes globales o documentación debe nacer o consolidarse en `PitayaCore`.

### 🚀 Flujo de Trabajo del Desarrollador (IA)
1. **Desarrollo Centralizado**: Realiza siempre los cambios globales en el repositorio `PitayaCore`.
2. **Despliegue Maestro**: Usa el script de push para disparar la sincronización en la nube:
   ```powershell
   # Desde c:\...\VisualCode\PitayaCore
   .\.scripts\gitpush.ps1
   ```
3. **Sincronización Local**: Para actualizar tus otros repositorios locales (`api`, `erp`, `talento`), usa el script unificado en la raíz:
   ```powershell
   # Desde c:\...\VisualCode
   .\gitsync-local.ps1
   ```

### 🛡️ Iron Sync v3: Estabilidad y Seguridad
El sistema de GitHub Actions está blindado con las siguientes protecciones:
- **Clonación Fresca**: No usa caché; descarga una copia limpia de `PitayaCore` para evitar código obsoleto.
- **Validación Flexible de SHA**: Verifica que el commit recibido sea el correcto o uno más reciente, con un bucle de reintento (5 intentos x 10s) para combatir el retardo de GitHub.
- **Cola de Seguridad (Concurrency)**: Si realizas varios pushes seguidos, GitHub los encola ordenadamente para evitar que las actualizaciones choquen entre sí.
- **Escudo de Credenciales**: Valida automáticamente que las llaves de Hostinger estén configuradas antes de intentar cualquier despliegue.

### 🛠️ Herramientas de Control
- `PitayaCore/.scripts/gitpush.ps1`: Dispara la actualización global en GitHub.
- `VisualCode/gitsync-local.ps1`: Mantiene todos tus repositorios locales al día de forma quirúrgica y segura.

## 📋 Antes de Empezar

Cuando el usuario solicite crear una nueva herramienta, **SIEMPRE pregunta**:

1. **Nombre del módulo** (ej: cupones, auditorías, vacaciones)
2. **Área/Carpeta** (marketing, rrhh, operaciones, gerencia, etc.)
3. **Funcionalidades específicas** requeridas
4. **Permisos necesarios** (además del obligatorio `vista`)
5. **Sufijo para archivos y tablas** (ej: `cupones_`, `vacaciones_`)

## 🏗️ Arquitectura Obligatoria

### Estructura de Archivos por Módulo

```
📁 modulos/{area}/
├── {herramienta}.php              # Archivo principal
├── uploads/                        # Archivos subidos (max 10MB)
├── css/
│   └── {herramienta}.css          # CSS personalizado
├── js/
│   └── {herramienta}.js           # JavaScript personalizado
└── ajax/
    ├── {herramienta}_guardar.php
    ├── {herramienta}_get_datos.php
    ├── {herramienta}_get_opciones_filtro.php
    ├── {herramienta}_get_{item}.php
    └── {herramienta}_eliminar.php
```

### Áreas del Sistema

- `ventas/` - Historial de ventas, cupones
- `rh/` - Recursos humanos
- `operaciones/` - Gestión de sucursales
- `marketing/` - Cupones, promociones
- `supervision/` - Auditorías y control
- `sucursales/` - Herramientas de punto de venta
- `sistemas/` - Control de permisos
- `mantenimiento/` - Gestión de activos
- `gerencia/` - Dirección general
- `compras/` - Gestión de OC, facturas
- `contabilidad/` - Descarga de datos
- Y más... (ver docs/00_Instrucciones_Generales.md líneas 31-54)

## 🎨 Identidad Visual

### Colores Corporativos

```css
/* Color principal */
--color-principal: #51B8AC;

/* Encabezado de tablas */
--color-header-tabla: #0E544C;

/* Botones de acción */
--btn-nuevo: #218838;
--btn-nuevo-hover: #1d6f42;
--btn-principal: #51B8AC;
```

### Tipografía

```css
font-family: 'Calibri', sans-serif;
font-size: clamp(12px, 2vw, 18px);
```

### Principios de Diseño

- ❌ **NO usar degradados**
- ✅ **Estilo minimalista y limpio**
- ✅ **Mobile-first responsive**
- ✅ **Modales Premium:** Efecto glassmorphism (blur), animaciones suaves y bordes redondeados.

## 🔐 Sistema de Permisos

### Implementación Obligatoria en Archivo Principal

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

### Función de Permisos

```php
tienePermiso($nombreHerramienta, $nombreAccion, $codNivelCargo)
```

### Permisos Típicos por Módulo

- `vista` - **OBLIGATORIO** para todas las herramientas
- `nuevo_registro` - Crear registros
- `editar` - Modificar registros
- `eliminar` - Eliminar registros
- `shortcut` - Acceso rápido en index del módulo
- `aprobar_gerencia` - Aprobaciones de nivel gerencial
- `exportar_{modulo}` - Exportar a Excel

### Permisos Específicos por Módulo

#### Mantenimiento (`historial_solicitudes_mantenimiento`)
- `vista` - Ver el historial de solicitudes.
- `nuevo_registro` - Acceder a formularios de creación de tickets y equipos.
- `vista_todas_sucursales` - Ver y filtrar por todas las sucursales en el historial.

#### Reglas de Sucursales en Mantenimiento
- Usuarios sin `vista_todas_sucursales` solo verán su sucursal asignada.
- En formularios de creación, el selector de sucursal se elimina para forzar la asignación automática basada en el cargo del usuario (toma la primera sucursal activa encontrada).

### Cargos Frecuentes

| CodNivelesCargos | Nombre |
|------------------|--------|
| 2 | Vendedor |
| 5 | Líder de Tienda |
| 8 | Jefe de Contabilidad |
| 11 | Jefe de Operaciones |
| 13 | Gerente de Desarrollo Humano |
| 15 | Líder de TI |
| 16 | Gerencia General |
| 49 | Gerencia Proyectos |

(Ver lista completa en docs/00_Instrucciones_Generales.md líneas 82-127)

## 📦 Componentes Globales

### Includes Obligatorios

```php
<?php
// SIEMPRE en este orden
require_once '../../core/auth/auth.php';                    // Incluye funciones.php y conexion.php
require_once '../../core/layout/menu_lateral.php';          // Menú lateral
require_once '../../core/layout/header_universal.php';      // Header universal
require_once '../../core/permissions/permissions.php';      // Sistema de permisos
?>
```

### Servicios Disponibles

#### Conexión a Base de Datos
```php
require_once '../../core/database/conexion.php';
// Variable $conn disponible globalmente — es un objeto PDO
// Charset: UTF-8
// Zona horaria: America/Managua
```

> ⚠️ **`$conn` es una instancia de `PDO`, NO de `mysqli`.** Ver sección crítica abajo.

---

## 🚨 CRÍTICO: Usar PDO — NUNCA MySQLi

> **Este es el error más frecuente que causa HTTP 500.**  
> El archivo `core/database/conexion.php` crea una conexión **PDO**.  
> Usar métodos de MySQLi (`$conn->query()` sin parámetros, `mysqli_fetch_assoc()`, `bind_param()`, etc.) **rompe la aplicación**.

### ❌ PROHIBIDO — Código MySQLi (causa errores 500)

```php
// ❌ JAMÁS usar esto:
$resultado = $conn->query("SELECT * FROM tabla");       // MySQLi estilo
$fila = mysqli_fetch_assoc($resultado);                  // Función MySQLi
$stmt = $conn->prepare($sql);                            // Hasta aquí igual...
$stmt->bind_param("si", $nombre, $id);                  // ← bind_param() es MySQLi
$stmt->execute();
$resultado = $stmt->get_result();                        // ← get_result() es MySQLi
```

### ✅ CORRECTO — Código PDO (obligatorio)

```php
// ✅ Consulta simple (sin parámetros externos)
$stmt = $conn->query("SELECT * FROM tabla");
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Consulta con parámetros (prepared statement PDO)
$sql = "SELECT * FROM tabla WHERE id = :id AND estado = :estado";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $id, ':estado' => 'activo']);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Obtener una sola fila
$stmt = $conn->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $id]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ INSERT / UPDATE / DELETE
$sql = "INSERT INTO tabla (nombre, fecha) VALUES (:nombre, :fecha)";
$stmt = $conn->prepare($sql);
$stmt->execute([':nombre' => $nombre, ':fecha' => $fecha]);
$ultimoId = $conn->lastInsertId();

// ✅ Contar filas afectadas
$filasAfectadas = $stmt->rowCount();

// ✅ Transacciones
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

### 📋 Tabla de equivalencias MySQLi → PDO

| MySQLi ❌ | PDO ✅ |
|-----------|--------|
| `$conn->query($sql)` (sin parámetros es igual) | `$conn->query($sql)` ✅ igual en PDO |
| `$stmt->bind_param("si", $a, $b)` | `$stmt->execute([':a' => $a, ':b' => $b])` |
| `$stmt->get_result()` | `$stmt->fetchAll(PDO::FETCH_ASSOC)` |
| `mysqli_fetch_assoc($res)` | `$stmt->fetch(PDO::FETCH_ASSOC)` |
| `$conn->insert_id` | `$conn->lastInsertId()` |
| `$stmt->num_rows` | `$stmt->rowCount()` |
| `mysqli_real_escape_string()` | ❌ no necesario — usar parámetros `:nombre` |

### Checklist antes de entregar código PHP

- [ ] ¿Ningún método es de MySQLi? (`bind_param`, `get_result`, `mysqli_*`)
- [ ] ¿Todos los inputs van como parámetros (`:nombre`)?
- [ ] ¿Se usa `fetchAll(PDO::FETCH_ASSOC)` o `fetch(PDO::FETCH_ASSOC)`?
- [ ] ¿Las transacciones usan `$conn->beginTransaction()` / `commit()` / `rollBack()`?

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

#### Envío de Correos
```php
require_once '../../core/email/EmailService.php';

// Funciones disponibles:
obtenerEmailPorCargo($codNivelCargo)
enviarCorreo($remitenteId, $destinatarios, $asunto, $cuerpoHtml, $archivos = [])
obtenerCredencialesUsuario($codOperario)
```

## 📝 Reglas de Codificación

### PHP

- 🚨 **CRÍTICO: Usar EXCLUSIVAMENTE PDO** — La variable `$conn` es PDO, nunca MySQLi
- 🚨 **JAMÁS usar** `bind_param()`, `get_result()`, `mysqli_fetch_assoc()` ni funciones `mysqli_*`
- ✅ **SIEMPRE** usar parámetros nombrados (`:nombre`) en prepared statements PDO
- ✅ **SIEMPRE** usar `fetchAll(PDO::FETCH_ASSOC)` o `fetch(PDO::FETCH_ASSOC)` para obtener resultados
- ✅ Validar y sanitizar **TODOS** los inputs
- ✅ Usar `try-catch` con `$conn->rollBack()` en transacciones
- ✅ **MANDATORIO:** Todo registro de fecha en base de datos debe ser siempre en horario **Nicaragua (America/Managua)**
- ✅ Comentar código complejo
- ✅ Nombres de variables en español descriptivos

### JavaScript

- ✅ Funciones con nombres descriptivos en español
- ✅ Usar `async/await` para AJAX
- ✅ Validar formularios antes de enviar
- ✅ Mostrar loaders durante operaciones
- ✅ Mensajes claros con SweetAlert2

### CSS

- ✅ Mobile-first responsive
- ✅ Usar variables CSS para colores
- ✅ Clases descriptivas con prefijo del módulo
- ✅ Consistencia con estilos globales

### SQL y Base de Datos

- 🚨 **MANDATORIO:** Para proyectos y herramientas que requieran cambiar la estructura de la base de datos o crear nuevas tablas, **SE DEBE CREAR UN ARCHIVO `.sql`** dentro de una carpeta `sql/` del módulo correspondiente (ej: `modulos/gerencia/sql/setup_tablas.sql`).
- ❌ **ESTÁ ESTRICTAMENTE PROHIBIDO** crear archivos PHP ejecutables (ej: `setup_db.php`) para alterar o crear tablas. Toda migración o estructura debe entregarse como `.sql` para ejecución manual por el administrador.
- ✅ Nombres de tablas: `{herramienta}_`
- ✅ Campos de auditoría: `fecha_creacion`, `usuario_creacion` (siempre en hora **Nicaragua**)
- ✅ IDs auto-increment
- ✅ Foreign keys con ON DELETE/UPDATE apropiados

## 🎨 Estructura HTML Estándar

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
            <?php echo renderHeader($usuario, false, 'Título de la Herramienta'); ?>
            
            <div class="container-fluid p-3">
                <!-- Contenido aquí -->
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/{herramienta}.js?v=<?php echo mt_rand(1, 10000); ?>"></script>
</body>
```

## 📘 Modal de Ayuda Universal (OBLIGATORIO)

**TODAS las herramientas deben incluir un modal de ayuda** con ID estándar `pageHelpModal`. El header universal incluye un botón de ayuda (ícono "i" turquesa) que detecta automáticamente este modal.

## 🗄️ Database Schema Research

When researching database tables, fields, or relations:
- **Primary Source**: ALWAYS use `docs/u839374897_erp.sql` as the single source of truth for the database schema.
- **Verification**: Do not assume table structures based on code alone. Search for the `CREATE TABLE` and `ALTER TABLE` statements within the SQL dump to confirm data types, enums, and foreign key constraints.
- **Updates**: If you identify a discrepancy between the code and the SQL dump, notify the user and prioritize the SQL dump structure.

## 📘 Modal de Ayuda Universal (OBLIGATORIO)

### Implementación Requerida

**TODAS las herramientas deben incluir un modal de ayuda** con ID estándar `pageHelpModal`. El header universal incluye un botón de ayuda (ícono "i" turquesa) que detecta automáticamente este modal.

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
                    Guía de {Nombre de la Herramienta}
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
                                    <i class="fas fa-check me-2"></i> Sección 1
                                </h6>
                                <p class="small text-muted mb-0">
                                    Descripción de funcionalidad...
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

1. **Funcionalidades principales** - Qué hace la herramienta
2. **Permisos y roles** - Quién puede hacer qué
3. **Flujo de trabajo** - Cómo usar la herramienta
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
        <p class="small text-muted mb-0">Información crítica...</p>
    </div>
</div>

<!-- Alertas informativas -->
<div class="alert alert-info py-2 px-3 small">
    <strong><i class="fas fa-info-circle me-1"></i> Nota:</strong>
    <br>
    Información adicional relevante.
</div>
```

### Reglas Obligatorias

- ✅ **ID**: Siempre `id="pageHelpModal"`
- ✅ **Backdrop**: Incluir `data-bs-backdrop="static"`
- ✅ **Z-index**: Incluir CSS de z-index
- ✅ **Tamaño**: Mínimo `modal-lg`
- ✅ **Contenido**: Documentación útil y completa
- ❌ **No**: Modales vacíos o sin información relevante

## 🎭 Modales Premium (Estándar de Diseño)

Para una experiencia de usuario superior, todos los modales nuevos deben seguir el estándar "Premium":

### Modales Premium (Estándar de Diseño)
Ubicación del CSS: `/core/assets/css/modales_premium.css`

Este CSS debe incluirse en cualquier página que requiera modales modernos con efecto glassmorphism y animaciones.
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

/* Animación de entrada */
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
                <h5 class="modal-title">Título Moderno</h5>
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


## 🔘 Botón de Acción Flotante (FAB) — ESTÁNDAR OBLIGATORIO

### Regla

**TODA página de historial o listado que permita crear un registro nuevo DEBE usar el botón circular flotante `.btn-floating-pitaya`.**  
Nunca usar botones pill o inline (`btn btn-success`, `btn btn-primary rounded-pill`, etc.) para esta acción.

### Fuente del CSS

El estilo está en `/core/assets/css/fab_button.css` — **no copiar el CSS a los archivos del módulo**.

Incluir en el `<head>` de la herramienta:

```html
<link rel="stylesheet" href="/core/assets/css/fab_button.css">
```

### Implementación

```php
<?php if (tienePermiso('nombre_herramienta', 'nuevo_registro', $cargoOperario)): ?>
<a href="nueva_herramienta.php" class="btn-floating-pitaya" title="Nuevo Registro">
    <i class="fas fa-plus"></i>
</a>
<?php endif; ?>
```

### Comportamiento Visual

- ⭕ Circular (65×65px), **posición fija** — esquina inferior derecha (`bottom: 35px; right: 35px`)
- 🎨 Color verde Pitaya `#51B8AC` en reposo · oscurece a `#0E544C` en hover
- ✨ Al hacer hover: escala 1.15× + rotación 90° con animación cúbica
- 🔲 Borde blanco de 3px · sombra verde suave

### Requisitos

- ✅ El ícono dentro SIEMPRE es `<i class="fas fa-plus"></i>` (Font Awesome 5)
- ✅ Incluir atributo `title` descriptivo (ej: `title="Nueva Solicitud"`)
- ✅ Envolver en bloque de permiso PHP con `tienePermiso()`
- ✅ La página DEBE cargar `/core/assets/css/global_tools.css` en el `<head>`
- ❌ **PROHIBIDO** redefinir `.btn-floating-pitaya` en CSS del módulo o en `<style>` inline

## 📊 Sistema de Filtros para Tablas

### Tipos de Filtro

1. **Texto Libre** (`data-type="text"`)
   - Para textos grandes o códigos autogenerados
   - Input de búsqueda libre

2. **Número con Rango** (`data-type="number"`)
   - Para cantidades
   - Inputs min y max

3. **Rango de Fechas** (`data-type="daterange"`)
   - Un solo calendario inteligente: desde/hasta
   - **Lógica de Selección**:
     - El panel **no se cierra** automáticamente tras la selección.
     - 1er clic: Define el inicio.
     - 2do clic: Define el fin (con auto-intercambio si es menor al inicio).
     - 3er clic en adelante: 
       - Si es antes del inicio: actualiza `desde`.
       - Si es después del fin o dentro del rango: actualiza `hasta`.
   - Incluir mensaje informativo: "Haz clic en dos fechas para definir el rango."

4. **Lista Definida** (`data-type="list"`)
   - Para datos con enum o consultas limitadas
   - Checkboxes con búsqueda
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

## 🎯 Columnas Estándar en Tablas

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

## 📄 Paginación Estándar

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

## 📚 Librerías Disponibles

- **jQuery 3.x** - DOM manipulation
- **Bootstrap 5.x** - UI framework
- **SweetAlert2** - Alertas bonitas
- **DataTables** (opcional) - Tablas avanzadas
- **Select2** (opcional) - Dropdowns mejorados
- **Chart.js** (opcional) - Gráficas

## 🗄️ Esquema de Base de Datos

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
-- Asignación histórica de cargos a operarios
-- Lógica: Fin IS NULL OR Fin >= CURDATE() AND Fecha <= CURDATE()
```

#### Sucursales
```sql
-- Información de sucursales
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
-- Permisos por cargo para cada acción
-- Campos: id, accion_tool_erp_id, CodNivelesCargos, permiso (allow/deny)
```

(Ver esquema completo en docs/03_Esquema_BaseDatos.md)

## 🚀 Proceso de Generación de Nueva Herramienta

### 1. Recopilar Información

Preguntar al usuario:
- Nombre del módulo
- Área (carpeta)
- Funcionalidades específicas
- Permisos requeridos
- Sufijo de archivos/tablas

### 2. Generar Estructura Completa

- ✅ Archivo PHP principal
- ✅ CSS específico
- ✅ JavaScript con todas las funciones
- ✅ Archivos AJAX necesarios
- ✅ **NUEVO:** Archivo(`.sql`) guardado siempre bajo una subcarpeta `sql/` (ej: `sql/migracion.sql`) para crear o alterar tablas. Nunca uses archivos `.php`.
- ✅ Carpeta uploads (si aplica, max 10MB)

### 3. Incluir Documentación

- ✅ Lista de herramientas (nombre/código) a crear manualmente
- ✅ Lista de permisos a crear manualmente
- ✅ Instrucciones de implementación
- ✅ Consideraciones especiales

### 4. Validar Contra Patrones

- ✅ ¿Usa header_universal?
- ✅ ¿Implementa permisos?
- ✅ ¿Sigue estructura de carpetas?
- ✅ ¿Colores corporativos correctos?
- ✅ ¿AJAX devuelve JSON?
- ✅ ¿El botón "Nuevo Registro" usa `.btn-floating-pitaya` (no un botón pill/inline)?
- ✅ ¿Carga `/core/assets/css/global_tools.css` en el `<head>`?

## 📦 Entregables Esperados

Para cada módulo nuevo:

1. ✅ Todos los archivos de código
2. ✅ Script SQL completo y probado
3. ✅ Lista de herramientas a crear en `tools_erp`
4. ✅ Lista de permisos necesarios
5. ✅ Instrucciones de implementación
6. ✅ Notas sobre configuración especial

**Formato**: Archivos separados listos para copiar/pegar

## 💡 Recordatorios Importantes

- 🚨 **Base de datos = PDO siempre.** Si el código usa MySQLi provocará error 500.
- Siempre seguir herramienta de ejemplo como referencia (docs/04_Plantilla_Modulo_Referencia.md)
- Mantener consistencia con módulos existentes
- Código limpio, comentado y profesional
- Pensar en escalabilidad y mantenimiento
- Validar en frontend **Y** backend
- Responsive design obligatorio
- Usar permisos granulares
- Formato de fechas: `dia-mes-año` (01-Ene-25)

## 📖 Referencias

- **Instrucciones Generales**: `docs/00_Instrucciones_Generales.md`
- **Estándares UI/UX**: `docs/01_Estandares_UI_UX.md`
- **Core Global**: `docs/02_Core_Global_Docs.md`
- **Esquema BD**: `docs/03_Esquema_BaseDatos.md`
- **Plantilla Referencia**: `docs/04_Plantilla_Modulo_Referencia.md`

---

## 🎯 Uso de Esta Skill

Cuando trabajes en el ERP de Batidos Pitaya:

1. **Lee esta skill** antes de comenzar cualquier desarrollo
2. **Sigue los estándares** establecidos aquí
3. **Consulta las referencias** para detalles específicos
4. **Valida tu código** contra los patrones definidos
5. **Genera documentación completa** para cada entregable
