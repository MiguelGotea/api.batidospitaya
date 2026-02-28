<?php
require 'core/database/conexion.php';
header('Content-Type: text/plain');

try {
    echo "Testing receiving message logic...\n";
    $texto = "hola";
    $instancia = "wsp-crmbot";
    $numCliente = "5055741601999";
    $lastIntent = null;

    $textoNorm = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $texto));
    echo "iconv done\n";

    echo "Requiring motor...\n";
    require_once __DIR__ . '/api/crm/motor_bot.php';

    echo "Procesando intent...\n";
    $resultado = procesarIntent($conn, $instancia, $texto, $lastIntent, $numCliente);

    echo "Result of intent:\n";
    print_r($resultado);


    // Testing update conversation

    $respuesta = $resultado['respuesta'];
    echo "JSON encoding...\n";
    $json = json_encode([
        'responder' => (bool) $respuesta,
        'texto_respuesta' => $respuesta,
        'intent' => $resultado['intent_name'],
        'nivel' => $resultado['nivel'] ?? 4,
        'conv_id' => 123,
    ]);
    if ($json === false) {
        throw new Exception("JSON error: " . json_last_error_msg());
    }

    echo "\nAll OK!";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
