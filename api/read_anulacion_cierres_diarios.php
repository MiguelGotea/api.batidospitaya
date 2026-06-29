<?php
/**
 * api/read_anulacion_cierres_diarios.php
 * Access consulta este endpoint para leer que cierres diarios (con sus precierres) 
 * debe anular localmente.
 *
 * Parámetros POST (JSON):
 *   token       : Token de autenticación
 *   sucursal    : Código del local
 *
 * Respuesta JSON:
 *   {
 *     "success": true,
 *     "registros": [
 *       {
 *         "CodigoCierre": N
 *       }, ...
 *     ]
 *   }
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('RAC_TOKEN',    'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('RAC_TABLE',    'anulacion_cierres_diarios');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function racVerifyToken(): bool
{
    $headers = getallheaders();
    $token   = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(RAC_TOKEN, $token);
}

function racError(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    racError(405, 'Método no permitido. Se requiere POST.');
}

if (!racVerifyToken()) {
    racError(401, 'Token inválido o faltante.');
}

$rawBody    = file_get_contents('php://input');
$bodyJson   = json_decode($rawBody, true);

$sucursal   = $bodyJson['sucursal'] ?? $_POST['sucursal'] ?? null;

if (empty($sucursal) || !is_numeric($sucursal) || (int)$sucursal < 1) {
    if ($sucursal !== '0') {
        racError(400, 'Parámetro sucursal inválido o faltante.');
    }
}

$sucursal = (int)$sucursal;

/** @var PDO $conn */
global $conn;

try {
    // Buscar los cierres que estan en status 0 para la sucursal
    $stmtPending = $conn->prepare(
        "SELECT CodigoCierre FROM `" . RAC_TABLE . "` WHERE Sucursal = ? AND status = 0"
    );
    $stmtPending->execute([$sucursal]);
    $registros = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'    => true,
        'registros'  => $registros
    ]);

} catch (PDOException $e) {
    racError(500, 'Error de base de datos: ' . $e->getMessage());
}
?>
