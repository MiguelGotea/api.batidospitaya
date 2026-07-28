<?php
/**
 * api/sync_ventas_dia.php
 * Endpoint para sincronización de ventas de un día completo desde Access a MySQL.
 * Soporta envío por chunks para evitar timeouts con días de alto volumen.
 *
 * Parámetros POST (JSON):
 *   sucursal      : Código de la sucursal / local (requerido, entero >= 1)
 *   fecha         : Fecha en formato YYYY-MM-DD (requerido)
 *   rows          : JSON array con los registros del chunk (requerido)
 *   chunk_index   : Número de chunk actual, 1-based (requerido)
 *   total_chunks  : Total de chunks que se enviarán (requerido)
 *   is_first_chunk: true/false — si es true ejecuta DELETE antes del INSERT
 *
 * Respuesta JSON:
 *   {
 *     "success": true,
 *     "chunk": N, "of": M,
 *     "fecha": "YYYY-MM-DD",
 *     "sucursal": N,
 *     "deleted": N,        <- solo presente cuando is_first_chunk = true
 *     "inserted": N,
 *     "total_in_chunk": N,
 *     "message": "..."
 *   }
 */

require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
define('API_TOKEN',  'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE',   __DIR__ . '/logs/sync_ventas_dia.log');
define('TABLE_NAME', 'VentasGlobalesAccessCSV');

// Headers CORS
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

// ── Helpers ───────────────────────────────────────────────────────────────────

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

// ── Columnas permitidas (idénticas a sync_ventas_pedido.php) ──────────────────
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

// 2) Leer body JSON
$rawBody  = file_get_contents('php://input');
$bodyJson = json_decode($rawBody, true);

if (!is_array($bodyJson)) {
    jsonError(400, 'Body inválido. Se espera un objeto JSON.');
}

$sucursal     = $bodyJson['sucursal']       ?? null;
$fecha        = $bodyJson['fecha']          ?? null;
$rowsInput    = $bodyJson['rows']           ?? null;
$chunkIndex   = $bodyJson['chunk_index']    ?? null;
$totalChunks  = $bodyJson['total_chunks']   ?? null;
$isFirstChunk = $bodyJson['is_first_chunk'] ?? false;

// 3) Validaciones ──────────────────────────────────────────────────────────────

// sucursal: entero >= 1
if (empty($sucursal) || !is_numeric($sucursal) || (int)$sucursal < 1) {
    jsonError(400, 'Parámetro sucursal inválido o faltante (debe ser entero >= 1).');
}

// fecha: formato YYYY-MM-DD y fecha real
if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    jsonError(400, 'Parámetro fecha inválido. Formato requerido: YYYY-MM-DD.');
}
$fechaDt = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fechaDt || $fechaDt->format('Y-m-d') !== $fecha) {
    jsonError(400, "La fecha '$fecha' no es una fecha de calendario válida.");
}

// rows: array no vacío
if (empty($rowsInput) || !is_array($rowsInput) || count($rowsInput) === 0) {
    jsonError(400, 'Parámetro rows vacío o inválido. No se ejecuta operación sin filas.');
}

// chunk_index: entero >= 1
if ($chunkIndex === null || !is_numeric($chunkIndex) || (int)$chunkIndex < 1) {
    jsonError(400, 'Parámetro chunk_index inválido (debe ser entero >= 1).');
}

// total_chunks: entero >= 1
if ($totalChunks === null || !is_numeric($totalChunks) || (int)$totalChunks < 1) {
    jsonError(400, 'Parámetro total_chunks inválido (debe ser entero >= 1).');
}

$sucursal    = (int)$sucursal;
$chunkIndex  = (int)$chunkIndex;
$totalChunks = (int)$totalChunks;
$totalRows   = count($rowsInput);

// 3b) Verificación cruzada: local en cada fila debe coincidir con sucursal declarada.
//     Protege contra payloads mal construidos que mezclen sucursales.
foreach ($rowsInput as $idxCheck => $rowCheck) {
    $localEnFila = isset($rowCheck['local']) ? (int)$rowCheck['local'] : null;
    if ($localEnFila !== null && $localEnFila !== $sucursal) {
        logMsg("RECHAZO cross-check: sucursal param=$sucursal pero fila[$idxCheck][local]=$localEnFila");
        jsonError(400,
            "Inconsistencia de sucursal: el parámetro dice $sucursal " .
            "pero la fila $idxCheck contiene local=$localEnFila. " .
            "Operación cancelada por seguridad."
        );
    }
}

logMsg(
    "INICIO chunk $chunkIndex/$totalChunks | Fecha: $fecha | Sucursal: $sucursal | " .
    "Filas: $totalRows | PrimerChunk: " . ($isFirstChunk ? 'SI' : 'NO')
);

// 4) Conexión BD
global $conn;
/** @var PDO $pdo */
$pdo = $conn;

try {
    $pdo->beginTransaction();

    $deletedRows = 0;

    // 5) DELETE — solo en el primer chunk para limpiar el día antes de reinsertar.
    //    Doble guardia: DATE(Fecha) + local evita borrados masivos accidentales.
    if ($isFirstChunk) {
        $stmtDel = $pdo->prepare(
            "DELETE FROM `" . TABLE_NAME . "` WHERE DATE(`Fecha`) = :fecha AND `local` = :suc"
        );
        $stmtDel->execute([':fecha' => $fecha, ':suc' => $sucursal]);
        $deletedRows = $stmtDel->rowCount();
        logMsg("DELETE OK | Fecha=$fecha | local=$sucursal | Eliminadas: $deletedRows filas.");
    }

    // 6) Preparar columnas del INSERT (se detectan de la primera fila del chunk)
    $firstRow  = $rowsInput[0];
    $inputCols = array_keys($firstRow);
    $safeCols  = array_intersect($inputCols, $ALLOWED_COLUMNS);

    if (empty($safeCols)) {
        $pdo->rollBack();
        jsonError(400, 'Ninguna columna del payload coincide con la tabla destino.');
    }

    $colList         = implode(', ', array_map(fn($c) => "`$c`", $safeCols));
    $placeholderList = implode(', ', array_map(fn($c) => ":$c", $safeCols));
    $insertSql       = "INSERT INTO `" . TABLE_NAME . "` ($colList) VALUES ($placeholderList)";
    $stmtIns         = $pdo->prepare($insertSql);

    // 7) Insertar fila por fila del chunk (errores individuales no hacen rollback total)
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
        'success'        => true,
        'chunk'          => $chunkIndex,
        'of'             => $totalChunks,
        'fecha'          => $fecha,
        'sucursal'       => $sucursal,
        'inserted'       => $insertedRows,
        'total_in_chunk' => $totalRows,
        'message'        => "Chunk $chunkIndex/$totalChunks OK | Fecha $fecha | Sucursal $sucursal | +$insertedRows filas"
    ];

    // deleted solo se incluye cuando se hizo el DELETE (primer chunk)
    if ($isFirstChunk) {
        $respuesta['deleted'] = $deletedRows;
    }

    if (!empty($errores)) {
        $respuesta['warnings'] = $errores;
    }

    logMsg(
        "FIN chunk $chunkIndex/$totalChunks | Eliminadas: $deletedRows | " .
        "Insertadas: $insertedRows | Errores: " . count($errores)
    );
    echo json_encode($respuesta);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logMsg("ERROR PDO: " . $e->getMessage());
    jsonError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logMsg("ERROR general: " . $e->getMessage());
    jsonError(500, 'Error interno: ' . $e->getMessage());
}
?>
