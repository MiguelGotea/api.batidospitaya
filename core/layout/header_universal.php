<?php
/**
 * Header Universal para Módulos ERP
 * Incluir este archivo en cada página: require_once '../../includes/header_universal.php';
 * Uso: echo renderHeader($usuario, $esAdmin, 'Título de la Página');
 */

/**
 * Función para renderizar el header universal
 * @param array $usuario - Array con datos del usuario
 * @param bool $esAdmin - Si el usuario es administrador
 * @param string $titulo - Título de la página (opcional)
 * @return string HTML del header
 */
function renderHeader($usuario, $esAdmin = false, $titulo = '')
{
    // Obtener cantidad de anuncios no leídos
    $cantidadAnunciosNoLeidos = 0;
    if (isset($_SESSION['usuario_id'])) {
        $cantidadAnunciosNoLeidos = obtenerCantidadAnunciosNoLeidos($_SESSION['usuario_id']);
    }

    // Obtener la URL de referencia para retroceder
    $paginaAnterior = $_SERVER['HTTP_REFERER'] ?? '';

    ob_start();
    ?>

    <!-- CSS COMPLETO del Header -->
    <style>
        /* ==================== HEADER BASE ==================== */
        .main-header {
            position: relative;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding: 14px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
            gap: 20px;
        }

        /* ==================== CONTENEDOR TÍTULO CON FLECHA ==================== */
        .header-title-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-right: auto;
            flex: 1;
            min-width: 0;
        }

        /* ==================== TÍTULO CENTRAL ==================== */
        .header-title {
            color: #0E544C;
            font-size: 1.2rem !important;
            font-weight: 600;
            margin: 0;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 0 1 auto;
            min-width: 0;
        }

        /* ==================== TÍTULO DE BIENVENIDA (ALINEADO A LA IZQUIERDA) ==================== */
        .welcome-title {
            text-align: left;
            color: #0E544C;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 0 1 auto;
            min-width: 0;
        }

        /* ==================== BOTÓN RETROCEDER CIRCULAR ==================== */
        .back-button-circle {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #51B8AC;
            background-color: transparent;
            border: 2px solid #51B8AC;
            font-weight: 600;
            font-size: 0.9rem !important;
            box-shadow: 0 2px 4px rgba(81, 184, 172, 0.2);
            flex-shrink: 0;
            white-space: nowrap;
            padding: 0;
        }

        .back-button-circle:hover {
            background-color: #51B8AC;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(81, 184, 172, 0.3);
            border-color: #51B8AC;
        }

        .back-button-circle:active {
            transform: translateY(0);
        }

        .back-button-circle i {
            font-size: 1.2rem !important;
        }

        /* ==================== BOTÓN DE AYUDA ==================== */
        .help-button-circle {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #51B8AC;
            background-color: white;
            border: 1.5px solid #51B8AC;
            font-weight: 600;
            font-size: 0.85rem !important;
            flex-shrink: 0;
            padding: 0;
        }

        .help-button-circle:hover {
            background-color: white;
            color: #51B8AC;
            border: 1px solid #51B8AC;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(81, 184, 172, 0.2);
        }

        .help-button-circle:active {
            transform: translateY(0);
        }

        .help-button-circle i {
            font-size: 0.85rem !important;
        }

        /* ==================== NOTIFICACIONES ==================== */
        .notification-bell {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            margin-left: auto;
        }

        .notification-bell:hover {
            background: #f8f9fa;
        }

        .bell-icon {
            font-size: 1.3rem !important;
            color: #666;
            transition: all 0.3s ease;
        }

        .notification-bell.has-notifications .bell-icon {
            color: #ffc107;
            animation: ring 2s ease-in-out infinite;
        }

        @keyframes ring {

            0%,
            100% {
                transform: rotate(0deg);
            }

            10%,
            30% {
                transform: rotate(-10deg);
            }

            20%,
            40% {
                transform: rotate(10deg);
            }
        }

        .notification-badge {
            position: absolute;
            top: 2px;
            right: 8px;
            background: #dc3545;
            color: white;
            font-size: 0.7rem !important;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .notification-text {
            position: relative;
            font-size: 0.85rem !important;
            color: #666;
            white-space: nowrap;
            background: #ffc107;
            color: #333;
            padding: 4px 12px 4px 10px;
            border-radius: 4px 0 0 4px;
            font-weight: 600;
            margin-right: -12px;
        }

        .notification-text::after {
            content: '';
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 12px 0 12px 8px;
            border-color: transparent transparent transparent #ffc107;
        }

        /* ==================== USER INFO ==================== */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border-radius: 50%;
            background-color: #51B8AC;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem !important;
            box-shadow: 0 2px 8px rgba(81, 184, 172, 0.3);
            text-transform: uppercase;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            text-align: left;
        }

        .user-name {
            font-weight: 600;
            color: #0E544C;
            font-size: 0.95rem !important;
            white-space: nowrap;
        }

        .user-role {
            color: #0E544C;
            font-size: 0.85rem !important;
            white-space: nowrap;
        }

        /* ==================== BOTÓN HAMBURGUESA INTEGRADO (solo móvil) ==================== */
        .header-mobile-menu-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            min-width: 38px;
            background: #51B8AC;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(81, 184, 172, 0.3);
            transition: all 0.3s ease;
            flex-shrink: 0;
            padding: 0;
        }

        .header-mobile-menu-btn:hover {
            background: #0E544C;
            transform: scale(1.05);
        }

        .header-mobile-menu-btn:active {
            transform: scale(0.95);
        }

        .header-mobile-menu-btn i {
            font-size: 1.1rem !important;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .main-header {
                justify-content: flex-start;
                padding: 8px 12px;
                flex-wrap: nowrap;
                gap: 8px;
                overflow: hidden;
            }

            .header-mobile-menu-btn {
                display: flex;
            }

            .header-title-container {
                gap: 6px;
                flex: 1;
                min-width: 0;
                margin-right: 0;
                margin-bottom: 0;
            }

            .header-title {
                font-size: 0.82rem !important;
                flex: 0 1 auto;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .welcome-title {
                font-size: 0.82rem !important;
                flex: 0 1 auto;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .back-button-circle {
                width: 32px;
                height: 32px;
                min-width: 32px;
                flex-shrink: 0;
            }

            .back-button-circle i {
                font-size: 0.9rem !important;
            }

            .help-button-circle {
                width: 22px;
                height: 22px;
                min-width: 22px;
                flex-shrink: 0;
            }

            .help-button-circle i {
                font-size: 0.75rem !important;
            }

            .notification-bell {
                padding: 4px 6px;
                gap: 4px;
                margin-left: 0;
                flex-shrink: 0;
            }

            .bell-icon {
                font-size: 1.1rem !important;
            }

            .notification-text {
                display: none;
            }

            .notification-badge {
                top: 0;
                right: 2px;
                font-size: 0.6rem !important;
                min-width: 15px;
                padding: 1px 3px;
            }

            .user-info {
                gap: 0;
                flex-shrink: 0;
            }

            .user-avatar {
                width: 34px;
                height: 34px;
                min-width: 34px;
                font-size: 0.95rem !important;
            }

            .user-details {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .main-header {
                padding: 6px 10px;
                gap: 6px;
            }

            .header-title {
                font-size: 0.78rem !important;
                flex: 0 1 auto;
            }

            .welcome-title {
                font-size: 0.78rem !important;
                flex: 0 1 auto;
            }

            .back-button-circle {
                width: 28px;
                height: 28px;
                min-width: 28px;
            }

            .help-button-circle {
                width: 20px;
                height: 20px;
                min-width: 20px;
            }

            .bell-icon {
                font-size: 1rem !important;
            }

            .user-avatar {
                width: 30px;
                height: 30px;
                min-width: 30px;
                font-size: 0.85rem !important;
            }
        }

        /* ==================== ANIMACIONES ==================== */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-header {
            animation: fadeIn 0.3s ease-out;
        }

        /* ==================== GLOBAL HELPER MODAL FIX ==================== */
        #pageHelpModal .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
            margin: 0.5rem auto;
            max-width: 900px; /* Ancho cómodo universal */
        }

        @media (min-width: 576px) {
            #pageHelpModal .modal-dialog {
                min-height: calc(100% - 3.5rem);
                margin: 1.75rem auto;
            }
        }
    </style>

    <!-- Header HTML -->
    <header class="main-header">
        <!-- Botón hamburguesa integrado (visible solo en móvil) -->
        <button class="header-mobile-menu-btn" onclick="toggleSidebarFromHeader()" title="Abrir menú">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title-container">
            <!-- Botón circular para retroceder -->
            <?php if (!empty($paginaAnterior) && parse_url($paginaAnterior, PHP_URL_HOST) === $_SERVER['HTTP_HOST'] && basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
                <button class="back-button-circle" onclick="goBackDistinct()" title="Volver a la página anterior">
                    <i class="fas fa-arrow-left"></i>
                </button>
            <?php endif; ?>

            <?php if (!empty($titulo)): ?>
                <h1 class="header-title"><?php echo htmlspecialchars($titulo); ?></h1>
                <!-- Botón de ayuda -->
                <button class="help-button-circle" onclick="openPageHelp()" title="Ver guía de ayuda">
                    <i class="fas fa-info"></i>
                </button>
            <?php else: ?>

                <h1 class="header-title welcome-title">
                    ¡
                    <?=
                        (isset($usuario['Genero']) && strtoupper($usuario['Genero']) === 'F')
                        ? 'Bienvenida'
                        : (isset($usuario['Genero']) && strtoupper($usuario['Genero']) === 'M'
                            ? 'Bienvenido'
                            : 'Bienvenid@')
                        ?>         <?= $esAdmin ?
                                 htmlspecialchars($usuario['nombre']) :
                                 htmlspecialchars($usuario['Nombre']) ?>!
                </h1>

            <?php endif; ?>
        </div>

        <!-- Notificaciones -->
        <div class="notification-bell <?= $cantidadAnunciosNoLeidos > 0 ? 'has-notifications' : '' ?>" id="notificationBell"
            onclick="irAAnuncios()"
            title="<?= $cantidadAnunciosNoLeidos > 0 ? $cantidadAnunciosNoLeidos . ' anuncio(s) pendiente(s)' : 'Sin anuncios nuevos' ?>">
            <?php if ($cantidadAnunciosNoLeidos > 0): ?>
                <span class="notification-text">Anuncios por Revisar</span>
            <?php endif; ?>
            <span></span>
            <span></span>
            <i class="fas fa-bell bell-icon"></i>
            <span></span>
            <span class="notification-badge" id="notificationBadge"><?= $cantidadAnunciosNoLeidos ?></span>
        </div>

        <div class="user-info">
            <div class="user-avatar"
                title="<?php echo $esAdmin ? htmlspecialchars($usuario['Nombre']) : htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']); ?>">
                <?php
                // Verificar si existe foto de perfil
                $fotoPerfil = $esAdmin ? ($usuario['foto_perfil'] ?? null) : ($usuario['foto_perfil'] ?? null);

                if (!empty($fotoPerfil) && file_exists('../../' . $fotoPerfil)):
                    ?>
                    <img src="../../<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil">
                <?php else: ?>
                    <?= $esAdmin ?
                        strtoupper(substr($usuario['nombre'], 0, 1)) :
                        strtoupper(substr($usuario['Nombre'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="user-details">
                <div class="user-name">
                    <?= $esAdmin ?
                        htmlspecialchars($usuario['nombre']) :
                        htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']) ?>
                </div>
                <small class="user-role">
                    <?= $esAdmin ?
                        'Administrador' :
                        htmlspecialchars($usuario['cargo_nombre'] ?? 'Sin cargo definido') ?>
                </small>
            </div>
        </div>
    </header>

    <!-- JavaScript del header -->
    <script>
        // Función para abrir el sidebar desde el header (móvil)
        // Delega a toggleSidebarMobile() definida en menu_lateral.php
        function toggleSidebarFromHeader() {
            if (typeof toggleSidebarMobile === 'function') {
                toggleSidebarMobile();
            } else {
                // Fallback por si el menú lateral no está cargado
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar) sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            let isBackForward = false;
            
            // Comprobar si la navegación fue mediante botones de retroceso/avance
            if (window.performance && window.performance.getEntriesByType) {
                const navEntries = window.performance.getEntriesByType("navigation");
                if (navEntries.length > 0 && navEntries[0].type === "back_forward") {
                    isBackForward = true;
                }
            } else if (window.performance && window.performance.navigation) {
                if (window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
                    isBackForward = true;
                }
            }
            
            let appHistory = JSON.parse(sessionStorage.getItem('custom_app_history') || '[]');
            const currentUrl = window.location.href;
            const currentPath = window.location.pathname;
            
            if (isBackForward) {
                // Sincronizar historial cortando todo lo que está después de esta URL
                let foundIndex = -1;
                for (let i = appHistory.length - 1; i >= 0; i--) {
                    if (appHistory[i].url === currentUrl) {
                        foundIndex = i;
                        break;
                    }
                }
                if (foundIndex !== -1) {
                    appHistory = appHistory.slice(0, foundIndex + 1);
                } else {
                    appHistory.push({ url: currentUrl, path: currentPath });
                }
            } else {
                // Nueva visita o refresh. Evitar duplicar en caso de simple refresh
                if (appHistory.length === 0 || appHistory[appHistory.length - 1].url !== currentUrl) {
                    appHistory.push({ url: currentUrl, path: currentPath });
                }
            }
            
            sessionStorage.setItem('custom_app_history', JSON.stringify(appHistory));
        });

        // Retroceder a la última página distinta (ignorando variables GET del mismo PHP)
        function goBackDistinct() {
            const appHistory = JSON.parse(sessionStorage.getItem('custom_app_history') || '[]');
            const currentPath = window.location.pathname;
            let steps = 0;
            let found = false;
            
            // appHistory.length - 1 es la página actual
            for (let i = appHistory.length - 2; i >= 0; i--) {
                if (appHistory[i].path !== currentPath) {
                    steps = (appHistory.length - 1) - i;
                    found = true;
                    break;
                }
            }
            
            if (found && steps > 0) {
                window.history.go(-steps);
            } else {
                window.history.back(); // Fallback si no hay historial distinto guardado
            }
        }

        // Obtener la URL base del sitio
        function getBaseUrl() {
            return window.location.protocol + '//' + window.location.host;
        }

        function irAAnuncios() {
            const baseUrl = getBaseUrl();

            // URL para marcar anuncios como leídos
            const marcarLeidosUrl = baseUrl + '/modulos/supervision/auditorias_original/marcar_anuncios_leidos.php';

            // URL para ir a anuncios
            const anunciosUrl = baseUrl + '/modulos/supervision/auditorias_original/index_avisos_publico.php';

            // Marcar anuncios como leídos
            fetch(marcarLeidosUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar el badge localmente
                        const badge = document.getElementById('notificationBadge');
                        const bell = document.getElementById('notificationBell');
                        if (badge) badge.remove();
                        if (bell) bell.classList.remove('has-notifications');

                        // Remover el texto "Pendientes"
                        const notifText = bell.querySelector('.notification-text');
                        if (notifText) notifText.remove();
                    }
                    // Redirigir a anuncios
                    window.location.href = anunciosUrl;
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Redirigir incluso si hay error
                    window.location.href = anunciosUrl;
                });
        }

        // Función para abrir el modal de ayuda de la página
        function openPageHelp() {
            const helpModal = document.getElementById('pageHelpModal');
            if (helpModal) {
                // Si existe el modal, abrirlo con Bootstrap
                const modal = new bootstrap.Modal(helpModal);
                modal.show();
            } else {
                console.log('No hay modal de ayuda definido para esta página');
            }
        }
    </script>

    <?php
    return ob_get_clean();
}

/**
 * Función para obtener la URL base del sitio dinámicamente
 * @return string URL base (ej: https://erp.batidospitaya.com)
 */
function getBaseUrl()
{
    // Determinar el protocolo (http o https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Obtener el host
    $host = $_SERVER['HTTP_HOST'];

    // Si estás detrás de un proxy, podrías necesitar ajustar esto
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }

    // Construir la URL base
    $baseUrl = $protocol . '://' . $host;

    // Opcional: Si tu sitio está en un subdirectorio, agregarlo
    // Ejemplo: si está en /erp/, descomenta la siguiente línea
    // $baseUrl .= '/erp';

    return $baseUrl;
}
?>