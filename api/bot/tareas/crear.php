<?php
/**
 * crear.php — Crea una tarea para el operario
 * POST: cod_operario, cod_cargo, titulo, descripcion, fecha_meta, prioridad
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Método no permitido', 405);

$body         = json_decode(file_get_contents('php://input'), true) ?? [];
$codOperario  = (int)($body['cod_operario'] ?? 0);
$codCargo     = (int)($body['cod_cargo']    ?? 0);
$titulo       = trim($body['titulo']       ?? '');
$descripcion  = trim($body['descripcion']  ?? '');
$fechaMeta    = trim($body['fecha_meta']   ?? '');
$prioridad    = trim($body['prioridad']    ?? 'media');

if (!$codOperario || !$titulo) {
    respuestaError('Datos incompletos: se requiere cod_operario y titulo');
}

if (!in_array($prioridad, ['alta', 'media', 'baja'])) $prioridad = 'media';

$fechaFinal = null;
if (!empty($fechaMeta)) {
    $dt = DateTime::createFromFormat('Y-m-d', $fechaMeta);
    $fechaFinal = ($dt !== false) ? $dt->format('Y-m-d') : null;
}

try {
    // Verificar si la columna prioridad existe (defensivo)
    $cols = $conn->query("SHOW COLUMNS FROM gestion_tareas_reuniones_items LIKE 'prioridad'")->fetchAll();
    $tienePrioridad = count($cols) > 0;

    if ($tienePrioridad) {
        $stmt = $conn->prepare("
            INSERT INTO gestion_tareas_reuniones_items
                (tipo, titulo, descripcion, cod_cargo_asignado, cod_cargo_creador,
                 cod_operario_creador, fecha_meta, estado, prioridad, fecha_creacion)
            VALUES
                ('tarea', :titulo, :descripcion, :codCargo, :codCargo,
                 :codOperario, :fechaMeta, 'en_progreso', :prioridad,
                 CONVERT_TZ(NOW(), '+00:00', '-06:00'))
        ");
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion ?: null,
            ':codCargo'    => $codCargo ?: null,
            ':codOperario' => $codOperario,
            ':fechaMeta'   => $fechaFinal,
            ':prioridad'   => $prioridad,
        ]);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO gestion_tareas_reuniones_items
                (tipo, titulo, descripcion, cod_cargo_asignado, cod_cargo_creador,
                 cod_operario_creador, fecha_meta, estado, fecha_creacion)
            VALUES
                ('tarea', :titulo, :descripcion, :codCargo, :codCargo,
                 :codOperario, :fechaMeta, 'en_progreso',
                 CONVERT_TZ(NOW(), '+00:00', '-06:00'))
        ");
        $stmt->execute([
            ':titulo'      => $titulo,
            ':descripcion' => $descripcion ?: null,
            ':codCargo'    => $codCargo ?: null,
            ':codOperario' => $codOperario,
            ':fechaMeta'   => $fechaFinal,
        ]);
    }

    $id = $conn->lastInsertId();
    respuestaOk([
        'data'    => ['id' => $id, 'titulo' => $titulo, 'fecha_meta' => $fechaFinal, 'prioridad' => $prioridad],
        'message' => "Tarea '$titulo' creada exitosamente."
    ]);
} catch (Exception $e) {
    error_log('Error tareas/crear.php: ' . $e->getMessage());
    respuestaError('Error interno al crear la tarea: ' . $e->getMessage(), 500);
}
