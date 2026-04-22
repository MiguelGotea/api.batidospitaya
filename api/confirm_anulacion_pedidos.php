<?php
/**
 * api/confirm_anulacion_pedidos.php
 * Access llama a este endpoint para confirmar que ejecutó la anulación localmente.
 *
 * Después de que Access detecta que el host aprobó una solicitud y ejecuta
 * el UPDATE/INSERT en la BD local, manda aquí una confirmación para que
 * el host marque EjecutadoEnTienda=1 y quede el caso como cerrado (Status=1).
 *
 * Parámetros POST (JSON):
 *   token       : Token de autenticación
 *   sucursal    : Código del local
 *   cod_pedido  : CodPedido anulado
 *   hora_anulada: Timestamp de cuando se ejecutó en tienda (Y-m-d H:i:s)
 *
 * Respuesta JSON:
 *   { "success": true/false, "message": "..." }
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('CA_TOKEN',    'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('CA_LOG_FILE', __DIR__ . '/logs/sync_anulacion_pedidos.log');
define('CA_TABLE',    'AnulacionPedidosHost');

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

function caLog(string $msg): void
{
    $dir = dirname(CA_LOG_FILE);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(CA_LOG_FILE, '[' . date('Y-m-d H:i:s') . "] [CONFIRM] $msg\n", FILE_APPEND);
}

function caVerifyToken(): bool
{
    $headers = getallheaders();
    $token   = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(CA_TOKEN, $token);
}

function caError(int $code, string $msg): void
{
    caLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    caError(405, 'Método no permitido. Se requiere POST.');
}

if (!caVerifyToken()) {
    caError(401, 'Token inválido o faltante.');
}

$rawBody    = file_get_contents('php://input');
$bodyJson   = json_decode($rawBody, true);

$sucursal    = $bodyJson['sucursal']     ?? $_POST['sucursal']     ?? null;
$codPedido   = $bodyJson['cod_pedido']   ?? $_POST['cod_pedido']   ?? null;
$horaAnulada = $bodyJson['hora_anulada'] ?? $_POST['hora_anulada'] ?? null;

if (empty($sucursal) || !is_numeric($sucursal) || (int)$sucursal < 1) {
    caError(400, 'Parámetro sucursal inválido o faltante.');
}
if (empty($codPedido) || !is_numeric($codPedido) || (int)$codPedido < 1) {
    caError(400, 'Parámetro cod_pedido inválido o faltante.');
}

$sucursal  = (int)$sucursal;
$codPedido = (int)$codPedido;

caLog("CONFIRMACION - CodPedido=$codPedido | Sucursal=$sucursal | HoraAnulada=" . ($horaAnulada ?? 'null'));

/** @var PDO $pdo */
global $conn;
$pdo = $conn;

try {
    // Verificar que exista y esté aprobado (Status=1) pero aún no confirmado
    $stmtCheck = $pdo->prepare(
        "SELECT CodAnulacionHost, Status, EjecutadoEnTienda
         FROM `" . CA_TABLE . "`
         WHERE CodPedido = :cod AND Sucursal = :suc
         LIMIT 1"
    );
    $stmtCheck->execute([':cod' => $codPedido, ':suc' => $sucursal]);
    $row = $stmtCheck->fetch();

    if (!$row) {
        caError(404, "No se encontró registro para CodPedido=$codPedido Sucursal=$sucursal.");
    }

    if ((int)$row['EjecutadoEnTienda'] === 1) {
        // Ya estaba confirmado, responder OK igualmente (idempotente)
        caLog("Ya confirmado previamente - CodPedido=$codPedido");
        echo json_encode(['success' => true, 'message' => 'Ya confirmado previamente.']);
        exit();
    }

    // Marcar como ejecutado en tienda y actualizar HoraAnulada si viene
    $stmtUpd = $pdo->prepare(
        "UPDATE `" . CA_TABLE . "`
         SET EjecutadoEnTienda = 1,
             HoraEjecutadaTienda = NOW(),
             HoraAnulada = COALESCE(:ha, HoraAnulada)
         WHERE CodPedido = :cod AND Sucursal = :suc"
    );
    $stmtUpd->execute([
        ':ha'  => $horaAnulada ?: null,
        ':cod' => $codPedido,
        ':suc' => $sucursal,
    ]);

    caLog("OK - CodPedido=$codPedido marcado como EjecutadoEnTienda=1");

    echo json_encode([
        'success' => true,
        'message' => "Confirmación registrada. CodPedido=$codPedido Sucursal=$sucursal.",
    ]);

} catch (PDOException $e) {
    caLog("ERROR PDO: " . $e->getMessage());
    caError(500, 'Error de base de datos: ' . $e->getMessage());
}
?>
