<?php
/**
 * worker_status.php — Estado y control del worker HikvisionIA
 * 
 * El flag de habilitación se guarda en un archivo JSON local:
 *   /api/hikvision/worker.flag.json
 * No requiere tabla adicional en la BD.
 *
 * GET  → Devuelve si el worker está habilitado y estadísticas de cola del día
 * POST → { "action": "start" | "stop", "updated_by": "Nombre" }
 *         Escribe el flag en el archivo y retorna el estado actualizado
 */

require_once __DIR__ . '/auth.php';

verificarTokenHIK();

// ── Ruta del archivo de flag ─────────────────────────────────
define('WORKER_FLAG_FILE', __DIR__ . '/worker.flag.json');

// ── Helpers de flag ──────────────────────────────────────────

function leerFlag(): array {
    if (!file_exists(WORKER_FLAG_FILE)) {
        return ['worker_habilitado' => false, 'updated_at' => null, 'updated_by' => null];
    }
    $data = json_decode(file_get_contents(WORKER_FLAG_FILE), true);
    return is_array($data) ? $data : ['worker_habilitado' => false, 'updated_at' => null, 'updated_by' => null];
}

function escribirFlag(bool $habilitado, string $by = 'sistema'): void {
    $data = [
        'worker_habilitado' => $habilitado,
        'updated_at'        => date('Y-m-d H:i:s'),
        'updated_by'        => $by,
    ];
    file_put_contents(WORKER_FLAG_FILE, json_encode($data), LOCK_EX);
}

// ── POST: cambiar estado ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = isset($data['action']) ? trim($data['action']) : null;
    $by     = isset($data['updated_by']) ? trim($data['updated_by']) : 'ERP';

    if (!in_array($action, ['start', 'stop'])) {
        hikErr('Acción inválida. Use: start | stop');
    }

    $nuevoEstado = ($action === 'start');
    escribirFlag($nuevoEstado, $by);

    hikOk([
        'action'            => $action,
        'worker_habilitado' => $nuevoEstado,
        'updated_by'        => $by,
        'updated_at'        => date('Y-m-d H:i:s'),
        'mensaje'           => $nuevoEstado
            ? 'Worker habilitado. Procesará items de la cola automáticamente.'
            : 'Worker detenido. No tomará nuevos items de la cola.',
    ]);
}

// ── GET: consultar estado ────────────────────────────────────
try {
    $flag = leerFlag();
    $hoy  = date('Y-m-d');

    // Estadísticas de cola del día actual (desde BD)
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

    // ¿Está el worker realmente activo en este momento?
    // Si hay algún item en 'procesando' actualizado en los últimos 3 min
    $activo = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM hikvision_cola_analisis
        WHERE estado     = 'procesando'
          AND updated_at >= DATE_SUB(NOW(), INTERVAL 3 MINUTE)
    ");
    $activo->execute();
    $workerProcesando = (int)$activo->fetch()['cnt'] > 0;

    hikOk([
        'worker_habilitado'  => (bool)$flag['worker_habilitado'],
        'worker_procesando'  => $workerProcesando,
        'config_updated_at'  => $flag['updated_at'],
        'config_updated_by'  => $flag['updated_by'],
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
