<?php
/**
 * pendientes.php — Campañas listas para enviar
 * GET /api/wsp/pendientes.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

try {
    $LIMITE_DESTINATARIOS = 50;

    // Campañas programadas cuya fecha de envío ya llegó
    $stmtCamp = $conn->prepare("
        SELECT 
            id,
            nombre,
            mensaje,
            imagen_url,
            DATE_FORMAT(fecha_envio, '%Y-%m-%d %H:%i:%s') AS fecha_envio,
            total_destinatarios,
            total_enviados
        FROM wsp_campanas_
        WHERE estado = 'programada'
          AND fecha_envio <= CONVERT_TZ(NOW(), '+00:00', '-06:00')
        ORDER BY fecha_envio ASC
        LIMIT 5
    ");
    $stmtCamp->execute();
    $campanas = $stmtCamp->fetchAll();

    $resultado = [];

    foreach ($campanas as $campana) {
        // Destinatarios pendientes de esta campaña
        $stmtDest = $conn->prepare("
            SELECT id, id_cliente, nombre, telefono, sucursal
            FROM wsp_destinatarios_
            WHERE campana_id = :cid
              AND enviado = 0
              AND (error IS NULL OR error = '')
            ORDER BY id ASC
            LIMIT :lim
        ");
        $stmtDest->bindValue(':cid', (int) $campana['id'], PDO::PARAM_INT);
        $stmtDest->bindValue(':lim', $LIMITE_DESTINATARIOS, PDO::PARAM_INT);
        $stmtDest->execute();
        $destinatarios = $stmtDest->fetchAll();

        if (empty($destinatarios))
            continue;

        $campana['destinatarios'] = $destinatarios;
        $resultado[] = $campana;

        // Marcar campaña como "enviando"
        $stmtUpd = $conn->prepare("
            UPDATE wsp_campanas_
            SET estado = 'enviando'
            WHERE id = :id AND estado = 'programada'
        ");
        $stmtUpd->execute([':id' => $campana['id']]);
    }

    echo json_encode([
        'success' => true,
        'campanas' => $resultado,
        'total' => count($resultado),
        'hora_api' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
