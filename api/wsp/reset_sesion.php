<?php
/**
 * reset_sesion.php — Solicita el reinicio de la sesión WhatsApp en el VPS
 * POST /api/wsp/reset_sesion.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

try {
    // Marcar el reset como solicitado en la BD
    // El VPS lo detecta en el próximo ciclo de pendientes.php (cada 60s)
    $stmt = $conn->prepare("
        UPDATE wsp_sesion_vps_
        SET reset_solicitado = 1
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        // Si no hay fila aún, insertar una
        $conn->exec("INSERT INTO wsp_sesion_vps_ (estado, reset_solicitado) VALUES ('desconectado', 1)");
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Reset solicitado. El VPS procesará el cambio en el próximo ciclo (máx. 60s).',
        'hora' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respuestaError('Error al solicitar reset: ' . $e->getMessage(), 500);
}
