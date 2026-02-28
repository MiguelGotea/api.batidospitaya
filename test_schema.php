<?php
require 'core/database/conexion.php';
header('Content-Type: text/plain');

try {
    echo "Testing queries...\n";

    // 1. Obtener número remitente actual de esta instancia
    $stmtSesion = $conn->prepare("SELECT numero_telefono FROM wsp_sesion_vps_ WHERE instancia = :inst LIMIT 1");
    $stmtSesion->execute([':inst' => 'wsp-crmbot']);
    $sesion = $stmtSesion->fetch();
    echo "1. session fetched\n";

    // 2. Buscar o crear conversación
    $stmtConv = $conn->prepare("SELECT id, status, last_intent FROM conversations WHERE instancia = :inst AND numero_cliente = :nc LIMIT 1");
    $stmtConv->execute([':inst' => 'wsp-crmbot', ':nc' => '50557416019test']);
    $conv = $stmtConv->fetch();
    echo "2. search conversation done\n";

    $stmtInsert = $conn->prepare("
        INSERT INTO conversations
            (instancia, numero_cliente, numero_remitente, status, last_intent, last_interaction_at, created_at, updated_at)
        VALUES
            (:inst, :nc, :nr, 'bot', NULL,
             CONVERT_TZ(NOW(),'+00:00','-06:00'),
             CONVERT_TZ(NOW(),'+00:00','-06:00'),
             CONVERT_TZ(NOW(),'+00:00','-06:00'))
    ");
    $stmtInsert->execute([':inst' => 'wsp-crmbot', ':nc' => '50557416019test', ':nr' => '0000']);
    $convId = $conn->lastInsertId();
    echo "3. insert conversation done (id: $convId)\n";

    // 3. Guardar mensaje entrante
    $stmtMsg = $conn->prepare("
        INSERT INTO messages (conversation_id, direction, sender_type, message_text, message_type, created_at)
        VALUES (:cid, 'in', 'user', :txt, :tipo, CONVERT_TZ(NOW(),'+00:00','-06:00'))
    ");
    $stmtMsg->execute([':cid' => $convId, ':txt' => 'hola test', ':tipo' => 'text']);
    echo "4. insert message done\n";

    // 4. Intent embeddings and bot intents SQL
    $stmt = $conn->prepare("
        SELECT id, intent_name, keywords, response_templates, priority
        FROM bot_intents
        WHERE is_active = 1 AND (instancia IS NULL OR instancia = :inst)
        ORDER BY priority DESC
    ");
    $stmt->execute([':inst' => 'wsp-crmbot']);
    $intents = $stmt->fetchAll();
    echo "5. fetch bot_intents done (" . count($intents) . ")\n";

    $stmtEmb = $conn->prepare("
        SELECT ie.intent_id, ie.term, ie.tfidf_weight, bi.intent_name
        FROM intent_embeddings ie
        JOIN bot_intents bi ON bi.id = ie.intent_id
        WHERE bi.is_active = 1 AND (bi.instancia IS NULL OR bi.instancia = :inst)
    ");
    $stmtEmb->execute([':inst' => 'wsp-crmbot']);
    $embRows = $stmtEmb->fetchAll();
    echo "6. fetch intent_embeddings done (" . count($embRows) . ")\n";

    // Clean up
    $conn->prepare("DELETE FROM messages WHERE conversation_id = :id")->execute([':id' => $convId]);
    $conn->prepare("DELETE FROM conversations WHERE id = :id")->execute([':id' => $convId]);
    echo "7. cleanup done\n";

    echo "\nAll OK!";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
