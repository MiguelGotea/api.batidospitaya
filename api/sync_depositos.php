<?php
/**
 * sync_depositos.php
 * Sincronización unidireccional Access → MySQL para [Depositos]
 * Tabla host: msaccess_masivo_Depositos
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('SDEP_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('SDEP_TABLE', 'msaccess_masivo_Depositos');
define('SDEP_LOG',   __DIR__ . '/logs/sync_depositos.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function sdepLog(string $msg): void
{
    $dir = dirname(SDEP_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(SDEP_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function sdepError(int $code, string $msg): void
{
    sdepLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function sdepVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(SDEP_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    sdepError(405, 'Se requiere POST.');
if (!sdepVerifyToken())                        sdepError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) sdepError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    sdepError(400, "Modo inválido: $modo");

global $conn;
$pdo = $conn;

sdepLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {

    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . SDEP_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        sdepLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros Sucursal=$sucursal"]);
        exit();
    }

    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . SDEP_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        sdepLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // modo = insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) sdepError(400, 'rows vacío o inválido.');

    $insertados = $actualizados = 0;

    $sql = "INSERT INTO `" . SDEP_TABLE . "`
                (Sucursal, CodDeposito, Monto, Denominacion, Tipo,
                 Fecha, Observacion, DuranteTurno, FechaUltimoSync)
            VALUES
                (:suc, :cod, :monto, :denom, :tipo,
                 :fecha, :obs, :turno, NOW())
            ON DUPLICATE KEY UPDATE
                Monto           = VALUES(Monto),
                Denominacion    = VALUES(Denominacion),
                Tipo            = VALUES(Tipo),
                Fecha           = VALUES(Fecha),
                Observacion     = VALUES(Observacion),
                DuranteTurno    = VALUES(DuranteTurno),
                FechaUltimoSync = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodDeposito']) ? (int)$r['CodDeposito'] : 0;
        if ($cod < 1) { sdepLog("Fila $idx ignorada: CodDeposito inválido."); continue; }

        $stmt->execute([
            ':suc'   => $sucursal,
            ':cod'   => $cod,
            ':monto' => isset($r['Monto'])       ? (int)$r['Monto']         : null,
            ':denom' => $r['Denominacion']        ?? null,
            ':tipo'  => $r['Tipo']                ?? null,
            ':fecha' => !empty($r['Fecha'])        ? $r['Fecha']             : null,
            ':obs'   => $r['Observacion']          ?? null,
            ':turno' => isset($r['DuranteTurno']) ? (int)$r['DuranteTurno'] : null,
        ]);

        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    sdepLog("insertar OK | +$insertados ins | ~$actualizados upd");
    echo json_encode([
        'success'      => true,
        'modo'         => $modo,
        'insertados'   => $insertados,
        'actualizados' => $actualizados,
        'message'      => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    sdepLog("PDO ERROR: " . $e->getMessage());
    sdepError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    sdepLog("ERROR: " . $e->getMessage());
    sdepError(500, 'Error interno: ' . $e->getMessage());
}
?>
