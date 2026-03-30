<?php
/**
 * auth.php — Autenticación compartida para endpoints WSP
 * Incluir en todos los endpoints que requieren token del VPS.
 *
 * Usa header: X-WSP-Token: <token>
 * El token se define en WSP_TOKEN en la configuración del servidor.
 */

header('Content-Type: application/json; charset=utf-8');

// Definir el token aquí o leerlo desde una constante/archivo de configuración
// ⚠️ CAMBIAR ESTE TOKEN POR UNO ALEATORIO SEGURO (mínimo 32 caracteres)
define('WSP_TOKEN_SECRETO', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');

function verificarTokenVPS()
{
    $tokenRecibido = $_SERVER['HTTP_X_WSP_TOKEN'] ?? '';
    
    // Intento alternativo de obtener el header si el anterior falla
    if (empty($tokenRecibido) && function_exists('getallheaders')) {
        $headers = getallheaders();
        $tokenRecibido = $headers['X-WSP-Token'] ?? $headers['x-wsp-token'] ?? '';
    }

    if (empty($tokenRecibido) || $tokenRecibido !== WSP_TOKEN_SECRETO) {
        http_response_code(401);
        error_log("❌ Intento fallido de autenticación WSP. Token recibido: " . (empty($tokenRecibido) ? 'VACÍO' : 'INVÁLIDO'));
        echo json_encode(['error' => 'No autorizado — token inválido o ausente']);
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
    echo json_encode(['error' => $mensaje]);
    exit;
}
