<?php
/**
 * Menú Lateral Universal para Módulos ERP - Sistema de Permisos Dinámico V2
 * Sidebar colapsable con acordeón vertical
 * Incluir este archivo en cada index: require_once '../../includes/menu_lateral_v2.php';
 * Uso: renderMenuLateral($cargoOperario);
 */

function detectarRutaBase() {
    $scriptActual = $_SERVER['SCRIPT_FILENAME'];
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];

    $rutaRelativa = str_replace($documentRoot, '', $scriptActual);
    $posModulos = strpos($rutaRelativa, '/modulos/');

    if ($posModulos !== false) {
        $rutaHastaModulos = substr($rutaRelativa, 0, $posModulos + 9);
        $rutaDespuesModulos = substr($rutaRelativa, $posModulos + 9);
        $nivelesProfundidad = substr_count($rutaDespuesModulos, '/');

        if ($nivelesProfundidad === 0) {
            return './';
        } else {
            return str_repeat('../', $nivelesProfundidad);
        }
    }
    return './';
}

function generarUrlModulo($rutaDestino) {
    $rutaBase = detectarRutaBase();

    if ($rutaDestino === 'index.php') {
        return $rutaBase . 'index.php';
    }

    if ($rutaDestino === 'logout.php') {
        return '/logout.php';
    }

    return $rutaBase . $rutaDestino;
}

function detectarModuloActual() {
    $scriptActual = $_SERVER['SCRIPT_FILENAME'];
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];

    $rutaRelativa = str_replace($documentRoot, '', $scriptActual);
    $posModulos = strpos($rutaRelativa, '/modulos/');

    if ($posModulos !== false) {
        $rutaDespuesModulos = substr($rutaRelativa, $posModulos + 9);
        $partes = explode('/', $rutaDespuesModulos);
        return $partes[0] ?? 'desconocido';
    }

    return 'raiz';
}

function renderMenuLateral($cargoOperario) {
    global $conn;

    if (!$cargoOperario) {
        return '';
    }

    // Incluir la conexión a la base de datos de manera segura
    require_once __DIR__ . '/../database/conexion.php';

    $paginaActual = basename($_SERVER['SCRIPT_NAME']);
    $moduloActual = detectarModuloActual();

    $menuFiltrado = [];

    // Opciones estáticas siempre visibles
    $menuFiltrado[] = [
        'nombre' => 'Inicio',
        'icon' => 'fas fa-home',
        'url' => 'index.php',
        'items' => []
    ];

    try {
        // Consulta para traer los menús a los que el usuario tiene acceso
        $query = "SELECT 
                    t.id, 
                    t.nombre, 
                    t.grupo, 
                    t.url_real, 
                    t.icono, 
                    t.orden
                  FROM tools_erp t
                  INNER JOIN acciones_tools_erp a ON t.id = a.tool_erp_id
                  INNER JOIN permisos_tools_erp p ON a.id = p.accion_tool_erp_id
                  WHERE t.activo = 1
                    AND a.nombre_accion = 'vista'
                    AND p.CodNivelesCargos = :cargoOperario
                    AND p.permiso = 'allow'
                  ORDER BY t.grupo ASC, t.orden ASC";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':cargoOperario', $cargoOperario, PDO::PARAM_INT);
        $stmt->execute();
        $herramientas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $gruposAgrupados = [];

        foreach ($herramientas as $herramienta) {
            $nombreGrupo = $herramienta['grupo'];
            
            if (!isset($gruposAgrupados[$nombreGrupo])) {
                $gruposAgrupados[$nombreGrupo] = [
                    'nombre' => $nombreGrupo,
                    'icon' => 'fas fa-folder', // Icono genérico
                    'items' => []
                ];
            }
            
            $gruposAgrupados[$nombreGrupo]['items'][] = [
                'nombre' => $herramienta['nombre'],
                'url' => $herramienta['url_real'],
                'icon' => $herramienta['icono']
            ];
            
            // Usar el icono del primer elemento del grupo como icono del grupo si existe
            if (count($gruposAgrupados[$nombreGrupo]['items']) === 1 && !empty($herramienta['icono'])) {
                $gruposAgrupados[$nombreGrupo]['icon'] = $herramienta['icono'];
            }
        }

        foreach ($gruposAgrupados as $grupo) {
            $menuFiltrado[] = $grupo;
        }

    } catch (PDOException $e) {
        error_log("Error al cargar menú lateral dinámico: " . $e->getMessage());
    }

    $menuFiltrado[] = [
        'nombre' => 'Cerrar Sesion',
        'icon' => 'fas fa-sign-out-alt',
        'url' => 'logout.php',
        'items' => []
    ];

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
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
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

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.08);
            }
        }

        .menu-group-name {
            white-space: nowrap;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease 0.1s;
            font-weight: 600;
            font-size: 0.95rem !important;
            flex: 1;
            text-align: left;
            /* Alinea el texto a la izquierda */
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
            text-align: left;
            /* Alinea el texto a la izquierda */
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
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
        /* Oculto: el control del menú móvil ahora está integrado en el header universal */
        .menu-toggle {
            display: none !important;
        }

        /* ==================== OVERLAY MÓVIL ==================== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
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
            padding: 20px;
            /* Cambiar de 0 1px a 20px */
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
                background: rgba(0, 0, 0, 0.7);
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
                    <div class="menu-group-title" onclick="toggleMenuGroup(<?php echo $index; ?>)"
                        data-tooltip="<?php echo htmlspecialchars($grupo['nombre']); ?>" role="button" aria-expanded="false"
                        aria-controls="items-<?php echo $index; ?>">
                        <div class="menu-icon-wrapper">
                            <i class="<?php echo $grupo['icon']; ?>"></i>
                        </div>
                        <span class="menu-group-name">
                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                        </span>
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
                                class="menu-item <?php echo $isActive; ?>" title="<?php echo htmlspecialchars($item['nombre']); ?>">
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
                        <span class="menu-group-name">
                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                        </span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- JavaScript del menú -->
    <script>
        (function () {
            'use strict';

            let activeGroupIndex = null;

            // Función para toggle de grupo (acordeón)
            window.toggleMenuGroup = function (index) {
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
            window.toggleSidebarMobile = function () {
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
            window.closeSidebarMobile = function () {
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
                item.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        closeSidebarMobile();
                    }
                });
            });

            // Marcar grupo activo si hay una página activa
            document.addEventListener('DOMContentLoaded', function () {
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

            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.attributeName === 'class') {
                        if (sidebar.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    }
                });
            });

            observer.observe(sidebar, {
                attributes: true
            });

            // Soporte para teclado (accesibilidad)
            document.addEventListener('keydown', function (e) {
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
