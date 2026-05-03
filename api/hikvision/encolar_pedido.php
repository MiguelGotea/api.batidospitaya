<?php
/**
 * encolar_pedido.php — Encola un pedido puntual para análisis inmediato
 * POST /api/hikvision/encolar_pedido.php
 * Header: X-WSP-Token
 * Body JSON: { "cod_pedido": 12345, "local": "10" }
 *
 * HERRAMIENTA MANUAL: para test o solicitudes específicas.
 * Se le asigna prioridad 1 (urgente) para que el worker lo tome primero.
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data       = json_decode(file_get_contents('php://input'), true);
$cod_pedido = isset($data['cod_pedido']) ? intval($data['cod_pedido']) : null;
$local      = isset($data['local'])      ? trim($data['local'])        : null;

if (!$cod_pedido || !$local) {
    hikErr('Faltan parámetros requeridos: cod_pedido, local');
}

try {
    // ── Verificar que no esté ya en cola activa ──────────────
    $stmt = $conn->prepare("
        SELECT id, estado
        FROM hikvision_cola_analisis
        WHERE cod_pedido   = :cp
          AND local_codigo = :lc
          AND estado IN ('pendiente', 'procesando')
        LIMIT 1
    ");
    $stmt->execute([':cp' => $cod_pedido, ':lc' => $local]);
    $existing = $stmt->fetch();

    if ($existing) {
        hikOk([
            'encolado'  => false,
            'mensaje'   => "Pedido ya existe en cola con estado: {$existing['estado']}",
            'id_cola'   => $existing['id'],
        ]);
    }

    // ── Buscar datos del pedido y sucursal ───────────────────
    // Usamos MIN(HoraCreado) y MAX(HoraImpreso) porque hay varias
    // filas por pedido (una por producto). Filtramos Anulado=0.
    $stmt = $conn->prepare("
        SELECT
            v.Fecha,
            MIN(v.HoraCreado)  AS hora_inicio,
            MAX(v.HoraImpreso) AS hora_fin,
            v.local,
            d.canal_caja,
            d.puerto_rtsp_vps,
            d.portal_ip_local,
            d.portal_usuario,
            d.portal_clave,
            d.tunel_activo,
            s.nombre           AS sucursal_nombre
        FROM VentasGlobalesAccessCSV v
        JOIN DVR_Sucursales d ON d.cod_sucursal = v.local
        LEFT JOIN sucursales s ON s.codigo = v.local
        WHERE v.CodPedido = :cp
          AND v.local     = :lc
          AND v.Anulado   = 0
        GROUP BY v.Fecha, v.local,
                 d.canal_caja, d.puerto_rtsp_vps,
                 d.portal_ip_local, d.portal_usuario, d.portal_clave,
                 d.tunel_activo, s.nombre
        LIMIT 1
    ");
    $stmt->execute([':cp' => $cod_pedido, ':lc' => $local]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        hikErr("Pedido $cod_pedido no encontrado en local $local (o está anulado)", 404);
    }

    // ── Validar configuración DVR ────────────────────────────
    if (!$pedido['tunel_activo']) {
        hikErr("La sucursal $local no tiene túnel SSH activo. Configura el túnel primero.", 422);
    }
    if (!$pedido['puerto_rtsp_vps'] || !$pedido['canal_caja']) {
        hikErr("La sucursal $local no tiene configuración RTSP completa (falta puerto o canal).", 422);
    }
    if (!$pedido['hora_inicio'] || !$pedido['hora_fin']) {
        hikErr("El pedido $cod_pedido no tiene HoraCreado o HoraImpreso registrada.", 422);
    }

    // ── Insertar en cola con prioridad ALTA (manual=1) ───────
    $ins = $conn->prepare("
        INSERT INTO hikvision_cola_analisis
            (cod_pedido, local_codigo, fecha, hora_inicio, hora_fin,
             canal_track, puerto_rtsp, dvr_ip_local, dvr_usuario, dvr_clave,
             estado, tipo, prioridad)
        VALUES
            (:cp, :lc, :fecha, :hi, :hf,
             :ct, :pr, :ip, :usr, :clave,
             'pendiente', 'manual', 1)
    ");
    $ins->execute([
        ':cp'    => $cod_pedido,
        ':lc'    => $local,
        ':fecha' => $pedido['Fecha'],
        ':hi'    => $pedido['hora_inicio'],
        ':hf'    => $pedido['hora_fin'],
        ':ct'    => $pedido['canal_caja'],
        ':pr'    => $pedido['puerto_rtsp_vps'],
        ':ip'    => $pedido['portal_ip_local'],
        ':usr'   => $pedido['portal_usuario'],
        ':clave' => $pedido['portal_clave'],
    ]);

    $id_cola = $conn->lastInsertId();

    hikOk([
        'encolado'        => true,
        'id_cola'         => (int) $id_cola,
        'cod_pedido'      => $cod_pedido,
        'local'           => $local,
        'sucursal'        => $pedido['sucursal_nombre'] ?? $local,
        'fecha'           => $pedido['Fecha'],
        'hora_inicio'     => $pedido['hora_inicio'],
        'hora_fin'        => $pedido['hora_fin'],
        'tipo'            => 'manual',
        'prioridad'       => 1,
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
