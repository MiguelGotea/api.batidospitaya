<?php
/**
 * borrar.php — Elimina el estado de confirmación pendiente de un celular
 *
 * POST { celular }
 * Se llama cuando el usuario confirma o cancela la acción.
 *
 * Llamado por: wsp-pitayabot/src/bot/confirmManager.js
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido', 405);
}

$input   = json_decode(file_get_contents('php://input'), true);
$celular = trim($input['celular'] ?? '');
$celular = preg_replace('/\D/', '', $celular);

if (empty($celular)) {
    respuestaError('celular es requerido');
}

try {
    $stmt = $conn->prepare("DELETE FROM bot_estado_confirmacion WHERE celular = :celular");
    $stmt->execute([':celular' => $celular]);

    respuestaOk(['eliminados' => $stmt->rowCount()]);

} catch (Exception $e) {
    error_log('Error borrar confirmacion: ' . $e->getMessage());
    respuestaError('Error eliminando estado de confirmación', 500);
}
