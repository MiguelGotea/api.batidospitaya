<?php
/**
 * alerta_anulacion_web.php — Detecta anulaciones web del día sin aviso previo
 *
 * Condiciones para alertar (dos casos):
 *
 *   CASO A — Normal (pendiente):
 *     - AnulacionPedidosHost.Modalidad = 2 (web)
 *     - AnulacionPedidosHost.Status = 0 (aún pendiente de resolución)
 *     - La fecha del pedido en VentasGlobalesAccessCSV = hoy
 *     - No existe en alertas_wsp_estado con tipo 'anulacion_web' y ese CodAnulacionHost
 *
 *   CASO B — Race condition (IA aprobó antes del primer ciclo del scheduler):
 *     - Modalidad = 2 (web)
 *     - AprobadoPor = 'IA Automática' con FechaAprobacion = hoy
 *     - No existe en alertas_wsp_estado con tipo 'anulacion_web' y ese CodAnulacionHost
 *     Esto asegura que el aviso de "Anulación Web Pendiente" siempre se envíe
 *     aunque la IA haya procesado la solicitud antes de que el bot corriera.
 *
 * IMPORTANTE: Este archivo NO registra la alerta en alertas_wsp_estado.
 * El bot llama a marcar_enviado.php SOLO después de confirmar entrega exitosa.
 * Esto garantiza que alertas con fallo de envío sean reintentadas el próximo minuto.
 *
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
    // ── 1. Buscar anulaciones web del día no alertadas ────────────────────
    //
    //  CASO A (normal): Status=0 (aún pendiente) → el aviso llega antes de resolverse.
    //  CASO B (race condition): La IA procesó y aprobó/rechazó ANTES de que el
    //    scheduler corriera por primera vez. En ese caso Status≠0 pero nunca se
    //    mandó el aviso de "Anulación Web Pendiente". Lo detectamos buscando
    //    anulaciones resueltas hoy por IA Automática sin su alerta previa.
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
          AND (
              -- Caso A: aún pendiente de resolución
              a.Status = 0
              OR
              -- Caso B: ya resuelta por IA hoy, pero aviso previo nunca se mandó
              (
                  a.AprobadoPor = 'IA Automática'
                  AND DATE(a.FechaAprobacion) = CURDATE()
              )
          )
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

    // ── 3. Construir alertas (SIN registrar — el bot confirma entrega) ────
    //
    //  El bot llama a marcar_enviado.php solo si sendMessage() es exitoso.
    //  Si el envío falla, la alerta NO queda en alertas_wsp_estado y
    //  será reintentada en el próximo ciclo (1 minuto).
    $alertas = [];

    foreach ($anulaciones as $a) {
        $codAnulacion = $a['CodAnulacionHost'];
        $sucursal     = $a['nombre_sucursal'] ?: 'Sucursal ' . $a['Sucursal'];
        $motivo       = $a['Motivo'] ?: '(sin motivo)';
        $hora         = $a['hora_solicitud'] ?: '—';

        $mensaje = "⚠️ *Alerta: Anulación Web Pendiente*\n" .
                   "📦 Pedido #" . $a['CodPedido'] . " ({$sucursal}) : {$motivo}";

        $alertas[] = [
            'tipo'          => 'anulacion_web',
            'key_unica'     => (string)$codAnulacion,
            'mensaje'       => $mensaje,
            'destinatarios' => $destinatarios,
            'datos_json'    => [
                'CodAnulacionHost' => $codAnulacion,
                'CodPedido'        => $a['CodPedido'],
                'Sucursal'         => $a['Sucursal'],
                'nombre_sucursal'  => $sucursal,
                'hora_solicitud'   => $hora,
            ],
        ];
    }

    return ['alertas' => $alertas];

} catch (Exception $e) {
    error_log('[alerta_anulacion_web] ' . $e->getMessage());
    return ['alertas' => []];
}
