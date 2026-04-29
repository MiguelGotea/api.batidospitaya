<?php
/**
 * alerta_conexion_pc.php — Detecta PCs sin conexión ≥ 60 minutos
 *
 * Retorna array de alertas con sus destinatarios por WhatsApp.
 * Solo alerta una vez por evento único (sucursal + pc + ultimo_ping).
 *
 * IMPORTANTE: Este archivo NO registra la alerta en alertas_wsp_estado.
 * El bot llama a marcar_enviado.php SOLO después de confirmar entrega exitosa.
 * Esto garantiza que alertas con fallo de envío sean reintentadas el próximo minuto.
 *
 * Llamado por: api/alertas/check_all.php
 */

require_once __DIR__ . '/../../core/database/conexion.php';

/**
 * Obtiene destinatarios (telefonos) de los cargos configurados para recibir esta alerta.
 * @param PDO $conn
 * @param string $nombreAlerta  nombre en tools_erp
 * @return string[]
 */
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

/**
 * Formatea segundos en texto legible: "1 hora 5 minutos"
 */
function formatearTiempoSinConexion(int $seg): string
{
    $horas   = intdiv($seg, 3600);
    $minutos = intdiv($seg % 3600, 60);

    if ($horas > 0 && $minutos > 0) {
        return "{$horas} hora" . ($horas > 1 ? 's' : '') . " {$minutos} min";
    } elseif ($horas > 0) {
        return "{$horas} hora" . ($horas > 1 ? 's' : '');
    }
    return "{$minutos} min";
}

try {
    // ── 1. Buscar PCs offline ≥ 60 min no alertadas aún ─────────────────
    $stmt = $conn->prepare("
        SELECT
            p.sucursal_codigo,
            p.pc_nombre,
            p.ip_local,
            p.ping_at,
            TIMESTAMPDIFF(SECOND, p.ping_at, NOW()) AS segundos,
            s.nombre AS nombre_sucursal,
            CONCAT(p.sucursal_codigo, '-', p.pc_nombre, '-', p.ping_at) AS key_unica
        FROM sistemas_ping_log p
        INNER JOIN (
            SELECT sucursal_codigo, pc_nombre, MAX(ping_at) AS ultimo_ping
            FROM sistemas_ping_log
            GROUP BY sucursal_codigo, pc_nombre
        ) latest
            ON  p.sucursal_codigo = latest.sucursal_codigo
            AND p.pc_nombre       = latest.pc_nombre
            AND p.ping_at         = latest.ultimo_ping
        LEFT JOIN sucursales s ON s.codigo = p.sucursal_codigo
        WHERE s.sucursal = 1
          AND TIMESTAMPDIFF(SECOND, p.ping_at, NOW()) >= 3600
          AND NOT EXISTS (
              SELECT 1 FROM alertas_wsp_estado
              WHERE tipo_alerta = 'conexion_pc'
                AND key_unica = CONCAT(p.sucursal_codigo, '-', p.pc_nombre, '-', p.ping_at)
          )
        ORDER BY segundos DESC
    ");
    $stmt->execute();
    $pcsOffline = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pcsOffline)) {
        return ['alertas' => []]; // check_all.php include; retorna vacío
    }

    // ── 2. Obtener destinatarios ──────────────────────────────────────────
    $destinatarios = obtenerDestinatariosAlerta($conn, 'alerta_conexion_pc');

    // Sin destinatarios configurados → omitir (reintenta el próximo minuto)
    if (empty($destinatarios)) {
        return ['alertas' => []];
    }

    // ── 3. Construir alertas (SIN registrar — el bot confirma entrega) ───────────
    //
    //  El bot llama a marcar_enviado.php solo si sendMessage() es exitoso.
    //  Si el envío falla, la alerta NO queda en alertas_wsp_estado y
    //  será reintentada en el próximo ciclo (1 minuto).
    $alertas = [];

    foreach ($pcsOffline as $pc) {
        $segundos      = (int)$pc['segundos'];
        $sucursal      = $pc['nombre_sucursal'] ?: $pc['sucursal_codigo'];
        $pcNombre      = $pc['pc_nombre'] ?: '(sin nombre)';

        $mensaje = "🔴 *Alerta: PC Sin Conexión*\n" .
                   "📍 Sucursal: {$sucursal} ({$pcNombre})";

        $alertas[] = [
            'tipo'          => 'conexion_pc',
            'key_unica'     => $pc['key_unica'],
            'mensaje'       => $mensaje,
            'destinatarios' => $destinatarios,
            'datos_json'    => [
                'sucursal'   => $sucursal,
                'pc_nombre'  => $pcNombre,
                'ping_at'    => $pc['ping_at'],
                'segundos'   => $segundos,
            ],
        ];
    }

    return ['alertas' => $alertas];

} catch (Exception $e) {
    error_log('[alerta_conexion_pc] ' . $e->getMessage());
    return ['alertas' => []];
}
