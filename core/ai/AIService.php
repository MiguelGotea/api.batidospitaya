<?php
/**
 * Servicio genérico de integración con múltiples IA
 * Soporta: Groq, OpenAI, DeepSeek
 * Ubicación: /core/ai/AIService.php
 */

class AIService
{
    private $conn;
    private $proveedor;
    private $apiKeys = [];
    private $endpoint;
    private $model;

    /**
     * @param PDO $conn Conexión a Base de Datos
     * @param string $proveedor Nombre del proveedor ('groq', 'openai', 'deepseek')
     */
    public function __construct($conn, $proveedor = 'groq')
    {
        $this->conn = $conn;
        $this->proveedor = strtolower(trim($proveedor));
        $this->configurarProveedor();
        $this->loadKeysFromDatabase();
    }

    /**
     * Configura Endpoints y Modelos basados en el proveedor
     */
    private function configurarProveedor()
    {
        switch ($this->proveedor) {
            case 'groq':
                $this->endpoint = 'https://api.groq.com/openai/v1/chat/completions';
                $this->model = 'llama-3.3-70b-versatile';
                break;
            case 'openai':
                $this->endpoint = 'https://api.openai.com/v1/chat/completions';
                $this->model = 'gpt-4o-mini';
                break;
            case 'deepseek':
                $this->endpoint = 'https://api.deepseek.com/chat/completions';
                $this->model = 'deepseek-chat';
                break;
            case 'google':
                // Google Gemini via Native REST API (v1beta)
                $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';
                $this->model = 'gemini-flash-latest';
                break;
            case 'cerebras':
                $this->endpoint = 'https://api.cerebras.ai/v1/chat/completions';
                $this->model = 'llama3.1-70b';
                break;
            case 'openrouter':
                $this->endpoint = 'https://openrouter.ai/api/v1/chat/completions';
                $this->model = 'nvidia/nemotron-3-nano-30b-a3b:free';
                break;
            case 'huggingface':
                $this->endpoint = 'https://router.huggingface.co/v1/chat/completions';
                $this->model = 'meta-llama/Llama-3.2-3B-Instruct';
                break;
            case 'mistral':
                $this->endpoint = 'https://api.mistral.ai/v1/chat/completions';
                $this->model = 'mistral-medium-latest';
                break;
            default:
                throw new \Exception("Proveedor de IA no soportado: {$this->proveedor}");
        }
    }

    /**
     * Permite sobreescribir el modelo por defecto si es necesario para tareas especificas
     */
    public function setModel($modelName)
    {
        $this->model = $modelName;
    }

    /**
     * Retorna el nombre del proveedor en uso
     */
    public function getProveedor()
    {
        return $this->proveedor;
    }

    /**
     * Verifica si hay llaves disponibles para este proveedor
     */
    public function hasAvailableKeys()
    {
        return !empty($this->apiKeys);
    }

