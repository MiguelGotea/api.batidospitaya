<?php
/**
 * auth.php — Autenticación compartida para endpoints Google Reviews
 * Incluir en todos los endpoints del módulo.
 *
 * Reutiliza el mismo token VPS que el módulo HIK/WSP.
 * Header requerido: X-WSP-Token: <token>
 *
 * Expone también hikOk() / hikErr() para respuestas estandarizadas.
 */

require_once __DIR__ . '/../../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Token compartido con los demás workers del VPS
define('GMB_TOKEN_VPS', 'c5b155ba8f6877a2eefca0183ab18e37fe9a6accde340cf5c88af724822cbf50');
define('GMB_TOKEN_ERP', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');

// IP del VPS (para proxy de trigger/status)
define('GMB_VPS_IP',   '198.211.97.243');
define('GMB_VPS_PORT', 3009);

function verificarTokenGMB()
{
    $token = $_SERVER['HTTP_X_WSP_TOKEN'] ?? '';

    if (empty($token) && function_exists('getallheaders')) {
        $h     = getallheaders();
        $token = $h['X-WSP-Token'] ?? $h['x-wsp-token'] ?? '';
    }

    if (empty($token) || ($token !== GMB_TOKEN_VPS && $token !== GMB_TOKEN_ERP)) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado — token inválido o ausente']);
        exit;
    }
}

function hikOk($data = [])
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function hikErr($mensaje, $codigo = 400)
{
    http_response_code($codigo);
    echo json_encode(['error' => $mensaje]);
    exit;
}
