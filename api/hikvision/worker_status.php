<?php
/**
 * worker_status.php — Estado y control del worker HikvisionIA
 *
 * Flag de habilitación: archivo JSON local (worker.flag.json).
 * Sin tabla adicional en BD.
 *
 * Estructura del flag:
 * {
 *   "worker_habilitado": true,
 *   "updated_at":   "2026-05-03 12:00:00",
 *   "updated_by":   "Admin",
 *   "last_enqueue": "2026-05-03 12:00:00"   ← fecha del último re-encolar
 * }
 *
 * GET  → Devuelve estado + estadísticas de cola del día.
 *         Si el worker está activo y han pasado ≥ 5 min desde el último
 *         re-encolar, lanza encolar_dia_completo para el día actual
 *         automáticamente (captura pedidos nuevos creados mientras el
 *         worker estaba corriendo).
 *
 * POST → { "action": "start"|"stop", "updated_by": "Nombre" }
 *         start: activa flag + encola día completo inmediatamente.
 *         stop:  desactiva flag (el worker deja de tomar items nuevos).
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

define('WORKER_FLAG_FILE', __DIR__ . '/worker.flag.json');
define('ENQUEUE_INTERVAL_SECONDS', 300); // re-encolar cada 5 minutos

// ── Helpers de flag ──────────────────────────────────────────

function leerFlag(): array {
    if (!file_exists(WORKER_FLAG_FILE)) {
        return [
            'worker_habilitado' => false,
            'updated_at'        => null,
            'updated_by'        => null,
            'last_enqueue'      => null,
        ];
    }
    $data = json_decode(file_get_contents(WORKER_FLAG_FILE), true);
    return is_array($data) ? $data : [
        'worker_habilitado' => false,
        'updated_at'        => null,
        'updated_by'        => null,
        'last_enqueue'      => null,
    ];
}

function escribirFlag(bool $habilitado, string $by = 'sistema', ?string $lastEnqueue = null): void {
    $current = leerFlag();
    $data = [
        'worker_habilitado' => $habilitado,
        'updated_at'        => date('Y-m-d H:i:s'),
        'updated_by'        => $by,
        'last_enqueue'      => $lastEnqueue ?? ($current['last_enqueue'] ?? null),
    ];
    file_put_contents(WORKER_FLAG_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function actualizarLastEnqueue(): void {
    $flag = leerFlag();
    $flag['last_enqueue'] = date('Y-m-d H:i:s');
    file_put_contents(WORKER_FLAG_FILE, json_encode($flag, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Llama a encolar_dia_completo para HOY internamente (sin HTTP externo).
 * Reutiliza la misma conexión $conn ya disponible.
 * Solo encola sucursales con túnel activo y pedidos no encolados.
 * Retorna cantidad de pedidos encolados.
 */
function encolarDiaHoy(): int {
    global $conn;
    $hoy = date('Y-m-d');

    try {
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
        $stmt->execute([':fecha' => $hoy]);
        $pedidos = $stmt->fetchAll();

        if (empty($pedidos)) return 0;

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
                error_log("HIK worker_status encolarDiaHoy: pedido {$p['CodPedido']}: " . $e->getMessage());
            }
        }

        return $insertados;

    } catch (Exception $e) {
        error_log("HIK worker_status encolarDiaHoy error: " . $e->getMessage());
        return 0;
    }
}

// ════════════════════════════════════════════════════════════
// POST: cambiar estado del worker
// ════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = isset($data['action']) ? trim($data['action']) : null;
    $by     = isset($data['updated_by']) ? trim($data['updated_by']) : 'ERP';

    if (!in_array($action, ['start', 'stop'])) {
        hikErr('Acción inválida. Use: start | stop');
    }

    $nuevoEstado = ($action === 'start');
    $lastEnqueue = null;
    $encolados   = 0;

    if ($nuevoEstado) {
        // Al encender: encolar inmediatamente los pedidos de HOY
        $encolados   = encolarDiaHoy();
        $lastEnqueue = date('Y-m-d H:i:s');
    }

    escribirFlag($nuevoEstado, $by, $lastEnqueue);

    hikOk([
        'action'            => $action,
        'worker_habilitado' => $nuevoEstado,
        'updated_by'        => $by,
        'updated_at'        => date('Y-m-d H:i:s'),
        'encolados_hoy'     => $encolados,
        'mensaje'           => $nuevoEstado
            ? "Worker activado. $encolados pedido(s) de hoy encolados."
            : 'Worker detenido. No tomará nuevos items de la cola.',
    ]);
}

// ════════════════════════════════════════════════════════════
// GET: consultar estado + auto re-encolar si corresponde
// ════════════════════════════════════════════════════════════

try {
    $flag        = leerFlag();
    $hoy         = date('Y-m-d');
    $encolados   = 0;
    $reEncolado  = false;

    // ── Auto re-encolar pedidos nuevos del día ───────────────
    // Solo si: worker activo + han pasado ≥ 5 min desde el último re-encolar
    if ($flag['worker_habilitado']) {
        $ahora       = time();
        $lastEnqueue = $flag['last_enqueue'] ? strtotime($flag['last_enqueue']) : 0;

        if (($ahora - $lastEnqueue) >= ENQUEUE_INTERVAL_SECONDS) {
            $encolados  = encolarDiaHoy();
            $reEncolado = true;
            actualizarLastEnqueue();
        }
    }

    // ── Estadísticas de cola del día actual ──────────────────
    $stats = $conn->prepare("
        SELECT
            SUM(estado = 'pendiente')   AS pendientes,
            SUM(estado = 'procesando')  AS procesando,
            SUM(estado = 'completado')  AS completados,
            SUM(estado = 'fallido')     AS fallidos,
            COUNT(*)                    AS total
        FROM hikvision_cola_analisis
        WHERE fecha = :hoy
    ");
    $stats->execute([':hoy' => $hoy]);
    $cola = $stats->fetch();

    // ── ¿Worker procesando activamente? ──────────────────────
    // Si hay algún item 'procesando' actualizado en los últimos 3 min
    $activeStmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM hikvision_cola_analisis
        WHERE estado     = 'procesando'
          AND fecha      = :hoy
          AND updated_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
    ");
    $activeStmt->execute([':hoy' => $hoy]);
    $workerProcesando = (int)$activeStmt->fetch()['cnt'] > 0;

    $respuesta = [
        'worker_habilitado'  => (bool)$flag['worker_habilitado'],
        'worker_procesando'  => $workerProcesando,
        'config_updated_at'  => $flag['updated_at'],
        'config_updated_by'  => $flag['updated_by'],
        'last_enqueue'       => $flag['last_enqueue'],
        'fecha_hoy'          => $hoy,
        'cola_hoy'           => [
            'pendientes'  => (int)($cola['pendientes']  ?? 0),
            'procesando'  => (int)($cola['procesando']  ?? 0),
            'completados' => (int)($cola['completados'] ?? 0),
            'fallidos'    => (int)($cola['fallidos']    ?? 0),
            'total'       => (int)($cola['total']       ?? 0),
        ],
    ];

    if ($reEncolado && $encolados > 0) {
        $respuesta['encolados_nuevos'] = $encolados;
    }

    hikOk($respuesta);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
