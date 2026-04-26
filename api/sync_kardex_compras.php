<?php
/**
 * sync_kardex_compras.php
 * Sincronización unidireccional Access → MySQL para [Compras]
 * Tabla host: msaccess_masivo_Compras
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('KC_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KC_TABLE', 'msaccess_masivo_Compras');
define('KC_LOG',   __DIR__ . '/logs/sync_kardex_compras.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kcLog(string $msg): void
{
    $dir = dirname(KC_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KC_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function kcError(int $code, string $msg): void
{
    kcLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function kcVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(KC_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    kcError(405, 'Se requiere POST.');
if (!kcVerifyToken())                          kcError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) kcError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    kcError(400, "Modo inválido: $modo");

global $conn;
$pdo = $conn;

kcLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {

    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . KC_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kcLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros Sucursal=$sucursal"]);
        exit();
    }

    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KC_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kcLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // modo = insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kcError(400, 'rows vacío o inválido.');

    $insertados = $actualizados = 0;

    $sql = "INSERT INTO `" . KC_TABLE . "`
                (Sucursal, CodIngresoAlmacen, CodCotizacion, Cantidad, Fecha,
                 CostoTotal, Observaciones, CodProveedor, Destino, Tipo,
                 Pagado, NumeroFactura, CodOperario, Ingresado, Lote, Peso,
                 FechaUltimoSync)
            VALUES
                (:suc, :cod, :codcot, :cant, :fecha,
                 :costo, :obs, :prov, :dest, :tipo,
                 :pagado, :factura, :oper, :ing, :lote, :peso,
                 NOW())
            ON DUPLICATE KEY UPDATE
                CodCotizacion   = VALUES(CodCotizacion),
                Cantidad        = VALUES(Cantidad),
                Fecha           = VALUES(Fecha),
                CostoTotal      = VALUES(CostoTotal),
                Observaciones   = VALUES(Observaciones),
                CodProveedor    = VALUES(CodProveedor),
                Destino         = VALUES(Destino),
                Tipo            = VALUES(Tipo),
                Pagado          = VALUES(Pagado),
                NumeroFactura   = VALUES(NumeroFactura),
                CodOperario     = VALUES(CodOperario),
                Ingresado       = VALUES(Ingresado),
                Lote            = VALUES(Lote),
                Peso            = VALUES(Peso),
                FechaUltimoSync = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodIngresoAlmacen']) ? (int)$r['CodIngresoAlmacen'] : 0;
        if ($cod < 1) { kcLog("Fila $idx ignorada: CodIngresoAlmacen inválido."); continue; }

        // Pagado puede ser DATE o NULL
        $pagado = (!empty($r['Pagado']) && $r['Pagado'] !== 'null') ? $r['Pagado'] : null;

        $stmt->execute([
            ':suc'    => $sucursal,
            ':cod'    => $cod,
            ':codcot' => isset($r['CodCotizacion'])  ? (int)$r['CodCotizacion']   : null,
            ':cant'   => isset($r['Cantidad'])        ? (float)$r['Cantidad']      : null,
            ':fecha'  => !empty($r['Fecha'])          ? $r['Fecha']                : null,
            ':costo'  => isset($r['CostoTotal'])      ? (float)$r['CostoTotal']    : null,
            ':obs'    => $r['Observaciones']          ?? null,
            ':prov'   => isset($r['CodProveedor'])    ? (int)$r['CodProveedor']    : null,
            ':dest'   => $r['Destino']                ?? null,
            ':tipo'   => $r['Tipo']                   ?? null,
            ':pagado' => $pagado,
            ':factura'=> $r['NumeroFactura']           ?? null,
            ':oper'   => isset($r['CodOperario'])     ? (int)$r['CodOperario']     : null,
            ':ing'    => isset($r['Ingresado'])        ? (int)$r['Ingresado']       : null,
            ':lote'   => isset($r['Lote'])            ? (float)$r['Lote']          : null,
            ':peso'   => isset($r['Peso'])            ? (float)$r['Peso']          : null,
        ]);

        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    kcLog("insertar OK | +$insertados ins | ~$actualizados upd");
    echo json_encode([
        'success'     => true,
        'modo'        => $modo,
        'insertados'  => $insertados,
        'actualizados'=> $actualizados,
        'message'     => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    kcLog("PDO ERROR: " . $e->getMessage());
    kcError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    kcLog("ERROR: " . $e->getMessage());
    kcError(500, 'Error interno: ' . $e->getMessage());
}
?>
