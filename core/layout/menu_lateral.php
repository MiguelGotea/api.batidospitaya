<?php
/**
 * Menú Lateral Universal para Módulos ERP - Sistema de Permisos
 * Sidebar colapsable con acordeón vertical
 * Incluir este archivo en cada index: require_once '../../includes/menu_lateral.php';
 * Uso: renderMenuLateral($cargoOperario, 'index.php');
 */

// Configuración global del menú basado en permisos por cargo
$menuGlobal = [
    [
        'nombre' => 'Inicio',
        'icon' => 'fas fa-home',
        'cargos_permitidos' => [],
        'url' => 'index.php', // Añade esta línea
        'items' => [] // Vacía el array de items
    ],
    [
        'nombre' => 'Comunicación Interna',
        'icon' => 'fas fa-comments',
        'cargos_permitidos' => [],
        'items' => [
            [
                'nombre' => 'Avisos Internos', 
                'url' => 'supervision/auditorias_original/index_avisos_publico.php',
                'cargos_permitidos' => []
            ],
            [ //exclusivo para atencion cliente y auxilir 
                'nombre' => 'Avisos Sucursales', 
                'url' => 'marketing/auditorias_original/index_avisos_publico.php',
                'cargos_permitidos' => [49, 13,  22, 26, 28, 42, 36]
            ],
            [
                'nombre' => 'Auditorías', 
                'url' => 'supervision/auditorias_original/index_auditorias_publico.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Promedios', 
                'url' => 'supervision/auditorias_original/promedio.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Reclamos', 
                'url' => 'supervision/auditorias_original/index_reclamos_publico.php',
                'cargos_permitidos' => []
            ],

            [
                'nombre' => 'Registrar KPIs', 
                'url' => 'supervision/auditorias_original/kpi.php',
                'cargos_permitidos' => [49, 11]
            ],
            [
                'nombre' => 'Nuevo Aviso', 
                'url' => 'supervision/auditorias_original/agregarAviso.php',
                'cargos_permitidos' => [49, 11, 13, 39, 30, 37, 42, 26]
            ],
            [
                'nombre' => 'Editar Avisos', 
                'url' => 'supervision/auditorias_original/index_avisos.php',
                'cargos_permitidos' => [49, 11, 13, 39, 30, 37, 42, 26]
            ]
        ]
    ],
    [
        'nombre' => 'Recursos Humanos',
        'icon' => 'fas fa-users',
        'cargos_permitidos' => [49, 11, 5, 43, 21, 42, 36, 13, 28, 30, 37, 39, 27, 8, 12],
        'items' => [
            [
                'nombre' => 'Marcacion', 
                'url' => '../../marcacion.php',
                'cargos_permitidos' => [49, 27, 16]
            ],
            [
                'nombre' => 'Historial Marcaciones', 
                'url' => 'sucursales/historial_marcaciones_sucursales.php',
                'cargos_permitidos' => [49, 27, 16]
            ],
            [
                'nombre' => 'Tardanzas', 
                'url' => 'operaciones/tardanzas_manual.php',
                'cargos_permitidos' => [49, 5, 43, 16, 21, 13, 28, 30, 37, 39, 8]
            ],
            [
                'nombre' => 'Faltas/Ausencias', 
                'url' => 'lideres/faltas_manual.php',
                'cargos_permitidos' => [49, 5, 43, 13, 28, 30, 37, 39, 8]
            ],
            [
                'nombre' => 'Viaticos', 
                'url' => 'operaciones/viaticos.php',
                'cargos_permitidos' => [49, 16, 8]
            ],
            [
                'nombre' => 'Vacaciones', 
                'url' => 'lideres/vacaciones.php',
                'cargos_permitidos' => [49, 13, 16, 39, 30, 37, 28]
            ],
            [
                'nombre' => 'Horas Extras', 
                'url' => 'operaciones/horas_extras_manual.php',
                'cargos_permitidos' => [49, 11, 16, 8, 13]
            ],
            [
                'nombre' => 'Feriados', 
                'url' => 'operaciones/feriados.php',
                'cargos_permitidos' => [49, 11, 16, 8, 13]
            ],
            [
                'nombre' => 'Reportes de Personal', 
                'url' => 'rrhh/reportes.php',
                'cargos_permitidos' => [49, 16, 21]
            ],
            [
                'nombre' => 'Generar Horarios', 
                'url' => 'lideres/programar_horarios_lider2.php',
                'cargos_permitidos' => [49, 5, 43]
            ],
            [
                'nombre' => 'Horarios Programados', 
                'url' => 'supervision/ver_horarios_compactos.php',
                'cargos_permitidos' => [49, 16, 11, 5, 43, 21, 42, 36, 13, 28, 30, 37, 39, 27, 8]
            ],
            [
                'nombre' => 'Marcaciones', 
                'url' => 'rh/ver_marcaciones_todas.php',
                'cargos_permitidos' => [49, 13, 5, 43, 8, 11, 21, 22, 36, 13, 28, 30, 37, 39, 8, 12]
            ],
            [
                'nombre' => 'Gestion Sucursales', 
                'url' => 'operaciones/gestion_colaboradores.php',
                'cargos_permitidos' => [49, 16, 36]
            ],
            [
                'nombre' => 'Gestion de Feriados', 
                'url' => 'rh/editar_feriados.php',
                'cargos_permitidos' => [49, 16, 13]
            ],
            [
                'nombre' => 'Plan Anual Feriados', 
                'url' => 'rh/plan_feriados_anual.php',
                'cargos_permitidos' => []
            ],
        ]
    ],
    [
        'nombre' => 'Talento Humano',
        'icon' => 'fas fa-users',
        'cargos_permitidos' => [49, 11, 5, 43, 21, 42, 36, 13, 28, 30, 37, 39, 27, 8, 12],
        'items' => [
           [
                'nombre' => 'Colaboradores', 
                'url' => 'rh/colaboradores.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
            [
                'nombre' => 'Nuevo Colaborador', 
                'url' => 'rh/nuevo_colaborador.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
            [
                'nombre' => 'Agenda Colaboradores', 
                'url' => 'rh/contactos_colaboradores.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
            [
                'nombre' => 'Cumpleaños Colaboradores', 
                'url' => 'rh/cumpleanos_colaboradores.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
        ]
    ],
    [
        'nombre' => 'Supervisión',
        'icon' => 'fas fa-eye',
        'cargos_permitidos' => [49, 11, 21, 8, 13],
        'items' => [
            [
                'nombre' => 'Auditorías de Efectivo', 
                'url' => 'supervision/auditorias_original/auditinternas/auditorias_consolidadas.php',
                'cargos_permitidos' => [49, 11, 21, 16]
            ],
            [   
                'nombre' => 'Deducciones', 
                'url' => 'supervision/auditorias_original/auditinternas/deducciones_total.php',
                'cargos_permitidos' => [49, 8, 11, 16, 13]
            ],
            [
                'nombre' => 'Control de Inventario', 
                'url' => 'supervision/inventario.php',
                'cargos_permitidos' => [49, 21, 16]
            ],
            [
                'nombre' => 'Faltantes de Caja', 
                'url' => 'supervision/auditorias_original/auditinternas/faltante_caja.php',
                'cargos_permitidos' => [49, 8, 16]
            ],
        ]
    ],
    
    [
        'nombre' => 'Atencion al Cliente',
        'icon' => 'fas fa-headset',
        'cargos_permitidos' => [49, 11, 21, 28, 50],
        'items' => [
            [
                'nombre' => 'Nuevo Reclamo', 
                'url' => 'supervision/auditorias_original/nuevoreclamo.php',
                'cargos_permitidos' => [49, 16, 21, 28, 50]
            ],
            [
                'nombre' => 'Procesar Reclamos', 
                'url' => 'supervision/auditorias_original/reclamospend.php',
                'cargos_permitidos' => [49, 16, 11]
            ],
            [
                'nombre' => 'Reseñas Google', 
                'url' => 'atencioncliente/resenas_google.php',
                'cargos_permitidos' => [49, 16, 21, 28, 50]
            ],
        ]
    ],
    [
        'nombre' => 'Club Pitaya',
        'icon' => 'fas fa-star"',
        'cargos_permitidos' => [49, 16, 22, 28, 27, 42, 26, 50],
        'items' => [
            [
                'nombre' => 'Cumpleaños', 
                'url' => 'atencioncliente/cumpleanos_clientes.php',
                'cargos_permitidos' => [49, 22, 28, 50]
            ],
            [
                'nombre' => 'Clientes Club', 
                'url' => 'atencioncliente/historial_clientes.php',
                'cargos_permitidos' => [49, 16, 22, 28, 27, 42, 26]
            ]
        ]
    ],
    [
        'nombre' => 'Compras',
        'icon' => 'fas fa-shopping-cart"',
        'cargos_permitidos' => [9, 15, 16, 49],
        'items' => [
            [
                'nombre' => 'Solicitudes Cotización', 
                'url' => 'compras/historial_solicitudes_cotizacion.php',
                'cargos_permitidos' => [9, 15, 16, 49]
            ],
            [
                'nombre' => 'Nueva Solicitud de Cotización', 
                'url' => 'compras/solicitud_cotizacion.php',
                'cargos_permitidos' => [9, 15, 16, 49]
            ],
        ]
    ],
    [
        'nombre' => 'Mantenimiento',
        'icon' => 'fas fa-tools',
        'cargos_permitidos' => [49, 11, 14, 21, 5, 43, 35, 12, 26, 42],
        'items' => [
            [
                'nombre' => 'Solicitudes', 
                'url' => 'mantenimiento/historial_solicitudes.php',
                'cargos_permitidos' => [49, 11, 16, 5, 43, 35, 14, 12, 26, 42]
            ],
            [
                'nombre' => 'Agenda Diaria', 
                'url' => 'mantenimiento/agenda_colaborador.php',
                'cargos_permitidos' => [49, 14, 16, 35]
            ],
            [
                'nombre' => 'Calendario', 
                'url' => 'mantenimiento/programacion_solicitudes.php',
                'cargos_permitidos' => [49, 21, 16, 35]
            ],
            [
                'nombre' => 'Mantenimiento', 
                'url' => 'mantenimiento/formulario_mantenimiento.php',
                'cargos_permitidos' => [49, 5, 43, 35, 16, 12]
            ],
            [
                'nombre' => 'Equipo', 
                'url' => 'mantenimiento/formulario_equipos.php',
                'cargos_permitidos' => [49, 5, 43, 16, 35, 12]
            ],
            [
                'nombre' => 'Planificacion', 
                'url' => 'mantenimiento/planificacion_mantenimiento.php',
                'cargos_permitidos' => [49, 35]
            ]
        ]

    ],
    [
        'nombre' => 'Activos',
        'icon' => 'fas fa-clipboard-list',
        'cargos_permitidos' => [49, 14, 35],
        'items' => [
            [
                'nombre' => 'Historial Equipos', 
                'url' => 'mantenimiento/equipos_lista.php',
                'cargos_permitidos' => [49, 16, 14, 35]
            ],
            [
                'nombre' => 'Nuevo Equipo', 
                'url' => 'mantenimiento/equipos_registro.php',
                'cargos_permitidos' => [49, 16, 14, 35]
            ],
            [
                'nombre' => 'Movimiento de Activos', 
                'url' => 'mantenimiento/equipos_movimientos.php',
                'cargos_permitidos' => [49, 16, 35]
            ],
            [
                'nombre' => 'Reporte Mantenimiento de Activos', 
                'url' => 'mantenimiento/equipos_reporte_mantenimiento.php',
                'cargos_permitidos' => [49, 16, 35]
            ],
        ]
    ],
    [
        'nombre' => 'Sistemas',
        'icon' => 'fas fa-laptop-code',
        'cargos_permitidos' => [49, 15],
        'items' => [
            [
                'nombre' => 'Permisos', 
                'url' => 'sistemas/gestion_permisos.php',
                'cargos_permitidos' => [49, 15]
            ],
        ]
    ],
    [
        'nombre' => 'Despacho',
        'icon' => 'fas fa-laptop-code',
        'cargos_permitidos' => [49, 12, 27],
        'items' => [
            [
                'nombre' => 'Compras Localea Configuracion', 
                'url' => 'cds/compra_local_configuracion_despacho.php',
                'cargos_permitidos' => [49, 12]
            ],
            [
                'nombre' => 'Pedido Central', 
                'url' => 'sucursales/compra_local_registro_pedidos.php',
                'cargos_permitidos' => [49, 27]
            ],
            [
                'nombre' => 'Compras Localea Consolidado', 
                'url' => 'cds/compra_local_consolidado_pedidos.php',
                'cargos_permitidos' => [49, 12]
            ],
        ]
    ],
    [
        'nombre' => 'Ventas',
        'icon' => 'fas fa-shopping-cart',
        'cargos_permitidos' => [49, 16, 27, 42, 26],
        'items' => [
            [
                'nombre' => 'Gestión de Ferias', 
                'url' => 'sucursales/ferias/index.php',
                'cargos_permitidos' => [49, 27, 26]
            ],
            [
                'nombre' => 'Cupones', 
                'url' => 'marketing/cupones.php',
                'cargos_permitidos' => [49, 16, 42, 26]
            ],
            [
                'nombre' => 'Historial Ventas', 
                'url' => 'ventas/historial_ventas.php',
                'cargos_permitidos' => [49, 16, 42, 26, 42]
            ],
            [
                'nombre' => 'Graficos con IA', 
                'url' => 'gerencia/ia_graficos_ventas.php',
                'cargos_permitidos' => [49]
            ],
            [
                'nombre' => 'Ventas Meta', 
                'url' => 'ventas/ventas_meta.php',
                'cargos_permitidos' => [49, 16]
            ],
            [
                'nombre' => 'Sorteos', 
                'url' => 'marketing/gestion_sorteos.php',
                'cargos_permitidos' => [49, 42]
            ],
        ]
    ],
    [
        'nombre' => 'KPIs',
        'icon' => 'fas fa-chart-line',
        'cargos_permitidos' => [5, 11, 12, 13, 16, 26, 27, 42, 43, 49],
        'items' => [
            [
                'nombre' => 'Desempeño de tiendas', 
                'url' => 'sucursales/kpi_sucursales.php',
                'cargos_permitidos' => [5, 11, 26, 27, 42, 43, 49]
            ],
            [
                'nombre' => 'Liderazgo', 
                'url' => 'gerencia/indicadores_resultado.php',
                'cargos_permitidos' => [49, 11, 12, 13, 42, 16]
            ],
            [
                'nombre' => 'Ventas', 
                'url' => 'gerencia/kpi_reportes_ventas.php',
                'cargos_permitidos' => [49, 11, 26, 42, 16]
            ],
            [
                'nombre' => 'Edición de Indicadores', 
                'url' => 'gerencia/indicadores_edicion.php',
                'cargos_permitidos' => [49, 11, 12, 13, 42, 16]
            ],
            [
                'nombre' => 'Tareas', 
                'url' => 'gerencia/gestion_proyectos.php',
                'cargos_permitidos' => [49, 11, 12, 13, 42, 16]
            ]
        ]
    ],
    [
        'nombre' => 'POS',
        'icon' => 'fas fa-tag',
        'cargos_permitidos' => [15, 49],
        'items' => [
            [
                'nombre' => 'Unidades', 
                'url' => 'POS/historial_unidades_conversion.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Presentacion', 
                'url' => 'POS/producto_presentacion_gestion.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Producto Maestro', 
                'url' => 'POS/productos_maestro_gestion.php',
                'cargos_permitidos' => []
            ],
        ]
    ],
    [
        'nombre' => 'Cerrar Sesion',
        'icon' => 'fas fa-sign-out-alt',
        'cargos_permitidos' => [],
        'url' => 'logout.php', // Añade esta línea
        'items' => [] // Vacía el array de items
    ],
];

/**
 * Detecta la ruta base automáticamente basado en la estructura de módulos
 */
function detectarRutaBase() {
    // Obtener la ruta del script actual
    $scriptActual = $_SERVER['SCRIPT_FILENAME'];
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    // Convertir a ruta relativa desde el document root
    $rutaRelativa = str_replace($documentRoot, '', $scriptActual);
    
    // Buscar la posición de '/modulos/' en la ruta
    $posModulos = strpos($rutaRelativa, '/modulos/');
    
    if ($posModulos !== false) {
        // Extraer la parte de la ruta hasta /modulos/
        $rutaHastaModulos = substr($rutaRelativa, 0, $posModulos + 9); // +9 para incluir '/modulos/'
        
        // Contar cuántos directorios hay después de /modulos/
        $rutaDespuesModulos = substr($rutaRelativa, $posModulos + 9);
        $nivelesProfundidad = substr_count($rutaDespuesModulos, '/');
        
        // Generar la ruta base (../../ etc.)
        if ($nivelesProfundidad === 0) {
            return './';
        } else {
            return str_repeat('../', $nivelesProfundidad);
        }
    }
    
    // Si no se encuentra /modulos/, asumir que estamos en la raíz
    return './';
}

/**
 * Genera la URL correcta para cualquier archivo en la estructura de módulos
 */
function generarUrlModulo($rutaDestino) {
    $rutaBase = detectarRutaBase();
    
    // Si el destino es solo "index.php", apuntar al index del módulo actual
    if ($rutaDestino === 'index.php') {
        return $rutaBase . 'index.php';
    }
    
    // Caso especial para logout.php - usar ruta absoluta desde la raíz del dominio
    if ($rutaDestino === 'logout.php') {
        return '/logout.php';
    }
    
    // Para otras rutas, construir la ruta completa
    return $rutaBase . $rutaDestino;
}

/**
 * Detecta el módulo actual basado en la ruta
 */
function detectarModuloActual() {
    $scriptActual = $_SERVER['SCRIPT_FILENAME'];
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    $rutaRelativa = str_replace($documentRoot, '', $scriptActual);
    $posModulos = strpos($rutaRelativa, '/modulos/');
    
    if ($posModulos !== false) {
        $rutaDespuesModulos = substr($rutaRelativa, $posModulos + 9); // +9 para saltar '/modulos/'
        $partes = explode('/', $rutaDespuesModulos);
        return $partes[0] ?? 'desconocido';
    }
    
    return 'raiz';
}


/**
 * Función para verificar si un cargo tiene acceso a un elemento
 */
function tieneAcceso($cargoOperario, $cargosPermitidos) {
    if (empty($cargosPermitidos)) {
        return true;
    }
    return in_array($cargoOperario, $cargosPermitidos);
}

/**
 * Función para filtrar el menú según los permisos del cargo
 */
function filtrarMenuPorPermisos($menu, $cargoOperario) {
    $menuFiltrado = [];
    
    foreach ($menu as $grupo) {
        if (tieneAcceso($cargoOperario, $grupo['cargos_permitidos'])) {
            $grupoFiltrado = $grupo;
            
            // Si el grupo tiene items, filtrarlos
            if (!empty($grupo['items'])) {
                $itemsFiltrados = [];
                
                foreach ($grupo['items'] as $item) {
                    if (tieneAcceso($cargoOperario, $item['cargos_permitidos'])) {
                        $itemsFiltrados[] = $item;
                    }
                }
                
                // Solo incluir el grupo si tiene items filtrados o si tiene URL directa
                if (!empty($itemsFiltrados)) {
                    $grupoFiltrado['items'] = $itemsFiltrados;
                    $menuFiltrado[] = $grupoFiltrado;
                }
            } else {
                // Grupo sin items pero con URL directa (como Inicio)
                $menuFiltrado[] = $grupoFiltrado;
            }
        }
    }
    
    return $menuFiltrado;
}

/**
 * Función principal para renderizar el menú lateral
 * @param int $cargoOperario - Código del cargo del usuario
 * @return string HTML del menú lateral
 */
function renderMenuLateral($cargoOperario) {
    global $menuGlobal;
    
    if (!$cargoOperario) {
        return '';
    }
    
    // Detectar automáticamente la página actual
    $paginaActual = basename($_SERVER['SCRIPT_NAME']);
    
    $menuFiltrado = filtrarMenuPorPermisos($menuGlobal, $cargoOperario);
    
    if (empty($menuFiltrado)) {
        return '';
    }
    
    // Detectar módulo actual para el Dashboard
    $moduloActual = detectarModuloActual();
    
    ob_start();
    ?>
 
     <!-- Font Awesome Universal -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- CSS COMPLETO del Menú Lateral -->
    <style>
        /* ==================== SIDEBAR BASE ==================== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 70px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar:hover {
            width: 260px;
        }
        
        /* ==================== HEADER ==================== */
        .sidebar-header {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid #e0e0e0;
            padding: 0 15px;
            overflow: hidden;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .sidebar-header .logo {
            height: 40px;
            width: auto;
            opacity: 1;
            transition: all 0.3s ease 0.15s;
        }
        
        /* ==================== GRUPOS ==================== */
        .menu-group {
            position: relative;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .menu-group-title {
            height: 60px;
            padding: 0;
            color: #0E544C;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: white;
        }
        
        .menu-group-title:hover {
            background: #f8f9fa;
        }
        
        .menu-group-title.active {
            background: #e8f5f3;
            border-right: 4px solid #51B8AC;
        }
        
        .menu-icon-wrapper {
            width: 70px;
            min-width: 70px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem !important;
            color: #51B8AC;
            transition: transform 0.3s ease;
        }
        
        .menu-group-title:hover .menu-icon-wrapper {
            transform: scale(1.1);
        }
        
        .menu-group-title.active .menu-icon-wrapper {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        
        .menu-group-name {
            white-space: nowrap;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease 0.1s;
            font-weight: 600;
            font-size: 0.95rem !important;
            flex: 1;
            text-align: left; /* Alinea el texto a la izquierda */
        }
        
        .sidebar:hover .menu-group-name {
            opacity: 1;
            transform: translateX(0);
        }
        
        .chevron-icon {
            margin-right: 15px;
            opacity: 0;
            transition: all 0.3s ease 0.1s;
            font-size: 0.8rem !important;
            color: #666;
        }
        
        .sidebar:hover .chevron-icon {
            opacity: 1;
        }
        
        .menu-group.active .chevron-icon {
            transform: rotate(90deg);
        }
        
        /* ==================== SUBGRUPOS (ACORDEÓN) ==================== */
        .menu-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fafafa;
        }
        
        /* Solo mostrar subgrupos cuando el sidebar está expandido Y el grupo está activo */
        .sidebar:hover .menu-group.active .menu-items {
            max-height: 600px;
        }
        
        .menu-item {
            padding: 12px 20px 12px 70px;
            color: #666;
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
            font-size: 0.9rem !important;
            border-left: 3px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left; /* Alinea el texto a la izquierda */
        }
        
        .sidebar:hover .menu-item {
            padding-left: 80px;
        }
        
        .menu-item:hover {
            background: #f0f0f0;
            color: #51B8AC;
            border-left-color: #51B8AC;
            padding-left: 85px;
        }
        
        .menu-item.active {
            background: #e8f5f3;
            color: #0E544C;
            border-left-color: #51B8AC;
            font-weight: 600;
        }
        
        /* ==================== TOOLTIP ==================== */
        .menu-group-title::before {
            content: attr(data-tooltip);
            position: absolute;
            left: 80px;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem !important;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 1001;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .menu-group-title::after {
            content: '';
            position: absolute;
            left: 70px;
            border: 5px solid transparent;
            border-right-color: #333;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .sidebar:not(:hover) .menu-group-title:hover::before,
        .sidebar:not(:hover) .menu-group-title:hover::after {
            opacity: 0.95;
            visibility: visible;
        }
        
        .sidebar:hover .menu-group-title::before,
        .sidebar:hover .menu-group-title::after {
            display: none;
        }
        
        /* ==================== BOTÓN TOGGLE MÓVIL ==================== */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1002;
            background: #51B8AC;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: #0E544C;
            transform: scale(1.05);
        }
        
        .menu-toggle:active {
            transform: scale(0.95);
        }
        
        .menu-toggle i {
            font-size: 1.2rem !important;
        }
        
        /* ==================== OVERLAY MÓVIL ==================== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* ==================== CONTENEDOR PRINCIPAL ==================== */
        .main-container {
            margin-left: 70px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }
        
        .contenedor-principal {
            width: 100%;
            margin: 0 auto;
            padding: 20px; /* Cambiar de 0 1px a 20px */
        }
        
        /* ==================== SCROLLBAR PERSONALIZADA ==================== */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #51B8AC;
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #0E544C;
        }
        
        /* Añade este CSS para los enlaces directos */
        .menu-group-title.direct-link {
            text-decoration: none;
            cursor: pointer;
        }
        
        .menu-group-title.direct-link:hover {
            background: #f8f9fa;
        }
        
        .menu-group-title.direct-link.active {
            background: #e8f5f3;
            border-right: 4px solid #51B8AC;
        }
        
        /* ==================== RESPONSIVE - MÓVIL ==================== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-70px);
                width: 70px;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                            width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .sidebar.show {
                transform: translateX(0);
                width: 260px;
            }
            
            /* Forzar expansión en móvil cuando está abierto */
            .sidebar.show .sidebar-header .logo {
                opacity: 1;
                transform: translateX(0);
            }
            
            .sidebar.show .menu-group-name {
                opacity: 1;
                transform: translateX(0);
            }
            
            .sidebar.show .chevron-icon {
                opacity: 1;
            }
            
            .sidebar.show .menu-item {
                padding-left: 80px;
            }
            
            /* Deshabilitar hover en móvil */
            .sidebar:hover {
                width: 70px;
            }
            
            .sidebar.show:hover {
                width: 260px;
            }
            
            /* Tooltips deshabilitados en móvil */
            .menu-group-title::before,
            .menu-group-title::after {
                display: none !important;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .main-container {
                margin-left: 0;
            }
            
        }
        
        /* ==================== ANIMACIONES ADICIONALES ==================== */
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .menu-item {
            animation: slideInFromLeft 0.3s ease-out;
        }
        
        /* ==================== ESTADOS DE CARGA ==================== */
        .sidebar.loading {
            pointer-events: none;
            opacity: 0.6;
        }
        
        /* ==================== MEJORAS VISUALES ==================== */
        .menu-group:last-child {
            border-bottom: none;
        }
        
        .menu-items:empty {
            display: none;
        }
        
        /* Efecto de resaltado al hacer click */
        .menu-item:active {
            background: #daf3f0;
            transform: scale(0.98);
        }
        
        /* ==================== ACCESIBILIDAD ==================== */
        .menu-group-title:focus,
        .menu-item:focus {
            outline: 2px solid #51B8AC;
            outline-offset: -2px;
        }
        
        /* ==================== SOPORTE PARA NAVEGADORES ==================== */
        @supports not (backdrop-filter: blur(10px)) {
            .sidebar-overlay {
                background: rgba(0,0,0,0.7);
            }
        }
    </style>
    
    <!-- Toggle del menú (móvil) -->
    <button class="menu-toggle" onclick="toggleSidebarMobile()" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para cerrar menú en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebarMobile()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo generarUrlModulo('../../assets/img/icon12.png'); ?>" alt="Batidos Pitaya" class="logo">
        </div>
        
        <?php foreach ($menuFiltrado as $index => $grupo): ?>
            <div class="menu-group" id="grupo-<?php echo $index; ?>">
                <?php if (!empty($grupo['items'])): ?>
                    <!-- Grupo con subitems (acordeón) -->
                    <div class="menu-group-title" 
                         onclick="toggleMenuGroup(<?php echo $index; ?>)"
                         data-tooltip="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                         role="button"
                         aria-expanded="false"
                         aria-controls="items-<?php echo $index; ?>">
                        <div class="menu-icon-wrapper">
                            <i class="<?php echo $grupo['icon']; ?>"></i>
                        </div>
                        <span class="menu-group-name"><?php echo htmlspecialchars($grupo['nombre']); ?></span>
                        <i class="fas fa-chevron-right chevron-icon"></i>
                    </div>
                    <div class="menu-items" id="items-<?php echo $index; ?>">
                        <?php foreach ($grupo['items'] as $item): ?>
                            <?php 
                            $isActive = '';

                            $urlFile = basename($item['url']);
                            if ($urlFile === $paginaActual) {
                                $isActive = 'active';
                            }

                            ?>
                            <a href="<?php echo htmlspecialchars(generarUrlModulo($item['url'])); ?>" 
                               class="menu-item <?php echo $isActive; ?>"
                               title="<?php echo htmlspecialchars($item['nombre']); ?>">
                                <?php echo htmlspecialchars($item['nombre']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Grupo sin subitems (enlace directo) -->
                    <?php 
                    $isActiveInicio = '';
                    if ($grupo['url'] && basename($grupo['url']) === $paginaActual) {
                        $isActiveInicio = 'active';
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars(generarUrlModulo($grupo['url'])); ?>" 
                       class="menu-group-title direct-link <?php echo $isActiveInicio; ?>"
                       data-tooltip="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                       title="<?php echo htmlspecialchars($grupo['nombre']); ?>">
                        <div class="menu-icon-wrapper">
                            <i class="<?php echo $grupo['icon']; ?>"></i>
                        </div>
                        <span class="menu-group-name"><?php echo htmlspecialchars($grupo['nombre']); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- JavaScript del menú -->
    <script>
        (function() {
            'use strict';
            
            let activeGroupIndex = null;
            
            // Función para toggle de grupo (acordeón)
            window.toggleMenuGroup = function(index) {
                const grupo = document.getElementById('grupo-' + index);
                const allGroups = document.querySelectorAll('.menu-group');
                const titulo = grupo.querySelector('.menu-group-title');
                
                // Cerrar otros grupos
                allGroups.forEach((g, i) => {
                    if (i !== index) {
                        g.classList.remove('active');
                        const t = g.querySelector('.menu-group-title');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Toggle del grupo actual
                const isActive = grupo.classList.toggle('active');
                titulo.setAttribute('aria-expanded', isActive);
                activeGroupIndex = isActive ? index : null;
            };
            
            // Función para abrir sidebar en móvil
            window.toggleSidebarMobile = function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                
                // Actualizar aria-label
                const toggle = document.querySelector('.menu-toggle');
                if (sidebar.classList.contains('show')) {
                    toggle.setAttribute('aria-label', 'Cerrar menú');
                } else {
                    toggle.setAttribute('aria-label', 'Abrir menú');
                }
            };
            
            // Función para cerrar sidebar en móvil
            window.closeSidebarMobile = function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                
                // Restaurar aria-label
                const toggle = document.querySelector('.menu-toggle');
                toggle.setAttribute('aria-label', 'Abrir menú');
                
                // Cerrar todos los grupos
                document.querySelectorAll('.menu-group').forEach(g => {
                    g.classList.remove('active');
                    const t = g.querySelector('.menu-group-title');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
                activeGroupIndex = null;
            };
            
            // Cerrar menú en móvil al hacer clic en un enlace
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebarMobile();
                    }
                });
            });
            
            // Marcar grupo activo si hay una página activa
            document.addEventListener('DOMContentLoaded', function() {
                const activeItem = document.querySelector('.menu-item.active');
                if (activeItem) {
                    const parentGroup = activeItem.closest('.menu-group');
                    if (parentGroup) {
                        parentGroup.classList.add('active');
                        const titulo = parentGroup.querySelector('.menu-group-title');
                        if (titulo) {
                            titulo.classList.add('active');
                            titulo.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
                
                // Marcar "Inicio" como activo si estamos en index.php
                const currentPage = window.location.pathname.split('/').pop();
                if (currentPage === 'index.php') {
                    const inicioLinks = document.querySelectorAll('.menu-group-title.direct-link');
                    inicioLinks.forEach(link => {
                        if (link.getAttribute('href') && link.getAttribute('href').includes('index.php')) {
                            link.classList.add('active');
                            link.closest('.menu-group').classList.add('active');
                        }
                    });
                }
            });
            
            // Prevenir scroll del body cuando el menú está abierto en móvil
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (sidebar.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    }
                });
            });
            
            observer.observe(sidebar, { attributes: true });
            
            // Soporte para teclado (accesibilidad)
            document.addEventListener('keydown', function(e) {
                // ESC para cerrar menú en móvil
                if (e.key === 'Escape' && window.innerWidth <= 768) {
                    closeSidebarMobile();
                }
            });
            
        })();
    </script>
    
    <?php
    return ob_get_clean();
}

