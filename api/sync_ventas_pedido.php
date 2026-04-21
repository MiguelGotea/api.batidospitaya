<?php
/**
 * api/sync_ventas_pedido.php
 * Endpoint para sincronización en vivo de ventas desde Access a MySQL.
 *
 * Recibe los registros de un pedido específico (filas de VentasGlobalesAccessCSV),
 * elimina los existentes para ese CodPedido + local, e inserta los nuevos.
 *
 * Parámetros POST:
 *   token      : Token de autenticación (requerido)
 *   sucursal   : Código de la sucursal / local (requerido)
 *   cod_pedido : Número de pedido a sincronizar (requerido)
 *   rows       : JSON array con los registros a insertar (requerido)
 *
 * Respuesta JSON:
 *   { "success": true/false, "deleted": N, "inserted": N, "message": "..." }
 */

require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
define('API_TOKEN',  'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE',   __DIR__ . '/logs/sync_ventas_pedido.log');
define('TABLE_NAME', 'VentasGlobalesAccessCSV');

// Headers
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

// ── Helpers ──────────────────────────────────────────────────────────────────
function logMsg(string $msg): void
{
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function verifyToken(): bool
{
    $headers = getallheaders();
    $token   = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(API_TOKEN, $token);
}

function jsonError(int $code, string $msg): void
{
    logMsg("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

// ── Columnas permitidas (exactas a la tabla) ──────────────────────────────────
$ALLOWED_COLUMNS = [
    'Anulado', 'MotivoAnulado', 'Fecha', 'Hora', 'CodPedido', 'CodCliente',
    'aPOS', 'Delivery_Nombre', 'Tipo', 'NombreGrupo', 'DBBatidos_Nombre',
    'Medida', 'Cantidad', 'CodigoPromocion', 'Precio', 'local', 'Caja',
    'Modalidad', 'Motorizado', 'Observaciones', 'Precio_Unitario_Sin_Descuento',
    'Impresiones', 'HoraCreado', 'HoraIngresoProducto', 'HoraImpreso',
    'Propina', 'Semana', 'Puntos', 'CodProducto', 'MontoFactura',
    'Sucursal_Nombre', 'PedidoDeCentral', 'CodMotorizado'
];

// ── Main ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError(405, 'Método no permitido. Se requiere POST.');
}

// 1) Autenticación
if (!verifyToken()) {
    jsonError(401, 'Token inválido o faltante.');
}

// 2) Leer body: puede venir como JSON puro o como form-data
$rawBody = file_get_contents('php://input');
$bodyJson = json_decode($rawBody, true);

$sucursal  = $bodyJson['sucursal']  ?? $_POST['sucursal']  ?? null;
$codPedido = $bodyJson['cod_pedido'] ?? $_POST['cod_pedido'] ?? null;
$rowsInput = $bodyJson['rows']      ?? null;

// Si rows no vino en JSON, intentar desde POST (Access puede mandar JSON string)
if ($rowsInput === null && isset($_POST['rows'])) {
    $rowsInput = json_decode($_POST['rows'], true);
}

// 3) Validar parámetros obligatorios
if (empty($sucursal) || !is_numeric($sucursal)) {
    jsonError(400, 'Parámetro sucursal inválido o faltante.');
}
if (empty($codPedido) || !is_numeric($codPedido)) {
    jsonError(400, 'Parámetro cod_pedido inválido o faltante.');
}
if (empty($rowsInput) || !is_array($rowsInput)) {
    jsonError(400, 'Parámetro rows inválido, vacío o no es un array JSON.');
}

$sucursal  = (int)$sucursal;
$codPedido = (int)$codPedido;
$totalRows = count($rowsInput);

logMsg("INICIO sync - Sucursal: $sucursal | CodPedido: $codPedido | Filas recibidas: $totalRows");

// 4) Conexión BD
global $conn;
/** @var PDO $pdo */
$pdo = $conn;

try {
    $pdo->beginTransaction();

    // 5) DELETE de registros existentes para este pedido y sucursal
    $stmtDel = $pdo->prepare(
        "DELETE FROM `" . TABLE_NAME . "` WHERE `CodPedido` = :cod AND `local` = :suc"
    );
    $stmtDel->execute([':cod' => $codPedido, ':suc' => $sucursal]);
    $deletedRows = $stmtDel->rowCount();
    logMsg("DELETE OK - Eliminadas $deletedRows filas existentes.");

    // 6) Validar y filtrar columnas de la primera fila
    $firstRow     = $rowsInput[0];
    $inputCols    = array_keys($firstRow);
    $safeCols     = array_intersect($inputCols, $ALLOWED_COLUMNS);

    if (empty($safeCols)) {
        $pdo->rollBack();
        jsonError(400, 'Ninguna columna del payload coincide con la tabla destino.');
    }

    // Preparar columnas para el INSERT
    $colList   = implode(', ', array_map(fn($c) => "`$c`", $safeCols));
    $placeholderList = implode(', ', array_map(fn($c) => ":$c", $safeCols));
    $insertSql = "INSERT INTO `" . TABLE_NAME . "` ($colList) VALUES ($placeholderList)";
    $stmtIns   = $pdo->prepare($insertSql);

    // 7) Insertar fila por fila (permite manejar errores individuales sin rollback total)
    $insertedRows = 0;
    $errores      = [];

    foreach ($rowsInput as $idx => $row) {
        $params = [];
        foreach ($safeCols as $col) {
            $val = $row[$col] ?? null;
            // Convertir string vacío a NULL
            if ($val !== null && trim((string)$val) === '') {
                $val = null;
            }
            $params[":$col"] = $val;
        }

        try {
            $stmtIns->execute($params);
            $insertedRows++;
        } catch (PDOException $rowEx) {
            $errores[] = "Fila $idx: " . $rowEx->getMessage();
            logMsg("ERROR fila $idx: " . $rowEx->getMessage());
        }
    }

    $pdo->commit();

    $respuesta = [
        'success'  => true,
        'deleted'  => $deletedRows,
        'inserted' => $insertedRows,
        'total'    => $totalRows,
        'message'  => "Sync OK - Pedido $codPedido | Sucursal $sucursal | +$insertedRows filas"
    ];

    if (!empty($errores)) {
        $respuesta['warnings'] = $errores;
    }

    logMsg("FIN OK - Eliminadas: $deletedRows | Insertadas: $insertedRows | Errores: " . count($errores));
    echo json_encode($respuesta);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logMsg("ERROR PDO general: " . $e->getMessage());
    jsonError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logMsg("ERROR general: " . $e->getMessage());
    jsonError(500, 'Error interno: ' . $e->getMessage());
}
?>
