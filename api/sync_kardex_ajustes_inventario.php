<?php
/**
 * sync_kardex_ajustes_inventario.php
 * Sincronización unidireccional Access → MySQL para [AjustesInventario]
 * Tabla host: msaccess_masivo_AjustesInventario
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('KAI_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KAI_TABLE', 'msaccess_masivo_AjustesInventario');
define('KAI_LOG',   __DIR__ . '/logs/sync_kardex_ajustes_inventario.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kaiLog(string $msg): void
{
    $dir = dirname(KAI_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KAI_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function kaiError(int $code, string $msg): void
{
    kaiLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function kaiVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(KAI_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    kaiError(405, 'Se requiere POST.');
if (!kaiVerifyToken())                         kaiError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) kaiError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    kaiError(400, "Modo inválido: $modo");

global $conn;
$pdo = $conn;

kaiLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {

    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . KAI_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kaiLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros Sucursal=$sucursal"]);
        exit();
    }

    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KAI_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kaiLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // modo = insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kaiError(400, 'rows vacío o inválido.');

    $insertados = $actualizados = 0;

    $sql = "INSERT INTO `" . KAI_TABLE . "`
                (Sucursal, CodAjustesInventario, CodCotizacion, Cantidad, Fecha, Observacion, FechaUltimoSync)
            VALUES
                (:suc, :cod, :codcot, :cant, :fecha, :obs, NOW())
            ON DUPLICATE KEY UPDATE
                CodCotizacion = VALUES(CodCotizacion),
                Cantidad      = VALUES(Cantidad),
                Fecha         = VALUES(Fecha),
                Observacion   = VALUES(Observacion),
                FechaUltimoSync = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodAjustesInventario']) ? (int)$r['CodAjustesInventario'] : 0;
        if ($cod < 1) { kaiLog("Fila $idx ignorada: CodAjustesInventario inválido."); continue; }

        $stmt->execute([
            ':suc'   => $sucursal,
            ':cod'   => $cod,
            ':codcot'=> isset($r['CodCotizacion']) ? (int)$r['CodCotizacion']  : null,
            ':cant'  => isset($r['Cantidad'])       ? (float)$r['Cantidad']     : null,
            ':fecha' => !empty($r['Fecha'])         ? $r['Fecha']               : null,
            ':obs'   => $r['Observacion']           ?? null,
        ]);

        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    kaiLog("insertar OK | +$insertados ins | ~$actualizados upd");
    echo json_encode([
        'success'     => true,
        'modo'        => $modo,
        'insertados'  => $insertados,
        'actualizados'=> $actualizados,
        'message'     => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    kaiLog("PDO ERROR: " . $e->getMessage());
    kaiError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    kaiLog("ERROR: " . $e->getMessage());
    kaiError(500, 'Error interno: ' . $e->getMessage());
}
?>
