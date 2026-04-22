<?php
/**
 * api/sync_anulacion_pedidos.php
 * Endpoint para sincronizar solicitudes de anulación desde Access → MySQL.
 *
 * Recibe un array de registros de AnulacionPedidos con Status=0 (pendientes)
 * e inserta los que no existen o actualiza los que sí existen en el host.
 * Solo acepta registros con Status=0; los con Status=1 no se vuelven a subir.
 *
 * Parámetros POST (JSON):
 *   token    : Token de autenticación (requerido)
 *   sucursal : Código del local (requerido)
 *   rows     : Array de registros AnulacionPedidos con Status=0
 *
 * Cada fila:
 *   CodPedido, HoraSolicitada, HoraAnulada, Status, Modalidad,
 *   CodPedidoCambio, Motivo, CodMotivoAnulacion, Sucursal
 *
 * Respuesta JSON:
 *   { "success": true/false, "insertados": N, "actualizados": N, "ignorados": N, "message": "..." }
 */

require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
define('AP_TOKEN',    'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('AP_LOG_FILE', __DIR__ . '/logs/sync_anulacion_pedidos.log');
define('AP_TABLE',    'AnulacionPedidosHost');

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
function apLog(string $msg): void
{
    $dir = dirname(AP_LOG_FILE);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(AP_LOG_FILE, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function apVerifyToken(): bool
{
    $headers = getallheaders();
    $token   = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(AP_TOKEN, $token);
}

function apError(int $code, string $msg): void
{
    apLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

// ── Main ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apError(405, 'Método no permitido. Se requiere POST.');
}

if (!apVerifyToken()) {
    apError(401, 'Token inválido o faltante.');
}

$rawBody  = file_get_contents('php://input');
$bodyJson = json_decode($rawBody, true);

$sucursal  = $bodyJson['sucursal'] ?? $_POST['sucursal'] ?? null;
$rowsInput = $bodyJson['rows']     ?? null;

if ($rowsInput === null && isset($_POST['rows'])) {
    $rowsInput = json_decode($_POST['rows'], true);
}

if (empty($sucursal) || !is_numeric($sucursal) || (int)$sucursal < 1) {
    apError(400, 'Parámetro sucursal inválido o faltante.');
}
if (empty($rowsInput) || !is_array($rowsInput)) {
    apError(400, 'Parámetro rows vacío o inválido.');
}

$sucursal = (int)$sucursal;

apLog("INICIO sync anulaciones - Sucursal: $sucursal | Filas recibidas: " . count($rowsInput));

/** @var PDO $pdo */
global $conn;
$pdo = $conn;

$insertados   = 0;
$actualizados = 0;
$ignorados    = 0;
$errores      = [];

try {
    foreach ($rowsInput as $idx => $row) {
        // Validar campos mínimos
        $codPedido = isset($row['CodPedido']) ? (int)$row['CodPedido'] : 0;
        $status    = isset($row['Status'])    ? (int)$row['Status']    : -1;

        if ($codPedido < 1) {
            $errores[] = "Fila $idx: CodPedido inválido.";
            continue;
        }

        // Solo se procesan Status=0 (pendientes)
        if ($status !== 0) {
            $ignorados++;
            apLog("Fila $idx: Ignorada - CodPedido=$codPedido Status=$status (no es pendiente).");
            continue;
        }

        // Verificar si ya existe en el host (por CodPedido + Sucursal)
        $stmtCheck = $pdo->prepare(
            "SELECT CodAnulacionHost, Status FROM `" . AP_TABLE . "`
             WHERE CodPedido = :cod AND Sucursal = :suc
             LIMIT 1"
        );
        $stmtCheck->execute([':cod' => $codPedido, ':suc' => $sucursal]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Si ya existe con Status=1 (resuelto), no actualizar
            if ((int)$existing['Status'] === 1) {
                $ignorados++;
                apLog("Fila $idx: Ignorada - CodPedido=$codPedido ya tiene Status=1 en host.");
                continue;
            }
            // Si existe con Status=0, actualizar datos (puede haber cambios en Motivo, etc.)
            $stmtUpd = $pdo->prepare(
                "UPDATE `" . AP_TABLE . "`
                 SET HoraSolicitada    = :hs,
                     Modalidad         = :mod,
                     CodPedidoCambio   = :cpc,
                     Motivo            = :mot,
                     CodMotivoAnulacion= :cma,
                     FechaUltimoSync   = NOW()
                 WHERE CodPedido = :cod AND Sucursal = :suc AND Status = 0"
            );
            $stmtUpd->execute([
                ':hs'  => $row['HoraSolicitada']     ?? null,
                ':mod' => $row['Modalidad']           ?? null,
                ':cpc' => $row['CodPedidoCambio']     ?? 0,
                ':mot' => $row['Motivo']              ?? null,
                ':cma' => $row['CodMotivoAnulacion']  ?? null,
                ':cod' => $codPedido,
                ':suc' => $sucursal,
            ]);
            $actualizados++;
            apLog("Actualizado - CodPedido=$codPedido Sucursal=$sucursal");
        } else {
            // Nuevo registro: insertar
            $stmtIns = $pdo->prepare(
                "INSERT INTO `" . AP_TABLE . "`
                 (CodPedido, HoraSolicitada, HoraAnulada, Status, Modalidad,
                  CodPedidoCambio, Motivo, CodMotivoAnulacion, Sucursal,
                  FechaUltimoSync, EjecutadoEnTienda)
                 VALUES
                 (:cod, :hs, :ha, 0, :mod,
                  :cpc, :mot, :cma, :suc,
                  NOW(), 0)"
            );
            $stmtIns->execute([
                ':cod' => $codPedido,
                ':hs'  => $row['HoraSolicitada']     ?? null,
                ':ha'  => $row['HoraAnulada']        ?? null,
                ':mod' => $row['Modalidad']           ?? null,
                ':cpc' => $row['CodPedidoCambio']     ?? 0,
                ':mot' => $row['Motivo']              ?? null,
                ':cma' => $row['CodMotivoAnulacion']  ?? null,
                ':suc' => $sucursal,
            ]);
            $insertados++;
            apLog("Insertado - CodPedido=$codPedido Sucursal=$sucursal");
        }
    }

    $respuesta = [
        'success'     => true,
        'insertados'  => $insertados,
        'actualizados'=> $actualizados,
        'ignorados'   => $ignorados,
        'message'     => "Sync anulaciones OK - Sucursal $sucursal | +$insertados ins | ~$actualizados upd | $ignorados ign",
    ];

    if (!empty($errores)) {
        $respuesta['warnings'] = $errores;
    }

    apLog("FIN OK - Insertados: $insertados | Actualizados: $actualizados | Ignorados: $ignorados | Errores: " . count($errores));
    echo json_encode($respuesta);

} catch (PDOException $e) {
    apLog("ERROR PDO: " . $e->getMessage());
    apError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    apLog("ERROR general: " . $e->getMessage());
    apError(500, 'Error interno: ' . $e->getMessage());
}
?>
