<?php
/**
 * status.php — Estado actual del servicio WhatsApp
 * GET /api/wsp/status.php
 * Público — el ERP lo consulta directamente (sin token)
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $stmt = $conn->prepare("
        SELECT estado, qr_base64, ultimo_ping, ip_vps
        FROM wsp_sesion_vps_
        WHERE id = 1
        LIMIT 1
    ");
    $stmt->execute();
    $fila = $stmt->fetch();

    if (!$fila) {
        echo json_encode(['estado' => 'desconectado', 'qr' => null]);
        exit;
    }

    // Considerar inactivo si el último ping fue hace más de 2 minutos
    $activo = false;
    if ($fila['ultimo_ping']) {
        $diff = time() - strtotime($fila['ultimo_ping']);
        $activo = ($diff < 120);
    }

    $estadoFinal = $activo ? $fila['estado'] : 'desconectado';

    echo json_encode([
        'estado' => $estadoFinal,
        'activo' => $activo,
        'ultimo_ping' => $fila['ultimo_ping'],
        'qr' => ($estadoFinal === 'qr_pendiente') ? $fila['qr_base64'] : null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno', 'detalle' => $e->getMessage()]);
}
