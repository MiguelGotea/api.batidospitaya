<?php
/**
 * encolar_pedido.php — Encola un pedido puntual para análisis inmediato.
 * POST /api/hikvision/encolar_pedido.php
 * Header: X-WSP-Token
 * Body JSON: { "cod_pedido": 12345, "local": "10" }
 *
 * HERRAMIENTA MANUAL: para test o solicitudes específicas.
 * Prioridad 1 (urgente) para que el worker lo tome primero.
 *
 * REGLAS DE NEGOCIO:
 *   - Rechaza pedidos de PedidosYa (no hay atención presencial real).
 *   - Detecta contexto de membresía automáticamente:
 *       · CodCliente = 0              → sin_membresia  (evaluar si ofreció)
 *       · CodCliente <> 0 + Membresia → vendida        (auto 10, la vendió)
 *       · CodCliente <> 0 sin product → ya_tenia       (null, no aplica)
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
    // ── Verificar que no esté ya en cola activa ───────────────
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
            'encolado' => false,
            'mensaje'  => "Pedido ya existe en cola con estado: {$existing['estado']}",
            'id_cola'  => $existing['id'],
        ]);
    }

    // ── Buscar datos del pedido, sucursal y contexto de membresía ─
    // Incluye Delivery_Nombre para rechazar PedidosYa.
    // MAX(CodCliente) por si hay varias filas del mismo pedido.
    // MAX(CASE...) detecta si alguna línea del pedido es "Membresia".
    $stmt = $conn->prepare("
        SELECT
            v.Fecha,
            MIN(v.HoraCreado)  AS hora_inicio,
            MAX(v.HoraImpreso) AS hora_fin,
            v.local,
            MAX(v.Delivery_Nombre)  AS delivery_nombre,
            MAX(v.CodCliente)       AS cod_cliente,
            MAX(CASE WHEN v.DBBatidos_Nombre = 'Membresia' THEN 1 ELSE 0 END) AS vendio_membresia,
            d.canal_caja,
            d.puerto_rtsp_vps,
            d.portal_ip_local,
            d.portal_usuario,
            d.portal_clave,
            d.tunel_activo,
            s.nombre AS sucursal_nombre
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

    // ── Rechazar PedidosYa (no hay atención presencial) ──────
    if ($pedido['delivery_nombre'] === 'PedidosYa') {
        hikOk([
            'encolado' => false,
            'omitido'  => true,
            'razon'    => 'PedidosYa: pedido de delivery externo, sin atención presencial en caja.',
            'cod_pedido' => $cod_pedido,
        ]);
    }

    // ── Validar configuración DVR ─────────────────────────────
    if (!$pedido['tunel_activo']) {
        hikErr("La sucursal $local no tiene túnel SSH activo.", 422);
    }
    if (!$pedido['puerto_rtsp_vps'] || !$pedido['canal_caja']) {
        hikErr("La sucursal $local no tiene configuración RTSP completa.", 422);
    }
    if (!$pedido['hora_inicio'] || !$pedido['hora_fin']) {
        hikErr("El pedido $cod_pedido no tiene HoraCreado o HoraImpreso registrada.", 422);
    }

    // ── Determinar contexto de membresía ─────────────────────
    if ($pedido['cod_cliente'] == 0) {
        $membresia_contexto = 'sin_membresia';   // evaluar normalmente
    } elseif ($pedido['vendio_membresia'] == 1) {
        $membresia_contexto = 'vendida';          // auto 10: vendió = ofreció
    } else {
        $membresia_contexto = 'ya_tenia';         // null: cliente ya tenía
    }

    // ── Insertar en cola con prioridad ALTA (manual=1) ────────
    $ins = $conn->prepare("
        INSERT INTO hikvision_cola_analisis
            (cod_pedido, local_codigo, fecha, hora_inicio, hora_fin,
             canal_track, puerto_rtsp, dvr_ip_local, dvr_usuario, dvr_clave,
             membresia_contexto, estado, tipo, prioridad)
        VALUES
            (:cp, :lc, :fecha, :hi, :hf,
             :ct, :pr, :ip, :usr, :clave,
             :membresia, 'pendiente', 'manual', 1)
    ");
    $ins->execute([
        ':cp'        => $cod_pedido,
        ':lc'        => $local,
        ':fecha'     => $pedido['Fecha'],
        ':hi'        => $pedido['hora_inicio'],
        ':hf'        => $pedido['hora_fin'],
        ':ct'        => $pedido['canal_caja'],
        ':pr'        => $pedido['puerto_rtsp_vps'],
        ':ip'        => $pedido['portal_ip_local'],
        ':usr'       => $pedido['portal_usuario'],
        ':clave'     => $pedido['portal_clave'],
        ':membresia' => $membresia_contexto,
    ]);

    $id_cola = $conn->lastInsertId();

    hikOk([
        'encolado'           => true,
        'id_cola'            => (int) $id_cola,
        'cod_pedido'         => $cod_pedido,
        'local'              => $local,
        'sucursal'           => $pedido['sucursal_nombre'] ?? $local,
        'fecha'              => $pedido['Fecha'],
        'hora_inicio'        => $pedido['hora_inicio'],
        'hora_fin'           => $pedido['hora_fin'],
        'membresia_contexto' => $membresia_contexto,
        'tipo'               => 'manual',
        'prioridad'          => 1,
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
