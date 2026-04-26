<?php
/**
 * sync_kardex_preingresos.php
 * Sincronización unidireccional Access → MySQL para [PreIngresoPitaya]
 * Tabla host: msaccess_masivo_PreIngresoPitaya
 * Modos: limpiar_30dias | limpiar_total | insertar
 */
require_once __DIR__ . '/../core/database/conexion.php';

define('KPI_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KPI_TABLE', 'msaccess_masivo_PreIngresoPitaya');
define('KPI_LOG',   __DIR__ . '/logs/sync_kardex_preingresos.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kpiLog(string $msg): void {
    $dir = dirname(KPI_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KPI_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}
function kpiError(int $code, string $msg): void {
    kpiLog("ERROR $code: $msg"); http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit();
}
function kpiVerifyToken(): bool {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = str_replace('Bearer ', '', trim($headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    return hash_equals(KPI_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') kpiError(405, 'Se requiere POST.');
if (!kpiVerifyToken()) kpiError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : -1;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) kpiError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar'])) kpiError(400, "Modo inválido: $modo");

global $conn; $pdo = $conn;
kpiLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {
    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare("DELETE FROM `" . KPI_TABLE . "` WHERE Sucursal=:suc AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kpiLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n Sucursal=$sucursal"]);
        exit();
    }
    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KPI_TABLE . "` WHERE Sucursal=:suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kpiLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n totales Sucursal=$sucursal"]);
        exit();
    }

    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kpiError(400, 'rows vacío o inválido.');
    $ins = $upd = 0;

    $stmt = $pdo->prepare(
        "INSERT INTO `" . KPI_TABLE . "` (Sucursal,CodPreIngresoPitaya,Fecha,Hora,Destino,Validado,Impreso,FechaUltimoSync)
         VALUES (:suc,:cod,:fecha,:hora,:dest,:val,:imp,NOW())
         ON DUPLICATE KEY UPDATE Fecha=VALUES(Fecha),Hora=VALUES(Hora),Destino=VALUES(Destino),
             Validado=VALUES(Validado),Impreso=VALUES(Impreso),FechaUltimoSync=NOW()"
    );
    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodPreIngresoPitaya']) ? (int)$r['CodPreIngresoPitaya'] : 0;
        if ($cod < 1) { kpiLog("Fila $idx ignorada."); continue; }
        $stmt->execute([':suc'=>$sucursal,':cod'=>$cod,':fecha'=>$r['Fecha']??null,
            ':hora'=>$r['Hora']??null,':dest'=>$r['Destino']??null,
            ':val'=>isset($r['Validado'])?(int)$r['Validado']:null,
            ':imp'=>isset($r['Impreso'])?(int)$r['Impreso']:null]);
        $rc=$stmt->rowCount(); if($rc===1)$ins++; elseif($rc===2)$upd++;
    }
    kpiLog("insertar OK | +$ins ins | ~$upd upd");
    echo json_encode(['success'=>true,'modo'=>$modo,'insertados'=>$ins,'actualizados'=>$upd,'message'=>"OK Sucursal=$sucursal +$ins ~$upd"]);

} catch (PDOException $e) { kpiLog("PDO: ".$e->getMessage()); kpiError(500,'DB error: '.$e->getMessage()); }
  catch (Exception $e)    { kpiLog("ERR: ".$e->getMessage()); kpiError(500,'Error: '.$e->getMessage()); }
?>
