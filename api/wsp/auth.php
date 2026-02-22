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
define('WSP_TOKEN_SECRETO', 'c5b155ba8f6877a2eefca0183ab18e37fe9a6accde340cf5c88af724822cbf50');

function verificarTokenVPS()
{
    $tokenRecibido = $_SERVER['HTTP_X_WSP_TOKEN'] ?? '';
    if (empty($tokenRecibido) || $tokenRecibido !== WSP_TOKEN_SECRETO) {
        http_response_code(401);
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
