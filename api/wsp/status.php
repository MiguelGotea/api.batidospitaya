<?php
/**
 * status.php — Estado actual del servicio WhatsApp
 * GET /api/wsp/status.php?instancia=wsp-clientes
 * Público — el ERP lo consulta a través de campanas_wsp_get_status.php (sin token)
 *
 * @param instancia  Nombre PM2 de la instancia (default: wsp-clientes)
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');


$instancia = $_GET['instancia'] ?? 'wsp-clientes';

try {
    $stmt = $conn->prepare("
        SELECT estado, qr_base64, numero_telefono, ultimo_ping, ip_vps
        FROM wsp_sesion_vps_
        WHERE instancia = :inst
        LIMIT 1
    ");
    $stmt->execute([':inst' => $instancia]);
    $fila = $stmt->fetch();

    if (!$fila) {
        echo json_encode(['estado' => 'desconectado', 'qr' => null, 'numero' => null]);
        exit;
    }

    // Considerar inactivo si el último ping fue hace más de 3 minutos
    // (El VPS envía heartbeat cada 60s — damos margen para latencias)
    $activo = false;
    $diffSegundos = null;
    if ($fila['ultimo_ping']) {
        $diffSegundos = time() - strtotime($fila['ultimo_ping']);
        $activo = ($diffSegundos < 180);
    }

    $estadoFinal = $activo ? $fila['estado'] : 'desconectado';

    echo json_encode([
        'estado'      => $estadoFinal,
        'instancia'   => $instancia,
        'activo'      => $activo,
        'ultimo_ping' => $fila['ultimo_ping'],
        'seg_desde_ping' => $diffSegundos,   // útil para debug desde el ERP
        'numero'      => ($estadoFinal === 'conectado') ? $fila['numero_telefono'] : null,
        'qr'          => ($estadoFinal === 'qr_pendiente') ? $fila['qr_base64'] : null
    ]);

}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno', 'detalle' => $e->getMessage()]);
}
