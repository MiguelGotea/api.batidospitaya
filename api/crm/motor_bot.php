<?php
/**
 * motor_bot.php — Motor de intenciones híbrido (4 niveles)
 * Incluido por recibir_mensaje.php
 *
 * Niveles:
 *   1 — Contexto (last_intent + mensaje corto)
 *   2 — Keywords directo (coincidencia en texto normalizado)
 *   3 — Similitud coseno con intent_embeddings (TF-IDF precalculado por Node.js)
 *   4 — Fallback "no_entiendo"
 *
 * Devuelve: procesarIntent($conn, $instancia, $texto, $lastIntent, $numero_cliente)
 *           → ['intent_name', 'nivel', 'respuesta', 'confidence']
 */

/**
 * Normaliza texto: minúsculas, sin acentos, sin puntuación
 */
function normalizarTexto(string $txt): string
{
    $txt = mb_strtolower($txt, 'UTF-8');
    $txt = iconv('UTF-8', 'ASCII//TRANSLIT', $txt);
    $txt = preg_replace('/[^a-z0-9\s]/', ' ', $txt);
    return trim(preg_replace('/\s+/', ' ', $txt));
}

/**
 * Tokeniza y elimina stopwords
 */
function tokenizar(string $txt): array
{
    static $stopwords = [
    'de',
    'la',
    'el',
    'en',
    'y',
    'a',
    'que',
    'es',
    'no',
    'lo',
    'un',
    'una',
    'me',
    'te',
    'se',
    'su',
    'al',
    'le',
    'para',
    'con',
    'por',
    'los',
    'las',
    'del',
    'mas',
    'pero',
    'si',
    'ya',
    'mi',
    'fue',
    'muy',
    'son',
    'hay',
    'tambien',
    'como',
    'este',
    'estos',
    'esta',
    'estas',
    'todo',
    'ser',
    'tiene',
    'puede',
    'hacer',
    'quiero',
    'necesito',
    'favor',
    'hola',
    'hey',
    'ok',
    'si',
    'gracias'
    ];
    $tokens = explode(' ', normalizarTexto($txt));
    return array_filter($tokens, fn($t) => strlen($t) > 2 && !in_array($t, $stopwords));
}

/**
 * Calcula similitud coseno entre dos vectores sparse (arrays asociativos)
 */
function cosenoCrm(array $v1, array $v2): float
{
    $dot = 0.0;
    foreach ($v1 as $term => $w) {
        if (isset($v2[$term]))
            $dot += $w * $v2[$term];
    }
    return $dot; // vectores ya normalizados
}

/**
 * Genera vector TF-IDF simple de un texto (para mensaje entrante)
 */
function vectorizarTexto(string $txt): array
{
    $tokens = tokenizar($txt);
    if (empty($tokens))
        return [];
    $tf = array_count_values($tokens);
    $total = count($tokens);
    $vector = [];
    $mag = 0.0;
    foreach ($tf as $term => $count) {
        $vector[$term] = $count / $total;
        $mag += $vector[$term] ** 2;
    }
    $mag = sqrt($mag) ?: 1.0;
    foreach ($vector as &$w)
        $w /= $mag;
    return $vector;
}

/**
 * Obtiene nombre del cliente desde clientesclub
 */
