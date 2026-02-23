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

        // Convertir imagen_url relativa a URL absoluta para que el VPS pueda descargarla
        if (!empty($campana['imagen_url']) && str_starts_with($campana['imagen_url'], '/')) {
            $campana['imagen_url'] = 'https://erp.batidospitaya.com' . $campana['imagen_url'];
        }

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

    // Verificar si hay un reset de sesión solicitado para ESTA instancia
    $instancia = $_GET['instancia'] ?? 'wsp-clientes';
    $stmtReset = $conn->prepare("SELECT reset_solicitado FROM wsp_sesion_vps_ WHERE instancia = :inst LIMIT 1");
    $stmtReset->execute([':inst' => $instancia]);
    $sesionRow = $stmtReset->fetch();
    $resetSolicitado = $sesionRow && (int) $sesionRow['reset_solicitado'] === 1;

    // Si había reset pendiente, limpiarlo ahora para que solo se ejecute una vez
    if ($resetSolicitado) {
        $stmtClearReset = $conn->prepare("UPDATE wsp_sesion_vps_ SET reset_solicitado = 0 WHERE instancia = :inst");
        $stmtClearReset->execute([':inst' => $instancia]);
    }

    echo json_encode([
        'success' => true,
        'campanas' => $resultado,
        'total' => count($resultado),
        'reset_solicitado' => $resetSolicitado,
        'hora_api' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
