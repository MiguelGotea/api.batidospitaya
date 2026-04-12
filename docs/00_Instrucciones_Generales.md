# CUSTOM INSTRUCTIONS - Batidos Pitaya ERP

## 🎯 CONTEXTO DEL PROYECTO

Sistema ERP modular para Batidos Pitaya desarrollado en PHP + MySQL.
Arquitectura estandarizada con componentes globales compartidos.
Ritmo de desarrollo: promedio de 1 herramienta completa por día.

---

## 🏗️ ARQUITECTURA OBLIGATORIA

### Estructura de archivos por módulo
```
📁 modulos/marketing/
├── cupones.php                             # Archivo principal
├── uploads/                                # uploads de las herramientas limitar max 10mb
├── css/
│   └── cupones.css                         # CSS personalizado
├── js/
│   └── cupones.js                          # JavaScript personalizado
└── ajax/
    ├── cupones_verificar_codigo.php        # AJAX para verificacion de codigo autogenerado
    ├── cupones_guardar.php                 # AJAX para guardar dtos de cupones
    ├── cupones_get_opciones_filtro.php     # AJAX para opciones especificas de filtros
    ├── cupones_get_datos.php               # AJAX para cargar datos de historial segun filtros y orden
    ├── cupones_get_cupon.php               # AJAX para buscar cupon especifico
    └── cupones_eliminar.php                # AJAX para eliminar registros
```

### Sistema de Modal de Ayuda Universal
Todas las herramientas deben incluir un modal con ID `pageHelpModal`. El header universal detecta este modal y activa automáticamente el botón de información (ícono "i" turquesa) para mostrar la guía contextual de la página.


### Áreas del sistema
- `ventas/` - Módulos de Historial de ventas, cupones
- `tecnicodesarrollohumano/` - Módulos de asistente administrativo
- `supervision/` - Auditorías y control
- `sucursales/` - pagina con las herramientas de indicadores y registros del punto de venta
- `sistemas/` - Control de permisos y herramientas de adm de sistema
- `rh/` - Recursos humanos
- `produccion/` - Herramientas de CDS parte d eproduccion registro de procesamiento, porcionamientos, tiempos, etc
- `operarios/` - Módulos herramietnas de colaboradores de tienda
- `operaciones/` - Módulos gestion de sucursales
- `marketing/` - Módulos de cupones, promociones, etc
- `mantenimiento/` - Módulos de gestion de activos, equipos y solciidtudes de antenimento de sucursales y central
- `lideres/` - Módulos de gestion de lideres de sucursales con respecto a sus colaboradores
- `infraestructura/` - Módulos de gestion de activos, mantenimiento , etc pero a nivel de planificacion
- `gerencia/` - Módulos de dirección general, reuniones de liderazgo
- `experienciadigital/` - Módulos de gestion de atencion al cleinte digital, redes sociales
- `diseno/` - Módulos de gestiones del disenador
- `desarrollo/` - Módulos de mejora continua, manual de trabajador de sucursales, tutotriales para vendedores, cajeros
- `contabilidad/` - Módulos de descarga de datos de contbidiad
- `compras/` - Módulos de dgestiond e oc, registro de facturas, solicitudes de compra, etc
- `cds/` - Módulos de gestion de cadena de suinistra a nivel de administracion
- `auxiliaradministrativo/` - Módulos de asistente adminsitrativo de rrhh 
- `atencionalcliente/` - atencion a cliente de clientes , seguimiento de reclamos, etc
- `legal/` - registro de documentos legales
- `productos/` - Catálogo de productos, recetas, insumos y presentaciones

### Sincronización y Despliegue de Módulos (GitHub vs Host)

El sistema utiliza una arquitectura modular donde solo los módulos autorizados son sincronizados con GitHub y desplegados al servidor Hostinger.

1. **Módulos Trackeados**: Solo los módulos listados en el `.gitignore` (vía `!/modulos/{nombre}/`) se suben a GitHub.
2. **Despliegue Automático**: El workflow `deploy-erp.yml` sincroniza estos mismos módulos al host. Si un módulo no está en la lista `MODULES` del workflow, no se subirá al servidor.
3. **Subida de Cambios**:
   - **Global**: `./.scripts/gitpush.ps1` (Sube todo el ERP de forma unificada).

---

## 🎨 IDENTIDAD VISUAL

### Colores corporativos
```css
--color-principal: #51B8AC
```

