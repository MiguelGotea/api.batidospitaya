<?php
// /core/auth/session_manager.php
// Gestor centralizado de sesiones del ERP con regla de 8 horas y corte a Medianoche (12:00 AM UTC-6)

if (!defined('SESSION_DURATION_SECONDS')) {
    define('SESSION_DURATION_SECONDS', 28800); // 8 horas en segundos
}

/**
 * Inicia la sesión de forma segura y consistente con almacenamiento privado
 */
function iniciarSesionSegura()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Configurar zona horaria de Nicaragua
    date_default_timezone_set('America/Managua');

    // Directorio privado de sesiones para evitar que el Garbage Collector de Hostinger las borre a los 24 minutos
    $sessionSavePath = dirname(__DIR__, 2) . '/core/sessions';

    if (!is_dir($sessionSavePath)) {
        @mkdir($sessionSavePath, 0777, true);
    }
    @chmod($sessionSavePath, 0777);

    if (is_dir($sessionSavePath) && is_writable($sessionSavePath)) {
        session_save_path($sessionSavePath);
    } else {
        // El directorio privado no está disponible; se usará el path por defecto del servidor.
        // GC ya está deshabilitado (gc_probability=0) así que las sesiones no serán borradas prematuramente.
        error_log('[SessionManager] ADVERTENCIA: Directorio de sesiones privado no disponible o no escribible: ' . $sessionSavePath);
    }

    // Configurar tiempo de vida de la sesión en el servidor y cookie
    ini_set('session.gc_maxlifetime',  SESSION_DURATION_SECONDS);
    ini_set('session.cookie_lifetime', SESSION_DURATION_SECONDS);

    // Deshabilitar el Garbage Collector automático de PHP para esta petición.
    // Esto impide que Hostinger (u otro entorno) destruya sesiones activas
    // mientras el usuario está trabajando, sin importar qué path se use.
    ini_set('session.gc_probability', 0);
    ini_set('session.gc_divisor',     1);

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

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
 * 1. Más de 8 horas de duración continua.
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

    // Regla 2: Validar tiempo máximo de 8 horas
    if (!$haExpirado && isset($_SESSION['login_time'])) {
        if (($ahora - $_SESSION['login_time']) > SESSION_DURATION_SECONDS) {
            $haExpirado = true;
            $motivoExpiracion = 'timeout_8h';
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
