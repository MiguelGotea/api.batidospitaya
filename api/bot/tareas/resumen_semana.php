<?php
/**
 * resumen_semana.php — Tareas con fecha_meta en los próximos 7 días
 * GET: ?cod_operario=N
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenBot();

$codOperario = (int)($_GET['cod_operario'] ?? 0);
if (!$codOperario) {
    respuestaError('Dato requerido: cod_operario');
}

try {
    $stmt = $conn->prepare("
        SELECT id, titulo, fecha_meta, estado, prioridad,
               DATEDIFF(fecha_meta, CURDATE()) AS dias_restantes
        FROM gestion_tareas_reuniones_items
        WHERE cod_operario_creador = :codOperario
          AND tipo = 'tarea'
          AND estado IN ('solicitado', 'en_progreso')
          AND fecha_meta IS NOT NULL
          AND fecha_meta BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY fecha_meta ASC
        LIMIT 20
    ");
    $stmt->execute([':codOperario' => $codOperario]);
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respuestaOk([
        'data'    => $tareas,
        'total'   => count($tareas),
        'message' => count($tareas) === 0
            ? 'No tienes tareas programadas para los próximos 7 días.'
            : 'Resumen de la semana obtenido.'
    ]);
} catch (Exception $e) {
    error_log('Error tareas/resumen_semana.php: ' . $e->getMessage());
    respuestaError('Error interno al obtener el resumen semanal', 500);
}
