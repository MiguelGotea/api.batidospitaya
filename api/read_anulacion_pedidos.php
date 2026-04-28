<?php
/**
 * api/read_anulacion_pedidos.php
 * Access consulta este endpoint para leer el estado de sus solicitudes pendientes.
 *
 * Access envía los CodPedidos que tiene en Status=0 y el host responde
 * con el estado actual de cada uno: si fue aprobado (Status=1), devuelve
 * el Motivo y HoraAnulada para que Access ejecute la anulación local.
 *
 * Parámetros POST (JSON):
 *   token       : Token de autenticación
 *   sucursal    : Código del local
 *   cod_pedidos : Array de CodPedido con Status=0 en Access (Opcional)
 *
 * Respuesta JSON:
 *   {
 *     "success": true,
 *     "registros": [
 *       {
 *         "CodPedido": N,
 *         "Status": 0|1,
 *         "Motivo": "...",
 *         "HoraAnulada": "...",
 *         "Modalidad": N,
 *         "ComentarioAprobacion": "...",
 *         "EjecutadoEnTienda": 0|1
 *       }, ...
 *     ]
 *   }
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('RA_TOKEN',    'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('RA_LOG_FILE', __DIR__ . '/logs/sync_anulacion_pedidos.log');
define('RA_TABLE',    'AnulacionPedidosHost');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function raLog(string $msg): void
{
    $dir = dirname(RA_LOG_FILE);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(RA_LOG_FILE, '[' . date('Y-m-d H:i:s') . "] [READ] $msg\n", FILE_APPEND);
}

function raVerifyToken(): bool
{
    $headers = getallheaders();
    $token   = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(RA_TOKEN, $token);
}

function raError(int $code, string $msg): void
{
    raLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    raError(405, 'Método no permitido. Se requiere POST.');
}

if (!raVerifyToken()) {
    raError(401, 'Token inválido o faltante.');
}

$rawBody    = file_get_contents('php://input');
$bodyJson   = json_decode($rawBody, true);

$sucursal   = $bodyJson['sucursal']    ?? $_POST['sucursal']    ?? null;
$codPedidos = $bodyJson['cod_pedidos'] ?? null;

if ($codPedidos === null && isset($_POST['cod_pedidos'])) {
    $codPedidos = json_decode($_POST['cod_pedidos'], true);
}

if (empty($sucursal) || !is_numeric($sucursal) || (int)$sucursal < 1) {
    raError(400, 'Parámetro sucursal inválido o faltante.');
}

$sucursal = (int)$sucursal;
$codPedidos = (!empty($codPedidos) && is_array($codPedidos)) ? array_filter(array_map('intval', $codPedidos), fn($v) => $v > 0) : [];
$codPedidos = array_values(array_unique($codPedidos));

raLog("CONSULTA - Sucursal=$sucursal | CodPedidos: " . implode(',', $codPedidos));

/** @var PDO $pdo */
global $conn;
$pdo = $conn;

try {
    $results = [];
    $params = [$sucursal];

    if (!empty($codPedidos)) {
        $placeholders = implode(',', array_fill(0, count($codPedidos), '?'));
        $stmt = $pdo->prepare(
            "SELECT CodPedido, Status, Motivo, HoraAnulada, Modalidad,
                    ComentarioAprobacion, EjecutadoEnTienda
             FROM `" . RA_TABLE . "`
             WHERE Sucursal = ? AND CodPedido IN ($placeholders)
             ORDER BY CodPedido ASC"
        );
        $stmt->execute(array_merge([$sucursal], $codPedidos));
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtPending = $pdo->prepare(
        "SELECT CodPedido, Status, Motivo, HoraAnulada, Modalidad,
                ComentarioAprobacion, EjecutadoEnTienda
         FROM `" . RA_TABLE . "`
         WHERE Sucursal = ? AND Status = 1 AND EjecutadoEnTienda = 0"
    );
    $stmtPending->execute([$sucursal]);
    $pendingApprovals = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

    $finalRegistros = array_values(array_column(array_merge($results, $pendingApprovals), null, 'CodPedido'));

    raLog("Respuesta - " . count($finalRegistros) . " registros encontrados.");

    echo json_encode([
        'success'    => true,
        'registros'  => $finalRegistros,
        'consultados'=> count($codPedidos),
        'encontrados'=> count($finalRegistros),
    ]);

} catch (PDOException $e) {
    raLog("ERROR PDO: " . $e->getMessage());
    raError(500, 'Error de base de datos: ' . $e->getMessage());
}
?>
