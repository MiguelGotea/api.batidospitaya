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

    respuestaOk(['mensaje' => 'Resultado registrado correctamente']);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
