# Herramienta de Ejemplo - Auditorías Consolidadas
Los estandares del ERP aplicados en este ejemplo son los siguientes:
- Estructura de archivos
- llamado de auth donde va incluido funciones.php y conexion.php
- llamado y aplicacion de menu lateral
- llamado y aplicacion de header universal
- llamado y aplicacion de permisos, siempre debe de existir en las apginas principales el permiso vista
- llamado de css para herramientas globales global_tools.css donde esta incluidos css * y body
- llamado de css personalizado en cada pagina, ejemplo: /public_html/modulos/marketing/css/cupones.css
- llamado de js personalizado en cada pagina, ejemplo: /public_html/modulos/marketing/js/cupones.js
- Estructura de div con main-container y sub-container que estan dentro de global_tools.css
- Uso de filtros de tablas para herramientas que llevan tablas, los tipos de filtro son los siguientes:
    - texto libre para textos grandes o codigos autogenerados
    - numero con rango min y maximos para cantidades
    - fecha con 1 calendario inteligente para seleccionar y filtrar rango de fechas:
        - El panel **no se cierra** automáticamente tras la selección.
        - Un solo calendario maneja el rango dinámicamente: el 1er clic fija el inicio, el 2do el fin, y clics posteriores ajustan el límite más cercano (o el fin si es interno o posterior).
        - Debe incluir un mensaje informativo: "Haz clic en dos fechas para definir el rango."
    - lista definida con check siempre con una celda de texto libre para filtrar entre las opciones que se le puede dar check, aplica para datos que tienen enum en la base de datos o cuando es una consulta limitada de una tabla como sucursales, cargos, tipos, estados, etc.
- COlumna de Acciones con todas las opciones de acciones sobre registros
- Columna de estado como etiquetas
- Sistema de Modal de Ayuda Universal con ID `pageHelpModal` y z-index ajustado.

##

## Estructura
```
📁 marketing/
├── cupones.php                             # Archivo principal
├── uploads/                                # Carpeta para archivos
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

## Archivo Principal: /public_html/modulos/marketing/cupones.php
```php
<?php
// cupones.php

require_once '../../core/auth/auth.php';
require_once '../../core/layout/menu_lateral.php';
require_once '../../core/layout/header_universal.php';
require_once '../../core/permissions/permissions.php';

$usuario = obtenerUsuarioActual();
$cargoOperario = $usuario['CodNivelesCargos'];

