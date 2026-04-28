<?php
/**
 * alertas/alerta_ia_aprobacion.php
 * Notifica por WhatsApp cuando la IA aprueba automáticamente una anulación.
 *
 * Condiciones para alertar:
 *   - AnulacionPedidosHost.ia_decision = 'aprobar'
 *   - AnulacionPedidosHost.AprobadoPor = 'IA Automática'
 *   - AnulacionPedidosHost.Status = 1 (fue aprobado)
 *   - FechaAprobacion = hoy (solo notificamos aprobaciones recientes del día)
 *   - No existe en alertas_wsp_estado con tipo 'ia_aprobacion' y esa key
 *
 * Llamado por: api/alertas/check_all.php
 */

require_once __DIR__ . '/../../core/database/conexion.php';

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
    // ── 1. Buscar aprobaciones IA del día no notificadas ──────────────────
    $stmt = $conn->prepare("
        SELECT
            a.CodAnulacionHost,
            a.CodPedido,
            a.CodPedidoCambio,
            a.Sucursal,
            a.Motivo,
            a.ia_decision,
            a.ia_resultado,
            TIME_FORMAT(TIME(a.FechaAprobacion), '%h:%i %p') AS hora_aprobacion,
            s.nombre AS nombre_sucursal
        FROM AnulacionPedidosHost a
        LEFT JOIN sucursales s
            ON s.codigo = CAST(a.Sucursal AS CHAR)
        WHERE a.ia_decision  = 'aprobar'
          AND a.AprobadoPor  = 'IA Automática'
          AND a.Status        = 1
          AND DATE(a.FechaAprobacion) = CURDATE()
          AND NOT EXISTS (
              SELECT 1 FROM alertas_wsp_estado
              WHERE tipo_alerta = 'ia_aprobacion'
                AND key_unica   = CAST(a.CodAnulacionHost AS CHAR)
          )
        ORDER BY a.CodAnulacionHost ASC
    ");
    $stmt->execute();
    $aprobaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($aprobaciones)) {
        return ['alertas' => []];
    }

    // ── 2. Obtener destinatarios con permiso alerta_anulacion_web ─────────
    // Reutilizamos el mismo permiso para no crear configuración duplicada.
    $destinatarios = obtenerDestinatariosAlerta($conn, 'alerta_anulacion_web');

    if (empty($destinatarios)) {
        return ['alertas' => []];
    }

    // ── 3. Construir alertas ──────────────────────────────────────────────
    $alertas = [];

    foreach ($aprobaciones as $a) {
        $codAnu   = $a['CodAnulacionHost'];
        $sucursal = $a['nombre_sucursal'] ?: 'Sucursal ' . $a['Sucursal'];
        $motivo   = $a['Motivo'] ?: '(sin motivo)';
        $hora     = $a['hora_aprobacion'] ?: '—';

        // Extraer comentario IA del JSON si está disponible
        $iaComentario = '';
        if (!empty($a['ia_resultado'])) {
            $iaObj = json_decode($a['ia_resultado'], true);
            $iaComentario = $iaObj['comentario'] ?? '';
        }

        $tieneCambio = !empty($a['CodPedidoCambio']) && intval($a['CodPedidoCambio']) > 0;
        $lineaCambio = $tieneCambio ? "\n🔄 Pedido Cambio #" . $a['CodPedidoCambio'] : '';

        $mensaje = "✅ *IA Auto-Aprobó una Anulación*\n" .
                   "📦 Pedido #{$a['CodPedido']}{$lineaCambio}\n" .
                   "📍 Sucursal: {$sucursal}\n" .
                   "🕐 Aprobado: {$hora}\n" .
                   "📋 Motivo: {$motivo}\n" .
                   ($iaComentario ? "🤖 IA: {$iaComentario}\n" : '') .
                   "🔗 https://erp.batidospitaya.com/modulos/sistemas/gestion_anulaciones.php";

        $alertas[] = [
            'tipo'          => 'ia_aprobacion',
            'key_unica'     => (string)$codAnu,
            'mensaje'       => $mensaje,
            'destinatarios' => $destinatarios,
            'datos_json'    => [
                'CodAnulacionHost' => $codAnu,
                'CodPedido'        => $a['CodPedido'],
                'Sucursal'         => $a['Sucursal'],
                'nombre_sucursal'  => $sucursal,
                'hora_aprobacion'  => $hora,
                'ia_comentario'    => $iaComentario,
            ],
        ];
    }

    return ['alertas' => $alertas];

} catch (Exception $e) {
    error_log('[alerta_ia_aprobacion] ' . $e->getMessage());
    return ['alertas' => []];
}
?>
