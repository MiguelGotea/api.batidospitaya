<?php
// /core/auth/session_manager.php
// Gestor centralizado de sesiones del ERP con inactividad de 8 horas y corte a Medianoche (12:00 AM UTC-6)

if (!defined('SESSION_DURATION_SECONDS')) {
    define('SESSION_DURATION_SECONDS', 28800); // 8 horas de inactividad máxima
}

/**
 * Inicia la sesión de forma segura usando MySQL como almacén de sesiones.
 * Esto evita que el GC externo de Hostinger destruya los archivos de sesión.
 */
function iniciarSesionSegura()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Configurar zona horaria de Nicaragua
    date_default_timezone_set('America/Managua');

    // -------------------------------------------------------------------------
    // Handler de sesiones en MySQL
    // Almacena sesiones en la tabla `php_sessions`, inmune al GC de Hostinger.
    // -------------------------------------------------------------------------
    require_once __DIR__ . '/DbSessionHandler.php';

    // Conexión PDO independiente para el handler (no depende de $conn global)
    $dbHost = 'localhost';
    $dbName = 'u839374897_erp';
    $dbUser = 'u839374897_erp';
    $dbPass = 'ERpPitHay2025$';

    try {
        $pdoSession = new PDO(
            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ]
        );

        $handler = new DbSessionHandler($pdoSession, SESSION_DURATION_SECONDS * 2);

        // Crear tabla si aún no existe (operación idempotente)
        $handler->instalarTabla();

        // Registrar el handler ANTES de session_start()
        session_set_save_handler($handler, true);

    } catch (PDOException $e) {
        // Fallback a archivos si la BD no está disponible
        error_log('[SessionManager] ADVERTENCIA: No se pudo iniciar handler de BD. Usando archivos. ' . $e->getMessage());

        $sessionSavePath = dirname(__DIR__, 2) . '/core/sessions';
        if (!is_dir($sessionSavePath)) {
            @mkdir($sessionSavePath, 0777, true);
        }
        @chmod($sessionSavePath, 0777);
        if (is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
            session_save_path($sessionSavePath);
        }
    }

    // Configurar tiempo de vida de sesión y cookie
    ini_set('session.gc_maxlifetime',  SESSION_DURATION_SECONDS * 2);
    ini_set('session.cookie_lifetime', SESSION_DURATION_SECONDS);

    // Deshabilitar GC automático de PHP (el DbSessionHandler tiene su propio GC)
    ini_set('session.gc_probability', 0);
    ini_set('session.gc_divisor',     1);

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => SESSION_DURATION_SECONDS,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(
            SESSION_DURATION_SECONDS,
            '/; samesite=Lax',
            '',
            $isSecure,
            true
        );
    }

    session_start();
}

/**
 * Verifica si la sesión actual ha expirado según las reglas de negocio:
 * 1. Inactividad de más de 8 horas (ventana deslizante por last_activity).
 * 2. Cambio de día (medianoche 12:00 AM en hora de Nicaragua).
 * 
 * @return bool true si la sesión es válida o no hay usuario autenticado; false si expiró
 */
function verificarExpiracionSesion()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        iniciarSesionSegura();
    }

    // Si no hay usuario logueado, no se evalúa expiración
    if (!isset($_SESSION['usuario_id'])) {
        return true;
    }

    date_default_timezone_set('America/Managua');
    $ahora = time();
    $fechaActual = date('Y-m-d');

    $haExpirado = false;
    $motivoExpiracion = '';

    // Regla 1: Validar fecha (Corte automático a las 12:00 AM del nuevo día)
    if (isset($_SESSION['login_date']) && $_SESSION['login_date'] !== $fechaActual) {
        $haExpirado = true;
        $motivoExpiracion = 'midnight';
    }

    // Regla 2: Validar inactividad máxima de 8 horas (ventana deslizante)
    // Se usa last_activity (que se actualiza en cada petición) en lugar de login_time
    // (que es fijo desde el login). Así la sesión solo expira si el usuario estuvo
    // inactivo más de SESSION_DURATION_SECONDS segundos seguidos.
    if (!$haExpirado) {
        $ultimaActividad = $_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? null;
        if ($ultimaActividad !== null && ($ahora - $ultimaActividad) > SESSION_DURATION_SECONDS) {
            $haExpirado = true;
            $motivoExpiracion = 'inactividad_8h';
        }
    }

    // Si no tenían registrados login_time o login_date (sesión antigua en transición), inicializarlos
    if (!$haExpirado && (!isset($_SESSION['login_time']) || !isset($_SESSION['login_date']))) {
        $_SESSION['login_time'] = $ahora;
        $_SESSION['login_date'] = $fechaActual;
    }

    if ($haExpirado) {
        // Limpiar y destruir sesión expirada
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return false;
    }

    // Actualizar marca de última actividad
    $_SESSION['last_activity'] = $ahora;
    return true;
}

/**
 * Registra los metadatos necesarios al iniciar sesión exitosamente
 * 
 * @param mixed $usuarioId ID o CodOperario del usuario
 * @param string $usuarioNombre Nombre o identificador del usuario
 * @param array $datosAdicionales Claves adicionales para almacenar en $_SESSION
 */
function registrarInicioSesion($usuarioId, $usuarioNombre, $datosAdicionales = [])
{
    date_default_timezone_set('America/Managua');
    $ahora = time();
    $fechaActual = date('Y-m-d');

    $_SESSION['usuario_id']     = $usuarioId;
    $_SESSION['usuario_nombre'] = $usuarioNombre;
    $_SESSION['login_time']     = $ahora;
    $_SESSION['login_date']     = $fechaActual;
    $_SESSION['last_activity']  = $ahora;

    foreach ($datosAdicionales as $key => $val) {
        $_SESSION[$key] = $val;
    }
}
