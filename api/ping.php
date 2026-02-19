<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Respuesta mínima y rápida
echo json_encode([
    'status' => 'success',
    'message' => 'pong',
    'timestamp' => time()
]);
exit;
?>