// Verificar acceso
if (!tienePermiso('cupones', 'vista', $cargoOperario)) {
    header('Location: /login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cupones</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" href="../../assets/img/icon12.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/global_tools.css?v=<?php echo mt_rand(1, 10000); ?>">
    <link rel="stylesheet" href="css/cupones.css?v=<?php echo mt_rand(1, 10000); ?>">
</head>
<body>
    <?php echo renderMenuLateral($cargoOperario); ?>
    
    <div class="main-container">
        <div class="sub-container">
            <?php echo renderHeader($usuario, false, 'Gestión de Cupones'); ?>
            
            <div class="container-fluid p-3">
                <!-- Botón para agregar nuevo cupón -->
                <?php if (tienePermiso('cupones', 'nuevo_registro', $cargoOperario)): ?>
                <div class="mb-3">
                    <button class="btn btn-success" onclick="abrirModalNuevoCupon()">
                        <i class="bi bi-plus-circle"></i> Nuevo Cupón
                    </button>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover cupones-table" id="tablaCupones">
                        <thead>
                            <tr>
                                <th data-column="numero_cupon" data-type="text">
                                    Número de Cupón
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="monto" data-type="number">
                                    Monto
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="fecha_caducidad" data-type="daterange">
                                    Fecha Caducidad
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="fecha_registro" data-type="daterange">
                                    Fecha Registro
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="nombre_sucursal" data-type="text">
                                    Sucursal
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="cod_pedido" data-type="text">
                                    Nº Pedido
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="observaciones" data-type="text">
                                    Observaciones
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th data-column="aplicado" data-type="list">
                                    Estado
                                    <i class="bi bi-funnel filter-icon" onclick="toggleFilter(this)"></i>
                                </th>
                                <th style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCuponesBody">
                            <!-- Datos cargados vía AJAX -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="mb-0">Mostrar:</label>
                        <select class="form-select form-select-sm" id="registrosPorPagina" style="width: auto;" onchange="cambiarRegistrosPorPagina()">
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="mb-0">registros</span>
                    </div>
                    <div id="paginacion"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar/editar cupón -->
    <div class="modal fade" id="modalCupon" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCuponTitulo">Nuevo Cupón</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCupon">
                        <input type="hidden" id="cuponId" name="id">
                        
                        <div class="mb-3">
                            <label for="numeroCupon" class="form-label">Número de Cupón *</label>
                            <input type="text" class="form-control" id="numeroCupon" name="numero_cupon" readonly>
                            <small class="text-muted">Se genera automáticamente</small>
                        </div>

                        <div class="mb-3">
                            <label for="montoCupon" class="form-label">Monto *</label>
                            <input type="number" class="form-control" id="montoCupon" name="monto" required min="0">
                        </div>

                        <div class="mb-3">
                            <label for="fechaCaducidad" class="form-label">Fecha de Caducidad *</label>
                            <input type="date" class="form-control" id="fechaCaducidad" name="fecha_caducidad" required>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Notas adicionales sobre el cupón"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCupon()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Guía de Ayuda (OBLIGATORIO) -->
    <div class="modal fade" id="pageHelpModal" tabindex="-1" aria-labelledby="pageHelpModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="pageHelpModalLabel">
                        <i class="fas fa-info-circle me-2"></i>
                        Guía de Gestión de Cupones
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="text-primary border-bottom pb-2 fw-bold">
                                        <i class="fas fa-plus-circle me-2"></i> Nuevo Registro
                                    </h6>
                                    <p class="small text-muted mb-0">
                                        Utilice el botón "Nuevo Cupón" para generar un código automático de descuento.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="text-warning border-bottom pb-2 fw-bold">
                                        <i class="fas fa-calendar-times me-2"></i> Caducidad
                                    </h6>
                                    <p class="small text-muted mb-0">
                                        Los cupones tienen una fecha límite; una vez pasada, cambian de estado automáticamente.
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
        /* Ajuste de z-index para evitar que el backdrop cubra el modal */
        #pageHelpModal {
            z-index: 1060 !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cupones.js?v=<?php echo mt_rand(1, 10000); ?>"></script>
</body>
</html>
```

## CSS: /public_html/modulos/marketing/css/cupones.css
```css
/* Asegurar scroll en la página */
html {
    overflow-y: scroll;
    min-height: 100vh;
}

body {
    min-height: 100vh;
}

/* Tabla */
.cupones-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cupones-table thead {
    background-color: #0E544C !important;
    color: white !important;
}

.cupones-table thead th {
    font-weight: 600;
    padding: 14px 12px;
    border: none;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    white-space: nowrap;
    font-size: 14px;
    background-color: #0E544C !important;
    color: white !important;
}

.cupones-table thead th:last-child {
    border-right: none;
}

.cupones-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
}

.cupones-table tbody tr:hover {
    background-color: #f8f9fa;
}

.cupones-table tbody td {
    padding: 12px;
    vertical-align: middle;
    font-size: 14px;
    border-right: 1px solid #e9ecef;
}

.cupones-table tbody td:last-child {
    border-right: none;
}

/* Iconos de filtro */
.filter-icon {
    cursor: pointer;
    margin-left: 6px;
    opacity: 0.6;
    transition: all 0.2s;
    font-size: 13px;
    position: relative;
    display: inline-block;
}

.filter-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

.filter-icon.active {
    opacity: 1;
    color: #51B8AC;
}

/* Indicador de filtro activo */
.filter-icon.has-filter::after {
    content: '';
    position: absolute;
    top: -5px;
    right: -5px;
    width: 10px;
    height: 10px;
    background-color: #dc3545;
    border-radius: 50%;
    border: 2px solid #0E544C;
}

/* Panel de filtro */
.filter-panel {
    position: fixed;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    padding: 16px;
    z-index: 1050;
    min-width: 300px;
    max-width: 350px;
    display: none;
}

.filter-panel.show {
    display: block;
}

.filter-section {
    margin-bottom: 16px;
}

.filter-section:last-of-type {
    margin-bottom: 12px;
}

.filter-section-title {
    display: block;
    font-weight: 600;
    font-size: 13px;
    color: #0E544C;
    margin-bottom: 8px;
}

/* Búsqueda */
.filter-search {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.filter-search:focus {
    outline: none;
    border-color: #51B8AC;
}

/* Inputs numéricos */
.numeric-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

/* Opciones de lista */
.filter-options {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 4px;
    margin-top: 8px;
}

.filter-option {
    padding: 8px 10px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-option:hover {
    background-color: #f8f9fa;
}

.filter-option input[type="checkbox"] {
    cursor: pointer;
}

/* Rango de fechas */
.daterange-inputs {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 6px;
}

.daterange-calendar-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 100%;
}

.daterange-month-selector {
    display: flex;
    gap: 4px;
}

.daterange-month-selector select {
    flex: 1;
    padding: 3px 4px;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    font-size: 11px;
    background-color: white;
}

.daterange-month-selector select:focus {
    outline: none;
    border-color: #51B8AC;
}

.daterange-calendar {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px;
    background-color: #fafafa;
}

.daterange-calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    margin-bottom: 2px;
}

.daterange-calendar-day-name {
    text-align: center;
    font-size: 9px;
    font-weight: 600;
    color: #6c757d;
    padding: 1px 0;
}

.daterange-calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}

.daterange-calendar-day {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s;
    background-color: white;
    padding: 14px;
}

.daterange-calendar-day:hover {
    background-color: #e9ecef;
}

.daterange-calendar-day.empty {
    cursor: default;
    background-color: transparent;
}

.daterange-calendar-day.selected {
    background-color: #51B8AC;
    color: white;
    font-weight: 600;
}

.daterange-calendar-day.in-range {
    background-color: rgba(81, 184, 172, 0.2);
}

/* Info de rango seleccionado */
.daterange-selected-info {
    margin-top: 8px;
    padding: 8px 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
    text-align: center;
    border: 1px solid #dee2e6;
}

.daterange-selected-info small {
    font-size: 11px;
    color: #495057;
}

.daterange-selected-info strong {
    color: #0E544C;
    font-weight: 600;
}


/* Botones de orden */
.filter-sort-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}

.filter-sort-btn {
    padding: 8px 12px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    text-align: center;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.filter-sort-btn:hover {
    background-color: #f8f9fa;
    border-color: #51B8AC;
}

.filter-sort-btn.active {
    background-color: #51B8AC;
    color: white;
    border-color: #51B8AC;
}

/* Botones de acción */
.filter-actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    margin-top: 12px;
    border-top: 1px solid #e9ecef;
}

.filter-action-btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.filter-action-btn.clear {
    background-color: #dc3545;
    color: white;
}

.filter-action-btn.clear:hover {
    background-color: #c82333;
}

