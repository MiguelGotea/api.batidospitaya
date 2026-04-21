<?php
/**
 * api/ping.php
 * Endpoint de heartbeat para sistemas Access en sucursales.
 * Recibe señal de vida y registra el estado en la BD.
 *
 * Parámetros POST o GET:
 *   sucursal   : Código de la sucursal (requerido)
 *   pc_nombre  : Nombre del equipo (COMPUTERNAME) - opcional
 *   pc_usuario : Usuario de Windows - opcional
 *   ip_local   : IP local del equipo - opcional
 *   version    : Versión del sistema Access - opcional
 *   modulo     : Módulo activo al momento del ping - opcional
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Responde inmediatamente a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function param(string $key, string $default = ''): string {
    $val = $_POST[$key] ?? $_GET[$key] ?? $default;
    return trim(htmlspecialchars(strip_tags((string)$val), ENT_QUOTES, 'UTF-8'));
}

// ── Leer parámetros ───────────────────────────────────────────────────────────
$sucursal     = param('sucursal');
$pc_nombre    = param('pc_nombre');
$pc_usuario   = param('pc_usuario');
$ip_local     = param('ip_local');
$version      = param('version');
$modulo       = param('modulo');
$ip_publica   = $_SERVER['REMOTE_ADDR'] ?? '';

// ── Validación mínima ─────────────────────────────────────────────────────────
if (empty($sucursal)) {
    http_response_code(400);
    echo json_encode([
        'status'    => 'error',
        'message'   => 'Parámetro "sucursal" requerido',
        'timestamp' => time()
    ]);
    exit;
}

// ── Conexión BD ───────────────────────────────────────────────────────────────
try {
    require_once __DIR__ . '/../core/database/conexion.php';

    $sql = "INSERT INTO sistemas_ping_log
                (sucursal_codigo, pc_nombre, pc_usuario, ip_local, ip_publica,
                 version_access, modulo_activo, ping_at, created_at)
            VALUES
                (:sucursal, :pc_nombre, :pc_usuario, :ip_local, :ip_publica,
                 :version, :modulo, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':sucursal'   => $sucursal,
        ':pc_nombre'  => $pc_nombre,
        ':pc_usuario' => $pc_usuario,
        ':ip_local'   => $ip_local,
        ':ip_publica' => $ip_publica,
        ':version'    => $version,
        ':modulo'     => $modulo,
    ]);

    // Limpiar registros viejos (>7 días) para mantener tabla liviana
    $conn->exec("DELETE FROM sistemas_ping_log WHERE ping_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

    echo json_encode([
        'status'      => 'success',
        'message'     => 'pong',
        'sucursal'    => $sucursal,
        'timestamp'   => time(),
        'server_time' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Si falla la BD, al menos responde pong para no bloquear el sistema Access
    error_log("[ping.php] Error BD: " . $e->getMessage());
    echo json_encode([
        'status'    => 'success',
        'message'   => 'pong',
        'timestamp' => time(),
        'db_error'  => true
    ]);
}
exit;
?>