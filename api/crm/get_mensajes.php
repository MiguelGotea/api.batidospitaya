<?php
/**
 * get_mensajes.php — Historial de mensajes de una conversación
 * GET /api/crm/get_mensajes.php?conversation_id=X
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenVPS();

$convId = (int) ($_GET['conversation_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(200, max(20, (int) ($_GET['per_page'] ?? 50)));
$offset = ($page - 1) * $perPage;

if (!$convId)
    respuestaError('conversation_id requerido');

try {
    // Meta de la conversación
    $stmtConv = $conn->prepare("
        SELECT c.*, s.numero_telefono AS numero_remitente_actual
        FROM conversations c
        LEFT JOIN wsp_sesion_vps_ s ON s.instancia = c.instancia
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmtConv->execute([':id' => $convId]);
    $conv = $stmtConv->fetch();

    if (!$conv)
        respuestaError('Conversación no encontrada', 404);

    // Total mensajes
    $total = (int) $conn->prepare("SELECT COUNT(*) FROM messages WHERE conversation_id = :id")
        ->execute([':id' => $convId]) ? 0 : 0;
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM messages WHERE conversation_id = :id");
    $stmtCount->execute([':id' => $convId]);
    $total = (int) $stmtCount->fetchColumn();

    // Mensajes (más recientes primero para paginación, el JS los invertirá)
    $stmt = $conn->prepare("
        SELECT id, direction, sender_type, message_text, message_type,
               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
        FROM messages
        WHERE conversation_id = :id
        ORDER BY id DESC
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':id', $convId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $mensajes = array_reverse($stmt->fetchAll()); // orden cronológico

    echo json_encode([
        'success' => true,
        'conversacion' => $conv,
        'total' => $total,
        'page' => $page,
        'mensajes' => $mensajes
    ]);

} catch (Exception $e) {
    respuestaError('Error: ' . $e->getMessage(), 500);
}