### Tipografía
- Fuente principal: 'Calibri', sans-serif
- General: font-size: clamp(12px, 2vw, 18px) 
---

## 🔐 SISTEMA DE PERMISOS

### Implementación obligatoria
```php
<?php
require_once('../../core/permissions/permissions.php');

// funcion de permisos
function tienePermiso($nombreHerramienta, $nombreAccion, $codNivelCargo) 

?>
```

### Cargos frecuentes
CodNivelesCargos|Nombre                                         
               2|Vendedor                                       
               5|Lider de Tienda                                
               8|Jefe de Contabilidad                           
               9|Analista de Compras                            
              10|Jefe de Logistica                              
              11|Jefe de Operaciones                            
              12|Jefe de Produccion                             
              13|Gerente de Desarrollo Humano                   
              14|Jefe de Mantenimiento                          
              15|Lider de TI                                    
              16|Gerencia General                               
              17|Jefe de Almacen                                
              19|Jefe de CDS                                    
              20|Conductor                                      
              21|Supervisor de Sucursales                       
              22|Atencion al Cliente                            
              23|Auxiliar de Almacen                            
              24|Motorizado                                     
              25|Diseñador                                      
              26|Lider de Mercadeo y Ventas                     
              27|Tiendas                                        
              28|Técnico de Desarrollo Humano                   
              29|Reclutador                                     
              30|Coordinadora de Clima y Cultura                
              31|Pasante de Experiencia                         
              32|Pasante de Mejora Continua                     
              33|Lider de Desarrollo                            
              34|Operador                                       
              35|Líder de Infraestructura y Expansión Comercial 
              36|Líder General de Tiendas Managua               
              37|Pasante de RRHH                                
              38|Auxiliar Administrativo                        
              39|Responsable de Reclutamiento y Selección       
              40|Técnico de Talento Humano                      
              41|Legal                                          
              42|Gerente de Marketing y Ventas                  
              43|Líder Interino                                 
              44|Vendedor Training                              
              45|Vendedor Junior                                
              46|Vendedor Asistente de Líder                    
              47|Vendedor Experto                               
              48|Técnico de Mejora Continua                     
              49|Gerencia Proyectos                             
              50|Especialista en experiencia digital del cliente

### Permisos típicos por módulo
- `vista` - permiso general para ver herrameinta, esto aplica a todas las herrameintas tienePermiso('index_gerencia', 'vista', $codNivelCargo) 
- `crear` - Crear registros
- `shortcut` - Acceso de vita rapida que se anexa automaticamente en el index de cada modulo que tenga permiso
- `editar` - Modificar registros
- `eliminar` - Eliminar registros
- `aprobar_gerencia` - Aprobaciones de nivel de gerencia
- `exportar_[modulo]` - Exportar a Excel
---

## 📋 COMPONENTES GLOBALES

### Header Universal
```php
<?php require_once('../../core/layout/header_universal.php'); ?>
```
- Incluye titulo de la pagina en uso
- Logo corporativo
- Información del usuario
- Alerta de vistas pendientes de anuncios

### Menú Lateral
```php
<?php require_once('../../core/layout/menu_lateral.php'); ?>
```
- Navegación por módulos y herramientas
- Permisos integrados
- Responsive

### Conexión BD
```php
<?php require_once('../../core/database/conexion.php'); ?>
```
- Variable `$conn` disponible globalmente
- Usa mysql
- Charset UTF-8
- Zona horaria America/Managua

### Funciones Usuarios
```php
<?php require_once('../../core/helpers/funciones.php'); ?>
```
Funciones disponibles:
- `obtenerNombreCompleto($id_empleado)`
- `obtenerCargo($id_empleado)`
- `obtenerSucursal($id_empleado)`
- `verificarPermiso($permiso)`

---

### Sistema de Modal de Ayuda (ID `pageHelpModal`)
Todas las herramientas deben implementar este modal para ayuda contextual.
- **ID Estándar**: `pageHelpModal`
- **Backdrop**: `data-bs-backdrop="static"`
- **Z-index**: Modal (1060), Backdrop (1050)
- **Activación**: Automática vía botón "i" en el header universal.

---

### Envio de correos corporativos
```php
<?php require_once '../../core/email/EmailService.php';; ?>
```
Funciones disponibles:
- `obtenerEmailPorCargo($codNivelCargo)` : Obtiene el email de trabajo del operario que actualmente ocupa un cargo específico en el organigrama
- `enviarCorreo($remitenteId, $destinatarios, $asunto, $cuerpoHtml, $archivos = [])`:  Función principal para enviar correos electrónicos con soporte para adjuntos.
- `obtenerCredencialesUsuario($codOperario)` : Recupera las credenciales de correo del operario desde la base de datos
---

