<?php
/**
 * auth_bot.php — Autenticación para endpoints del PitayaBot
 *
 * Incluir en todos los endpoints de api/bot/ que requieren token del VPS.
 * Usa header: X-WSP-Token: <token>
 *
 * ⚠️ IMPORTANTE: Este token debe coincidir con WSP_TOKEN en el .env de wsp-pitayabot
 *    El token se puede configurar aquí directamente o leerlo desde
 *    la misma constante usada en auth.php del módulo wsp (si es el mismo).
 */

header('Content-Type: application/json; charset=utf-8');

// Token secreto para el bot — cambiar por un valor aleatorio de 32+ chars
// Se puede usar el mismo token del WSP_TOKEN en el .env del VPS
define('BOT_TOKEN_SECRETO', 'c5b155ba8f6877a2eefca0183ab18e37fe9a6accde340cf5c88af724822cbf50');

function verificarTokenBot()
{
    $tokenRecibido = $_SERVER['HTTP_X_WSP_TOKEN'] ?? '';
    if (empty($tokenRecibido) || $tokenRecibido !== BOT_TOKEN_SECRETO) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado — token inválido o ausente']);
        exit;
    }
}

function respuestaOk($data = [])
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function respuestaError($mensaje, $codigo = 400)
{
    http_response_code($codigo);
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

/**
 * Envía un mensaje real vía WhatsApp llamando al VPS de DigitalOcean
 */
function enviarMensajeWsp($numero, $mensaje)
{
    $vpsUrl = "http://198.211.97.243:3007/send";
    $token  = BOT_TOKEN_SECRETO;

    $payload = json_encode([
        'to'      => $numero,
        'message' => $mensaje
    ]);

    $ch = curl_init($vpsUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-WSP-Token: ' . $token
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("PitayaBot cURL Error (To: $numero): $curlErr");
    }

    if ($httpCode === 200) {
        $res = json_decode($response, true);
        return ($res['success'] ?? false);
    } else {
        error_log("PitayaBot VPS HTTP Error $httpCode: $response");
    }

    return false;
}
