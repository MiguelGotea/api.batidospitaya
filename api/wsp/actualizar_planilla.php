<?php
/**
 * actualizar_planilla.php — El VPS reporta el resultado de cada mensaje de planilla
 * POST /api/wsp/actualizar_planilla.php
 * Requiere: Header X-WSP-Token
 *
 * Body: { campana_id, destinatario_id, resultado, detalle }
 *   campana_id      → id de wsp_planilla_programaciones_
 *   destinatario_id → id de wsp_planilla_destinatarios_
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    respuestaError('Método no permitido', 405);

$body = json_decode(file_get_contents('php://input'), true);
$programacionId = (int) ($body['campana_id'] ?? 0);
$destinatarioId = (int) ($body['destinatario_id'] ?? 0);
$resultado = $body['resultado'] ?? '';
$detalle = $body['detalle'] ?? null;

if (!$programacionId || !$destinatarioId || !in_array($resultado, ['exito', 'error'])) {
    respuestaError('Parámetros inválidos: campana_id, destinatario_id y resultado son requeridos');
}

try {
    $errorGuardar = ($resultado === 'error') ? $detalle : null;

    // ── Actualizar registro de destinatario ──
    $stmtDest = $conn->prepare("
        UPDATE wsp_planilla_destinatarios_
        SET enviado     = 1,
            error       = :err,
            fecha_envio = CONVERT_TZ(NOW(),'+00:00','-06:00')
        WHERE id = :id AND programacion_id = :pid
    ");
    $stmtDest->execute([
        ':err' => $errorGuardar,
        ':id' => $destinatarioId,
        ':pid' => $programacionId
    ]);

    // ── Recalcular contadores de la programación ──
    $stmtCount = $conn->prepare("
        UPDATE wsp_planilla_programaciones_ p
        SET
            total_enviados = (
                SELECT COUNT(*) FROM wsp_planilla_destinatarios_
                WHERE programacion_id = :pid1
                  AND enviado = 1 AND (error IS NULL OR error = '')
            ),
            total_errores = (
                SELECT COUNT(*) FROM wsp_planilla_destinatarios_
                WHERE programacion_id = :pid2
                  AND enviado = 1 AND error IS NOT NULL AND error != ''
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
        SELECT COUNT(*) AS total, SUM(enviado) AS enviados
        FROM wsp_planilla_destinatarios_
        WHERE programacion_id = :pid
    ");
    $stmtCheck->execute([':pid' => $programacionId]);
    $check = $stmtCheck->fetch();

    if ((int) $check['total'] > 0 && (int) $check['total'] === (int) $check['enviados']) {
        $stmtFin = $conn->prepare("
            UPDATE wsp_planilla_programaciones_
            SET estado = 'completada'
            WHERE id = :id AND estado = 'enviando'
        ");
        $stmtFin->execute([':id' => $programacionId]);
    }

    respuestaOk(['mensaje' => 'Resultado de planilla registrado correctamente']);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