## 📝 REGLAS DE CODIFICACIÓN

### PHP
- ✅ Siempre usar `prepared statements` para consultas SQL
- ✅ Validar y sanitizar TODOS los inputs
- ✅ Usar `try-catch` para operaciones críticas
- ✅ Comentar código complejo
- ✅ Nombres de variables en español descriptivos

### JavaScript
- ✅ Funciones con nombres descriptivos en español
- ✅ Usar `async/await` para AJAX
- ✅ Validar formularios antes de enviar
- ✅ Mostrar loaders durante operaciones
- ✅ Mensajes claros de éxito/error con SweetAlert2

### CSS
- ✅ Mobile-first responsive
- ✅ Usar variables CSS para colores
- ✅ Clases descriptivas con prefijo del módulo
- ✅ Consistencia con estilos globales

### SQL
- ✅ Nombres de tablas: `herrameienta_`
- ✅ Campos de auditoría: `fecha_creacion`, `usuario_creacion`
- ✅ IDs auto-increment
- ✅ Foreign keys con ON DELETE/UPDATE apropiados

---

## 🚀 PROCESO DE GENERACIÓN

Cuando solicite una nueva herramienta, SIEMPRE:

1. **Preguntar detalles:**
   - Nombre del módulo
   - Área (gerencia/rrhh/etc)
   - Funcionalidades específicas
   - Permisos requeridos, asumir que siempre se creara el permiso vista para cada herramienta creada
   - Sufijo de los archivos y tablas a usar

2. **Generar estructura completa:**
   - Archivo PHP principal
   - CSS específico
   - JavaScript con todas las funciones
   - Archivos AJAX necesarios
   - SQL para crear tablas
   - Carpeta uploads si aplica, limitar archivos subidos 10mb maximo
   - **Modal de Ayuda Universal**: Estructura `pageHelpModal` con contenido explicativo de la herramienta.

3. **Incluir documentación:**
   - Lista de herramientas(nombre codigo de cada pagina) a crear manualmente
   - Lista de permisos a crear manualmente
   - Instrucciones de implementación
   - Consideraciones especiales

4. **Validar contra patrones:**
   - ¿Usa header_universal?
   - ¿Implementa permisos?
   - ¿Sigue estructura de carpetas?
   - ¿Colores corporativos correctos?
   - ¿AJAX devuelve JSON?

---

## 📦 LIBRERÍAS DISPONIBLES

- **jQuery 3.x** - DOM manipulation
- **Bootstrap 4.x** - UI framework
- **SweetAlert2** - Alertas bonitas
- **DataTables** (opcional) - Tablas avanzadas
- **Select2** (opcional) - Dropdowns mejorados
- **Chart.js** (opcional) - Gráficas

---

## 🎯 ENTREGABLES ESPERADOS

Para cada módulo nuevo, generar:

1. ✅ Todos los archivos de código
2. ✅ Script SQL completo y probado
3. ✅ Lista de nombre de herramientas asignadas a cada pagina creada necesarios
4. ✅ Lista de permisos necesarios
5. ✅ Instrucciones de implementación
6. ✅ Notas sobre configuración especial
7. ✅ **Guía Informativa**: Estructura HTML del modal `pageHelpModal` completa.

**Formato de entrega:** Archivos separados listos para copiar/pegar

---

## 💡 RECORDATORIOS

- Siempre seguir herramienta de ejemplo como referencia
- Mantener consistencia con módulos existentes
- Código limpio, comentado y profesional
- Pensar en escalabilidad y mantenimiento
- Validar en frontend Y backend
- Responsive design obligatorio
- Usar permisos granulares
 
 ---
 
 ## ⚙️ INFRAESTRUCTURA Y CONFIGURACIÓN
 
 Para configuración de nuevos entornos o ingreso de nuevos desarrolladores, consultar:
 - [**05_Guia_Instalacion_Nueva_PC.md**](05_Guia_Instalacion_Nueva_PC.md): Pasos detallados para configurar GitHub, Git, SSH y sincronización en una máquina nueva.
- [**DEPLOY_SETUP.md**](DEPLOY_SETUP.md): Detalles técnicos del servidor y llaves SSH.

