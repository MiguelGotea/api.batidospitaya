<?php
/**
 * status.php — Estado actual del servicio WhatsApp
 * GET /api/wsp/status.php
 * Público — el ERP lo consulta directamente (sin token)
 *
 * Retorna si el VPS está conectado, desconectado o esperando QR.
 * Si estado=qr_pendiente, incluye el QR en base64.
 * Considera "inactivo" si el último ping fue hace más de 2 minutos.
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $result = $conn->query("
        SELECT estado, qr_base64, ultimo_ping, ip_vps
        FROM wsp_sesion_vps_
        WHERE id = 1
        LIMIT 1
    ");

    if (!$result || $result->num_rows === 0) {
        echo json_encode(['estado' => 'desconectado', 'qr' => null]);
        exit;
    }

    $fila = $result->fetch_assoc();

    // Verificar si el VPS está activo (ping reciente)
    $ultimoPing = $fila['ultimo_ping'];
    $activo = false;
    if ($ultimoPing) {
        $diff = time() - strtotime($ultimoPing);
        $activo = ($diff < 120); // activo si ping < 2 minutos
    }

    $estadoFinal = $activo ? $fila['estado'] : 'desconectado';

    $respuesta = [
        'estado' => $estadoFinal,
        'activo' => $activo,
        'ultimo_ping' => $ultimoPing,
        'qr' => ($estadoFinal === 'qr_pendiente') ? $fila['qr_base64'] : null
    ];

    echo json_encode($respuesta);

} catch (Exception $e) {
    // Si la tabla no existe aún (SQL no ejecutado), devolver desconectado en vez de 500
    $msg = $e->getMessage();
    if (str_contains($msg, "doesn't exist") || str_contains($msg, 'Table') || str_contains($msg, 'wsp_sesion_vps_')) {
        echo json_encode([
            'estado' => 'desconectado',
            'activo' => false,
            'qr' => null,
            '_nota' => 'Tabla no creada aún — ejecutar campanas_wsp_install.sql'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno: ' . $msg]);
    }
}
