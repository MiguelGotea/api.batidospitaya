<?php
require_once __DIR__ . '/api/crm/motor_bot.php';
require_once __DIR__ . '/core/database/conexion.php';

echo "Regenerando embeddings para todos los intents...\n";

// Aumentar prioridad de sucursales a 12 (para que le ganen a "sucursales" general que tiene 10)
$conn->query("UPDATE bot_intents SET priority = 12 WHERE intent_name LIKE 'dir_%'");
echo "Prioridad de sucursales (dir_%) aumentada a 12.\n";

$stmt = $conn->query("SELECT * FROM bot_intents");
$intents = $stmt->fetchAll();

foreach ($intents as $intent) {
    $id = $intent['id'];
    $nombre = $intent['intent_name'];
    $keywords = $intent['keywords'];
    $templates = json_decode($intent['response_templates'], true) ?? [];

    // Función extract from crm_bot_guardar_intent
    $conn->prepare("DELETE FROM intent_embeddings WHERE intent_id = :id")->execute([':id' => $id]);

    $corpus = implode(' ', array_merge([$keywords], $templates));
    $txt = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $corpus));
    $txt = preg_replace('/[^a-z0-9\s]/', ' ', $txt);
    $tokens = array_filter(explode(' ', $txt), fn($t) => strlen($t) > 2);

    static $stopwords = ['de', 'la', 'el', 'en', 'y', 'a', 'que', 'es', 'no', 'lo', 'un', 'una', 'me', 'te', 'se', 'su', 'al', 'le', 'para', 'con', 'por', 'los', 'las'];
    $tokens = array_values(array_filter($tokens, fn($t) => !in_array($t, $stopwords)));

    if (empty($tokens))
        continue;

    $tf = array_count_values($tokens);
    $total = count($tokens);
    $vector = [];
    $mag = 0.0;

    foreach ($tf as $term => $count) {
        $vector[$term] = $count / $total;
        $mag += $vector[$term] ** 2;
    }
    $mag = sqrt($mag) ?: 1.0;

    $stmtEmb = $conn->prepare("INSERT INTO intent_embeddings (intent_id, term, tfidf_weight) VALUES (:iid, :term, :w)");
    foreach ($vector as $term => $w) {
        $stmtEmb->execute([':iid' => $id, ':term' => $term, ':w' => round($w / $mag, 6)]);
    }

    echo "✔ Embeddings regenerados para ID $id ($nombre)\n";
}

echo "\n¡Todo listo! Ya puedes probar el bot.";
