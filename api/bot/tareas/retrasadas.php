<?php
/**
 * retrasadas.php — Lista tareas vencidas del operario
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
               DATEDIFF(CURDATE(), fecha_meta) AS dias_retraso
        FROM gestion_tareas_reuniones_items
        WHERE cod_operario_creador = :codOperario
          AND tipo = 'tarea'
          AND estado IN ('solicitado', 'en_progreso')
          AND fecha_meta IS NOT NULL
          AND fecha_meta < CURDATE()
        ORDER BY fecha_meta ASC
        LIMIT 20
    ");
    $stmt->execute([':codOperario' => $codOperario]);
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respuestaOk([
        'data'    => $tareas,
        'total'   => count($tareas),
        'message' => count($tareas) === 0
            ? 'No tienes tareas retrasadas. ¡Excelente! 🎉'
            : 'Listado de tareas vencidas obtenido.'
    ]);
} catch (Exception $e) {
    error_log('Error tareas/retrasadas.php: ' . $e->getMessage());
    respuestaError('Error interno al obtener tareas retrasadas', 500);
}