/* Botones de acciones en tabla */
.btn-accion {
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-right: 4px;
}

.btn-accion i {
    font-size: 14px;
}

.btn-editar {
    background-color: #0d6efd;
    color: white;
}

.btn-editar:hover {
    background-color: #0b5ed7;
}

.btn-eliminar {
    background-color: #dc3545;
    color: white;
}

.btn-eliminar:hover {
    background-color: #c82333;
}

/* Paginación */
#paginacion {
    display: flex;
    gap: 4px;
    align-items: center;
}

.pagination-btn {
    padding: 6px 12px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
    min-width: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pagination-btn:hover:not(:disabled) {
    background-color: #f8f9fa;
    border-color: #51B8AC;
}

.pagination-btn.active {
    background-color: #51B8AC;
    color: white;
    border-color: #51B8AC;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Badges de estado */
.badge {
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 500;
    border-radius: 4px;
}

/* Scrollbar personalizada */
.filter-options::-webkit-scrollbar {
    width: 6px;
}

.filter-options::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.filter-options::-webkit-scrollbar-thumb {
    background: #51B8AC;
    border-radius: 3px;
}

.filter-options::-webkit-scrollbar-thumb:hover {
    background: #0E544C;
}

/* Responsive */
@media (max-width: 768px) {
    .filter-panel {
        min-width: 260px;
        max-width: 90vw;
    }

    .cupones-table {
        font-size: 13px;
    }

    .cupones-table thead th,
    .cupones-table tbody td {
        padding: 10px 8px;
    }

    .btn-accion {
        padding: 4px 8px;
        font-size: 12px;
    }
}
```

## JavaScript: /public_html/modulos/marketing/js/cupones.js
```javascript
let paginaActual = 1;
let registrosPorPagina = 25;
let filtrosActivos = {};
let ordenActivo = { columna: null, direccion: 'asc' };
let panelFiltroAbierto = null;
let totalRegistros = 0;
let modalCupon;

// Inicializar
$(document).ready(function () {
    modalCupon = new bootstrap.Modal(document.getElementById('modalCupon'));
    cargarDatos();

    // Cerrar filtros solo si se hace clic fuera del panel Y del icono
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.filter-panel, .filter-icon').length) {
            cerrarTodosFiltros();
        }
    });

    // NO cerrar filtros al hacer scroll en la tabla
    $('.table-responsive').on('scroll', function (e) {
        e.stopPropagation();
    });

    // NO cerrar filtros al hacer scroll en la página
    $(window).on('scroll', function (e) {
        // Solo cerrar si el scroll es significativo (más de 50px desde que se abrió)
        if (panelFiltroAbierto && Math.abs($(window).scrollTop() - scrollTopInicial) > 50) {
            cerrarTodosFiltros();
        }
    });

    $(window).on('resize', function () {
        if (panelFiltroAbierto) {
            cerrarTodosFiltros();
        }
    });
});

let scrollTopInicial = 0;

