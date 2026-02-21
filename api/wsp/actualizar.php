<?php
/**
 * actualizar.php — El VPS reporta el resultado de cada mensaje enviado
 * POST /api/wsp/actualizar.php
 * Requiere: Header X-WSP-Token
 *
 * Body JSON:
 *  {
 *    "campana_id": 1,
 *    "destinatario_id": 5,
 *    "resultado": "exito" | "error",
 *    "detalle": "mensaje opcional de error"
 *  }
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
    $ahora = "CONVERT_TZ(NOW(),'+00:00','-06:00')";

    // Actualizar destinatario
    $stmt = $conn->prepare("
        UPDATE wsp_destinatarios_
        SET enviado    = 1,
            error      = ?,
            fecha_envio = CONVERT_TZ(NOW(),'+00:00','-06:00')
        WHERE id = ? AND campana_id = ?
    ");
    $errorGuardar = ($resultado === 'error') ? $detalle : null;
    $stmt->bind_param('sii', $errorGuardar, $destinatarioId, $campanaId);
    $stmt->execute();
    $stmt->close();

    // Registrar en log
    $tipo = ($resultado === 'exito') ? 'exito' : 'error';
    $stmt2 = $conn->prepare("
        INSERT INTO wsp_logs_ (campana_id, destinatario_id, tipo, detalle, fecha)
        VALUES (?, ?, ?, ?, CONVERT_TZ(NOW(),'+00:00','-06:00'))
    ");
    $stmt2->bind_param('iiss', $campanaId, $destinatarioId, $tipo, $detalle);
    $stmt2->execute();
    $stmt2->close();

    // Recalcular contadores de la campaña
    $stmt3 = $conn->prepare("
        UPDATE wsp_campanas_ c
        SET 
            total_enviados = (
                SELECT COUNT(*) FROM wsp_destinatarios_
                WHERE campana_id = ? AND enviado = 1 AND (error IS NULL OR error = '')
            ),
            total_errores = (
                SELECT COUNT(*) FROM wsp_destinatarios_
                WHERE campana_id = ? AND enviado = 1 AND error IS NOT NULL AND error != ''
            )
        WHERE c.id = ?
    ");
    $stmt3->bind_param('iii', $campanaId, $campanaId, $campanaId);
    $stmt3->execute();
    $stmt3->close();

    // Verificar si la campaña está completamente enviada
    $stmtCheck = $conn->prepare("
        SELECT 
            COUNT(*) total,
            SUM(enviado) enviados
        FROM wsp_destinatarios_
        WHERE campana_id = ?
    ");
    $stmtCheck->bind_param('i', $campanaId);
    $stmtCheck->execute();
    $rCheck = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($rCheck['total'] > 0 && $rCheck['total'] == $rCheck['enviados']) {
        // Todos enviados — marcar como completada
        $stmtFin = $conn->prepare("
            UPDATE wsp_campanas_
            SET estado = 'completada'
            WHERE id = ? AND estado = 'enviando'
        ");
        $stmtFin->bind_param('i', $campanaId);
        $stmtFin->execute();
        $stmtFin->close();
    }

    respuestaOk(['mensaje' => 'Resultado registrado correctamente']);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
