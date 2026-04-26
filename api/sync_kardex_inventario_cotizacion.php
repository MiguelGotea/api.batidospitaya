<?php
/**
 * sync_kardex_inventario_cotizacion.php
 * Sincronización unidireccional Access → MySQL para [Inventario Cotizacion]
 * Tabla host: msaccess_masivo_InventarioCotizacion
 *
 * Modos (campo "modo" en el JSON):
 *   limpiar_30dias : DELETE registros de los últimos 30 días de esta sucursal
 *   limpiar_total  : DELETE TODOS los registros de esta sucursal (masivo)
 *   insertar       : INSERT lote de filas (rows[]) — 200 filas por llamada
 *
 * Parámetros POST (JSON):
 *   sucursal : INT  (requerido siempre)
 *   modo     : string (requerido siempre)
 *   rows     : array (requerido solo en modo=insertar)
 *
 * Respuesta JSON:
 *   { "success": true, "modo": "...", "afectados": N, "message": "..." }
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('KIC_TOKEN',   'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('KIC_TABLE',   'msaccess_masivo_InventarioCotizacion');
define('KIC_LOG',     __DIR__ . '/logs/sync_kardex_inventario_cotizacion.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

// ── Helpers ──────────────────────────────────────────────────────────────────
function kicLog(string $msg): void
{
    $dir = dirname(KIC_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(KIC_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function kicError(int $code, string $msg): void
{
    kicLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function kicVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(KIC_TOKEN, $token);
}

// ── Entrada ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    kicError(405, 'Se requiere POST.');
if (!kicVerifyToken())                         kicError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) kicError(400, 'Sucursal inválida.');  // 0 permitido (central)
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    kicError(400, "Modo inválido: $modo");

/** @var PDO $conn */
global $conn;
$pdo = $conn;

kicLog("INICIO | Sucursal=$sucursal | Modo=$modo");

// ── Modos ────────────────────────────────────────────────────────────────────
try {

    // ── limpiar_30dias ───────────────────────────────────────────────────────
    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . KIC_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kicLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros (últimos 30 días) Sucursal=$sucursal"]);
        exit();
    }

    // ── limpiar_total ────────────────────────────────────────────────────────
    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . KIC_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        kicLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // ── insertar ─────────────────────────────────────────────────────────────
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) kicError(400, 'rows vacío o inválido.');

    $insertados   = 0;
    $actualizados = 0;

    $sql = "INSERT INTO `" . KIC_TABLE . "`
                (Sucursal, CodICotizacion, CodCotizacion, Cantidad, Fecha,
                 lista, CodOperario, primerenvio, segundoenvio,
                 cantidadunidad, cantidadpaquete, FechaUltimoSync)
            VALUES
                (:suc, :cod, :codcot, :cant, :fecha,
                 :lista, :oper, :penv, :senv,
                 :cuni, :cpaq, NOW())
            ON DUPLICATE KEY UPDATE
                CodCotizacion   = VALUES(CodCotizacion),
                Cantidad        = VALUES(Cantidad),
                Fecha           = VALUES(Fecha),
                lista           = VALUES(lista),
                CodOperario     = VALUES(CodOperario),
                primerenvio     = VALUES(primerenvio),
                segundoenvio    = VALUES(segundoenvio),
                cantidadunidad  = VALUES(cantidadunidad),
                cantidadpaquete = VALUES(cantidadpaquete),
                FechaUltimoSync = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodICotizacion']) ? (int)$r['CodICotizacion'] : 0;
        if ($cod < 1) { kicLog("Fila $idx ignorada: CodICotizacion inválido."); continue; }

        $stmt->execute([
            ':suc'   => $sucursal,
            ':cod'   => $cod,
            ':codcot'=> isset($r['CodCotizacion'])   ? (int)$r['CodCotizacion']   : null,
            ':cant'  => isset($r['Cantidad'])         ? (float)$r['Cantidad']      : null,
            ':fecha' => !empty($r['Fecha'])           ? $r['Fecha']                : null,
            ':lista' => isset($r['lista'])            ? (int)$r['lista']           : null,
            ':oper'  => isset($r['CodOperario'])      ? (int)$r['CodOperario']     : null,
            ':penv'  => isset($r['primerenvio'])      ? (float)$r['primerenvio']   : null,
            ':senv'  => isset($r['segundoenvio'])     ? (float)$r['segundoenvio']  : null,
            ':cuni'  => isset($r['cantidadunidad'])   ? (float)$r['cantidadunidad']: null,
            ':cpaq'  => isset($r['cantidadpaquete'])  ? (float)$r['cantidadpaquete']:null,
        ]);

        // rowCount()=1 insert, 2 update en ON DUPLICATE KEY
        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    kicLog("insertar OK | +$insertados ins | ~$actualizados upd | filas=" . count($rows));
    echo json_encode([
        'success'     => true,
        'modo'        => $modo,
        'insertados'  => $insertados,
        'actualizados'=> $actualizados,
        'message'     => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    kicLog("PDO ERROR: " . $e->getMessage());
    kicError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    kicLog("ERROR: " . $e->getMessage());
    kicError(500, 'Error interno: ' . $e->getMessage());
}
?>
