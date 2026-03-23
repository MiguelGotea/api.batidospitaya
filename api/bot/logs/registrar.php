<?php
/**
 * registrar.php — Guarda un registro de operación en bot_operaciones_log
 *
 * POST { cod_operario, celular, intent, mensaje_entrada, respuesta_bot,
 *         exitoso, error_detalle, duracion_ms }
 *
 * Llamado por: wsp-pitayabot/src/bot/messageHandler.js
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    respuestaError('Body JSON requerido');
}

$codOperario    = isset($input['cod_operario'])    ? (int)$input['cod_operario']    : null;
$celular        = trim($input['celular']        ?? '');
$intent         = trim($input['intent']         ?? 'desconocido');
$mensajeEntrada = trim($input['mensaje_entrada'] ?? '');
$respuestaBot   = trim($input['respuesta_bot']  ?? '');
$exitoso        = isset($input['exitoso'])        ? (int)(bool)$input['exitoso']    : 1;
$errorDetalle   = trim($input['error_detalle']   ?? '') ?: null;
$duracionMs     = isset($input['duracion_ms'])    ? (int)$input['duracion_ms']      : null;

try {
    $stmt = $conn->prepare("
        INSERT INTO bot_operaciones_log
            (cod_operario, celular, intent, mensaje_entrada, respuesta_bot,
             exitoso, error_detalle, duracion_ms, creado_en)
        VALUES
            (:cod_operario, :celular, :intent, :mensaje_entrada, :respuesta_bot,
             :exitoso, :error_detalle, :duracion_ms, NOW())
    ");
    $stmt->execute([
        ':cod_operario'    => $codOperario,
        ':celular'         => $celular,
        ':intent'          => $intent,
        ':mensaje_entrada' => mb_substr($mensajeEntrada, 0, 2000),
        ':respuesta_bot'   => mb_substr($respuestaBot,   0, 2000),
        ':exitoso'         => $exitoso,
        ':error_detalle'   => $errorDetalle ? mb_substr($errorDetalle, 0, 1000) : null,
        ':duracion_ms'     => $duracionMs
    ]);

    respuestaOk(['log_id' => (int)$conn->lastInsertId()]);

} catch (Exception $e) {
    error_log('Error registrar.php (bot logs): ' . $e->getMessage());
    respuestaError('Error guardando log', 500);
}
