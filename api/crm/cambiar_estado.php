<?php
/**
 * cambiar_estado.php — Cambia status de conversación (bot ↔ humano)
 * POST /api/crm/cambiar_estado.php
 * Requiere: Header X-WSP-Token
 *
 * Body: { conversation_id, nuevo_status }
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenVPS();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$convId = (int) ($body['conversation_id'] ?? 0);
$nuevo = $body['nuevo_status'] ?? '';

if (!$convId || !in_array($nuevo, ['bot', 'humano'])) {
    respuestaError('Parámetros inválidos: conversation_id y nuevo_status requeridos');
}

try {
    $stmt = $conn->prepare("
        UPDATE conversations
        SET status = :status, updated_at = CONVERT_TZ(NOW(),'+00:00','-06:00')
        WHERE id = :id
    ");
    $stmt->execute([':status' => $nuevo, ':id' => $convId]);

    if ($stmt->rowCount() === 0)
        respuestaError('Conversación no encontrada', 404);

    respuestaOk(['conversation_id' => $convId, 'nuevo_status' => $nuevo]);

} catch (Exception $e) {
    respuestaError('Error: ' . $e->getMessage(), 500);
}
