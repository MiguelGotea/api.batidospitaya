<?php
/**
 * auth.php — Autenticación compartida para endpoints Resumen Reuniones IA
 * Incluir en todos los endpoints del módulo.
 *
 * Header requerido: X-Resumen-Token: <token>
 *
 * ============================================================
 * TOKENS DE COMUNICACIÓN SERVIDOR-A-SERVIDOR
 * ============================================================
 * RESUMEN_TOKEN_VPS  → Lo usa el VPS para autenticarse con esta API
 *                      Copiar al .env del VPS como: REUNIONES_API_TOKEN
 *
 * RESUMEN_TOKEN_ERP  → Lo usa el ERP para autenticarse con esta API
 *                      Copiar a los archivos ajax del ERP (hardcodeado)
 *                      También lo usa la API (aprobar.php) para llamar al VPS
 *                      El VPS lo verifica en DELETE /api/audio/{id}
 *
 * RESUMEN_TOKEN_VPS  = 4f7a2c9e1d8b3650a9f2c8e3b1d7a5c2e8f4b9d3a6c1e7f2b8d5a3c9e6f1b4d7
 * RESUMEN_TOKEN_ERP  = 9a3f6c2e5b8d1470f3a9c8e2b5d7a4c1e8f4b9d3a6c2e7f1b8d5a4c9e3f2b5d8
 * ============================================================
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

define('RESUMEN_TOKEN_VPS', '4f7a2c9e1d8b3650a9f2c8e3b1d7a5c2e8f4b9d3a6c1e7f2b8d5a3c9e6f1b4d7');
define('RESUMEN_TOKEN_ERP', '9a3f6c2e5b8d1470f3a9c8e2b5d7a4c1e8f4b9d3a6c2e7f1b8d5a4c9e3f2b5d8');

/**
 * Verifica que el header X-Resumen-Token sea válido (VPS o ERP).
 * Aborta con 401 si no pasa.
 */
function verificarTokenResumen(): void
{
    $token = $_SERVER['HTTP_X_RESUMEN_TOKEN'] ?? '';

    if (empty($token) && function_exists('getallheaders')) {
        $h     = getallheaders();
        $token = $h['X-Resumen-Token'] ?? $h['x-resumen-token'] ?? '';
    }

    if (empty($token) || ($token !== RESUMEN_TOKEN_VPS && $token !== RESUMEN_TOKEN_ERP)) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado — token inválido o ausente']);
        exit;
    }
}

/**
 * Verifica que el token sea específicamente el del VPS.
 */
function verificarTokenVPS(): void
{
    $token = $_SERVER['HTTP_X_RESUMEN_TOKEN'] ?? '';

    if (empty($token) && function_exists('getallheaders')) {
        $h     = getallheaders();
        $token = $h['X-Resumen-Token'] ?? $h['x-resumen-token'] ?? '';
    }

    if (empty($token) || $token !== RESUMEN_TOKEN_VPS) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado — se requiere token VPS']);
        exit;
    }
}

/**
 * Verifica que el token sea específicamente el del ERP.
 */
function verificarTokenERP(): void
{
    $token = $_SERVER['HTTP_X_RESUMEN_TOKEN'] ?? '';

    if (empty($token) && function_exists('getallheaders')) {
        $h     = getallheaders();
        $token = $h['X-Resumen-Token'] ?? $h['x-resumen-token'] ?? '';
    }

    if (empty($token) || $token !== RESUMEN_TOKEN_ERP) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado — se requiere token ERP']);
        exit;
    }
}

/** Respuesta exitosa */
function reunionOk(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

/** Respuesta de error */
function reunionErr(string $mensaje, int $codigo = 400): void
{
    http_response_code($codigo);
    echo json_encode(['success' => false, 'error' => $mensaje]);
    exit;
}
