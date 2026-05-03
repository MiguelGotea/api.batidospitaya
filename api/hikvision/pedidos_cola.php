<?php
/**
 * pedidos_cola.php — El worker VPS consulta el siguiente item pendiente
 * GET /api/hikvision/pedidos_cola.php
 * Header: X-WSP-Token
 * Query param opcional: ?limit=3 (por defecto 1)
 *
 * Usa transacción para marcar atómicamente como 'procesando'
 * y evitar que dos workers tomen el mismo item.
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$limit = min((int)($_GET['limit'] ?? 1), 10); // Máximo 10 por llamada

try {
    $conn->beginTransaction();

    // Seleccionar los siguientes N items pendientes con bloqueo
    $stmt = $conn->prepare("
        SELECT id, cod_pedido, local_codigo, fecha,
               hora_inicio, hora_fin, canal_track, puerto_rtsp,
               dvr_ip_local, dvr_usuario, dvr_clave, vps_ip, tipo
        FROM hikvision_cola_analisis
        WHERE estado = 'pendiente'
        ORDER BY prioridad ASC, created_at ASC
        LIMIT :lim
        FOR UPDATE
    ");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();

    if (empty($items)) {
        $conn->commit();
        hikOk(['items' => [], 'total' => 0]);
    }

    // Marcar todos como 'procesando' en lote
    $ids = array_column($items, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $conn->prepare("
        UPDATE hikvision_cola_analisis
        SET estado = 'procesando', updated_at = NOW()
        WHERE id IN ($placeholders)
    ")->execute($ids);

    $conn->commit();

    hikOk([
        'items' => $items,
        'total' => count($items),
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
