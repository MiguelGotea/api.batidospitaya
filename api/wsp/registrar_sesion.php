<?php
/**
 * registrar_sesion.php — El VPS reporta su estado de conexión WhatsApp
 * POST /api/wsp/registrar_sesion.php
 * Requiere: Header X-WSP-Token
 *
 * Body JSON:
 *  {
 *    "estado": "desconectado" | "qr_pendiente" | "conectado",
 *    "qr_base64": "data:image/png;base64,..." | null
 *  }
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
    // Obtener IP del VPS
    $ipVPS = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

    // Actualizar (solo hay 1 fila en wsp_sesion_vps_)
    $stmt = $conn->prepare("
        UPDATE wsp_sesion_vps_
        SET estado      = ?,
            qr_base64   = ?,
            ultimo_ping  = CONVERT_TZ(NOW(),'+00:00','-06:00'),
            ip_vps       = ?
        WHERE id = 1
    ");
    $stmt->bind_param('sss', $estado, $qr, $ipVPS);
    $stmt->execute();
    $stmt->close();

    // Si se conectó, limpiar QR
    if ($estado === 'conectado') {
        $conn->query("UPDATE wsp_sesion_vps_ SET qr_base64 = NULL WHERE id = 1");
    }

    respuestaOk(['estado' => $estado]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
