<?php
/**
 * api/wear/auth.php
 * Middleware de autenticación para endpoints de Wear OS.
 * Valida el API Key enviado en el header X-Api-Key.
 *
 * Uso: require_once __DIR__ . '/auth.php'; al inicio de cada endpoint.
 */

// Token estático para la app Wear OS
define('WEAR_API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');

// Headers CORS y Content-Type
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

// Preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Leer API Key del header
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($apiKey) || $apiKey !== WEAR_API_TOKEN) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. API Key inválida o ausente.'
    ]);
    exit;
}
?>
