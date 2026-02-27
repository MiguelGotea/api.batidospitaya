<?php
/**
 * actualizar_planilla.php — El VPS reporta el resultado de cada mensaje de planilla
 * POST /api/wsp/actualizar_planilla.php
 * Requiere: Header X-WSP-Token
 *
 * Actualiza las columnas wsp_* directamente en BoletaPago.
 * Body: { campana_id, destinatario_id, resultado, detalle }
 *   campana_id      → id de wsp_planilla_programaciones_
 *   destinatario_id → id_boleta de BoletaPago
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    respuestaError('Método no permitido', 405);

$body = json_decode(file_get_contents('php://input'), true);
$programacionId = (int) ($body['campana_id'] ?? 0);
$idBoleta = (int) ($body['destinatario_id'] ?? 0);
$resultado = $body['resultado'] ?? '';
$detalle = $body['detalle'] ?? null;

if (!$programacionId || !$idBoleta || !in_array($resultado, ['exito', 'error'])) {
    respuestaError('Parámetros inválidos: campana_id, destinatario_id y resultado son requeridos');
}

try {
    $errorGuardar = ($resultado === 'error') ? $detalle : null;

    // ── Marcar boleta como enviada en BoletaPago ──
    $stmtBoleta = $conn->prepare("
        UPDATE BoletaPago
        SET wsp_enviado    = 1,
            wsp_error      = :err,
            wsp_fecha_envio = CONVERT_TZ(NOW(),'+00:00','-06:00')
        WHERE id_boleta         = :id
          AND wsp_programacion_id = :pid
    ");
    $stmtBoleta->execute([
        ':err' => $errorGuardar,
        ':id' => $idBoleta,
        ':pid' => $programacionId
    ]);

    // ── Recalcular contadores de la programación ──
    $stmtCount = $conn->prepare("
        UPDATE wsp_planilla_programaciones_ p
        SET
            total_enviados = (
                SELECT COUNT(*) FROM BoletaPago
                WHERE wsp_programacion_id = :pid1
                  AND wsp_enviado = 1
                  AND (wsp_error IS NULL OR wsp_error = '')
            ),
            total_errores = (
                SELECT COUNT(*) FROM BoletaPago
                WHERE wsp_programacion_id = :pid2
                  AND wsp_enviado = 1
                  AND wsp_error IS NOT NULL AND wsp_error != ''
            )
        WHERE p.id = :pid3
    ");
    $stmtCount->execute([
        ':pid1' => $programacionId,
        ':pid2' => $programacionId,
        ':pid3' => $programacionId
    ]);

    // ── Verificar si la programación está completamente enviada ──
    $stmtCheck = $conn->prepare("
        SELECT COUNT(*) AS total, SUM(wsp_enviado) AS enviados
        FROM BoletaPago
        WHERE wsp_programacion_id = :pid
    ");
    $stmtCheck->execute([':pid' => $programacionId]);
    $check = $stmtCheck->fetch();

    if ((int) $check['total'] > 0 && (int) $check['total'] === (int) $check['enviados']) {
        $conn->prepare("
            UPDATE wsp_planilla_programaciones_
            SET estado = 'completada'
            WHERE id = :id AND estado = 'enviando'
        ")->execute([':id' => $programacionId]);
    }

    respuestaOk(['mensaje' => 'Resultado de planilla registrado correctamente']);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
