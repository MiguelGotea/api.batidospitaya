<?php
/**
 * registrar_sesion.php — El VPS reporta su estado de conexión WhatsApp
 * POST /api/wsp/registrar_sesion.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    respuestaError('Método no permitido', 405);

$body = json_decode(file_get_contents('php://input'), true);
$estado = $body['estado'] ?? '';
$qr = $body['qr_base64'] ?? null;

$estadosValidos = ['desconectado', 'qr_pendiente', 'conectado'];
if (!in_array($estado, $estadosValidos)) {
    respuestaError('Estado inválido. Valores: ' . implode(', ', $estadosValidos));
}

try {
    $ipVPS = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

    // Si se conectó, limpiar el QR
    if ($estado === 'conectado')
        $qr = null;

    $stmt = $conn->prepare("
        UPDATE wsp_sesion_vps_
        SET estado      = :estado,
            qr_base64   = :qr,
            ultimo_ping  = CONVERT_TZ(NOW(),'+00:00','-06:00'),
            ip_vps       = :ip
        WHERE id = 1
    ");
    $stmt->execute([
        ':estado' => $estado,
        ':qr' => $qr,
        ':ip' => $ipVPS
    ]);

    respuestaOk(['estado' => $estado]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
