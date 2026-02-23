<?php
/**
 * actualizar.php — El VPS reporta el resultado de cada mensaje enviado
 * POST /api/wsp/actualizar.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    respuestaError('Método no permitido', 405);

$body = json_decode(file_get_contents('php://input'), true);
$campanaId = (int) ($body['campana_id'] ?? 0);
$destinatarioId = (int) ($body['destinatario_id'] ?? 0);
$resultado = $body['resultado'] ?? '';
$detalle = $body['detalle'] ?? null;

if (!$campanaId || !$destinatarioId || !in_array($resultado, ['exito', 'error'])) {
    respuestaError('Parámetros inválidos: campana_id, destinatario_id y resultado son requeridos');
}

try {
    $errorGuardar = ($resultado === 'error') ? $detalle : null;

    // Actualizar destinatario
    $stmtDest = $conn->prepare("
        UPDATE wsp_destinatarios_
        SET enviado     = 1,
            error       = :err,
            fecha_envio = CONVERT_TZ(NOW(),'+00:00','-06:00')
        WHERE id = :id AND campana_id = :cid
    ");
    $stmtDest->execute([
        ':err' => $errorGuardar,
        ':id' => $destinatarioId,
        ':cid' => $campanaId
    ]);

    // Insertar en log
    $tipo = ($resultado === 'exito') ? 'exito' : 'error';
    $stmtLog = $conn->prepare("
        INSERT INTO wsp_logs_ (campana_id, destinatario_id, tipo, detalle, fecha)
        VALUES (:cid, :did, :tipo, :det, CONVERT_TZ(NOW(),'+00:00','-06:00'))
    ");
    $stmtLog->execute([
        ':cid' => $campanaId,
        ':did' => $destinatarioId,
        ':tipo' => $tipo,
        ':det' => $detalle
    ]);

    // Recalcular contadores de la campaña
    $stmtCount = $conn->prepare("
        UPDATE wsp_campanas_ c
        SET
            total_enviados = (
                SELECT COUNT(*) FROM wsp_destinatarios_
                WHERE campana_id = :cid1
                  AND enviado = 1 AND (error IS NULL OR error = '')
            ),
            total_errores = (
                SELECT COUNT(*) FROM wsp_destinatarios_
                WHERE campana_id = :cid2
                  AND enviado = 1 AND error IS NOT NULL AND error != ''
            )
        WHERE c.id = :cid3
    ");
    $stmtCount->execute([':cid1' => $campanaId, ':cid2' => $campanaId, ':cid3' => $campanaId]);

    // Verificar si la campaña está completamente enviada
    $stmtCheck = $conn->prepare("
        SELECT COUNT(*) AS total, SUM(enviado) AS enviados
        FROM wsp_destinatarios_
        WHERE campana_id = :cid
    ");
    $stmtCheck->execute([':cid' => $campanaId]);
    $check = $stmtCheck->fetch();

    if ((int) $check['total'] > 0 && (int) $check['total'] === (int) $check['enviados']) {
        $stmtFin = $conn->prepare("
            UPDATE wsp_campanas_
            SET estado = 'completada'
            WHERE id = :id AND estado = 'enviando'
        ");
        $stmtFin->execute([':id' => $campanaId]);
    }


    // ── CRM History: guardar mensaje de campaña en historial unificado ──
    if ($resultado === 'exito') {
        try {
            // Obtener número del destinatario y el mensaje de la campaña
            $stmtDatos = $conn->prepare("
                SELECT d.telefono, c.mensaje, c.instancia
                FROM wsp_destinatarios_ d
                JOIN wsp_campanas_ c ON c.id = d.campana_id
                WHERE d.id = :did AND d.campana_id = :cid
                LIMIT 1
            ");
            $stmtDatos->execute([':did' => $destinatarioId, ':cid' => $campanaId]);
            $datos = $stmtDatos->fetch();

            if ($datos) {
                $numCliente = preg_replace('/\D/', '', $datos['telefono']);
                $instancia = $datos['instancia'] ?? 'wsp-clientes';

                // Buscar o crear conversación para este número en esa instancia
                $stmtConv = $conn->prepare("
                    SELECT id FROM conversations
                    WHERE instancia = :inst AND numero_cliente = :nc
                    LIMIT 1
                ");
                $stmtConv->execute([':inst' => $instancia, ':nc' => $numCliente]);
                $conv = $stmtConv->fetch();

                if (!$conv) {
                    // Obtener número remitente de la instancia
                    $sesion = $conn->prepare("SELECT numero_telefono FROM wsp_sesion_vps_ WHERE instancia = :i LIMIT 1");
                    $sesion->execute([':i' => $instancia]);
                    $numRem = $sesion->fetchColumn() ?: '0';

                    $stmtCrConv = $conn->prepare("
                        INSERT INTO conversations
                            (instancia, numero_cliente, numero_remitente, status, last_interaction_at, created_at, updated_at)
                        VALUES
                            (:inst, :nc, :nr, 'bot',
                             CONVERT_TZ(NOW(),'+00:00','-06:00'),
                             CONVERT_TZ(NOW(),'+00:00','-06:00'),
                             CONVERT_TZ(NOW(),'+00:00','-06:00'))
                    ");
                    $stmtCrConv->execute([':inst' => $instancia, ':nc' => $numCliente, ':nr' => $numRem]);
                    $convId = $conn->lastInsertId();
                } else {
                    $convId = $conv['id'];
                }

                // Insertar mensaje de campaña
                $stmtMsg = $conn->prepare("
                    INSERT INTO messages
                        (conversation_id, direction, sender_type, message_text, message_type, created_at)
                    VALUES
                        (:cid, 'out', 'campaign', :txt, 'text', CONVERT_TZ(NOW(),'+00:00','-06:00'))
                ");
                $stmtMsg->execute([':cid' => $convId, ':txt' => $datos['mensaje']]);

                $conn->prepare("UPDATE conversations SET last_interaction_at = CONVERT_TZ(NOW(),'+00:00','-06:00'), updated_at = CONVERT_TZ(NOW(),'+00:00','-06:00') WHERE id = :id")
                    ->execute([':id' => $convId]);
            }
        } catch (Exception $crmErr) {
            // No interrumpir el flujo normal por errores del CRM
            error_log('CRM history error en actualizar.php: ' . $crmErr->getMessage());
        }
    }

    respuestaOk(['mensaje' => 'Resultado registrado correctamente']);


} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
