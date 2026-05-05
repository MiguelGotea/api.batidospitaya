<?php
/**
 * sync_status.php — Consulta el estado del último sync en el worker VPS
 * GET /api/google/reviews/sync_status.php
 * Header: X-WSP-Token
 *
 * Actúa como proxy: llama a http://VPS_IP:3009/sync/status
 */

require_once __DIR__ . '/../auth.php';

verificarTokenGMB();

$workerUrl = 'http://' . GMB_VPS_IP . ':' . GMB_VPS_PORT . '/sync/status';

$ch = curl_init($workerUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    hikErr('No se pudo conectar al worker: ' . $curlErr, 503);
}

if ($httpCode !== 200) {
    hikErr('El worker respondió con código ' . $httpCode, 502);
}

$data = json_decode($response, true);
if (!$data) {
    hikErr('Respuesta inválida del worker', 502);
}

// Pasar la respuesta del worker directamente al cliente
echo json_encode(array_merge(['success' => true], $data));
exit;
