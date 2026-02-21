<?php
/**
 * pendientes.php — Campañas listas para enviar
 * GET /api/wsp/pendientes.php
 * Requiere: Header X-WSP-Token
 *
 * Retorna campañas con estado='programada' cuya fecha_envio ya pasó,
 * junto con sus destinatarios pendientes (enviado=0).
 * Limita a MAX 50 destinatarios por llamada para controlar el flujo.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

try {
    // Límite de destinatarios por polling (control de flujo anti-ban)
    $LIMITE_DESTINATARIOS = 50;

    // Obtener campañas listas (fecha llegó y estado=programada)
    $sqlCampanas = "
        SELECT 
            id,
            nombre,
            mensaje,
            imagen_url,
            fecha_envio,
            total_destinatarios,
            total_enviados
        FROM wsp_campanas_
        WHERE estado = 'programada'
          AND fecha_envio <= CONVERT_TZ(NOW(), '+00:00', '-06:00')
        ORDER BY fecha_envio ASC
        LIMIT 5
    ";

    $campanas = [];
    $result = $conn->query($sqlCampanas);

    if ($result && $result->num_rows > 0) {
        while ($campana = $result->fetch_assoc()) {
            // Obtener destinatarios pendientes de esta campaña
            $stmt = $conn->prepare("
                SELECT 
                    id,
                    id_cliente,
                    nombre,
                    telefono
                FROM wsp_destinatarios_
                WHERE campana_id = ?
                  AND enviado   = 0
                  AND (error IS NULL OR error = '')
                ORDER BY id ASC
                LIMIT ?
            ");
            $stmt->bind_param('ii', $campana['id'], $LIMITE_DESTINATARIOS);
            $stmt->execute();
            $rDest = $stmt->get_result();

            $destinatarios = [];
            while ($d = $rDest->fetch_assoc()) {
                $destinatarios[] = $d;
            }
            $stmt->close();

            if (!empty($destinatarios)) {
                $campana['destinatarios'] = $destinatarios;
                $campanas[] = $campana;

                // Marcar campaña como "enviando" si aún está en "programada"
                $stmtUpd = $conn->prepare("
                    UPDATE wsp_campanas_ SET estado = 'enviando'
                    WHERE id = ? AND estado = 'programada'
                ");
                $stmtUpd->bind_param('i', $campana['id']);
                $stmtUpd->execute();
                $stmtUpd->close();
            }
        }
    }

    echo json_encode([
        'success' => true,
        'campanas' => $campanas,
        'total' => count($campanas),
        'hora_api' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
