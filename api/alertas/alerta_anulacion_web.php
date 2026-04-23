<?php
/**
 * alerta_anulacion_web.php — Detecta anulaciones web del día sin resolver
 *
 * Condiciones para alertar:
 *   - AnulacionPedidosHost.Modalidad = 2 (web)
 *   - AnulacionPedidosHost.Status = 0 (pendiente)
 *   - La fecha del pedido en VentasGlobalesAccessCSV = hoy
 *     (si aún no se subió el pedido, Fecha es NULL → no alerta hasta que suba)
 *   - No existe en alertas_wsp_estado con ese CodAnulacionHost
 *
 * Una sola alerta por CodAnulacionHost, nunca se resetea.
 * Llamado por: api/alertas/check_all.php
 */

require_once __DIR__ . '/../../core/database/conexion.php';

// Reutiliza función de destinatarios si ya fue definida por check_all.php
if (!function_exists('obtenerDestinatariosAlerta')) {
    function obtenerDestinatariosAlerta(PDO $conn, string $nombreAlerta): array
    {
        $stmt = $conn->prepare("
            SELECT DISTINCT o.telefono_corporativo
            FROM Operarios o
            INNER JOIN AsignacionNivelesCargos anc
                ON anc.CodOperario = o.CodOperario
                AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                AND anc.Fecha <= CURDATE()
            INNER JOIN permisos_tools_erp p
                ON p.CodNivelesCargos = anc.CodNivelesCargos
                AND p.permiso = 'allow'
            INNER JOIN acciones_tools_erp ac
                ON ac.id = p.accion_tool_erp_id
                AND ac.nombre_accion = 'recibir'
            INNER JOIN tools_erp t
                ON t.id = ac.tool_erp_id
                AND t.tipo_componente = 'alerta'
                AND t.nombre = :nombre
            WHERE o.telefono_corporativo IS NOT NULL
              AND o.telefono_corporativo != ''
        ");
        $stmt->execute([':nombre' => $nombreAlerta]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

try {
    // ── 1. Buscar anulaciones web pendientes del día no alertadas ─────────
    //
    //  v.local es VARCHAR con el número de sucursal (ej: '13'), NO 'S13'.
    //  HoraSolicitada tiene fecha ~1988 (cero de Access) → usamos solo TIME().
    //  Si el pedido aún no se subió a VentasGlobalesAccessCSV → Fecha NULL
    //  → HAVING filtra esos casos → no alerta hasta que aparezca con fecha de hoy.

    $stmt = $conn->prepare("
        SELECT
            a.CodAnulacionHost,
            a.CodPedido,
            a.Sucursal,
            a.Motivo,
            TIME_FORMAT(TIME(a.HoraSolicitada), '%h:%i %p') AS hora_solicitud,
            s.nombre AS nombre_sucursal,
            MAX(v.Fecha) AS fecha_pedido
        FROM AnulacionPedidosHost a
        LEFT JOIN sucursales s
            ON s.codigo = CAST(a.Sucursal AS CHAR)
        LEFT JOIN VentasGlobalesAccessCSV v
            ON v.CodPedido = a.CodPedido
            AND v.local = CAST(a.Sucursal AS CHAR)
        WHERE a.Modalidad = 2
          AND a.Status = 0
          AND NOT EXISTS (
              SELECT 1 FROM alertas_wsp_estado
              WHERE tipo_alerta = 'anulacion_web'
                AND key_unica = CAST(a.CodAnulacionHost AS CHAR)
          )
        GROUP BY
            a.CodAnulacionHost,
            a.CodPedido,
            a.Sucursal,
            a.Motivo,
            a.HoraSolicitada,
            s.nombre
        HAVING fecha_pedido = CURDATE()
        ORDER BY a.CodAnulacionHost ASC
    ");
    $stmt->execute();
    $anulaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($anulaciones)) {
        return ['alertas' => []];
    }

    // ── 2. Obtener destinatarios ──────────────────────────────────────────
    $destinatarios = obtenerDestinatariosAlerta($conn, 'alerta_anulacion_web');

    // Sin destinatarios → omitir, reintenta el próximo minuto
    if (empty($destinatarios)) {
        return ['alertas' => []];
    }

    // ── 3. Construir alertas y registrar en alertas_wsp_estado ───────────
    $alertas    = [];
    $stmtInsert = $conn->prepare("
        INSERT IGNORE INTO alertas_wsp_estado (tipo_alerta, key_unica, datos_json)
        VALUES ('anulacion_web', :key, :datos)
    ");

    foreach ($anulaciones as $a) {
        $codAnulacion = $a['CodAnulacionHost'];
        $sucursal     = $a['nombre_sucursal'] ?: 'Sucursal ' . $a['Sucursal'];
        $motivo       = $a['Motivo'] ?: '(sin motivo)';
        $hora         = $a['hora_solicitud'] ?: '—';

        $mensaje = "⚠️ *Alerta: Anulación Web Pendiente*\n" .
                   "📦 Pedido #" . $a['CodPedido'] . "\n" .
                   "📍 Sucursal: {$sucursal}\n" .
                   "🕐 Solicitado: {$hora}\n" .
                   "📋 Motivo: {$motivo}\n" .
                   "🔗 https://erp.batidospitaya.com/modulos/sistemas/gestion_anulaciones.php";

        // Registrar → nunca vuelve a alertar este CodAnulacionHost
        $stmtInsert->execute([
            ':key'   => (string)$codAnulacion,
            ':datos' => json_encode([
                'CodAnulacionHost' => $codAnulacion,
                'CodPedido'        => $a['CodPedido'],
                'Sucursal'         => $a['Sucursal'],
                'nombre_sucursal'  => $sucursal,
                'hora_solicitud'   => $hora,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $alertas[] = [
            'tipo'          => 'anulacion_web',
            'key_unica'     => (string)$codAnulacion,
            'mensaje'       => $mensaje,
            'destinatarios' => $destinatarios,
        ];
    }

    return ['alertas' => $alertas];

} catch (Exception $e) {
    error_log('[alerta_anulacion_web] ' . $e->getMessage());
    return ['alertas' => []];
}