    /**
     * Extrae las llaves de la base de datos MySQL para este proveedor
     */
    private function loadKeysFromDatabase()
    {
        try {
            // Auto-reset: Si ya es un nuevo día, desbloquear llaves que se agotaron ayer
            $this->conn->prepare("
                UPDATE ia_proveedores_api 
                SET limite_alcanzado_hoy = 0 
                WHERE proveedor = ? 
                AND limite_alcanzado_hoy = 1 
                AND DATE(ultimo_uso) < CURDATE()
            ")->execute([$this->proveedor]);

            $stmt = $this->conn->prepare("
                SELECT id, api_key 
                FROM ia_proveedores_api 
                WHERE proveedor = ? 
                AND activa = 1 
                AND limite_alcanzado_hoy = 0
            ");
            $stmt->execute([$this->proveedor]);
            $keysData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Llenar el arreglo con formato [id => 'apikey'] para poder actualizar limites
            $this->apiKeys = [];
            foreach ($keysData as $row) {
                $this->apiKeys[$row['id']] = $row['api_key'];
            }
        } catch (Exception $e) {
            error_log("Error SQL al cargar llaves {$this->proveedor}: " . $e->getMessage());
        }
    }

    /**
     * Marcar una llave específica como "Agotada" para que no se use más hoy
     */
    private function marcarLlaveAgotada($idLlave)
    {
        try {
            $stmt = $this->conn->prepare("
                UPDATE ia_proveedores_api 
                SET limite_alcanzado_hoy = 1 
                WHERE id = ?
            ");
            $stmt->execute([$idLlave]);
        } catch (Exception $e) {
            error_log("Error al deshabilitar llave {$this->proveedor}: " . $e->getMessage());
        }
    }

    /**
     * Enviar un prompt a la IA seleccionada de manera genérica
     * @param string $systemPrompt Instrucciones para la IA
     * @param string $userPrompt Consulta del usuario
     * @param float $temperature (Opcional) nivel de creatividad
     * @param array $extraParts (Opcional) Partes adicionales para el payload (útil para archivos en Gemini)
     * @return array|string Respuesta decodificada o texto según corresponda
     */
    public function procesarPrompt($systemPrompt, $userPrompt, $temperature = 0.2, $extraParts = [])
    {
        if (empty($this->apiKeys)) {
            throw new Exception("No hay API Keys de {$this->proveedor} disponibles o todas llegaron a su límite diario.");
        }

        if ($this->proveedor === 'google') {
            // Payload nativo para Google AI Studio
            $parts = [
                ['text' => "Contexto del sistema:\n$systemPrompt\n\nPregunta del usuario:\n$userPrompt"]
            ];

            // Si hay partes extra (como inline_data para PDFs), las agregamos
            if (!empty($extraParts)) {
                $parts = array_merge($parts, $extraParts);
            }

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => $parts
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => 4096,
                    'topP' => 0.95,
                    'topK' => 40,
                    'response_mime_type' => 'application/json'
                ]
            ];
        } else {
            // Payload estándar compatible con OpenAI (Groq, DeepSeek, Cerebras, OpenAI)
            
            $userContent = $userPrompt;

            // Si hay partes extra (como imágenes base64), convertimos el content en un arreglo (Vision API)
            if (!empty($extraParts)) {
                $userContent = [
                    ['type' => 'text', 'text' => $userPrompt]
                ];
                foreach ($extraParts as $part) {
                    if (isset($part['inline_data'])) {
                        $mime = $part['inline_data']['mime_type'];
                        $data = $part['inline_data']['data'];
                        $userContent[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:$mime;base64,$data"
                            ]
                        ];
                    }
                }
            }

            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userContent
                    ]
                ],
                'temperature' => $temperature,
                'max_tokens' => 2000,
                'top_p' => 0.9
            ];
        }

        // Copia del arreglo [id => apiKey] para rotar
        $llavesDisponibles = $this->apiKeys;
        $ultimoError = null;

        while (!empty($llavesDisponibles)) {
            // Obtener el ID y Clave de forma aleatoria (para uso tipo balanceador)
            $idLlave = array_rand($llavesDisponibles);
            $currentApiKey = $llavesDisponibles[$idLlave];

            // Remover de las opciones de este ciclo
            unset($llavesDisponibles[$idLlave]);

            $url = $this->endpoint;
            $headers = ['Content-Type: application/json'];

            if ($this->proveedor === 'google') {
                // El endpoint nativo incluye el modelo y la llave en la URL
                $url .= $this->model . ':generateContent?key=' . $currentApiKey;
            } else {
                // OpenAI y otros compatibles usan Bearer Token
                $headers[] = 'Authorization: Bearer ' . $currentApiKey;

                // Headers adicionales para proveedores especificos
                if ($this->proveedor === 'openrouter') {
                    $headers[] = 'HTTP-Referer: http://localhost';
                    $headers[] = 'X-Title: Batidos Pitaya ERP';
                }
            }

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 90,
                CURLOPT_SSL_VERIFYPEER => false // Importante para algunos entornos locales
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_errno($ch) ? curl_error($ch) : null;

            curl_close($ch);

            if ($curlError) {
                $ultimoError = new Exception('Error cURL: ' . $curlError);
                continue; // Intentar con la siguiente llave si fue intermitencia
            }

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                $content = '';

                if ($this->proveedor === 'google') {
                    // Extracción nativa Google
                    $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                } else {
                    // Extracción estándar OpenAI
                    $content = $result['choices'][0]['message']['content'] ?? '';
                }

                if (empty($content)) {
                    throw new Exception("Respuesta exitosa de {$this->proveedor} pero contenido vacío o formato inválido.");
                }

                // Actualizar timestamp de ultimo uso
                try {
                    $this->conn->prepare("UPDATE ia_proveedores_api SET ultimo_uso = NOW() WHERE id = ?")->execute([$idLlave]);
                } catch (Exception $e) {
                }

                return $content;
            }

            // Manejo de errores
            $errorData = json_decode($response, true);
            $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : ('HTTP ' . $httpCode);

            // Loguear el error completo para debug interno
            error_log("Error API {$this->proveedor} (HTTP $httpCode): " . $response);

            // Si el error es 429 (Too Many Requests / Quota Reached), deshabilitar llave en DB y rotar
            if ($httpCode === 429) {
                $ultimoError = new Exception("Error 429 en {$this->proveedor}: {$errorMsg}");
                $this->marcarLlaveAgotada($idLlave);
                // Intentar con la siguiente llave
                continue;
            }

            // Error de saldo insuficiente en DeepSeek (402)
            if ($httpCode === 402 && $this->proveedor === 'deepseek') {
                $ultimoError = new Exception("Saldo insuficiente en DeepSeek (HTTP 402): {$errorMsg}");
                $this->marcarLlaveAgotada($idLlave);
                continue;
            }

            // Errores de servidor temporales (500, 502, 503, 504)
            // Intentamos rotar a otra llave sin marcar ésta como permanentemente agotada
            if ($httpCode >= 500 && $httpCode <= 504) {
                $ultimoError = new Exception("Error temporal del servidor {$this->proveedor} ({$httpCode}): {$errorMsg}");
                // No marcamos como agotada, solo pasamos a la siguiente en este ciclo
                continue;
            }

            // Otro error fatal (400, 401, 404, etc)
            throw new Exception("Error API de {$this->proveedor} ({$httpCode}): " . $errorMsg);
        }

        // Si se acaba el bucle y la lista queda vacía:
        $totalLlavesIntentadas = count($this->apiKeys);
        if ($ultimoError) {
            throw new Exception("Se intentaron {$totalLlavesIntentadas} llaves de {$this->proveedor}, pero todas fallaron o agotaron su cuota. Último error: " . $ultimoError->getMessage());
        }

        throw new Exception("No hay API Keys de {$this->proveedor} disponibles o todas llegaron a su límite diario.");
    }

    /**
     * Función utilitaria para extraer JSON seguro del texto de respuesta de un LLM
     */
    public function extraerJSON($content)
    {
        // Limpiar posibles espacios o saltos de línea al inicio/final
        $content = trim($content);

        // Buscar el primer '{' o '[' y el último '}' o ']'
        $startBrace = strpos($content, '{');
        $startBracket = strpos($content, '[');
        
        $start = false;
        if ($startBrace !== false && $startBracket !== false) {
            $start = min($startBrace, $startBracket);
        } else if ($startBrace !== false) {
            $start = $startBrace;
        } else if ($startBracket !== false) {
            $start = $startBracket;
        }

        $endBrace = strrpos($content, '}');
        $endBracket = strrpos($content, ']');

        $end = false;
        if ($endBrace !== false && $endBracket !== false) {
            $end = max($endBrace, $endBracket);
        } else if ($endBrace !== false) {
            $end = $endBrace;
        } else if ($endBracket !== false) {
            $end = $endBracket;
        }

        if ($start !== false && $end !== false) {
            $content = substr($content, $start, $end - $start + 1);
        }

        // Reemplazar posibles secuencias de escape inválidas que LLMs a veces incluyen
        $content = str_replace(["\n", "\r", "\t"], [" ", " ", " "], $content);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Intentar reparación básica si está truncado
            $reparado = $this->repararJSONIncompleto($content);
            $decoded = json_decode($reparado, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido retornado por la IA (incluso tras reparar): ' . json_last_error_msg() . ' | Raw: ' . substr($content, 0, 300));
            }
        }

        return $decoded;
    }

    /**
     * Intenta cerrar llaves y corchetes de un JSON truncado
     */
    private function repararJSONIncompleto($json)
    {
        $json = trim($json);
        $abiertos = [];
        $longitud = strlen($json);
        $enString = false;
        $escape = false;

        for ($i = 0; $i < $longitud; $i++) {
            $char = $json[$i];
            if ($char === '"' && !$escape) {
                $enString = !$enString;
            }
            if (!$enString) {
                if ($char === '{' || $char === '[') $abiertos[] = $char;
                if ($char === '}' || $char === ']') array_pop($abiertos);
            }
            $escape = ($char === '\\' && !$escape);
        }

        // Si terminó dentro de un string, cerrarlo
        if ($enString) $json .= '"';

        // Cerrar en orden inverso
        while (!empty($abiertos)) {
            $ultimo = array_pop($abiertos);
            $json .= ($ultimo === '{') ? '}' : ']';
        }

        return $json;
    }
}
?>