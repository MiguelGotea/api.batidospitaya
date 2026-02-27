<?php
/**
 * pendientes_planilla.php — Programaciones de planilla listas para enviar
 * GET /api/wsp/pendientes_planilla.php
 * Requiere: Header X-WSP-Token
 *
 * Lee directamente BoletaPago (columnas wsp_*) en lugar de
 * una tabla de destinatarios separada.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

try {
    $LIMITE_DESTINATARIOS = 50;

    // Programaciones cuya fecha_envio ya llegó y que tienen boletas pendientes de enviar
    $stmtProg = $conn->prepare("
        SELECT
            p.id,
            p.fecha_planilla,
            p.mensaje,
            p.imagen_url,
            DATE_FORMAT(p.fecha_envio, '%Y-%m-%d %H:%i:%s') AS fecha_envio,
            p.total_destinatarios,
            p.total_enviados
        FROM wsp_planilla_programaciones_ p
        WHERE (p.estado = 'programada' OR p.estado = 'enviando')
          AND p.fecha_envio <= CONVERT_TZ(NOW(), '+00:00', '-06:00')
          AND p.total_enviados + p.total_errores < p.total_destinatarios
        ORDER BY p.fecha_envio ASC
        LIMIT 5
    ");
    $stmtProg->execute();
    $programaciones = $stmtProg->fetchAll();

    $resultado = [];

    foreach ($programaciones as $prog) {

        // Destinatarios pendientes: leer directamente de BoletaPago
        $stmtDest = $conn->prepare("
            SELECT
                b.id_boleta                    AS id,
                b.cod_operario,
                CONCAT(
                    COALESCE(o.Nombre,''),' ',
                    COALESCE(o.Nombre2,''),' ',
                    COALESCE(o.Apellido,''),' ',
                    COALESCE(o.Apellido2,'')
                )                              AS nombre,
                COALESCE(
                    NULLIF(TRIM(o.telefono_corporativo),''),
                    NULLIF(TRIM(o.Celular),'')
                )                              AS telefono,
                :fp                            AS fecha_planilla
            FROM BoletaPago b
            INNER JOIN Operarios o ON o.CodOperario = b.cod_operario
            WHERE b.wsp_programacion_id = :pid
              AND b.wsp_enviado = 0
            ORDER BY b.id_boleta ASC
            LIMIT :lim
        ");
        $stmtDest->bindValue(':pid', (int) $prog['id'], PDO::PARAM_INT);
        $stmtDest->bindValue(':lim', $LIMITE_DESTINATARIOS, PDO::PARAM_INT);
        $stmtDest->bindValue(':fp', date('d-M-Y', strtotime($prog['fecha_planilla'])));
        $stmtDest->execute();
        $destinatarios = $stmtDest->fetchAll();

        // Filtrar filas sin teléfono (puede pasar si el operario no tiene ninguno)
        $destinatarios = array_filter($destinatarios, fn($d) => !empty($d['telefono']));
        $destinatarios = array_values($destinatarios);

        if (empty($destinatarios))
            continue;

        // Convertir imagen_url relativa a URL absoluta
        if (!empty($prog['imagen_url']) && str_starts_with($prog['imagen_url'], '/')) {
            $prog['imagen_url'] = 'https://erp.batidospitaya.com' . $prog['imagen_url'];
        }

        $prog['destinatarios'] = $destinatarios;
        $resultado[] = $prog;

        // Marcar la programación como "enviando"
        $conn->prepare("
            UPDATE wsp_planilla_programaciones_
            SET estado = 'enviando'
            WHERE id = :id AND estado = 'programada'
        ")->execute([':id' => $prog['id']]);
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
