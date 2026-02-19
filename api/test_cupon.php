<?php
/**
 * Script de prueba para aplicar cupones
 * Ahora con integración de base de datos para verificación
 * Ejecución: php api/test_cupon.php
 */

// 1. Usar conexión centralizada para verificar estado en DB antes/después
require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
$api_token = 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2';
$url = 'http://api.batidospitaya.com/api/aplicar_cupon.php';

// Datos de prueba
$numero_cupon = '467465170';
$cod_sucursal = 1;
$cod_pedido = 12345;
$reset_antes_de_probar = true; // Si es true, pondrá 'aplicado = 0' en la BD antes de llamar a la API

echo "--- Iniciando prueba de aplicar cupón ---\n";
echo "URL: $url\n";
echo "Cupón: $numero_cupon\n\n";

// --- VERIFICACIÓN/PREPARACIÓN EN BASE DE DATOS ---
global $conn;
$pdo = $conn;

// Consultar estado inicial
$stmt = $pdo->prepare("SELECT aplicado FROM cupones_sucursales WHERE numero_cupon = ?");
$stmt->execute([$numero_cupon]);
$estado_inicial = $stmt->fetch();

if (!$estado_inicial) {
    echo "AVISO: El cupón '$numero_cupon' no existe en la base de datos local.\n";
    echo "Se procederá con la petición a la API de todas formas para probar la respuesta de error.\n\n";
} else {
    echo "Estado inicial en DB: " . ($estado_inicial['aplicado'] ? "YA APLICADO" : "PENDIENTE") . "\n";

    if ($reset_antes_de_probar && $estado_inicial['aplicado']) {
        echo "Reseteando cupón a 'PENDIENTE' para la prueba...\n";
        $stmt = $pdo->prepare("UPDATE cupones_sucursales SET aplicado = 0, hora_activacion = NULL WHERE numero_cupon = ?");
        $stmt->execute([$numero_cupon]);
        echo "Cupón reseteado correctamente.\n\n";
    }
}

// --- PETICIÓN A LA API ---
echo "Llamando a la API...\n";

$data = [
    'numero_cupon' => $numero_cupon,
    'cod_sucursal' => $cod_sucursal,
    'cod_pedido' => $cod_pedido,
    'token' => $api_token
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_token,
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// --- MOSTRAR RESULTADOS API ---
echo "Código HTTP: $http_code\n";

if ($error) {
    echo "Error de cURL: $error\n";
} else {
    echo "Respuesta API:\n";
    $json = json_decode($response);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo $response . "\n";
    }
}

// --- VERIFICACIÓN FINAL EN BASE DE DATOS ---
if ($estado_inicial) {
    echo "\nVerificando cambio en DB...\n";
    $stmt = $pdo->prepare("SELECT aplicado, hora_activacion FROM cupones_sucursales WHERE numero_cupon = ?");
    $stmt->execute([$numero_cupon]);
    $estado_final = $stmt->fetch();

    echo "Estado final en DB: " . ($estado_final['aplicado'] ? "APLICADO ✅" : "PENDIENTE ❌") . "\n";
    echo "Hora activación: " . ($estado_final['hora_activacion'] ?? 'N/A') . "\n";
}

echo "\n--- Fin de la prueba ---\n";
