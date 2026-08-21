<?php
/**
 * sync_kardex_procesamiento.php
 * Sincronizacion unidireccional Access -> MySQL para [Procesamiento]
 * Tabla host: msaccess_masivo_Procesamiento
 * Modos: limpiar_30dias | limpiar_total | limpiar_dia | insertar
 */
require_once __DIR__ . '/../core/database/conexion.php';

define('KPROC_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KPROC_TABLE', 'msaccess_masivo_Procesamiento');
define('KPROC_LOG',   __DIR__ . '/logs/sync_kardex_procesamiento.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kprocLog(string $msg): void {
    $dir = dirname(KPROC_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KPROC_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}
function kprocError(int $code, string $msg): void {
    kprocLog("ERROR $code: $msg"); http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]); exit();
}
function kprocVerifyToken(): bool {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = str_replace('Bearer ', '', trim($headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    return hash_equals(KPROC_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') kprocError(405, 'Se requiere POST.');
if (!kprocVerifyToken()) kprocError(401, 'Token invalido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : -1;
$modo     = trim($body['modo'] ?? '');
$fecha    = trim($body['fecha'] ?? '');

if ($sucursal < 0) kprocError(400, 'Sucursal invalida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'limpiar_dia', 'insertar'])) kprocError(400, "Modo invalido: $modo");

global $conn; $pdo = $conn;
kprocLog("INICIO | Sucursal=$sucursal | Modo=$modo" . ($fecha ? " | Fecha=$fecha" : ''));

try {
    // -- limpiar_30dias
    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare("DELETE FROM `" . KPROC_TABLE . "` WHERE Sucursal=:suc AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kprocLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n Sucursal=$sucursal"]);
        exit();
    }

    // -- limpiar_total
    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KPROC_TABLE . "` WHERE Sucursal=:suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kprocLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n totales Sucursal=$sucursal"]);
        exit();
    }

    // -- limpiar_dia
    if ($modo === 'limpiar_dia') {
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) kprocError(400, 'Fecha invalida para limpiar_dia.');
        $stmt = $pdo->prepare("DELETE FROM `" . KPROC_TABLE . "` WHERE Sucursal=:suc AND DATE(Fecha)=:fecha");
        $stmt->execute([':suc' => $sucursal, ':fecha' => $fecha]);
        $n = $stmt->rowCount();
        kprocLog("limpiar_dia OK | Fecha=$fecha | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n, 'message' => "Eliminados $n dia=$fecha Sucursal=$sucursal"]);
        exit();
    }

    // -- insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kprocError(400, 'rows vacio o invalido.');
    $ins = $upd = 0;

    $stmt = $pdo->prepare(
        "INSERT INTO `" . KPROC_TABLE . "`
             (Sucursal, CodProcesamiento, CodCotizacion, Cantidad,
              MedidaInicial, MedidaFinal, Fecha, Observaciones, Operario, FechaUltimoSync)
         VALUES (:suc,:cod,:codcot,:cant,:medini,:medfin,:fecha,:obs,:operario,NOW())
         ON DUPLICATE KEY UPDATE
             CodCotizacion  = VALUES(CodCotizacion),
             Cantidad       = VALUES(Cantidad),
             MedidaInicial  = VALUES(MedidaInicial),
             MedidaFinal    = VALUES(MedidaFinal),
             Fecha          = VALUES(Fecha),
             Observaciones  = VALUES(Observaciones),
             Operario       = VALUES(Operario),
             FechaUltimoSync = NOW()"
    );

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodProcesamiento']) ? (int)$r['CodProcesamiento'] : 0;
        if ($cod < 1) { kprocLog("Fila $idx ignorada (CodProcesamiento invalido)."); continue; }
        $stmt->execute([
            ':suc'      => $sucursal,
            ':cod'      => $cod,
            ':codcot'   => isset($r['CodCotizacion'])  ? (int)$r['CodCotizacion']    : null,
            ':cant'     => isset($r['Cantidad'])        ? (float)$r['Cantidad']        : null,
            ':medini'   => isset($r['MedidaInicial'])   ? (float)$r['MedidaInicial']  : null,
            ':medfin'   => isset($r['MedidaFinal'])     ? (float)$r['MedidaFinal']    : null,
            ':fecha'    => isset($r['Fecha'])            ? $r['Fecha']                 : null,
            ':obs'      => isset($r['Observaciones'])    ? (string)$r['Observaciones'] : null,
            ':operario' => isset($r['Operario'])         ? (int)$r['Operario']         : null,
        ]);
        $rc = $stmt->rowCount(); if ($rc === 1) $ins++; elseif ($rc === 2) $upd++;
    }

    kprocLog("insertar OK | +$ins ins | ~$upd upd");
    echo json_encode(['success' => true, 'modo' => $modo, 'insertados' => $ins, 'actualizados' => $upd, 'message' => "OK Sucursal=$sucursal +$ins ~$upd"]);

} catch (PDOException $e) { kprocLog("PDO: " . $e->getMessage()); kprocError(500, 'DB error: ' . $e->getMessage()); }
  catch (Exception $e)    { kprocLog("ERR: " . $e->getMessage()); kprocError(500, 'Error: '    . $e->getMessage()); }
?>
