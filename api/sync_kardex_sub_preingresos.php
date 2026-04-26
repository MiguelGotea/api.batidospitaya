<?php
/**
 * sync_kardex_sub_preingresos.php
 * Sincronización unidireccional Access → MySQL para [SubPreIngresosPitaya]
 * Tabla host: msaccess_masivo_SubPreIngresosPitaya
 *
 * NOTA limpiar_30dias: Se filtra por JOIN con msaccess_masivo_PreIngresoPitaya.Fecha
 * ya que SubPreIngresosPitaya no tiene campo Fecha propio.
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */
require_once __DIR__ . '/../core/database/conexion.php';

define('KSPI_TOKEN',     'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KSPI_TABLE',     'msaccess_masivo_SubPreIngresosPitaya');
define('KSPI_TABLE_PAD', 'msaccess_masivo_PreIngresoPitaya');
define('KSPI_LOG',       __DIR__ . '/logs/sync_kardex_sub_preingresos.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kspiLog(string $msg): void {
    $dir = dirname(KSPI_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KSPI_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}
function kspiError(int $code, string $msg): void {
    kspiLog("ERROR $code: $msg"); http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit();
}
function kspiVerifyToken(): bool {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = str_replace('Bearer ', '', trim($headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    return hash_equals(KSPI_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') kspiError(405, 'Se requiere POST.');
if (!kspiVerifyToken()) kspiError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : -1;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) kspiError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar'])) kspiError(400, "Modo inválido: $modo");

global $conn; $pdo = $conn;
kspiLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {
    // ── limpiar_30dias ───────────────────────────────────────────────────────
    // JOIN con PreIngresoPitaya del host para obtener la fecha del padre
    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE sp FROM `" . KSPI_TABLE . "` sp
             INNER JOIN `" . KSPI_TABLE_PAD . "` pp
                     ON sp.CodPreIngresoPitaya = pp.CodPreIngresoPitaya
                    AND sp.Sucursal = pp.Sucursal
             WHERE sp.Sucursal = :suc
               AND pp.Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kspiLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success'=>true,'modo'=>$modo,'afectados'=>$n,'message'=>"Eliminados $n Sucursal=$sucursal"]);
        exit();
    }

    // ── limpiar_total ────────────────────────────────────────────────────────
    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KSPI_TABLE . "` WHERE Sucursal=:suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kspiLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success'=>true,'modo'=>$modo,'afectados'=>$n,'message'=>"Eliminados $n totales Sucursal=$sucursal"]);
        exit();
    }

    // ── insertar ─────────────────────────────────────────────────────────────
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kspiError(400, 'rows vacío o inválido.');
    $ins = $upd = 0;

    $stmt = $pdo->prepare(
        "INSERT INTO `" . KSPI_TABLE . "`
             (Sucursal, CodSubPreIngresoPitaya, CodCotizacion, Cantidad, CodPreIngresoPitaya, alerta, FechaUltimoSync)
         VALUES (:suc,:cod,:codcot,:cant,:codpre,:alerta,NOW())
         ON DUPLICATE KEY UPDATE
             CodCotizacion       = VALUES(CodCotizacion),
             Cantidad            = VALUES(Cantidad),
             CodPreIngresoPitaya = VALUES(CodPreIngresoPitaya),
             alerta              = VALUES(alerta),
             FechaUltimoSync     = NOW()"
    );
    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodSubPreIngresoPitaya']) ? (int)$r['CodSubPreIngresoPitaya'] : 0;
        if ($cod < 1) { kspiLog("Fila $idx ignorada."); continue; }
        $stmt->execute([
            ':suc'    => $sucursal,
            ':cod'    => $cod,
            ':codcot' => isset($r['CodCotizacion'])       ? (int)$r['CodCotizacion']       : null,
            ':cant'   => isset($r['Cantidad'])             ? (float)$r['Cantidad']           : null,
            ':codpre' => isset($r['CodPreIngresoPitaya'])  ? (int)$r['CodPreIngresoPitaya']  : null,
            ':alerta' => isset($r['alerta'])               ? (int)$r['alerta']               : null,
        ]);
        $rc=$stmt->rowCount(); if($rc===1)$ins++; elseif($rc===2)$upd++;
    }
    kspiLog("insertar OK | +$ins ins | ~$upd upd");
    echo json_encode(['success'=>true,'modo'=>$modo,'insertados'=>$ins,'actualizados'=>$upd,'message'=>"OK Sucursal=$sucursal +$ins ~$upd"]);

} catch (PDOException $e) { kspiLog("PDO: ".$e->getMessage()); kspiError(500,'DB error: '.$e->getMessage()); }
  catch (Exception $e)    { kspiLog("ERR: ".$e->getMessage()); kspiError(500,'Error: '.$e->getMessage()); }
?>
