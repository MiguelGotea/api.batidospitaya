<?php
/**
 * recibir_mensaje.php — Punto de entrada del motor CRM Bot
 * POST /api/crm/recibir_mensaje.php
 * Requiere: Header X-WSP-Token
 *
 * Body:
 *   instancia       — wsp-crmbot, wsp-clientes...
 *   numero_cliente  — número del cliente que escribió
 *   texto           — contenido del mensaje
 *   tipo            — text | image | audio | ...
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    respuestaError('Método no permitido', 405);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$instancia = trim($body['instancia'] ?? '');
$numCliente = preg_replace('/\D/', '', $body['numero_cliente'] ?? '');
$texto = trim($body['texto'] ?? '');
$tipo = $body['tipo'] ?? 'text';

if (!$instancia || !$numCliente)
    respuestaError('Faltan campos: instancia, numero_cliente');

try {
    $ahora = 'CONVERT_TZ(NOW(),\'+00:00\',\'-06:00\')';

    // 1. Obtener número remitente actual de esta instancia
    $stmtSesion = $conn->prepare("
        SELECT numero_telefono FROM wsp_sesion_vps_ WHERE instancia = :inst LIMIT 1
    ");
    $stmtSesion->execute([':inst' => $instancia]);
    $sesion = $stmtSesion->fetch();
    $numRemitente = $sesion['numero_telefono'] ?? '0';

    // 2. Buscar o crear conversación (clave: instancia + numero_cliente)
    $stmtConv = $conn->prepare("
        SELECT id, status, last_intent FROM conversations
        WHERE instancia = :inst AND numero_cliente = :nc
        LIMIT 1
    ");
    $stmtConv->execute([':inst' => $instancia, ':nc' => $numCliente]);
    $conv = $stmtConv->fetch();

    if (!$conv) {
        $stmtInsert = $conn->prepare("
            INSERT INTO conversations
                (instancia, numero_cliente, numero_remitente, status, last_intent, last_interaction_at, created_at, updated_at)
            VALUES
                (:inst, :nc, :nr, 'bot', NULL,
                 CONVERT_TZ(NOW(),'+00:00','-06:00'),
                 CONVERT_TZ(NOW(),'+00:00','-06:00'),
                 CONVERT_TZ(NOW(),'+00:00','-06:00'))
        ");
        $stmtInsert->execute([':inst' => $instancia, ':nc' => $numCliente, ':nr' => $numRemitente]);
        $convId = $conn->lastInsertId();
        $convStatus = 'bot';
        $lastIntent = null;
    } else {
        $convId = $conv['id'];
        $convStatus = $conv['status'];
        $lastIntent = $conv['last_intent'];
    }

    // 3. Guardar mensaje entrante
    $stmtMsg = $conn->prepare("
        INSERT INTO messages (conversation_id, direction, sender_type, message_text, message_type, created_at)
        VALUES (:cid, 'in', 'user', :txt, :tipo, CONVERT_TZ(NOW(),'+00:00','-06:00'))
    ");
    $stmtMsg->execute([':cid' => $convId, ':txt' => $texto ?: '[media]', ':tipo' => $tipo]);

    // 4. Si el agente tomó control → no responder con bot
    if ($convStatus === 'humano') {
        echo json_encode(['responder' => false, 'razon' => 'En control humano']);
        exit;
    }

    // 5. Detectar switch a humano por keywords universales
    $triggerHumano = ['asesor', 'humano', 'agente', 'soporte real', 'hablar con alguien', 'persona real'];
    $textoNorm = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $texto));
    $esSwitch = false;
    foreach ($triggerHumano as $trigger) {
        if (str_contains($textoNorm, $trigger)) {
            $esSwitch = true;
            break;
        }
    }

    // 6. Motor de intenciones
    require_once __DIR__ . '/motor_bot.php';
    $resultado = procesarIntent($conn, $instancia, $texto, $lastIntent, $numCliente);

    if ($esSwitch) {
        $resultado['intent_name'] = 'humano';
    }

    // 7. Si la intención es "humano" → cambiar status
    if ($resultado['intent_name'] === 'humano') {
        $conn->prepare("UPDATE conversations SET status='humano', updated_at=CONVERT_TZ(NOW(),'+00:00','-06:00') WHERE id=:id")
            ->execute([':id' => $convId]);
    }

    // 8. Obtener respuesta humanizada
    $respuesta = $resultado['respuesta'];

    // 9. Guardar respuesta del bot
    if ($respuesta) {
        $stmtBotMsg = $conn->prepare("
            INSERT INTO messages (conversation_id, direction, sender_type, message_text, message_type, created_at)
            VALUES (:cid, 'out', 'bot', :txt, 'text', CONVERT_TZ(NOW(),'+00:00','-06:00'))
        ");
        $stmtBotMsg->execute([':cid' => $convId, ':txt' => $respuesta]);
    }

    // 10. Actualizar conversación con último intent e interacción
    $stmtUpdConv = $conn->prepare("
        UPDATE conversations
        SET last_intent         = :li,
            last_interaction_at = CONVERT_TZ(NOW(),'+00:00','-06:00'),
            updated_at          = CONVERT_TZ(NOW(),'+00:00','-06:00')
        WHERE id = :id
    ");
    $stmtUpdConv->execute([':li' => $resultado['intent_name'], ':id' => $convId]);

    echo json_encode([
        'responder' => (bool) $respuesta,
        'texto_respuesta' => $respuesta,
        'intent' => $resultado['intent_name'],
        'nivel' => $resultado['nivel'] ?? 4,
        'conv_id' => $convId,
        'nuevo_status' => $resultado['intent_name'] === 'humano' ? 'humano' : $convStatus
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
