<?php
/**
 * worker_status.php — Estado y control del worker HikvisionIA (vía BD)
 * 
 * GET  /api/hikvision/worker_status.php
 *   → Devuelve si el worker está habilitado y estadísticas de cola del día
 *
 * POST /api/hikvision/worker_status.php
 *   Body JSON: { "action": "start" } | { "action": "stop" }
 *   → Activa o desactiva el flag en BD. El worker chequea este flag
 *     en cada iteración vía pedidos_cola.php.
 *
 * Nota: El API está en Hostinger (mismo host que ERP), no en el VPS.
 * Control del worker se hace exclusivamente a través de la BD.
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ── Asegurar que existe la tabla de configuración ────────
    $conn->exec("
        CREATE TABLE IF NOT EXISTS hikvision_worker_config (
            id               INT          NOT NULL DEFAULT 1,
            worker_habilitado TINYINT(1)  NOT NULL DEFAULT 0,
            updated_at       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by       VARCHAR(100)         DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── Asegurar que existe la fila de config ────────────────
    $conn->exec("
        INSERT IGNORE INTO hikvision_worker_config (id, worker_habilitado)
        VALUES (1, 0)
    ");

    // ── POST: cambiar estado ─────────────────────────────────
    if ($method === 'POST') {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = isset($data['action']) ? trim($data['action']) : null;
        $by     = isset($data['updated_by']) ? trim($data['updated_by']) : 'ERP';

        if (!in_array($action, ['start', 'stop'])) {
            hikErr('Acción inválida. Use: start | stop');
        }

        $nuevoEstado = ($action === 'start') ? 1 : 0;

        $conn->prepare("
            UPDATE hikvision_worker_config
            SET worker_habilitado = :estado,
                updated_by        = :by
            WHERE id = 1
        ")->execute([':estado' => $nuevoEstado, ':by' => $by]);

        hikOk([
            'action'           => $action,
            'worker_habilitado' => $nuevoEstado,
            'mensaje'          => $action === 'start'
                ? 'Worker habilitado. Procesará items de la cola automáticamente.'
                : 'Worker detenido. No tomará nuevos items de la cola.',
        ]);
    }

    // ── GET: consultar estado ────────────────────────────────
    $cfg = $conn->query("
        SELECT worker_habilitado, updated_at, updated_by
        FROM hikvision_worker_config
        WHERE id = 1
        LIMIT 1
    ")->fetch();

    $hoy = date('Y-m-d');

    // Estadísticas de cola del día actual
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

    // Inferir si el worker está procesando activamente:
    // Si hay algún item en 'procesando' actualizado en los últimos 3 minutos
    $activo = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM hikvision_cola_analisis
        WHERE estado     = 'procesando'
          AND updated_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
    ");
    $activo->execute();
    $workerActivo = (int)$activo->fetch()['cnt'] > 0;

    hikOk([
        'worker_habilitado'  => (bool)$cfg['worker_habilitado'],
        'worker_procesando'  => $workerActivo,
        'config_updated_at'  => $cfg['updated_at'],
        'config_updated_by'  => $cfg['updated_by'],
        'fecha_hoy'          => $hoy,
        'cola_hoy'           => [
            'pendientes'  => (int)($cola['pendientes']  ?? 0),
            'procesando'  => (int)($cola['procesando']  ?? 0),
            'completados' => (int)($cola['completados'] ?? 0),
            'fallidos'    => (int)($cola['fallidos']    ?? 0),
            'total'       => (int)($cola['total']       ?? 0),
        ],
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
