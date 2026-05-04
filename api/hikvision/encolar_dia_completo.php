<?php
/**
 * encolar_dia_completo.php — Encola todos los pedidos presenciales de un día.
 * POST /api/hikvision/encolar_dia_completo.php
 * Header: X-WSP-Token
 * Body JSON:
 *   { "fecha": "2026-05-02" }               → todas las sucursales activas
 *   { "fecha": "2026-05-02", "local": "10" } → solo esa sucursal
 *
 * REGLAS DE NEGOCIO:
 *   - Excluye pedidos con Delivery_Nombre = 'PedidosYa' (y cualquier delivery).
 *   - Detecta automáticamente el contexto de membresía por pedido:
 *       · CodCliente = 0              → sin_membresia
 *       · CodCliente <> 0 + Membresia → vendida
 *       · CodCliente <> 0 sin Membresia → ya_tenia
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data  = json_decode(file_get_contents('php://input'), true);
$fecha = isset($data['fecha']) ? trim($data['fecha']) : null;
$local = isset($data['local']) ? trim($data['local']) : null;

if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    hikErr('Parámetro requerido: fecha (formato YYYY-MM-DD)');
}
if ($fecha > date('Y-m-d')) {
    hikErr('No se puede encolar una fecha futura.');
}

try {
    $localFiltro = '';
    $params      = [':fecha' => $fecha];

    if ($local !== null) {
        $localFiltro = 'AND v.local = :local';
        $params[':local'] = $local;
    }

    // ── Consulta pedidos presenciales con contexto de membresía ─
    // Exclusiones:
    //   · Delivery_Nombre no nulo ni vacío (incluye PedidosYa y cualquier delivery)
    //   · Motorizado asignado
    //   · Ya en cola con estado pendiente/procesando/completado
    // Contexto membresía:
    //   · MAX(CodCliente): si alguna fila del pedido tiene cliente → tiene membresía
    //   · MAX(CASE...'Membresia'): si alguna línea es "Membresia" → la vendió en este pedido
    $stmt = $conn->prepare("
        SELECT
            v.CodPedido,
            v.local,
            v.Fecha,
            MIN(v.HoraCreado)  AS hora_inicio,
            MAX(v.HoraImpreso) AS hora_fin,
            MAX(v.CodCliente)  AS cod_cliente,
            MAX(CASE WHEN v.DBBatidos_Nombre = 'Membresia' THEN 1 ELSE 0 END) AS vendio_membresia,
            d.canal_caja,
            d.puerto_rtsp_vps,
            d.portal_ip_local,
            d.portal_usuario,
            d.portal_clave
        FROM VentasGlobalesAccessCSV v
        JOIN DVR_Sucursales d
            ON d.cod_sucursal    = v.local
           AND d.tunel_activo    = 1
           AND d.puerto_rtsp_vps IS NOT NULL
           AND d.canal_caja      IS NOT NULL
        WHERE v.Fecha    = :fecha
          AND v.Anulado  = 0
          AND (v.Delivery_Nombre IS NULL OR v.Delivery_Nombre = '')
          AND (v.Motorizado      IS NULL OR v.Motorizado      = '')
          $localFiltro
        GROUP BY v.CodPedido, v.local, v.Fecha,
                 d.canal_caja, d.puerto_rtsp_vps,
                 d.portal_ip_local, d.portal_usuario, d.portal_clave
        HAVING hora_inicio IS NOT NULL
           AND hora_fin    IS NOT NULL
           AND v.CodPedido NOT IN (
               SELECT cod_pedido
               FROM hikvision_cola_analisis
               WHERE fecha  = :fecha
                 AND estado IN ('pendiente', 'procesando', 'completado')
           )
        ORDER BY hora_inicio ASC
    ");
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();

    if (empty($pedidos)) {
        hikOk([
            'encolados' => 0,
            'mensaje'   => 'No hay pedidos nuevos para encolar.',
        ]);
    }

    // ── Insertar en cola en lote ──────────────────────────────
    $ins = $conn->prepare("
        INSERT INTO hikvision_cola_analisis
            (cod_pedido, local_codigo, fecha, hora_inicio, hora_fin,
             canal_track, puerto_rtsp, dvr_ip_local, dvr_usuario, dvr_clave,
             membresia_contexto, estado, tipo, prioridad)
        VALUES
            (:cp, :lc, :fecha, :hi, :hf,
             :ct, :pr, :ip, :usr, :clave,
             :membresia, 'pendiente', 'automatico', 5)
    ");

    $insertados = 0;
    $errores    = 0;
    $resumen_membresia = ['sin_membresia' => 0, 'vendida' => 0, 'ya_tenia' => 0];

    foreach ($pedidos as $p) {
        // Calcular contexto de membresía para este pedido
        if ($p['cod_cliente'] == 0) {
            $membresia_contexto = 'sin_membresia';
        } elseif ($p['vendio_membresia'] == 1) {
            $membresia_contexto = 'vendida';
        } else {
            $membresia_contexto = 'ya_tenia';
        }

        try {
            $ins->execute([
                ':cp'        => $p['CodPedido'],
                ':lc'        => $p['local'],
                ':fecha'     => $p['Fecha'],
                ':hi'        => $p['hora_inicio'],
                ':hf'        => $p['hora_fin'],
                ':ct'        => $p['canal_caja'],
                ':pr'        => $p['puerto_rtsp_vps'],
                ':ip'        => $p['portal_ip_local'],
                ':usr'       => $p['portal_usuario'],
                ':clave'     => $p['portal_clave'],
                ':membresia' => $membresia_contexto,
            ]);
            $insertados++;
            $resumen_membresia[$membresia_contexto]++;
        } catch (Exception $e) {
            $errores++;
            error_log("HIK encolar_dia: error en pedido {$p['CodPedido']}: " . $e->getMessage());
        }
    }

    hikOk([
        'encolados'         => $insertados,
        'errores'           => $errores,
        'fecha'             => $fecha,
        'local_filtro'      => $local ?? 'todas',
        'membresia_resumen' => $resumen_membresia,
        'mensaje'           => "$insertados pedidos encolados para análisis.",
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
