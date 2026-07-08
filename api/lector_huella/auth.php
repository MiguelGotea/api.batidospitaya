<?php
/**
 * auth.php — Autenticación compartida para endpoints Lector Huella Marcación
 * Incluir en todos los endpoints del módulo.
 *
 * Header requerido: X-Huella-Token: <token>
 *
 * ============================================================
 * TOKENS DE COMUNICACIÓN SERVIDOR-A-SERVIDOR
 * ============================================================
 * HUELLA_TOKEN_VPS → Lo usa el VPS para autenticarse con esta API
 *                    Debe coincidir con HUELLA_TOKEN_VPS en el .env del VPS
 *
 * HUELLA_TOKEN_ERP → Lo usa el ERP (testlectorhuella.php) para llamar a esta API
 * ============================================================
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// ── URL del servicio FastAPI en el VPS ────────────────────────
// El servicio escucha en el puerto 8889 del droplet (sin nginx/SSL — solo servidor a servidor)
define('HUELLA_VPS_URL', 'http://198.211.97.243:8889');

// ── Tokens ───────────────────────────────────────────────────
define('HUELLA_TOKEN_VPS', 'hv_7f3a9c2e5b8d1470f3a9c8e2b5d7a4c1e8f4b9d3a6c2e7f1b8d5a4c9e3f2b5d8');
define('HUELLA_TOKEN_ERP', 'he_4f7a2c9e1d8b3650a9f2c8e3b1d7a5c2e8f4b9d3a6c2e7f1b8d5a4c9e3f2b5d8');

/**
 * Lee el token de todas las fuentes posibles.
 * Fallback para Apache/Hostinger que a veces no pasa headers custom a PHP.
 */
function _leerTokenHuella(): string
{
    // 1. Forma estándar: $_SERVER (funciona en nginx y Apache con mod_php)
    $token = $_SERVER['HTTP_X_HUELLA_TOKEN'] ?? '';

    // 2. getallheaders() — funciona en Apache mod_php
    if (empty($token) && function_exists('getallheaders')) {
        $h     = getallheaders();
        $token = $h['X-Huella-Token'] ?? $h['x-huella-token'] ?? '';
    }

    // 3. Body JSON — fallback para entornos donde Apache stripea headers custom
    if (empty($token)) {
        $raw  = @file_get_contents('php://input');
        $body = $raw ? (json_decode($raw, true) ?? []) : [];
        $token = $body['_huella_token'] ?? '';
    }

    // 4. Query string como último recurso
    if (empty($token)) {
        $token = $_GET['_huella_token'] ?? '';
    }

    return $token;
}

/**
 * Verifica que el header X-Huella-Token sea válido (VPS o ERP).
 */
function verificarTokenHuella(): void
{
    $token = _leerTokenHuella();
    if (empty($token) || ($token !== HUELLA_TOKEN_VPS && $token !== HUELLA_TOKEN_ERP)) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado — token inválido o ausente']);
        exit;
    }
}

/**
 * Verifica que el token sea específicamente el del ERP.
 */
function verificarTokenHuellaERP(): void
{
    $token = _leerTokenHuella();
    if (empty($token) || $token !== HUELLA_TOKEN_ERP) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado — se requiere token ERP']);
        exit;
    }
}

/** Respuesta exitosa */
function huellaOk(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

/** Respuesta de error */
function huellaErr(string $mensaje, int $codigo = 400): void
{
    http_response_code($codigo);
    echo json_encode(['success' => false, 'error' => $mensaje]);
    exit;
}

/**
 * Hace una petición cURL al servicio FastAPI del VPS.
 *
 * @param string $endpoint  Ej: '/api/enroll'
 * @param array  $payload   Datos a enviar como JSON
 * @return array ['http_code' => int, 'body' => array]
 */
function llamarVPS(string $endpoint, array $payload): array
{
    $url = HUELLA_VPS_URL . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Huella-Token: ' . HUELLA_TOKEN_ERP,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException("cURL error: $curl_err");
    }

    $body = json_decode($response, true) ?? [];
    return ['http_code' => $http_code, 'body' => $body];
}
?>
