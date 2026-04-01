# Estilos Globales, Patrones y Estructura Global

## Variables CSS
- Color encabezado tablas {verde oscuro}: #0E544C
- Botones generales (verde clar0): #51B8AC
- Boton nuevo, aceptar, aprobar verde claro #218838
- Boton nuevo, aceptar, aprobar verde claro hover  #1d6f42
- No usar ningun color degradado
- Estilo minimalista, limpio

## Estructura de carpetas
```
📁 public_html/
├── index.php
├── logout.php
├── login.php
├── core/
│   ├── session/
│   ├── router/
│   ├── email/
│   │   └── EmailService.php             # Herramienta para envio de correo corporativo 
│   ├── permissiones/
│   │   └── permissions.php              # Pagina para asignar permisos vista, edicion, etc de herramientas
│   ├── layout/
│   │   ├── menu_lateral.php             # Menu lateral universal para todas las herramientas
│   │   └── header_universal.php         # headedr universal para todas las herramientas
│   ├── helpers/
│   │   ├── funciones.php                # FUnciones de apoyo para datos de operarios, cargos, etc
│   │   └── config.php                   #ajsute de hroario America/managua
│   ├── database/
│   │   └── conexion.php                 # conexion a base de datos
│   ├── auth/
│   │   └── auth.php                     # datos de usuario logueado y redireccion a su modulo
│   └── assets/
│       └── css/
│           └── indexmodulos.css         # css global del index de todos los modulos
│           └── global_tools.css         # css general de toda sla sherramientas * y body
├── modulos/
│   ├── marketing/
│   │   ├── Index.php                   # Pagina de inicio del modulo marketing
│   │   ├── css/                        # css exclusivo de las herramientas de marketing
│   │   ├── js/                         # js exclusivo de las herramientas de marketing
│   │   ├── ajax/                       # ajax exclusivo de las herramientas de marketing
│   │   ├── uploads/                    # uploads de las herramientas de marketing
│   │   └── ...                   
│   ├── gerencia/
│   │   └── Index.php                   # Pagina de inicio del modulo gerencia
│   │   ├── css/                        # css exclusivo de las herramientas de gerencia
│   │   ├── js/                         # js exclusivo de las herramientas de gerencia
│   │   ├── ajax/                       # ajax exclusivo de las herramientas de gerencia
│   │   ├── uploads/                    # uploads de las herramientas de gerencia
│   │   └── ... 
│   ├── operaciones/
│   │   └── Index.php                   # Pagina de inicio del modulo operaciones
│   │   ├── css/                        # css exclusivo de las herramientas de operaciones
│   │   ├── js/                         # js exclusivo de las herramientas de operaciones
│   │   ├── ajax/                       # ajax exclusivo de las herramientas de operaciones
│   │   ├── uploads/                    # uploads de las herramientas de operaciones
│   │   └── ... 
│   ├── mantenimiento/
│   │   └── Index.php                   # Pagina de inicio del modulo mantenimiento
│   │   ├── css/                        # css exclusivo de las herramientas de mantenimiento
│   │   ├── js/                         # js exclusivo de las herramientas de mantenimiento
│   │   ├── ajax/                       # ajax exclusivo de las herramientas de mantenimiento
│   │   ├── uploads/                    # uploads de las herramientas de mantenimiento
│   │   └── ... 
│   └─── ... 
├── uploads/                            # uploads globales
└── cronjobs/                           # cronjobs de hostinger

```

## Variables Fecha
- Formato Fechas: dia / mes / año  01/En/25

## Patrón de paginación
```javascript
const elementosPorPagina = [10, 25, 50, 100, 500];
```

## Sistema Universal de Modal de Ayuda

### Descripción
Todas las herramientas deben incluir un modal de ayuda con ID estándar `pageHelpModal`. El header universal incluye un botón de ayuda (ícono "i" turquesa) que detecta automáticamente si existe este modal y lo abre.

### Implementación Obligatoria

#### 1. Estructura del Modal
Agregar al final de cada página PHP, antes del cierre de `</body>`:

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
                    Guía de [Nombre de la Herramienta]
                </h5>
                <button type="button" class="btn-close btn-close-white" 
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- CONTENIDO PERSONALIZADO AQUÍ -->
            </div>
        </div>
    </div>
</div>

<style>
    /* Ajuste de z-index para evitar que el backdrop cubra el modal */
    #pageHelpModal {
        z-index: 1060 !important;
    }
    .modal-backdrop {
        z-index: 1050 !important;
    }