function obtenerNombreCliente(PDO $conn, string $numero): string
{
    try {
        $stmt = $conn->prepare("
            SELECT Nombre FROM clientesclub
            WHERE REPLACE(REPLACE(REPLACE(Telefono, '-', ''), ' ', ''), '+', '') LIKE :num
               OR REPLACE(REPLACE(REPLACE(Telefono2, '-', ''), ' ', ''), '+', '') LIKE :num
            LIMIT 1
        ");
        $stmt->execute([':num' => '%' . substr($numero, -8)]);
        $row = $stmt->fetch();
        if ($row)
            return ucfirst(strtolower(explode(' ', $row['Nombre'])[0]));
    } catch (Exception $e) { /* silencioso */
    }
    return 'amigo/a';
}

/**
 * Motor principal de intenciones
 */
function procesarIntent(PDO $conn, string $instancia, string $texto, ?string $lastIntent, string $numCliente): array
{
    $textoNorm = normalizarTexto($texto);
    $tokens = tokenizar($texto);

    // Cargar intenciones activas (filtrar por instancia o globales)
    $stmt = $conn->prepare("
        SELECT id, intent_name, keywords, response_templates, priority, media_url
        FROM bot_intents
        WHERE is_active = 1 AND (instancia IS NULL OR instancia = :inst)
        ORDER BY priority DESC
    ");
    $stmt->execute([':inst' => $instancia]);
    $intents = $stmt->fetchAll();

    if (empty($intents)) {
        return fallback('No hay intenciones configuradas', 4, $conn, $numCliente);
    }

    // Separar intent "no_entiendo" y "humano" del resto
    $intentsNormales = array_filter($intents, fn($i) => !in_array($i['intent_name'], ['no_entiendo', 'humano']));

    // ── NIVEL 1: CONTEXTO ──────────────────────────────────────────────
    // Si el mensaje es muy corto y hay un lastIntent válido → mantener contexto
    if ($lastIntent && mb_strlen($texto) < 15 && !empty($tokens)) {
        $intentContexto = array_filter($intents, fn($i) => $i['intent_name'] === $lastIntent);
        if ($intentContexto) {
            $intent = reset($intentContexto);
            return construirRespuesta($conn, $intent, $numCliente, 1, 0.95);
        }
    }

    // ── NIVEL 2: KEYWORDS DIRECTAS (alta confianza) ───────────────────
    $mejorKeywords = null;
    $mejorScore = 0;

    foreach ($intentsNormales as $intent) {
        if (!$intent['keywords'])
            continue;
        $kws = array_map('normalizarTexto', array_map('trim', explode(',', $intent['keywords'])));
        $score = 0;
        foreach ($kws as $kw) {
            if (!$kw)
                continue;
            if (str_contains($textoNorm, $kw)) {
                // Coincidencia exacta de frase → peso alto
                $score += strlen($kw) > 4 ? $intent['priority'] * 3 : $intent['priority'];
            }
        }
        if ($score > $mejorScore) {
            $mejorScore = $score;
            $mejorKeywords = $intent;
        }
    }

    // Umbral: si hay coincidencia clara → usar Nivel 2
    if ($mejorKeywords && $mejorScore >= $mejorKeywords['priority'] * 2) {
        return construirRespuesta($conn, $mejorKeywords, $numCliente, 2, min(0.95, $mejorScore / 100));
    }

    // ── NIVEL 3: SIMILITUD COSENO (embeddings TF-IDF) ─────────────────
    $msgVector = vectorizarTexto($texto);
    if (!empty($msgVector)) {
        // Cargar vectores precalculados por intent
        $stmtEmb = $conn->prepare("
            SELECT ie.intent_id, ie.term, ie.tfidf_weight, bi.intent_name
            FROM intent_embeddings ie
            JOIN bot_intents bi ON bi.id = ie.intent_id
            WHERE bi.is_active = 1 AND (bi.instancia IS NULL OR bi.instancia = :inst)
        ");
        $stmtEmb->execute([':inst' => $instancia]);
        $embRows = $stmtEmb->fetchAll();

        // Reconstruir vectores por intent_id
        $vectoresPorIntent = [];
        foreach ($embRows as $row) {
            $vectoresPorIntent[$row['intent_id']]['intent_name'] = $row['intent_name'];
            $vectoresPorIntent[$row['intent_id']]['vector'][$row['term']] = (float) $row['tfidf_weight'];
        }

        $mejorSim = 0;
        $mejorIntEmb = null;
        foreach ($vectoresPorIntent as $iid => $embData) {
            $sim = cosenoCrm($msgVector, $embData['vector']);
            if ($sim > $mejorSim) {
                $mejorSim = $sim;
                $mejorIntEmb = $embData['intent_name'];
            }
        }

        if ($mejorIntEmb && $mejorSim > 0.80) {
            $intentEmb = array_filter($intents, fn($i) => $i['intent_name'] === $mejorIntEmb);
            if ($intentEmb) {
                return construirRespuesta($conn, reset($intentEmb), $numCliente, 3, $mejorSim);
            }
        }
    }

    // ── NIVEL 4: FALLBACK KEYWORDS + no_entiendo ──────────────────────
    // Usar el mejor score de keywords aunque sea bajo
    if ($mejorKeywords && $mejorScore > 0) {
        return construirRespuesta($conn, $mejorKeywords, $numCliente, 4, 0.5);
    }

    // Respuesta "no_entiendo"
    $noEnt = array_values(array_filter($intents, fn($i) => $i['intent_name'] === 'no_entiendo'));
    if ($noEnt) {
        return construirRespuesta($conn, $noEnt[0], $numCliente, 4, 0.0);
    }

    return fallback('Sin respuesta disponible', 4, $conn, $numCliente);
}

/**
 * Construye la respuesta eligiendo un template aleatorio e insertando {{nombre}}
 */
function construirRespuesta(PDO $conn, array $intent, string $numCliente, int $nivel, float $conf): array
{
    $templates = json_decode($intent['response_templates'], true) ?? ['Entendido.'];
    $template = $templates[array_rand($templates)];

    // Sustituir {{nombre}}
    $nombre = obtenerNombreCliente($conn, $numCliente);
    $respuesta = str_replace('{{nombre}}', $nombre, $template);

    return [
        'intent_name' => $intent['intent_name'],
        'nivel' => $nivel,
        'confidence' => $conf,
        'respuesta' => $respuesta,
        'media_url' => $intent['media_url'] ?? null
    ];
}

function fallback(string $msg, int $nivel, PDO $conn, string $numCliente): array
{
    return [
        'intent_name' => 'no_entiendo',
        'nivel' => $nivel,
        'confidence' => 0.0,
        'respuesta' => '¿En qué te puedo ayudar? 😊',
        'media_url' => null
    ];
}
