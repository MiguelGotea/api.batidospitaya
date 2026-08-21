<?php
/**
 * sync_kardex_porcionamiento.php
 * Sincronización unidireccional Access → MySQL para [Porcionamiento]
 * Tabla host: msaccess_masivo_Porcionamiento
 * Modos: limpiar_30dias | limpiar_total | limpiar_dia | insertar
 */
require_once __DIR__ . '/../core/database/conexion.php';

define('KPO_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KPO_TABLE', 'msaccess_masivo_Porcionamiento');
define('KPO_LOG',   __DIR__ . '/logs/sync_kardex_porcionamiento.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kpoLog(string $msg): void {
    $dir = dirname(KPO_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KPO_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}
function kpoError(int $code, string $msg): void {
    kpoLog("ERROR $code: $msg"); http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit();
}
function kpoVerifyToken(): bool {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = str_replace('Bearer ', '', trim($headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    return hash_equals(KPO_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') kpoError(405, 'Se requiere POST.');
if (!kpoVerifyToken()) kpoError(401, 'Token invalido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : -1;
$modo     = trim($body['modo'] ?? '');
$fecha    = trim($body['fecha'] ?? '');

if ($sucursal < 0) kpoError(400, 'Sucursal invalida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'limpiar_dia', 'insertar'])) kpoError(400, "Modo invalido: $modo");

global $conn; $pdo = $conn;
kpoLog("INICIO | Sucursal=$sucursal | Modo=$modo" . ($fecha ? " | Fecha=$fecha" : ''));

try {
    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare("DELETE FROM `" . KPO_TABLE . "` WHERE Sucursal=:suc AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kpoLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n Sucursal=$sucursal"]);
        exit();
    }
    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KPO_TABLE . "` WHERE Sucursal=:suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kpoLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n totales Sucursal=$sucursal"]);
        exit();
    }
    if ($modo === 'limpiar_dia') {
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) kpoError(400, 'Fecha invalida para limpiar_dia.');
        $stmt = $pdo->prepare("DELETE FROM `" . KPO_TABLE . "` WHERE Sucursal=:suc AND DATE(Fecha)=:fecha");
        $stmt->execute([':suc' => $sucursal, ':fecha' => $fecha]);
        $n = $stmt->rowCount();
        kpoLog("limpiar_dia OK | Fecha=$fecha | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n dia=$fecha Sucursal=$sucursal"]);
        exit();
    }

    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kpoError(400, 'rows vacio o invalido.');
    $ins = $upd = 0;

    $stmt = $pdo->prepare(
        "INSERT INTO `" . KPO_TABLE . "`
             (Sucursal, CodPorcionamiento, CodCotizacion, CodProcesamiento, Cantidad,
              Observaciones, Fecha, CodOperario, Procedencia, CodSubPorcionamiento,
              HInicial, HFinal, FechaUltimoSync)
         VALUES (:suc,:cod,:codcot,:codproc,:cant,:obs,:fecha,:codop,:proc,:codsub,:hini,:hfin,NOW())
         ON DUPLICATE KEY UPDATE
             CodCotizacion        = VALUES(CodCotizacion),
             CodProcesamiento     = VALUES(CodProcesamiento),
             Cantidad             = VALUES(Cantidad),
             Observaciones        = VALUES(Observaciones),
             Fecha                = VALUES(Fecha),
             CodOperario          = VALUES(CodOperario),
             Procedencia          = VALUES(Procedencia),
             CodSubPorcionamiento = VALUES(CodSubPorcionamiento),
             HInicial             = VALUES(HInicial),
             HFinal               = VALUES(HFinal),
             FechaUltimoSync      = NOW()"
    );

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodPorcionamiento']) ? (int)$r['CodPorcionamiento'] : 0;
        if ($cod < 1) { kpoLog("Fila $idx ignorada (CodPorcionamiento invalido)."); continue; }
        $stmt->execute([
            ':suc'     => $sucursal,
            ':cod'     => $cod,
            ':codcot'  => isset($r['CodCotizacion'])       ? (int)$r['CodCotizacion']       : null,
            ':codproc' => isset($r['CodProcesamiento'])     ? (int)$r['CodProcesamiento']     : null,
            ':cant'    => isset($r['Cantidad'])              ? (float)$r['Cantidad']           : null,
            ':obs'     => isset($r['Observaciones'])         ? (string)$r['Observaciones']     : null,
            ':fecha'   => isset($r['Fecha'])                 ? $r['Fecha']                     : null,
            ':codop'   => isset($r['CodOperario'])           ? (int)$r['CodOperario']          : null,
            ':proc'    => isset($r['Procedencia'])           ? (int)$r['Procedencia']           : null,
            ':codsub'  => isset($r['CodSubPorcionamiento'])  ? (int)$r['CodSubPorcionamiento']  : null,
            ':hini'    => isset($r['HInicial'])              ? $r['HInicial']                   : null,
            ':hfin'    => isset($r['HFinal'])                ? $r['HFinal']                     : null,
        ]);
        $rc = $stmt->rowCount(); if ($rc === 1) $ins++; elseif ($rc === 2) $upd++;
    }

    kpoLog("insertar OK | +$ins ins | ~$upd upd");
    echo json_encode(['success' => true, 'modo' => $modo, 'insertados' => $ins, 'actualizados' => $upd, 'message' => "OK Sucursal=$sucursal +$ins ~$upd"]);

} catch (PDOException $e) { kpoLog("PDO: " . $e->getMessage()); kpoError(500, 'DB error: ' . $e->getMessage()); }
  catch (Exception $e)    { kpoLog("ERR: " . $e->getMessage()); kpoError(500, 'Error: '    . $e->getMessage()); }
?>
