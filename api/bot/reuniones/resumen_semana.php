<?php
/**
 * resumen_semana.php — Reuniones de los proximos 7 dias del operario
 *
 * GET ?cod_operario=5
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$codOperario = (int)($_GET['cod_operario'] ?? 0);
if (!$codOperario) respuestaError('cod_operario requerido');

try {
    $stmt = $conn->prepare("
        SELECT
            r.id, r.titulo, r.fecha_meta, r.hora_inicio, r.duracion_min, r.lugar,
            DATEDIFF(r.fecha_meta, CURDATE()) AS dias_restantes,
            GROUP_CONCAT(
                TRIM(o.Nombre)
                ORDER BY o.Nombre SEPARATOR ', '
            ) AS participantes_nombres
        FROM gestion_tareas_reuniones_items r
        LEFT JOIN gestion_tareas_reuniones_participantes grp ON grp.id_item = r.id
        LEFT JOIN AsignacionNivelesCargos anc
               ON anc.CodNivelesCargos = grp.cod_cargo
              AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        LEFT JOIN Operarios o ON o.CodOperario = anc.CodOperario
        WHERE r.tipo = 'reunion'
          AND r.estado != 'cancelado'
          AND r.fecha_meta BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
          AND (
              r.cod_operario_creador = :cod
              OR anc.CodOperario = :cod2
          )
        GROUP BY r.id
        ORDER BY r.fecha_meta ASC, r.hora_inicio ASC
        LIMIT 10
    ");
    $stmt->execute([':cod' => $codOperario, ':cod2' => $codOperario]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respuestaOk(['data' => $rows, 'total' => count($rows)]);

} catch (Exception $e) {
    error_log('Error reuniones/resumen_semana.php: ' . $e->getMessage());
    respuestaError('Error interno', 500);
}
