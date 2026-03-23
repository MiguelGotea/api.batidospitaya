<?php
/**
 * horarios_libres.php — Devuelve reuniones de un dia para calcular huecos libres
 *
 * GET ?cod_operario=5&fecha=2026-03-28
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$codOperario = (int)($_GET['cod_operario'] ?? 0);
$fecha       = trim($_GET['fecha'] ?? '');

if (!$codOperario || !$fecha) respuestaError('cod_operario y fecha requeridos');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) respuestaError('Formato fecha invalido');

try {
    $stmt = $conn->prepare("
        SELECT
            r.id, r.titulo,
            r.hora_inicio,
            r.duracion_min,
            TIME_FORMAT(r.hora_inicio, '%H:%i') AS hora_inicio_fmt,
            TIME_FORMAT(
                ADDTIME(r.hora_inicio, SEC_TO_TIME(r.duracion_min * 60)),
                '%H:%i'
            ) AS hora_fin_fmt
        FROM gestion_tareas_reuniones_items r
        LEFT JOIN gestion_tareas_reuniones_participantes grp ON grp.id_item = r.id
        LEFT JOIN AsignacionNivelesCargos anc
               ON anc.CodNivelesCargos = grp.cod_cargo
              AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        WHERE r.tipo = 'reunion'
          AND r.estado != 'cancelado'
          AND r.fecha_meta = :fecha
          AND r.hora_inicio IS NOT NULL
          AND (
              r.cod_operario_creador = :cod
              OR anc.CodOperario = :cod2
          )
        GROUP BY r.id
        ORDER BY r.hora_inicio ASC
    ");
    $stmt->execute([':fecha' => $fecha, ':cod' => $codOperario, ':cod2' => $codOperario]);
    $reuniones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    respuestaOk([
        'data'  => $reuniones,
        'total' => count($reuniones),
        'fecha' => $fecha,
    ]);

} catch (Exception $e) {
    error_log('Error reuniones/horarios_libres.php: ' . $e->getMessage());
    respuestaError('Error interno', 500);
}
