<?php
/**
 * modificar.php — Modifica una tarea existente
 * POST: id_tarea, cod_operario, fecha_meta (y/o titulo, descripcion, prioridad)
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Método no permitido', 405);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$idTarea     = (int)($body['id_tarea']    ?? 0);
$codOperario = (int)($body['cod_operario'] ?? 0);

if (!$idTarea || !$codOperario) {
    respuestaError('Datos incompletos: se requiere id_tarea y cod_operario');
}

// Determinar qué campos actualizar
$setCampos = [];
$params    = [':idTarea' => $idTarea, ':codOperario' => $codOperario];

if (!empty($body['fecha_meta'])) {
    $dt = DateTime::createFromFormat('Y-m-d', trim($body['fecha_meta']));
    if ($dt) {
        $setCampos[]             = 'fecha_meta = :fecha_meta';
        $params[':fecha_meta']   = $dt->format('Y-m-d');
    }
}
if (!empty($body['titulo'])) {
    $setCampos[]       = 'titulo = :titulo';
    $params[':titulo'] = trim($body['titulo']);
}
if (!empty($body['descripcion'])) {
    $setCampos[]            = 'descripcion = :descripcion';
    $params[':descripcion'] = trim($body['descripcion']);
}
if (!empty($body['prioridad']) && in_array($body['prioridad'], ['alta', 'media', 'baja'])) {
    $setCampos[]          = 'prioridad = :prioridad';
    $params[':prioridad'] = $body['prioridad'];
}

if (empty($setCampos)) {
    respuestaError('No se recibió ningún campo a modificar');
}

try {
    // Verificar que la tarea pertenece al operario
    $check = $conn->prepare("
        SELECT id, titulo FROM gestion_tareas_reuniones_items
        WHERE id = :idTarea AND cod_operario_creador = :codOperario AND tipo = 'tarea'
        LIMIT 1
    ");
    $check->execute([':idTarea' => $idTarea, ':codOperario' => $codOperario]);
    $tarea = $check->fetch(PDO::FETCH_ASSOC);

    if (!$tarea) {
        respuestaError('Tarea no encontrada o no tienes acceso a ella', 404);
    }

    $sql = 'UPDATE gestion_tareas_reuniones_items SET ' . implode(', ', $setCampos) . '
            WHERE id = :idTarea AND cod_operario_creador = :codOperario';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    respuestaOk([
        'data'    => ['id' => $idTarea, 'titulo' => $tarea['titulo']],
        'message' => "Tarea '{$tarea['titulo']}' actualizada exitosamente."
    ]);
} catch (Exception $e) {
    error_log('Error tareas/modificar.php: ' . $e->getMessage());
    respuestaError('Error interno al modificar la tarea', 500);
}
