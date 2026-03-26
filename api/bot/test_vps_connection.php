<?php
/**
 * test_vps_connection.php — Diagnóstico de conectividad hacia el VPS
 */
header('Content-Type: text/plain; charset=utf-8');

$ip   = '198.211.97.243';
$port = 3007;
$url  = "http://$ip:$port/health";

echo "🔍 Probando conexión a $url...\n\n";

// 1. Prueba de Socket (fsockopen)
echo "--- Prueba 1: Socket (fsockopen) ---\n";
$fp = @fsockopen($ip, $port, $errno, $errstr, 5);
if (!$fp) {
    echo "❌ FALLO: $errstr ($errno)\n";
} else {
    echo "✅ ÉXITO: El puerto $port está abierto en el VPS.\n";
    fclose($fp);
}

echo "\n--- Prueba 2: cURL (detallado) ---\n";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_VERBOSE        => true,
]);
$stderr = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $stderr);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);

rewind($stderr);
$verboseLog = stream_get_contents($stderr);
fclose($stderr);

echo "HTTP Code: $httpCode\n";
if ($curlErr) echo "cURL Error: $curlErr\n";
echo "\nDetalle de la conexión:\n$verboseLog\n";
echo "\nRespuesta del servidor: " . ($response ?: '(vacío)') . "\n";
