<?php
/**
 * dvr_sucursales.php — Lista DVRs Hikvision para el módulo NTP Setup
 *
 * GET /api/hikvision/dvr_sucursales.php
 * Header: X-WSP-Token
 *
 * Parámetros GET opcionales:
 *   tunel_activo=1     → solo DVRs con túnel activo (default del cliente Python)
 *   cod_sucursal=N     → un DVR específico por código
 *
 * Respuesta:
 * {
 *   "success": true,
 *   "dvrs": [ { cod_sucursal, nombre_sucursal, portal_ip_local, ... }, ... ]
 * }
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

// ── Construcción de la query ──────────────────────────────────
$where  = [];
$params = [];

if (isset($_GET['tunel_activo'])) {
    $where[]  = 'tunel_activo = ?';
    $params[] = (int) $_GET['tunel_activo'];
}

if (isset($_GET['cod_sucursal'])) {
    $where[]  = 'cod_sucursal = ?';
    $params[] = (int) $_GET['cod_sucursal'];
}

$sql = "
    SELECT
        cod_sucursal,
        nombre_sucursal,
        modelo,
        marca,
        serial,
        portal_ip_local,
        portal_usuario,
        portal_clave,
        puerto_rtsp_vps,
        tunel_activo,
        puerto_http_vps
    FROM DVR_Sucursales
    " . ($where ? ('WHERE ' . implode(' AND ', $where)) : '') . "
    ORDER BY nombre_sucursal ASC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $dvrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar tipos
    foreach ($dvrs as &$dvr) {
        $dvr['cod_sucursal']    = (int)  $dvr['cod_sucursal'];
        $dvr['tunel_activo']    = (bool) $dvr['tunel_activo'];
        $dvr['puerto_rtsp_vps'] = $dvr['puerto_rtsp_vps'] !== null ? (int) $dvr['puerto_rtsp_vps'] : null;
        $dvr['puerto_http_vps'] = $dvr['puerto_http_vps'] !== null ? (int) $dvr['puerto_http_vps'] : null;
    }
    unset($dvr);

    hikOk(['dvrs' => $dvrs]);

} catch (Exception $e) {
    hikErr('Error de base de datos: ' . $e->getMessage(), 500);
}
