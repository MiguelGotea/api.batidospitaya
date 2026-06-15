<?php
/**
 * sync_cierre_diario.php
 * Sincronización unidireccional Access → MySQL para [CierreDiario]
 * Tabla host: msaccess_masivo_CierreDiario
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('SCD_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('SCD_TABLE', 'msaccess_masivo_CierreDiario');
define('SCD_LOG',   __DIR__ . '/logs/sync_cierre_diario.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function scdLog(string $msg): void
{
    $dir = dirname(SCD_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(SCD_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function scdError(int $code, string $msg): void
{
    scdLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function scdVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(SCD_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    scdError(405, 'Se requiere POST.');
if (!scdVerifyToken())                         scdError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) scdError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    scdError(400, "Modo inválido: $modo");

global $conn;
$pdo = $conn;

scdLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {

    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . SCD_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        scdLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros Sucursal=$sucursal"]);
        exit();
    }

    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . SCD_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        scdLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // modo = insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) scdError(400, 'rows vacío o inválido.');

    $insertados = $actualizados = 0;

    $sql = "INSERT INTO `" . SCD_TABLE . "`
                (Sucursal, CodigoCierre, HoraInicial, HoraFinal, Fecha,
                 CodOperario, MFCor, MFDol, Faltante,
                 TotalHugo, TotalPedidosYa, TotalTransferencia, TotalPOS,
                 Observaciones, FechaUltimoSync)
            VALUES
                (:suc, :cod, :horainicial, :horafinal, :fecha,
                 :oper, :mfcor, :mfdol, :faltante,
                 :hugo, :pedidosya, :transferencia, :pos,
                 :obs, NOW())
            ON DUPLICATE KEY UPDATE
                HoraInicial        = VALUES(HoraInicial),
                HoraFinal          = VALUES(HoraFinal),
                Fecha              = VALUES(Fecha),
                CodOperario        = VALUES(CodOperario),
                MFCor              = VALUES(MFCor),
                MFDol              = VALUES(MFDol),
                Faltante           = VALUES(Faltante),
                TotalHugo          = VALUES(TotalHugo),
                TotalPedidosYa     = VALUES(TotalPedidosYa),
                TotalTransferencia = VALUES(TotalTransferencia),
                TotalPOS           = VALUES(TotalPOS),
                Observaciones      = VALUES(Observaciones),
                FechaUltimoSync    = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodigoCierre']) ? (int)$r['CodigoCierre'] : 0;
        if ($cod < 1) { scdLog("Fila $idx ignorada: CodigoCierre inválido."); continue; }

        $stmt->execute([
            ':suc'           => $sucursal,
            ':cod'           => $cod,
            ':horainicial'   => !empty($r['HoraInicial'])          ? $r['HoraInicial']                 : null,
            ':horafinal'     => !empty($r['HoraFinal'])            ? $r['HoraFinal']                   : null,
            ':fecha'         => !empty($r['Fecha'])                 ? $r['Fecha']                       : null,
            ':oper'          => isset($r['CodOperario'])            ? (int)$r['CodOperario']            : null,
            ':mfcor'         => isset($r['MFCor'])                  ? (float)$r['MFCor']                : null,
            ':mfdol'         => isset($r['MFDol'])                  ? (float)$r['MFDol']                : null,
            ':faltante'      => isset($r['Faltante'])               ? (int)$r['Faltante']               : null,
            ':hugo'          => isset($r['TotalHugo'])              ? (float)$r['TotalHugo']            : null,
            ':pedidosya'     => isset($r['TotalPedidosYa'])         ? (float)$r['TotalPedidosYa']       : null,
            ':transferencia' => isset($r['TotalTransferencia'])     ? (float)$r['TotalTransferencia']   : null,
            ':pos'           => isset($r['TotalPOS'])               ? (float)$r['TotalPOS']             : null,
            ':obs'           => $r['Observaciones']                  ?? null,
        ]);

        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    scdLog("insertar OK | +$insertados ins | ~$actualizados upd");
    echo json_encode([
        'success'      => true,
        'modo'         => $modo,
        'insertados'   => $insertados,
        'actualizados' => $actualizados,
        'message'      => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    scdLog("PDO ERROR: " . $e->getMessage());
    scdError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    scdLog("ERROR: " . $e->getMessage());
    scdError(500, 'Error interno: ' . $e->getMessage());
}
?>
