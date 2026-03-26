<?php
/**
 * clasificar.php — Clasifica la intención de un mensaje usando cascada de IA
 *
 * POST: { "mensaje": "crear tarea revisar inventario" }
 *
 * La cascada sigue el mismo orden que AIService.php del ERP:
 * google → openai → deepseek → mistral → cerebras → openrouter → huggingface → groq
 *
 * Retorna:
 * {
 *   "success": true,
 *   "data": {
 *     "intent": "crear_tarea",
 *     "entidades": { ... },
 *     "confianza": 0.98,
 *     "ambiguo": false,
 *     "frase_confirmacion": "Vas a crear la tarea...",
 *     "proveedor_usado": "google"
 *   }
 * }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();
header('Content-Type: application/json; charset=utf-8');

$body    = json_decode(file_get_contents('php://input'), true);
$mensaje = trim($body['mensaje'] ?? '');

if (!$mensaje) {
    respuestaError('El campo "mensaje" es requerido');
}

// ─── Orden de cascada (mismo que el ERP) ─────────────────────────
$cascada = [
    'google', 'openai', 'deepseek', 'mistral',
    'cerebras', 'openrouter', 'huggingface', 'groq'
];

// ─── Config por proveedor ─────────────────────────────────────────
$proveedoresConfig = [
    'google'      => ['tipo' => 'google',      'model' => 'gemini-flash-latest',                  'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/'],
    'openai'      => ['tipo' => 'openai',      'model' => 'gpt-4o-mini',                          'endpoint' => 'https://api.openai.com/v1/chat/completions'],
    'deepseek'    => ['tipo' => 'openai',      'model' => 'deepseek-chat',                        'endpoint' => 'https://api.deepseek.com/chat/completions'],
    'mistral'     => ['tipo' => 'openai',      'model' => 'mistral-medium-latest',                'endpoint' => 'https://api.mistral.ai/v1/chat/completions'],
    'cerebras'    => ['tipo' => 'openai',      'model' => 'llama3.1-70b',                         'endpoint' => 'https://api.cerebras.ai/v1/chat/completions'],
    'openrouter'  => ['tipo' => 'openrouter',  'model' => 'nvidia/nemotron-3-nano-30b-a3b:free',  'endpoint' => 'https://openrouter.ai/api/v1/chat/completions'],
    'huggingface' => ['tipo' => 'openai',      'model' => 'meta-llama/Llama-3.2-3B-Instruct',    'endpoint' => 'https://router.huggingface.co/v1/chat/completions'],
    'groq'        => ['tipo' => 'openai',      'model' => 'llama-3.3-70b-versatile',              'endpoint' => 'https://api.groq.com/openai/v1/chat/completions'],
];

// ─── System prompt del clasificador ──────────────────────────────
function buildSystemPrompt(): string {
    $tz   = new DateTimeZone('America/Managua');
    $dt   = new DateTime('now', $tz);
    $fmt  = new IntlDateFormatter('es_NI', IntlDateFormatter::LONG, IntlDateFormatter::NONE, $tz, null, "EEEE d 'de' MMMM 'de' y");
    $hoy  = $fmt->format($dt);

    return <<<PROMPT
Eres un clasificador de intenciones para un asistente empresarial por WhatsApp.
El usuario trabaja en una empresa y te envía mensajes en español.

Responde ÚNICAMENTE con un objeto JSON válido, sin texto adicional, sin backticks.

Intenciones disponibles:
- crear_tarea
- buscar_tarea
- modificar_tarea_fecha
- finalizar_tarea
- cancelar_tarea
- buscar_tareas_retrasadas
- resumen_tareas_semana
- crear_reunion
- buscar_reunion
- modificar_reunion_fecha
- cancelar_reunion
- resumen_reuniones_semana
- horarios_libres
- crear_nota
- buscar_nota
- crear_nota_decision
- enviar_correo
- buscar_correo
- correos_pendientes
- consulta_libre
- desconocido

Esquema de respuesta:
{
  "intent": "nombre_de_la_intencion",
  "entidades": {
    "titulo": null,
    "descripcion": null,
    "fecha": null,
    "hora": null,
    "participantes": [],
    "prioridad": null,
    "estado_destino": null,
    "contenido": null,
    "destinatario": null,
    "remitente": null,
    "palabras_clave": [],
    "fecha_consulta": null
  },
  "confianza": 0.95,
  "ambiguo": false,
  "frase_confirmacion": "Texto en español resumiendo la acción"
}

Hoy es: $hoy
PROMPT;
}

// ─── Obtener API key activa de un proveedor ───────────────────────
function obtenerApiKey(PDO $conn, string $proveedor): ?array {
    // Auto-reset diario
    $conn->prepare("
        UPDATE ia_proveedores_api
        SET limite_alcanzado_hoy = 0
        WHERE proveedor = ? AND limite_alcanzado_hoy = 1 AND DATE(ultimo_uso) < CURDATE()
    ")->execute([$proveedor]);

    $stmt = $conn->prepare("
        SELECT id, api_key FROM ia_proveedores_api
        WHERE proveedor = ? AND activa = 1 AND limite_alcanzado_hoy = 0
    ");
    $stmt->execute([$proveedor]);
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($keys)) return null;

    $sel = $keys[array_rand($keys)];
    $conn->prepare("UPDATE ia_proveedores_api SET ultimo_uso = NOW() WHERE id = ?")
         ->execute([$sel['id']]);

    return $sel;
}

// ─── Llamar a un proveedor vía cURL ───────────────────────────────
function llamarProveedor(array $config, string $apiKey, string $sistema, string $usuario): string {
    $headers = ['Content-Type: application/json'];
    $url     = $config['endpoint'];

    if ($config['tipo'] === 'google') {
        $url .= $config['model'] . ':generateContent?key=' . $apiKey;
        $payload = [
            'contents'        => [['role' => 'user', 'parts' => [['text' => "$sistema\n\nMensaje del usuario:\n$usuario"]]]],
            'generationConfig'=> ['temperature' => 0.1, 'maxOutputTokens' => 1024, 'response_mime_type' => 'application/json']
        ];
    } else {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
        if ($config['tipo'] === 'openrouter') {
            $headers[] = 'HTTP-Referer: https://api.batidospitaya.com';
            $headers[] = 'X-Title: Batidos Pitaya PitayaBot';
        }
        $payload = [
            'model'       => $config['model'],
            'messages'    => [
                ['role' => 'system', 'content' => $sistema],
                ['role' => 'user',   'content' => $usuario]
            ],
            'temperature' => 0.1,
            'max_tokens'  => 800
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    curl_close($ch);

    if ($errno || !$response) {
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }

    $data = json_decode($response, true);

    if ($config['tipo'] === 'google') {
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    } else {
        $text = $data['choices'][0]['message']['content'] ?? '';
    }

    if (!$text) {
        // Si la API devolvió error (rate limit, etc.) lanzar excepción informativa
        $errorMsg = $data['error']['message'] ?? $data['message'] ?? 'Respuesta vacía';
        throw new RuntimeException("Proveedor rechazó la solicitud: $errorMsg");
    }

    return $text;
}

// ─── Extraer JSON de la respuesta del LLM ─────────────────────────
function extraerJSON(string $texto): array {
    $inicio = strpos($texto, '{');
    $fin    = strrpos($texto, '}');
    if ($inicio === false || $fin === false) {
        throw new RuntimeException('No se encontró JSON en la respuesta de la IA');
    }
    $json = json_decode(substr($texto, $inicio, $fin - $inicio + 1), true);
    if (!is_array($json) || !isset($json['intent'])) {
        throw new RuntimeException('JSON inválido o sin campo "intent"');
    }
    return $json;
}

// ─── Cascada principal ────────────────────────────────────────────
$systemPrompt = buildSystemPrompt();
$erroresLog   = [];

foreach ($cascada as $proveedor) {
    $keyData = obtenerApiKey($conn, $proveedor);
    if (!$keyData) {
        $erroresLog[] = "$proveedor: sin keys disponibles";
        continue;
    }

    $config = $proveedoresConfig[$proveedor];

    try {
        $texto     = llamarProveedor($config, $keyData['api_key'], $systemPrompt, $mensaje);
        $resultado = extraerJSON($texto);

        respuestaOk([
            'data' => array_merge($resultado, ['proveedor_usado' => $proveedor])
        ]);

    } catch (RuntimeException $e) {
        $erroresLog[] = "$proveedor: " . $e->getMessage();
        error_log("[clasificar.php] Fallo con $proveedor: " . $e->getMessage());
    }
}

// Si todos fallaron
error_log('[clasificar.php] Todos los proveedores fallaron: ' . implode(' | ', $erroresLog));
respuestaOk([
    'intent'             => 'desconocido',
    'entidades'          => [],
    'confianza'          => 0,
    'ambiguo'            => true,
    'frase_confirmacion' => 'No pude entender tu mensaje. ¿Puedes reformularlo?',
    'proveedor_usado'    => null
]);
