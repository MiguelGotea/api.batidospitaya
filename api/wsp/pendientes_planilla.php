<?php
/**
 * pendientes_planilla.php — Programaciones de planilla listas para enviar
 * GET /api/wsp/pendientes_planilla.php
 * Requiere: Header X-WSP-Token
 *
 * Devuelve programaciones de planilla cuya fecha_envio ya llegó,
 * con los colaboradores destinatarios (telefono_corporativo → Celular).
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

try {
    $LIMITE_DESTINATARIOS = 50;

    // Programaciones pendientes cuya fecha_envio ya llegó
    $stmtProg = $conn->prepare("
        SELECT
            id,
            fecha_planilla,
            mensaje,
            imagen_url,
            DATE_FORMAT(fecha_envio, '%Y-%m-%d %H:%i:%s') AS fecha_envio,
            total_destinatarios,
            total_enviados
        FROM wsp_planilla_programaciones_
        WHERE (estado = 'programada' OR estado = 'enviando')
          AND fecha_envio <= CONVERT_TZ(NOW(), '+00:00', '-06:00')
          AND total_enviados + total_errores < total_destinatarios
        ORDER BY fecha_envio ASC
        LIMIT 5
    ");
    $stmtProg->execute();
    $programaciones = $stmtProg->fetchAll();

    $resultado = [];

    foreach ($programaciones as $prog) {

        // Poblar / re-sincronizar destinatarios si no existen aún
        // (en guardar ya se insertan, pero esto garantiza que estén listos)
        $stmtDest = $conn->prepare("
            SELECT
                d.id,
                d.cod_operario,
                d.nombre,
                d.telefono,
                :fp AS fecha_planilla
            FROM wsp_planilla_destinatarios_ d
            WHERE d.programacion_id = :pid
              AND d.enviado = 0
              AND (d.error IS NULL OR d.error = '')
            ORDER BY d.id ASC
            LIMIT :lim
        ");
        $stmtDest->bindValue(':pid', (int) $prog['id'], PDO::PARAM_INT);
        $stmtDest->bindValue(':lim', $LIMITE_DESTINATARIOS, PDO::PARAM_INT);
        $stmtDest->bindValue(':fp', date('d-M-Y', strtotime($prog['fecha_planilla'])));
        $stmtDest->execute();
        $destinatarios = $stmtDest->fetchAll();

        if (empty($destinatarios))
            continue;

        // Convertir imagen_url relativa a URL absoluta
        if (!empty($prog['imagen_url']) && str_starts_with($prog['imagen_url'], '/')) {
            $prog['imagen_url'] = 'https://erp.batidospitaya.com' . $prog['imagen_url'];
        }

        $prog['destinatarios'] = $destinatarios;
        $resultado[] = $prog;

        // Marcar como "enviando"
        $stmtUpd = $conn->prepare("
            UPDATE wsp_planilla_programaciones_
            SET estado = 'enviando'
            WHERE id = :id AND estado = 'programada'
        ");
        $stmtUpd->execute([':id' => $prog['id']]);
    }

    echo json_encode([
        'success' => true,
        'campanas' => $resultado,   // usa el mismo key 'campanas' para compatibilidad con el worker
        'total' => count($resultado),
        'hora_api' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
