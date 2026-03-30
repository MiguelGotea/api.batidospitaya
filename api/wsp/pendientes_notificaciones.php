<?php
/**
 * pendientes_notificaciones.php — Consulta de notificaciones transaccionales pendientes
 * GET /api/wsp/pendientes_notificaciones.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

verificarTokenVPS();

$instancia = $_GET['instancia'] ?? 'wsp-clientes';

try {
    // Buscar mensajes de esta instancia que estén en estado 'pendiente'
    $stmt = $conn->prepare("
        SELECT id, celular, mensaje 
        FROM `wsp_notificaciones_clientesclub_pendientes_` 
        WHERE estado = 'pendiente' 
          AND instancia = :instancia 
        ORDER BY creado_at ASC 
        LIMIT 20
    ");
    $stmt->execute([':instancia' => $instancia]);
    $pendientes = $stmt->fetchAll();

    // Marcar como 'enviando' para evitar duplicidad si el polling es concurrente
    if (!empty($pendientes)) {
        $ids = array_column($pendientes, 'id');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmtUpd = $conn->prepare("
            UPDATE `wsp_notificaciones_clientesclub_pendientes_` 
            SET estado = 'enviando' 
            WHERE id IN ($placeholders)
        ");
        $stmtUpd->execute($ids);
    }

    echo json_encode([
        'success' => true,
        'notificaciones' => $pendientes,
        'total' => count($pendientes),
        'hora_api' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
