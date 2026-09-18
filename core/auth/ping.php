<?php
// /core/auth/ping.php
// Endpoint ligero de Heartbeat para mantener activa la sesión y verificar expiración

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/session_manager.php';

iniciarSesionSegura();

$esValida = verificarExpiracionSesion();

if (!$esValida || !isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'expired',
        'message' => 'La sesión ha expirado o no está activa.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$ahora = time();
$loginTime = $_SESSION['login_time'] ?? $ahora;
$segundosRestantes = max(0, SESSION_DURATION_SECONDS - ($ahora - $loginTime));

http_response_code(200);
echo json_encode([
    'status'    => 'active',
    'usuario'   => $_SESSION['usuario_nombre'] ?? '',
    'time_left' => $segundosRestantes
], JSON_UNESCAPED_UNICODE);
exit();
