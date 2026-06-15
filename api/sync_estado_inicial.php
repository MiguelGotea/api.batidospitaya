<?php
/**
 * sync_estado_inicial.php
 * Sincronización unidireccional Access → MySQL para [EstadoInicial]
 * Tabla host: msaccess_masivo_EstadoInicial
 *
 * Modos: limpiar_30dias | limpiar_total | insertar
 */

require_once __DIR__ . '/../core/database/conexion.php';

define('SEI_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('SEI_TABLE', 'msaccess_masivo_EstadoInicial');
define('SEI_LOG',   __DIR__ . '/logs/sync_estado_inicial.log');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

function seiLog(string $msg): void
{
    $dir = dirname(SEI_LOG);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents(SEI_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function seiError(int $code, string $msg): void
{
    seiLog("ERROR $code: $msg");
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

function seiVerifyToken(): bool
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token   = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token   = str_replace('Bearer ', '', trim($token));
    return hash_equals(SEI_TOKEN, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    seiError(405, 'Se requiere POST.');
if (!seiVerifyToken())                         seiError(401, 'Token inválido.');

$body     = json_decode(file_get_contents('php://input'), true);
$sucursal = isset($body['sucursal']) ? (int)$body['sucursal'] : 0;
$modo     = trim($body['modo'] ?? '');

if ($sucursal < 0) seiError(400, 'Sucursal inválida.');
if (!in_array($modo, ['limpiar_30dias', 'limpiar_total', 'insertar']))
    seiError(400, "Modo inválido: $modo");

global $conn;
$pdo = $conn;

seiLog("INICIO | Sucursal=$sucursal | Modo=$modo");

try {

    if ($modo === 'limpiar_30dias') {
        $stmt = $pdo->prepare(
            "DELETE FROM `" . SEI_TABLE . "`
             WHERE Sucursal = :suc
               AND Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
        );
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        seiLog("limpiar_30dias OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros Sucursal=$sucursal"]);
        exit();
    }

    if ($modo === 'limpiar_total') {
        $stmt = $pdo->prepare("DELETE FROM `" . SEI_TABLE . "` WHERE Sucursal = :suc");
        $stmt->execute([':suc' => $sucursal]);
        $n = $stmt->rowCount();
        seiLog("limpiar_total OK | Eliminados=$n");
        echo json_encode(['success' => true, 'modo' => $modo, 'afectados' => $n,
                          'message' => "Eliminados $n registros totales Sucursal=$sucursal"]);
        exit();
    }

    // modo = insertar
    $rows = $body['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) seiError(400, 'rows vacío o inválido.');

    $insertados = $actualizados = 0;

    $sql = "INSERT INTO `" . SEI_TABLE . "`
                (Sucursal, CodCajaInicial, Dinero, Fecha, Selladora,
                 TipoCambio_C, Feriado, Observaciones, Eventos,
                 FechaUltimoSync)
            VALUES
                (:suc, :cod, :dinero, :fecha, :selladora,
                 :tipocambio, :feriado, :obs, :eventos,
                 NOW())
            ON DUPLICATE KEY UPDATE
                Dinero          = VALUES(Dinero),
                Fecha           = VALUES(Fecha),
                Selladora       = VALUES(Selladora),
                TipoCambio_C    = VALUES(TipoCambio_C),
                Feriado         = VALUES(Feriado),
                Observaciones   = VALUES(Observaciones),
                Eventos         = VALUES(Eventos),
                FechaUltimoSync = NOW()";

    $stmt = $pdo->prepare($sql);

    foreach ($rows as $idx => $r) {
        $cod = isset($r['CodCajaInicial']) ? (int)$r['CodCajaInicial'] : 0;
        if ($cod < 1) { seiLog("Fila $idx ignorada: CodCajaInicial inválido."); continue; }

        $stmt->execute([
            ':suc'        => $sucursal,
            ':cod'        => $cod,
            ':dinero'     => isset($r['Dinero'])          ? (float)$r['Dinero']        : null,
            ':fecha'      => !empty($r['Fecha'])           ? $r['Fecha']                : null,
            ':selladora'  => isset($r['Selladora'])        ? (int)$r['Selladora']       : null,
            ':tipocambio' => isset($r['TipoCambio$_C$'])   ? (float)$r['TipoCambio$_C$']: null,
            ':feriado'    => isset($r['Feriado'])           ? (int)$r['Feriado']         : null,
            ':obs'        => $r['Observaciones']            ?? null,
            ':eventos'    => $r['Eventos']                  ?? null,
        ]);

        $rc = $stmt->rowCount();
        if ($rc === 1) $insertados++;
        elseif ($rc === 2) $actualizados++;
    }

    seiLog("insertar OK | +$insertados ins | ~$actualizados upd");
    echo json_encode([
        'success'      => true,
        'modo'         => $modo,
        'insertados'   => $insertados,
        'actualizados' => $actualizados,
        'message'      => "OK Sucursal=$sucursal +$insertados ins ~$actualizados upd"
    ]);

} catch (PDOException $e) {
    seiLog("PDO ERROR: " . $e->getMessage());
    seiError(500, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    seiLog("ERROR: " . $e->getMessage());
    seiError(500, 'Error interno: ' . $e->getMessage());
}
?>
