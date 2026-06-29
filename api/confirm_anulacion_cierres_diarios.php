<?php
/**
 * api/confirm_anulacion_cierres_diarios.php
 * Access consulta este endpoint para confirmar que ya ejecutó el UPDATE de los cierres
 * para que el servidor marque sus IDs con status = 1
 *
 * Parámetros POST (JSON):
 *   token       : Token de autenticación
 *   sucursal    : Código del local
 *   codigos     : Array de Código Cierre que se procesaron (ej. [3441, 3442, 3443])
 *
 * Respuesta JSON:
 *   {
 *     "success": true
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
$codigos    = $bodyJson['codigos'] ?? null;

if (empty($sucursal) || !is_numeric($sucursal) || (int)$sucursal < 1) {
    if ($sucursal !== '0') {
        racError(400, 'Parámetro sucursal inválido o faltante.');
    }
}

$sucursal = (int)$sucursal;

if (empty($codigos) || !is_array($codigos)) {
    racError(400, 'Parámetro codigos inválido o faltante. Debe ser un arreglo de enteros.');
}

$codigos = array_filter(array_map('intval', $codigos), fn($v) => $v > 0);
if (empty($codigos)) {
    echo json_encode(['success' => true, 'message' => 'Ningun codigo valido procesado.']);
    exit();
}

/** @var PDO $conn */
global $conn;

try {
    // Actualizar status = 1 para los codigos enviados
    $placeholders = implode(',', array_fill(0, count($codigos), '?'));
    $stmtUpdate = $conn->prepare(
        "UPDATE `" . RAC_TABLE . "` SET status = 1 WHERE Sucursal = ? AND CodigoCierre IN ($placeholders)"
    );
    
    $params = array_merge([$sucursal], $codigos);
    $stmtUpdate->execute($params);

    echo json_encode([
        'success'    => true,
        'afectados'  => $stmtUpdate->rowCount()
    ]);

} catch (PDOException $e) {
    racError(500, 'Error de base de datos: ' . $e->getMessage());
}
?>
