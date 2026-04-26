<?php
/**
 * sync_kardex_merma_cotizacion.php
 * Sincronización unidireccional Access → MySQL para [Merma Cotizacion]
 * Tabla host: msaccess_masivo_MermaCotizacion
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('KMC_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KMC_TABLE', 'msaccess_masivo_MermaCotizacion');
define('KMC_LOG',   __DIR__ . '/logs/sync_kardex_merma_cotizacion.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function kmcLog(string $msg): void
{
    $dir = dirname(KMC_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KMC_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function kmcError(int $code, string $msg): void
{
    kmcLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function kmcVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(KMC_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    kmcError(405, 'Se requiere POST.');
if (!kmcVerifyToken())                         kmcError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) kmcError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    kmcError(400, "Modo inválido: $modo");

global $conn;
$pdo = $conn;

kmcLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {

    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . KMC_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kmcLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros Sucursal=$sucursal"]);
        exit();
    }

    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KMC_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kmcLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // modo = insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kmcError(400, 'rows vacío o inválido.');

    $insertados = $actualizados = 0;

    $sql = "INSERT INTO `" . KMC_TABLE . "`
                (Sucursal, CodMermaUnidad, CodCotizacion, Cantidad, Fecha,
                 Observacion, CodIncidencia, Operario, FechaUltimoSync)
            VALUES
                (:suc, :cod, :codcot, :cant, :fecha,
                 :obs, :inc, :oper, NOW())
            ON DUPLICATE KEY UPDATE
                CodCotizacion   = VALUES(CodCotizacion),
                Cantidad        = VALUES(Cantidad),
                Fecha           = VALUES(Fecha),
                Observacion     = VALUES(Observacion),
                CodIncidencia   = VALUES(CodIncidencia),
                Operario        = VALUES(Operario),
                FechaUltimoSync = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodMermaUnidad']) ? (int)$r['CodMermaUnidad'] : 0;
        if ($cod < 1) { kmcLog("Fila $idx ignorada: CodMermaUnidad inválido."); continue; }

        $stmt->execute([
            ':suc'   => $sucursal,
            ':cod'   => $cod,
            ':codcot'=> isset($r['CodCotizacion']) ? (int)$r['CodCotizacion']  : null,
            ':cant'  => isset($r['Cantidad'])       ? (float)$r['Cantidad']     : null,
            ':fecha' => !empty($r['Fecha'])         ? $r['Fecha']               : null,
            ':obs'   => $r['Observacion']           ?? null,
            ':inc'   => isset($r['CodIncidencia'])  ? (int)$r['CodIncidencia']  : null,
            ':oper'  => isset($r['Operario'])       ? (int)$r['Operario']       : null,
        ]);

        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    kmcLog("insertar OK | +$insertados ins | ~$actualizados upd");
    echo json_encode([
        'success'     => true,
        'modo'        => $modo,
        'insertados'  => $insertados,
        'actualizados'=> $actualizados,
        'message'     => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    kmcLog("PDO ERROR: " . $e->getMessage());
    kmcError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    kmcLog("ERROR: " . $e->getMessage());
    kmcError(500, 'Error interno: ' . $e->getMessage());
}
?>
