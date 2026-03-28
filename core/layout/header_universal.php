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
            padding: 12px 20px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
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
        }

        /* ==================== TÍTULO DE BIENVENIDA (ALINEADO A LA IZQUIERDA) ==================== */
        .welcome-title {
            text-align: left;
            color: #0E544C;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            width: 26px;
            height: 26px;
            border-radius: 50%;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            background-color: #51B8AC;
            border: none;
            font-weight: 600;
            font-size: 0.9rem !important;
            box-shadow: 0 2px 4px rgba(81, 184, 172, 0.2);
            flex-shrink: 0;
            white-space: nowrap;
            padding: 0;
        }

        .help-button-circle:hover {
            background-color: #3d9a8f;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(81, 184, 172, 0.4);
        }

        .help-button-circle:active {
            transform: translateY(0);
        }

        .help-button-circle i {
            font-size: 1.0rem !important;
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

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .main-header {
                justify-content: flex-start;
                padding: 12px 15px;
                flex-wrap: wrap;
                gap: 15px;
            }

            .header-title-container {
                gap: 10px;
                order: 1;
                flex: 0 0 100%;
                margin-bottom: 10px;
            }

            .header-title {
                font-size: 0.95rem !important;
                flex: 1;
                line-height: 1.3;
                white-space: normal;
                word-wrap: break-word;
            }

            .welcome-title {
                font-size: 0.95rem !important;
                flex: 1;
                line-height: 1.3;
                white-space: normal;
                word-wrap: break-word;
            }

            .back-button-circle {
                width: 36px;
                height: 36px;
                order: 0;
            }

            .back-button-circle i {
                font-size: 1rem !important;
            }

            .help-button-circle {
                width: 36px;
                height: 36px;
            }

            .help-button-circle i {
                font-size: 1rem !important;
            }

            .notification-bell {
                padding: 6px 8px;
                gap: 5px;
                order: 2;
                margin-left: 0;
            }

            .bell-icon {
                font-size: 1.2rem !important;
            }

            .notification-text {
                display: none;
            }

            .notification-badge {
                top: 0;
                right: 4px;
            }

            .user-info {
                gap: 10px;
                order: 3;
                margin-left: auto;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                min-width: 40px;
                font-size: 1.1rem !important;
            }

            .user-details {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .header-title {
                font-size: 0.85rem !important;
            }

            .welcome-title {
                font-size: 0.85rem !important;
            }

            .back-button-circle {
                width: 34px;
                height: 34px;
            }

            .help-button-circle {
                width: 34px;
                height: 34px;
            }

            .help-button-circle i {
                font-size: 1rem !important;
            }

            .bell-icon {
                font-size: 1.1rem !important;
            }

            .notification-badge {
                font-size: 0.65rem !important;
                padding: 1px 4px;
                min-width: 16px;
            }

            .user-avatar {
                width: 38px;
                height: 38px;
                min-width: 38px;
                font-size: 1rem !important;
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
    </style>

    <!-- Header HTML -->
    <header class="main-header">
        <div class="header-title-container">
            <!-- Botón circular para retroceder -->
            <?php if (!empty($paginaAnterior) && parse_url($paginaAnterior, PHP_URL_HOST) === $_SERVER['HTTP_HOST'] && basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
                <button class="back-button-circle" onclick="window.history.back()" title="Volver a la página anterior">
                    <i class="fas fa-arrow-left"></i>
                </button>
            <?php endif; ?>

            <?php if (!empty($titulo)): ?>
                <h1 class="header-title"><?php echo htmlspecialchars($titulo); ?></h1>
                <!-- Botón de ayuda -->
                <button class="help-button-circle" onclick="openPageHelp()" title="Ver guía de ayuda">
                    <i class="fas fa-info-circle"></i>
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

    <!-- JavaScript para notificaciones -->
    <script>
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