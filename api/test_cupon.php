<?php
/**
 * Script de prueba para aplicar cupones
 * Ejecución: php api/test_cupon.php
 */

// === CONFIGURACIÓN ===
// El token debe coincidir con el definido en aplicar_cupon.php
$api_token = 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2';
$url = 'http://localhost/api/aplicar_cupon.php'; // Ajusta la URL según tu entorno local

// Datos de prueba
$numero_cupon = '467465170'; // Cambia esto por un cupón real en tu BD si deseas una prueba exitosa
$cod_sucursal = 1;
$cod_pedido = 12345;

echo "--- Iniciando prueba de aplicar cupón ---\n";
echo "URL: $url\n";
echo "Cupón: $numero_cupon\n\n";

// Preparar los datos
$data = [
    'numero_cupon' => $numero_cupon,
    'cod_sucursal' => $cod_sucursal,
    'cod_pedido' => $cod_pedido,
    'token' => $api_token
];

// Iniciar cURL
$ch = curl_init($url);

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Envío como POST standard
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_token, // También lo enviamos en el header como buena práctica
    'Content-Type: application/x-www-form-urlencoded'
]);

// Para entornos locales que no tengan SSL configurado correctamente
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Ejecutar petición
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Mostrar resultados
echo "Código HTTP: $http_code\n";

if ($error) {
    echo "Error de cURL: $error\n";
} else {
    echo "Respuesta:\n";
    $json = json_decode($response);
    if ($json) {
        // Imprimir JSON formateado
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo $response . "\n";
    }
}

echo "\n--- Fin de la prueba ---\n";