// Cargar datos
function cargarDatos() {
    $.ajax({
        url: 'ajax/cupones_get_datos.php',
        method: 'POST',
        data: {
            pagina: paginaActual,
            registros_por_pagina: registrosPorPagina,
            filtros: JSON.stringify(filtrosActivos),
            orden: JSON.stringify(ordenActivo)
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                totalRegistros = response.total_registros;
                renderizarTabla(response.datos);
                renderizarPaginacion(response.total_registros);
                actualizarIndicadoresFiltros();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function () {
            alert('Error al cargar los datos');
        }
    });
}

// Renderizar tabla
function renderizarTabla(datos) {
    const tbody = $('#tablaCuponesBody');
    tbody.empty();

    if (datos.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center py-4">No se encontraron registros</td></tr>');
        return;
    }

    datos.forEach(row => {
        const tr = $('<tr>');

        tr.append(`<td>${row.numero_cupon || '-'}</td>`);
        tr.append(`<td>${Number(row.monto).toLocaleString()}</td>`);
        tr.append(`<td>${formatearFecha(row.fecha_caducidad)}</td>`);
        tr.append(`<td>${formatearFecha(row.fecha_registro)}</td>`);

        // Nombre de sucursal
        tr.append(`<td>${row.nombre_sucursal || '-'}</td>`);

        // Número de pedido
        tr.append(`<td>${row.cod_pedido || '-'}</td>`);

        // Observaciones con tooltip si es muy largo
        const observaciones = row.observaciones || '-';
        const observacionesCorto = observaciones.length > 50
            ? observaciones.substring(0, 50) + '...'
            : observaciones;
        tr.append(`<td title="${observaciones}">${observacionesCorto}</td>`);

        // Estado
        const estadoClass = row.aplicado == 1 ? 'badge bg-success' : 'badge bg-warning text-dark';
        const estadoTexto = row.aplicado == 1 ? 'Aplicado' : 'Disponible';
        tr.append(`<td><span class="${estadoClass}">${estadoTexto}</span></td>`);

        // Botones de acciones - solo si no está aplicado
        let btnAcciones = '';
        if (row.aplicado == 0) {
            btnAcciones = `
                <button class="btn-accion btn-editar" onclick="editarCupon(${row.id})" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn-accion btn-eliminar" onclick="eliminarCupon(${row.id})" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            `;
        }
        tr.append(`<td>${btnAcciones}</td>`);

        tbody.append(tr);
    });
}

// Abrir modal para nuevo cupón
function abrirModalNuevoCupon() {
    $('#modalCuponTitulo').text('Nuevo Cupón');
    $('#formCupon')[0].reset();
    $('#cuponId').val('');

    // Generar código automático
    generarCodigoCupon();

    modalCupon.show();
}

// Generar código de cupón automático
function generarCodigoCupon() {
    // Generar número aleatorio de 9 dígitos
    const codigo = Math.floor(100000000 + Math.random() * 900000000);

    // Verificar que no exista en la base de datos
    $.ajax({
        url: 'ajax/cupones_verificar_codigo.php',
        method: 'POST',
        data: { numero_cupon: codigo },
        dataType: 'json',
        async: false,
        success: function (response) {
            if (response.existe) {
                // Si existe, generar uno nuevo recursivamente
                generarCodigoCupon();
            } else {
                $('#numeroCupon').val(codigo);
            }
        },
        error: function () {
            // En caso de error, usar el código generado
            $('#numeroCupon').val(codigo);
        }
    });
}

// Editar cupón
function editarCupon(id) {
    $.ajax({
        url: 'ajax/cupones_get_cupon.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                $('#modalCuponTitulo').text('Editar Cupón');
                $('#cuponId').val(response.data.id);
                $('#numeroCupon').val(response.data.numero_cupon);
                $('#montoCupon').val(response.data.monto);
                $('#fechaCaducidad').val(response.data.fecha_caducidad);
                $('#observaciones').val(response.data.observaciones || '');
                modalCupon.show();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function () {
            alert('Error al cargar el cupón');
        }
    });
}

// Guardar cupón
function guardarCupon() {
    const formData = $('#formCupon').serialize();
    const id = $('#cuponId').val();
    const accion = id ? 'editar' : 'crear';

    $.ajax({
        url: 'ajax/cupones_guardar.php',
        method: 'POST',
        data: formData + '&accion=' + accion,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                modalCupon.hide();
                cargarDatos();
                alert(response.message);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function () {
            alert('Error al guardar el cupón');
        }
    });
}

// Eliminar cupón
function eliminarCupon(id) {
    if (!confirm('¿Está seguro de eliminar este cupón?')) {
        return;
    }

    $.ajax({
        url: 'ajax/cupones_eliminar.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                cargarDatos();
                alert(response.message);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function () {
            alert('Error al eliminar el cupón');
        }
    });
}

// Toggle filtro
function toggleFilter(icon) {
    const th = $(icon).closest('th');
    const columna = th.data('column');
    const tipo = th.data('type');

    if (panelFiltroAbierto === columna) {
        cerrarTodosFiltros();
        return;
    }

    cerrarTodosFiltros();
    scrollTopInicial = $(window).scrollTop(); // Guardar posición de scroll
    crearPanelFiltro(th, columna, tipo, icon);
    panelFiltroAbierto = columna;
    $(icon).addClass('active');
    actualizarIndicadoresFiltros();
}

// Crear panel de filtro
function crearPanelFiltro(th, columna, tipo, icon) {
    const panel = $('<div class="filter-panel show"></div>');

    // Agregar clase especial si es filtro de fecha
    if (tipo === 'daterange') {
        panel.addClass('has-daterange');
    }

    // Ordenamiento
    panel.append(`
        <div class="filter-section">
            <span class="filter-section-title">Ordenar:</span>
            <div class="filter-sort-buttons">
                <button class="filter-sort-btn ${ordenActivo.columna === columna && ordenActivo.direccion === 'asc' ? 'active' : ''}" 
                        onclick="aplicarOrden('${columna}', 'asc')">
                    <i class="bi bi-sort-alpha-down"></i> A→Z
                </button>
                <button class="filter-sort-btn ${ordenActivo.columna === columna && ordenActivo.direccion === 'desc' ? 'active' : ''}" 
                        onclick="aplicarOrden('${columna}', 'desc')">
                    <i class="bi bi-sort-alpha-up"></i> Z→A
                </button>
            </div>
        </div>
    `);

    // Botones de acción después del ordenamiento
    panel.append(`
        <div class="filter-actions">
            <button class="filter-action-btn clear" onclick="limpiarFiltro('${columna}')">
                <i class="bi bi-x-circle"></i> Limpiar
            </button>
        </div>
    `);

    // Agregar al body primero
    $('body').append(panel);

    // Filtros según tipo (después de agregar al DOM)
    if (tipo === 'text') {
        const valorActual = filtrosActivos[columna] || '';
        panel.append(`
            <div class="filter-section" style="margin-top: 12px;">
                <span class="filter-section-title">Buscar:</span>
                <input type="text" class="filter-search" placeholder="Escribir..." 
                       value="${valorActual}"
                       oninput="filtrarBusqueda('${columna}', this.value)">
            </div>
        `);
        posicionarPanelFiltro(panel, icon);
    } else if (tipo === 'number') {
        crearFiltroNumerico(panel, columna);
        posicionarPanelFiltro(panel, icon);
    } else if (tipo === 'list') {
        cargarOpcionesFiltro(panel, columna, icon);
    } else if (tipo === 'daterange') {
        crearCalendarioDoble(panel, columna);
        posicionarPanelFiltro(panel, icon);
    }
}

// Crear filtro numérico
function crearFiltroNumerico(panel, columna) {
    const valorMin = filtrosActivos[columna]?.min || '';
    const valorMax = filtrosActivos[columna]?.max || '';

    panel.append(`
        <div class="filter-section" style="margin-top: 12px;">
            <span class="filter-section-title">Rango:</span>
            <div class="numeric-inputs">
                <input type="number" class="filter-search" placeholder="Mínimo" 
                       value="${valorMin}"
                       onchange="filtrarNumerico('${columna}', 'min', this.value)">
                <input type="number" class="filter-search" placeholder="Máximo" 
                       value="${valorMax}"
                       onchange="filtrarNumerico('${columna}', 'max', this.value)">
            </div>
        </div>
    `);
}

// Filtrar numérico
function filtrarNumerico(columna, tipo, valor) {
    if (!filtrosActivos[columna]) {
        filtrosActivos[columna] = {};
    }

    if (valor === '') {
        delete filtrosActivos[columna][tipo];
        if (Object.keys(filtrosActivos[columna]).length === 0) {
            delete filtrosActivos[columna];
        }
    } else {
        filtrosActivos[columna][tipo] = valor;
    }

    paginaActual = 1;
    cargarDatos();
    // NO cerrar el filtro automáticamente
}

// Crear calendario único para rango de fechas
function crearCalendarioDoble(panel, columna) {
    const fechaDesde = filtrosActivos[columna]?.desde || '';
    const fechaHasta = filtrosActivos[columna]?.hasta || '';

    const hoy = new Date();
    const mesActual = hoy.getMonth();
    const añoActual = hoy.getFullYear();

    // Determinar mes inicial basado en fechas seleccionadas
    let mesInicial = mesActual;
    let añoInicial = añoActual;

    if (fechaDesde) {
        const d = new Date(fechaDesde);
        mesInicial = d.getMonth();
        añoInicial = d.getFullYear();
    }

    panel.append(`
        <div class="filter-section" style="margin-top: 8px; margin-bottom: 6px;">
            <span class="filter-section-title">Seleccionar rango:</span>
            <div class="daterange-inputs">
                <div class="daterange-calendar-container">
                    <div class="daterange-month-selector">
                        <select id="mesCalendario" onchange="actualizarCalendarioUnico('${columna}')"></select>
                        <select id="añoCalendario" onchange="actualizarCalendarioUnico('${columna}')"></select>
                    </div>
                    <div class="daterange-calendar" id="calendarioRango"></div>
                </div>
                <div class="daterange-info mt-2" style="font-size: 0.8rem; color: #666;">
                    <i class="bi bi-info-circle"></i> Haz clic en dos fechas para definir el rango.
                </div>
            </div>
        </div>
    `);

    setTimeout(() => {
        inicializarSelectoresFechaUnico(mesInicial, añoInicial);
        actualizarCalendarioUnico(columna);
    }, 50);
}

// Inicializar selectores de fecha para calendario único
function inicializarSelectoresFechaUnico(mesInicial, añoInicial) {
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    // Llenar selector de mes
    const selectMes = $('#mesCalendario');
    meses.forEach((mes, idx) => {
        selectMes.append(`<option value="${idx}" ${idx === mesInicial ? 'selected' : ''}>${mes}</option>`);
    });

    // Llenar selector de año
    const selectAño = $('#añoCalendario');
    const añoActual = new Date().getFullYear();
    for (let año = añoActual - 5; año <= añoActual + 1; año++) {
        selectAño.append(`<option value="${año}" ${año === añoInicial ? 'selected' : ''}>${año}</option>`);
    }
}

// Actualizar calendario único
function actualizarCalendarioUnico(columna) {
    const mes = parseInt($('#mesCalendario').val());
    const año = parseInt($('#añoCalendario').val());
    const calendarioId = '#calendarioUnico';


    const primerDia = new Date(año, mes, 1).getDay();
    const diasEnMes = new Date(año, mes + 1, 0).getDate();

    const diasSemana = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
    let html = '<div class="daterange-calendar-header">';
    diasSemana.forEach(dia => {
        html += `<div class="daterange-calendar-day-name">${dia}</div>`;
    });
    html += '</div><div class="daterange-calendar-days">';

    // Días vacíos al inicio
    for (let i = 0; i < primerDia; i++) {
        html += '<div class="daterange-calendar-day empty"></div>';
    }

    // Días del mes
    for (let dia = 1; dia <= diasEnMes; dia++) {
        const fechaStr = `${año}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
        const clases = obtenerClasesCalendario(fechaStr, columna);
        html += `<div class="daterange-calendar-day ${clases}" onclick="event.stopPropagation(); seleccionarFechaUnico('${fechaStr}', '${columna}')">${dia}</div>`;
    }

    html += '</div>';
    $(calendarioId).html(html);
}

// Obtener clases para días del calendario
function obtenerClasesCalendario(fecha, columna) {
    const fechaDesde = filtrosActivos[columna]?.desde;
    const fechaHasta = filtrosActivos[columna]?.hasta;

    let clases = [];

    if (fecha === fechaDesde || fecha === fechaHasta) {
        clases.push('selected');
    } else if (fechaDesde && fechaHasta) {
        if (fecha > fechaDesde && fecha < fechaHasta) {
            clases.push('in-range');
        }
    }

    return clases.join(' ');
}

// Seleccionar fecha en calendario único con lógica inteligente
function seleccionarFechaUnico(fecha, columna) {
    // Detener propagación del evento
    if (window.event) {
        window.event.stopPropagation();
    }

    if (!filtrosActivos[columna]) {
        filtrosActivos[columna] = {};
    }

    const fechaDesde = filtrosActivos[columna].desde;
    const fechaHasta = filtrosActivos[columna].hasta;

    // Lógica inteligente de selección:
    // 1. Si no hay fechas seleccionadas, esta es la fecha "desde"
    if (!fechaDesde && !fechaHasta) {
        filtrosActivos[columna].desde = fecha;
    }
    // 2. Si solo hay "desde", determinar si esta es "hasta" o nueva "desde"
    else if (fechaDesde && !fechaHasta) {
        if (fecha >= fechaDesde) {
            // La fecha es mayor o igual, se convierte en "hasta"
            filtrosActivos[columna].hasta = fecha;
        } else {
            // La fecha es menor, se convierte en nueva "desde"
            filtrosActivos[columna].desde = fecha;
        }
    }
    // 3. Si ambas fechas están seleccionadas
    else if (fechaDesde && fechaHasta) {
        if (fecha < fechaDesde) {
            // La fecha es menor que "desde", se convierte en nueva "desde"
            filtrosActivos[columna].desde = fecha;
        } else if (fecha > fechaHasta) {
            // La fecha es mayor que "hasta", se convierte en nueva "hasta"
            filtrosActivos[columna].hasta = fecha;
        } else {
            // La fecha está dentro del rango, reiniciar selección
            filtrosActivos[columna] = { desde: fecha };
        }
    }

    // Actualizar el calendario para mostrar el rango
    actualizarCalendarioUnico(columna);

    // Aplicar filtro cuando ambas fechas están seleccionadas
    if (filtrosActivos[columna].desde && filtrosActivos[columna].hasta) {
        paginaActual = 1;
        cargarDatos();
    }
}

// Cargar opciones de filtro
function cargarOpcionesFiltro(panel, columna, icon) {
    $.ajax({
        url: 'ajax/cupones_get_opciones_filtro.php',
        method: 'POST',
        data: { columna: columna },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                let html = '<div class="filter-section" style="margin-top: 12px;">';
                html += '<span class="filter-section-title">Filtrar por:</span>';
                html += '<input type="text" class="filter-search" placeholder="Buscar..." onkeyup="buscarEnOpciones(this)">';
                html += '<div class="filter-options">';

                response.opciones.forEach(opcion => {
                    const checked = filtrosActivos[columna] && filtrosActivos[columna].includes(opcion.valor) ? 'checked' : '';
                    html += `
                        <div class="filter-option">
                            <input type="checkbox" value="${opcion.valor}" ${checked}
                                   onchange="toggleOpcionFiltro('${columna}', '${opcion.valor}', this.checked)">
                            <span>${opcion.texto}</span>
                        </div>
                    `;
                });

                html += '</div></div>';
                panel.append(html);

                // Posicionar después de agregar el contenido
                posicionarPanelFiltro(panel, icon);
            }
        }
    });
}

// Posicionar panel
function posicionarPanelFiltro(panel, icon) {
    const iconOffset = $(icon).offset();
    const iconWidth = $(icon).outerWidth();
    const iconHeight = $(icon).outerHeight();
    const panelWidth = panel.outerWidth();
    const panelHeight = panel.outerHeight();
    const windowWidth = $(window).width();
    const windowHeight = $(window).height();
    const scrollTop = $(window).scrollTop();

    // Intentar posicionar debajo del icono
    let top = iconOffset.top + iconHeight + 5;
    let left = iconOffset.left - panelWidth + iconWidth;

    // Ajustar horizontalmente si se sale de la pantalla
    if (left + panelWidth > windowWidth) {
        left = windowWidth - panelWidth - 10;
    }
    if (left < 10) {
        left = 10;
    }

    // Verificar si cabe debajo del icono
    const espacioAbajo = windowHeight + scrollTop - top;
    const espacioArriba = iconOffset.top - scrollTop;

    // Si no cabe abajo pero sí arriba, posicionar arriba
    if (espacioAbajo < panelHeight && espacioArriba > panelHeight) {
        top = iconOffset.top - panelHeight - 5;
    }

    // Si no cabe en ningún lado, ajustar al espacio disponible
    if (top + panelHeight > windowHeight + scrollTop) {
        top = Math.max(scrollTop + 10, windowHeight + scrollTop - panelHeight - 10);
    }

    if (top < scrollTop + 10) {
        top = scrollTop + 10;
    }

    panel.css({
        top: top + 'px',
        left: left + 'px'
    });
}

// Actualizar indicadores
function actualizarIndicadoresFiltros() {
    $('.filter-icon').removeClass('has-filter');
    Object.keys(filtrosActivos).forEach(columna => {
        const valor = filtrosActivos[columna];
        if ((Array.isArray(valor) && valor.length > 0) ||
            (!Array.isArray(valor) && typeof valor === 'object' && Object.keys(valor).length > 0) ||
            (!Array.isArray(valor) && typeof valor !== 'object' && valor !== '')) {
            $(`th[data-column="${columna}"] .filter-icon`).addClass('has-filter');
        }
    });
}

// Limpiar filtro
function limpiarFiltro(columna) {
    delete filtrosActivos[columna];
    cerrarTodosFiltros();
    paginaActual = 1;
    cargarDatos();
}

// Cerrar filtros
function cerrarTodosFiltros() {
    $('.filter-panel').remove();
    $('.filter-icon').removeClass('active');
    panelFiltroAbierto = null;
}

// Aplicar orden
function aplicarOrden(columna, direccion) {
    ordenActivo = { columna, direccion };
    cerrarTodosFiltros();
    paginaActual = 1;
    cargarDatos();
}

// Filtrar búsqueda
function filtrarBusqueda(columna, valor) {
    if (valor.trim() === '') {
        delete filtrosActivos[columna];
    } else {
        filtrosActivos[columna] = valor;
    }
    paginaActual = 1;
    cargarDatos();
    // NO cerrar el filtro automáticamente
}

// Toggle opción filtro
function toggleOpcionFiltro(columna, valor, checked) {
    if (!filtrosActivos[columna]) {
        filtrosActivos[columna] = [];
    }
    if (checked) {
        if (!filtrosActivos[columna].includes(valor)) {
            filtrosActivos[columna].push(valor);
        }
    } else {
        filtrosActivos[columna] = filtrosActivos[columna].filter(v => v !== valor);
        if (filtrosActivos[columna].length === 0) {
            delete filtrosActivos[columna];
        }
    }
    paginaActual = 1;
    cargarDatos();
    // NO cerrar el filtro automáticamente
}

// Cambiar registros por página
function cambiarRegistrosPorPagina() {
    registrosPorPagina = parseInt($('#registrosPorPagina').val());
    paginaActual = 1;
    cargarDatos();
}

// Renderizar paginación
function renderizarPaginacion(totalRegistros) {
    const totalPaginas = Math.ceil(totalRegistros / registrosPorPagina);
    const paginacion = $('#paginacion');
    paginacion.empty();

    paginacion.append(`
        <button class="pagination-btn" onclick="cambiarPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-left"></i>
        </button>
    `);

    let inicio = Math.max(1, paginaActual - 2);
    let fin = Math.min(totalPaginas, paginaActual + 2);

    if (inicio > 1) {
        paginacion.append(`<button class="pagination-btn" onclick="cambiarPagina(1)">1</button>`);
        if (inicio > 2) {
            paginacion.append(`<span class="pagination-btn" disabled>...</span>`);
        }
    }

    for (let i = inicio; i <= fin; i++) {
        const activeClass = i === paginaActual ? 'active' : '';
        paginacion.append(`<button class="pagination-btn ${activeClass}" onclick="cambiarPagina(${i})">${i}</button>`);
    }

    if (fin < totalPaginas) {
        if (fin < totalPaginas - 1) {
            paginacion.append(`<span class="pagination-btn" disabled>...</span>`);
        }
        paginacion.append(`<button class="pagination-btn" onclick="cambiarPagina(${totalPaginas})">${totalPaginas}</button>`);
    }

    paginacion.append(`
        <button class="pagination-btn" onclick="cambiarPagina(${paginaActual + 1})" ${paginaActual === totalPaginas ? 'disabled' : ''}>
            <i class="bi bi-chevron-right"></i>
        </button>
    `);
}

// Cambiar página
function cambiarPagina(pagina) {
    if (pagina < 1 || pagina > Math.ceil(totalRegistros / registrosPorPagina)) return;
    paginaActual = pagina;
    cargarDatos();
}

// Buscar en opciones
function buscarEnOpciones(input) {
    const busqueda = input.value.toLowerCase();
    const opciones = $(input).siblings('.filter-options').find('.filter-option');
    opciones.each(function () {
        const texto = $(this).text().toLowerCase();
        $(this).toggle(texto.includes(busqueda));
    });
}

// Formatear fecha
function formatearFecha(fecha) {
    if (!fecha) return '-';
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const d = new Date(fecha);
    const año = String(d.getFullYear()).slice(-2);
    return `${String(d.getDate()).padStart(2, '0')}-${meses[d.getMonth()]}-${año}`;
}

// Formatear fecha larga para mostrar en el rango seleccionado
function formatearFechaLarga(fecha) {
    if (!fecha) return '-';
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const d = new Date(fecha);
    return `${String(d.getDate()).padStart(2, '0')} ${meses[d.getMonth()]} ${d.getFullYear()}`;
}
```

## AJAX: /public_html/modulos/marketing/ajax/cupones_get_opciones_filtro.php
```php
<?php
require_once '../../../core/database/conexion.php';
header('Content-Type: application/json');

try {
    $columna = isset($_POST['columna']) ? $_POST['columna'] : '';
    
    $opciones = [];
    
    if ($columna === 'aplicado') {
        $opciones = [
            ['valor' => '0', 'texto' => 'Disponible'],
            ['valor' => '1', 'texto' => 'Aplicado']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'opciones' => $opciones
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
```

## AJAX: /public_html/modulos/marketing/ajax/cupones_get_datos.php
```php
<?php
require_once '../../../core/database/conexion.php';
header('Content-Type: application/json');

try {
    $pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
    $registros_por_pagina = isset($_POST['registros_por_pagina']) ? (int)$_POST['registros_por_pagina'] : 25;
    $filtros = isset($_POST['filtros']) ? json_decode($_POST['filtros'], true) : [];
    $orden = isset($_POST['orden']) ? json_decode($_POST['orden'], true) : ['columna' => null, 'direccion' => 'asc'];
    
    $offset = ($pagina - 1) * $registros_por_pagina;
    
    // Construir WHERE
    $where = [];
    $params = [];
    
    // Filtro de texto (numero_cupon)
    if (isset($filtros['numero_cupon']) && $filtros['numero_cupon'] !== '') {
        $where[] = "c.numero_cupon LIKE :numero_cupon";
        $params[":numero_cupon"] = '%' . $filtros['numero_cupon'] . '%';
    }
    
    // Filtro de texto (observaciones)
    if (isset($filtros['observaciones']) && $filtros['observaciones'] !== '') {
        $where[] = "c.observaciones LIKE :observaciones";
        $params[":observaciones"] = '%' . $filtros['observaciones'] . '%';
    }
    
    // Filtro de texto (nombre_sucursal)
    if (isset($filtros['nombre_sucursal']) && $filtros['nombre_sucursal'] !== '') {
        $where[] = "s.nombre LIKE :nombre_sucursal";
        $params[":nombre_sucursal"] = '%' . $filtros['nombre_sucursal'] . '%';
    }
    
    // Filtro de texto (cod_pedido)
    if (isset($filtros['cod_pedido']) && $filtros['cod_pedido'] !== '') {
        $where[] = "c.cod_pedido LIKE :cod_pedido";
        $params[":cod_pedido"] = '%' . $filtros['cod_pedido'] . '%';
    }
    
    // Filtro numérico (monto)
    if (isset($filtros['monto']) && is_array($filtros['monto'])) {
        if (!empty($filtros['monto']['min'])) {
            $where[] = "c.monto >= :monto_min";
            $params[':monto_min'] = $filtros['monto']['min'];
        }
        if (!empty($filtros['monto']['max'])) {
            $where[] = "c.monto <= :monto_max";
            $params[':monto_max'] = $filtros['monto']['max'];
        }
    }
    
    // Filtro de lista (aplicado/estado)
    if (isset($filtros['aplicado']) && is_array($filtros['aplicado']) && count($filtros['aplicado']) > 0) {
        $placeholders = [];
        foreach ($filtros['aplicado'] as $idx => $valor) {
            $key = ":aplicado_$idx";
            $placeholders[] = $key;
            $params[$key] = $valor;
        }
        $where[] = "c.aplicado IN (" . implode(',', $placeholders) . ")";
    }
    
    // Filtro de rango de fechas de caducidad
    if (isset($filtros['fecha_caducidad']) && is_array($filtros['fecha_caducidad'])) {
        if (!empty($filtros['fecha_caducidad']['desde'])) {
            $where[] = "c.fecha_caducidad >= :fecha_caducidad_desde";
            $params[':fecha_caducidad_desde'] = $filtros['fecha_caducidad']['desde'];
        }
        if (!empty($filtros['fecha_caducidad']['hasta'])) {
            $where[] = "c.fecha_caducidad <= :fecha_caducidad_hasta";
            $params[':fecha_caducidad_hasta'] = $filtros['fecha_caducidad']['hasta'];
        }
    }
    
    // Filtro de rango de fechas de registro
    if (isset($filtros['fecha_registro']) && is_array($filtros['fecha_registro'])) {
        if (!empty($filtros['fecha_registro']['desde'])) {
            $where[] = "c.fecha_registro >= :fecha_registro_desde";
            $params[':fecha_registro_desde'] = $filtros['fecha_registro']['desde'];
        }
        if (!empty($filtros['fecha_registro']['hasta'])) {
            $where[] = "c.fecha_registro <= :fecha_registro_hasta";
            $params[':fecha_registro_hasta'] = $filtros['fecha_registro']['hasta'];
        }
    }
    
    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Construir ORDER BY
    $orderClause = '';
    if ($orden['columna']) {
        $columnas_validas = ['numero_cupon', 'monto', 'fecha_caducidad', 'fecha_registro', 'observaciones', 'aplicado', 'nombre_sucursal', 'cod_pedido'];
        if (in_array($orden['columna'], $columnas_validas)) {
            $direccion = strtoupper($orden['direccion']) === 'DESC' ? 'DESC' : 'ASC';
            
            // Mapear columnas virtuales a columnas reales
            $columna_real = $orden['columna'];
            if ($orden['columna'] === 'nombre_sucursal') {
                $columna_real = 's.nombre';
            } else {
                $columna_real = 'c.' . $orden['columna'];
            }
            
            $orderClause = "ORDER BY {$columna_real} $direccion";
        }
    } else {
        $orderClause = "ORDER BY c.fecha_registro DESC";
    }
    
    // Consulta de conteo
    $sqlCount = "SELECT COUNT(*) as total 
                 FROM cupones_sucursales c
                 LEFT JOIN sucursales s ON c.cod_sucursal = s.codigo
                 $whereClause";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRegistros = $stmtCount->fetch()['total'];
    
    // Consulta de datos con paginación
    $sql = "SELECT 
                c.id,
                c.numero_cupon,
                c.monto,
                c.fecha_caducidad,
                c.fecha_registro,
                c.observaciones,
                c.aplicado,
                c.cod_pedido,
                s.nombre as nombre_sucursal
            FROM cupones_sucursales c
            LEFT JOIN sucursales s ON c.cod_sucursal = s.codigo
            $whereClause
            $orderClause
            LIMIT :offset, :limit";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $registros_por_pagina, PDO::PARAM_INT);
    
    $stmt->execute();
    $datos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'datos' => $datos,
        'total_registros' => $totalRegistros
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
```

## AJAX: /public_html/modulos/marketing/ajax/cupones_get_cupon.php
```php
<?php
require_once '../../../core/database/conexion.php';
header('Content-Type: application/json');

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }
    
    $sql = "SELECT id, numero_cupon, monto, fecha_caducidad, observaciones, aplicado 
            FROM cupones_sucursales 
            WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $cupon = $stmt->fetch();
    
    if (!$cupon) {
        throw new Exception('Cupón no encontrado');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $cupon
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
```

## AJAX: /public_html/modulos/marketing/ajax/cupones_eliminar.php
```php
<?php
require_once '../../../core/database/conexion.php';
header('Content-Type: application/json');

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }
    
    // Verificar que el cupón existe y no está aplicado
    $sqlCheck = "SELECT aplicado FROM cupones_sucursales WHERE id = :id";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    $cupon = $stmtCheck->fetch();
    
    if (!$cupon) {
        throw new Exception('Cupón no encontrado');
    }
    
    if ($cupon['aplicado'] == 1) {
        throw new Exception('No se puede eliminar un cupón que ya ha sido aplicado');
    }
    
    // Eliminar cupón
    $sql = "DELETE FROM cupones_sucursales WHERE id = :id AND aplicado = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cupón eliminado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
```