</style>
```

#### 2. Contenido del Modal
El contenido debe incluir:

- **Secciones organizadas**: Usar cards de Bootstrap para agrupar información
- **Iconos descriptivos**: FontAwesome para mejor visualización
- **Colores consistentes**: Clases de Bootstrap (text-primary, text-warning, etc.)
- **Responsive**: Grid de Bootstrap (col-md-6, col-md-12)

#### 3. Ejemplo de Contenido

```html
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 bg-light">
                <div class="card-body">
                    <h6 class="text-primary border-bottom pb-2 fw-bold">
                        <i class="fas fa-check me-2"></i> Característica 1
                    </h6>
                    <p class="small text-muted mb-0">
                        Descripción de la característica...
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 bg-light">
                <div class="card-body">
                    <h6 class="text-warning border-bottom pb-2 fw-bold">
                        <i class="fas fa-exclamation-triangle me-2"></i> Importante
                    </h6>
                    <p class="small text-muted mb-0">
                        Información importante...
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alertas informativas -->
    <div class="alert alert-info py-2 px-3 small">
        <strong><i class="fas fa-info-circle me-1"></i> Nota:</strong>
        <br>
        Información adicional relevante.
    </div>
</div>
```

#### 4. Reglas Importantes

- ✅ **ID Obligatorio**: Siempre usar `id="pageHelpModal"`
- ✅ **Backdrop Estático**: Incluir `data-bs-backdrop="static"`
- ✅ **Z-index**: Incluir el CSS de z-index
- ✅ **Tamaño Mínimo**: Usar `modal-lg` o `modal-xl`
- ❌ **No usar**: Modales pequeños o sin contenido útil

### Beneficios

- Consistencia en toda la aplicación
- Ayuda contextual siempre disponible
- Mejor experiencia de usuario
- Documentación integrada en la herramienta

## Estilo Premium para Modales (Glassmorphism)

Para implementar modales premium con efecto de desenfoque, animaciones suaves y botones modernos, se debe incluir el archivo CSS global:

```html
<link rel="stylesheet" href="/core/assets/css/modales_premium.css?v=<?php echo mt_rand(1, 10000); ?>">
```

Este estándar reemplaza los estilos de modal antiguos y proporciona:
- **Glassmorphism**: Fondo semi-transparente con `backdrop-filter: blur(10px)`.
- **Animaciones**: Entrada suave con `modalSlideUp`.
- **Botones Modernos**: Clases `.btn-modern`, `.btn-modern-primary`, `.btn-modern-secondary`, `.btn-modern-danger`.
- **Bordes Redondeados**: Radio de 20px para el contenedor del modal.

### Guía de Uso de Botones
```css
/* Botones con estilo moderno */
.btn-modern {
    padding: 12px 30px;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    border: none;
}

.btn-modern-primary { background: #0E544C; color: white; }
.btn-modern-secondary { background: #f8f9fa; color: #445; }
```

- **Bordes:** Siempre usar bordes redondeados pronunciados (`15px` a `20px`).
- **Sombras:** Usar sombras difusas (`box-shadow`) en lugar de bordes sólidos.
- **Interacción:** Los botones deben tener efectos de hover (elevación o cambio de brillo).
- **Consistencia:** Mantener el color institucional `#0E544C` para acciones primarias.

## Sistema de Filtros Avanzados (Header Filters)

Todas las tablas que requieran filtrado dinámico deben implementar el sistema de **Header Filters** de alta fidelidad.

### Visuales Obligatorios
1. **Punto Rojo de Actividad**: Cuando una columna tiene un filtro activo, el icono de embudo debe mostrar un punto rojo superior derecho.
   ```css
   .filter-icon.has-filter::after {
       content: '';
       position: absolute;
       top: -5px; right: -5px;
       width: 10px; height: 10px;
       background-color: #dc3545;
       border-radius: 50%;
       border: 2px solid #0E544C;
   }
   ```
2. **Posiciones Fijas**: Los paneles de filtro deben ser `position: fixed` para evitar recortes por `overflow-hidden` de la tabla o card.
3. **Z-Index**: Los paneles deben tener un `z-index: 1050` o superior para flotar sobre cualquier otro elemento.

### Comportamiento Estándar
- **Orden**: Usar etiquetas `A→Z` y `Z→A`.
- **Rango de Fechas**: El calendario debe permitir seleccionar Mes y Año. No cerrar el panel hasta que el usuario haga clic fuera.
- **Limpieza**: Siempre incluir un botón "Limpiar" rojo (`.clear`) dentro del panel de cada columna.
