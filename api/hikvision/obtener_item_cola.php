<?php
/**
 * obtener_item_cola.php — Devuelve un item específico de la cola por su id
 * GET /api/hikvision/obtener_item_cola.php?id=33
 * Header: X-WSP-Token
 *
 * A diferencia de pedidos_cola.php, este endpoint:
 *   - NO marca el item como 'procesando'
 *   - NO usa transacción exclusiva (FOR UPDATE)
 *   - Retorna el item independientemente de su estado actual
 *   - Usado por test_manual.py para procesar directamente sin el worker daemon
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    hikErr('Falta parámetro requerido: id');
}

try {
    $stmt = $conn->prepare("
        SELECT
            c.id, c.cod_pedido, c.local_codigo, c.fecha,
            c.hora_inicio, c.hora_fin, c.canal_track, c.puerto_rtsp,
            c.dvr_ip_local, c.dvr_usuario, c.dvr_clave, c.vps_ip,
            c.tipo, c.estado, c.prioridad, c.created_at, c.updated_at,
            s.nombre AS sucursal_nombre
        FROM hikvision_cola_analisis c
        LEFT JOIN sucursales s ON s.codigo = c.local_codigo
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    if (!$item) {
        hikErr("Item $id no encontrado en la cola", 404);
    }

    hikOk(['item' => $item]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
