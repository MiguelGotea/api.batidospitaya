<?php
/**
 * sync_kardex_sub_porcionamiento.php
 * Sincronizacion unidireccional Access -> MySQL para [SubPorcionamiento]
 * Tabla host: msaccess_masivo_SubPorcionamiento
 *
 * NOTA limpiar_30dias: SubPorcionamiento no tiene campo Fecha propio,
 * se filtra por JOIN con msaccess_masivo_Porcionamiento.Fecha
 *
 * Modos: limpiar_30dias | limpiar_total | limpiar_dia | insertar
 */
require_once __DIR__ . '/../core/database/conexion.php';

define('KSPO_TOKEN',     'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KSPO_TABLE',     'msaccess_masivo_SubPorcionamiento');
define('KSPO_TABLE_PAD', 'msaccess_masivo_Porcionamiento');
define('KSPO_LOG',       __DIR__ . '/logs/sync_kardex_sub_porcionamiento.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kspoLog(string $msg): void {
    $dir = dirname(KSPO_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KSPO_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}
function kspoError(int $code, string $msg): void {
    kspoLog("ERROR $code: $msg"); http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit();
}
function kspoVerifyToken(): bool {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = str_replace('Bearer ', '', trim($headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    return hash_equals(KSPO_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') kspoError(405, 'Se requiere POST.');
if (!kspoVerifyToken()) kspoError(401, 'Token invalido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : -1;
$modo     = trim($body['modo'] ?? '');
$fecha    = trim($body['fecha'] ?? '');

if ($sucursal < 0) kspoError(400, 'Sucursal invalida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'limpiar_dia', 'insertar'])) kspoError(400, "Modo invalido: $modo");

global $conn; $pdo = $conn;
kspoLog("INICIO | Sucursal=$sucursal | Modo=$modo" . ($fecha ? " | Fecha=$fecha" : ''));

try {
    // -- limpiar_30dias: JOIN con tabla padre para obtener la fecha
    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE sp FROM `" . KSPO_TABLE . "` sp
             INNER JOIN `" . KSPO_TABLE_PAD . "` pp
                     ON sp.CodSubPorcionamiento = pp.CodSubPorcionamiento
                    AND sp.Sucursal = pp.Sucursal
             WHERE sp.Sucursal = :suc
               AND pp.Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kspoLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n Sucursal=$sucursal"]);
        exit();
    }

    // -- limpiar_total
    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KSPO_TABLE . "` WHERE Sucursal=:suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kspoLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n totales Sucursal=$sucursal"]);
        exit();
    }

    // -- limpiar_dia: JOIN con padre por fecha
    if ($modo === 'limpiar_dia') {
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) kspoError(400, 'Fecha invalida para limpiar_dia.');
        $stmt = $pdo->prepare(
            "DELETE sp FROM `" . KSPO_TABLE . "` sp
             INNER JOIN `" . KSPO_TABLE_PAD . "` pp
                     ON sp.CodSubPorcionamiento = pp.CodSubPorcionamiento
                    AND sp.Sucursal = pp.Sucursal
             WHERE sp.Sucursal = :suc
               AND DATE(pp.Fecha) = :fecha"
        );
        $stmt->execute([':suc' => $sucursal, ':fecha' => $fecha]);
        $n = $stmt->rowCount();
        kspoLog("limpiar_dia OK | Fecha=$fecha | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n dia=$fecha Sucursal=$sucursal"]);
        exit();
    }

    // -- insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kspoError(400, 'rows vacio o invalido.');
    $ins = $upd = 0;

    $stmt = $pdo->prepare(
        "INSERT INTO `" . KSPO_TABLE . "`
             (Sucursal, CodSubPorcionamiento, Procedencia, CodProcesamiento, Cantidad,
              Fecha, HInicial, HFinal, CodOperario, FechaUltimoSync)
         VALUES (:suc,:cod,:proc,:codproc,:cant,:fecha,:hini,:hfin,:codop,NOW())
         ON DUPLICATE KEY UPDATE
             Procedencia      = VALUES(Procedencia),
             CodProcesamiento = VALUES(CodProcesamiento),
             Cantidad         = VALUES(Cantidad),
             Fecha            = VALUES(Fecha),
             HInicial         = VALUES(HInicial),
             HFinal           = VALUES(HFinal),
             CodOperario      = VALUES(CodOperario),
             FechaUltimoSync  = NOW()"
    );

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodSubPorcionamiento']) ? (int)$r['CodSubPorcionamiento'] : 0;
        if ($cod < 1) { kspoLog("Fila $idx ignorada (CodSubPorcionamiento invalido)."); continue; }
        $stmt->execute([
            ':suc'     => $sucursal,
            ':cod'     => $cod,
            ':proc'    => isset($r['Procedencia'])       ? (int)$r['Procedencia']       : null,
            ':codproc' => isset($r['CodProcesamiento'])  ? (int)$r['CodProcesamiento']  : null,
            ':cant'    => isset($r['Cantidad'])           ? (float)$r['Cantidad']        : null,
            ':fecha'   => isset($r['Fecha'])              ? $r['Fecha']                  : null,
            ':hini'    => isset($r['HInicial'])           ? $r['HInicial']               : null,
            ':hfin'    => isset($r['HFinal'])             ? $r['HFinal']                 : null,
            ':codop'   => isset($r['CodOperario'])        ? (int)$r['CodOperario']       : null,
        ]);
        $rc = $stmt->rowCount(); if ($rc === 1) $ins++; elseif ($rc === 2) $upd++;
    }

    kspoLog("insertar OK | +$ins ins | ~$upd upd");
    echo json_encode(['success' => true, 'modo' => $modo, 'insertados' => $ins, 'actualizados' => $upd, 'message' => "OK Sucursal=$sucursal +$ins ~$upd"]);

} catch (PDOException $e) { kspoLog("PDO: " . $e->getMessage()); kspoError(500, 'DB error: ' . $e->getMessage()); }
  catch (Exception $e)    { kspoLog("ERR: " . $e->getMessage()); kspoError(500, 'Error: '    . $e->getMessage()); }
?>
