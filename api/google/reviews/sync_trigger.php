<?php
/**
 * sync_trigger.php — Dispara el sync manual en el worker VPS
 * POST /api/google/reviews/sync_trigger.php
 * Header: X-WSP-Token
 *
 * Actúa como proxy: llama a http://VPS_IP:3009/sync/trigger
 * El ERP llama a este endpoint (que tiene CORS/sesión), no al VPS directamente.
 */

require_once __DIR__ . '/../auth.php';

verificarTokenGMB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    hikErr('Método no permitido. Usar POST.', 405);
}

$workerUrl = 'http://' . GMB_VPS_IP . ':' . GMB_VPS_PORT . '/sync/trigger';

$ch = curl_init($workerUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => '',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    hikErr('No se pudo conectar al worker: ' . $curlErr, 503);
}

$data = json_decode($response, true);

if ($httpCode === 202) {
    hikOk([
        'message'   => $data['message']   ?? 'Sync iniciado',
        'startedAt' => $data['startedAt'] ?? null,
    ]);
} elseif ($httpCode === 409) {
    hikErr($data['error'] ?? 'El sync ya está en ejecución', 409);
} else {
    hikErr('El worker respondió con código ' . $httpCode . ': ' . $response, 502);
}
