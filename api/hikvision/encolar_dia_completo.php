<?php
/**
 * encolar_dia_completo.php — Encola todos los pedidos presenciales de un día
 * POST /api/hikvision/encolar_dia_completo.php
 * Header: X-WSP-Token
 * Body JSON:
 *   { "fecha": "2026-05-02" }                      → todas las sucursales activas
 *   { "fecha": "2026-05-02", "local": "10" }        → solo esa sucursal
 *
 * HERRAMIENTA AUTOMÁTICA: corre el día completo excluyendo Delivery.
 * Solo encola sucursales con tunel_activo=1 y RTSP configurado.
 * Omite pedidos que ya estén en cola (evita duplicados).
 * Prioridad 5 (normal / automático).
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$data  = json_decode(file_get_contents('php://input'), true);
$fecha = isset($data['fecha']) ? trim($data['fecha']) : null;
$local = isset($data['local']) ? trim($data['local']) : null; // opcional

if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    hikErr('Parámetro requerido: fecha (formato YYYY-MM-DD)');
}

// Validar fecha no futura
if ($fecha > date('Y-m-d')) {
    hikErr('No se puede encolar una fecha futura. Usa la fecha de hoy o anterior.');
}

try {
    // ── Construir filtro de local (opcional) ─────────────────
    $localFiltro = '';
    $params      = [':fecha' => $fecha];

    if ($local !== null) {
        $localFiltro = 'AND v.local = :local';
        $params[':local'] = $local;
    }

    // ── Consulta de pedidos presenciales no encolados ────────
    // - Agrupamos por CodPedido para obtener 1 fila por pedido
    // - Excluimos Delivery: sin Delivery_Nombre ni Motorizado asignado
    // - Solo sucursales con túnel activo y configuración RTSP completa
    // - Excluimos los que ya están pendientes o procesando en la cola
    $stmt = $conn->prepare("
        SELECT
            v.CodPedido,
            v.local,
            v.Fecha,
            MIN(v.HoraCreado)  AS hora_inicio,
            MAX(v.HoraImpreso) AS hora_fin,
            d.canal_caja,
            d.puerto_rtsp_vps,
            d.portal_ip_local,
            d.portal_usuario,
            d.portal_clave
        FROM VentasGlobalesAccessCSV v
        JOIN DVR_Sucursales d
            ON d.cod_sucursal   = v.local
           AND d.tunel_activo   = 1
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
               WHERE fecha        = :fecha
                 AND estado IN ('pendiente', 'procesando', 'completado')
               -- 'fallido' SÍ se puede re-encolar desde aquí
           )
        ORDER BY hora_inicio ASC
    ");
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();

    if (empty($pedidos)) {
        hikOk([
            'encolados' => 0,
            'mensaje'   => 'No hay pedidos nuevos para encolar (ya procesados, en cola, o ninguno presencial).',
        ]);
    }

    // ── Insertar en cola en lote ─────────────────────────────
    $ins = $conn->prepare("
        INSERT INTO hikvision_cola_analisis
            (cod_pedido, local_codigo, fecha, hora_inicio, hora_fin,
             canal_track, puerto_rtsp, dvr_ip_local, dvr_usuario, dvr_clave,
             estado, tipo, prioridad)
        VALUES
            (:cp, :lc, :fecha, :hi, :hf,
             :ct, :pr, :ip, :usr, :clave,
             'pendiente', 'automatico', 5)
    ");

    $insertados = 0;
    $errores    = 0;

    foreach ($pedidos as $p) {
        try {
            $ins->execute([
                ':cp'    => $p['CodPedido'],
                ':lc'    => $p['local'],
                ':fecha' => $p['Fecha'],
                ':hi'    => $p['hora_inicio'],
                ':hf'    => $p['hora_fin'],
                ':ct'    => $p['canal_caja'],
                ':pr'    => $p['puerto_rtsp_vps'],
                ':ip'    => $p['portal_ip_local'],
                ':usr'   => $p['portal_usuario'],
                ':clave' => $p['portal_clave'],
            ]);
            $insertados++;
        } catch (Exception $e) {
            $errores++;
            error_log("HIK encolar_dia: error en pedido {$p['CodPedido']}: " . $e->getMessage());
        }
    }

    hikOk([
        'encolados'      => $insertados,
        'errores'        => $errores,
        'fecha'          => $fecha,
        'local_filtro'   => $local ?? 'todas',
        'mensaje'        => "$insertados pedidos encolados para análisis.",
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
