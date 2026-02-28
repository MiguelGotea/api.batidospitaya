<?php
/**
 * enviar_manual.php — El agente humano envía un mensaje manual desde el ERP
 * POST /api/crm/enviar_manual.php
 * Requiere: Header X-WSP-Token
 *
 * Body: { conversation_id, texto, agente_id }
 * → Guarda en messages
 * → Notifica al VPS local (Express en 127.0.0.1:PORT) para que envíe el WA real
 *
 * NOTA: Este endpoint solo guarda. El ERP debe llamar también al VPS directamente
 * o el VPS puede hacer polling. Para esta versión: guarda + devuelve ok.
 * El outbound real se hará vía endpoint /send del Express del VPS (por nginx proxy).
 */

require_once __DIR__ . '/../wsp/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenVPS();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$convId = (int) ($body['conversation_id'] ?? 0);
$texto = trim($body['texto'] ?? '');
$agente = (int) ($body['agente_id'] ?? 0);

if (!$convId || !$texto)
    respuestaError('conversation_id y texto son requeridos');

try {
    // Obtener datos de la conversación para saber a quién enviar
    $stmtConv = $conn->prepare("
        SELECT c.instancia, c.numero_cliente, s.ip_vps,
               IFNULL(s.estado, 'desconectado') AS estado_vps
        FROM conversations c
        LEFT JOIN wsp_sesion_vps_ s ON s.instancia = c.instancia
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmtConv->execute([':id' => $convId]);
    $conv = $stmtConv->fetch();

    if (!$conv)
        respuestaError('Conversación no encontrada', 404);

    // Guardar el mensaje del agente en el historial
    $stmtMsg = $conn->prepare("
        INSERT INTO messages (conversation_id, direction, sender_type, message_text, message_type, created_at)
        VALUES (:cid, 'out', 'agent', :txt, 'text', CONVERT_TZ(NOW(),'+00:00','-06:00'))
    ");
    $stmtMsg->execute([':cid' => $convId, ':txt' => $texto]);
    $msgId = $conn->lastInsertId();

    // Actualizar conversación
    $conn->prepare("UPDATE conversations SET last_interaction_at = CONVERT_TZ(NOW(),'+00:00','-06:00'), updated_at = CONVERT_TZ(NOW(),'+00:00','-06:00') WHERE id = :id")
        ->execute([':id' => $convId]);

    // Intentar notificar al VPS para envío inmediato (best-effort)
    $enviado = false;
    $instanciaPorts = [
        'wsp-clientes' => 3001,
        'wsp-rrhh' => 3002,
        'wsp-crmbot' => 3003
    ];
    $port = $instanciaPorts[$conv['instancia']] ?? 3001;

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode([
                'numero' => $conv['numero_cliente'],
                'texto' => $texto
            ]),
            'timeout' => 5
        ]
    ]);

    try {
        $vpsResp = @file_get_contents("http://127.0.0.1:{$port}/send", false, $ctx);
        $enviado = $vpsResp !== false;
    } catch (Exception $vpsErr) {
        // El VPS no respondió — el mensaje quedó guardado igual
    }

    respuestaOk([
        'message_id' => $msgId,
        'enviado_via_vps' => $enviado,
        'nota' => $enviado
            ? 'Mensaje enviado y guardado'
            : 'Guardado. VPS respondió sin disponibilidad inmediata — el mensaje quedó en historial'
    ]);

} catch (Exception $e) {
    respuestaError('Error: ' . $e->getMessage(), 500);
}
