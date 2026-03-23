<?php
/**
 * cambiar_estado.php — Cambia el estado de una tarea (finalizar o cancelar)
 * POST: id_tarea, cod_operario, estado_destino ('finalizado'|'cancelado')
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');
verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respuestaError('Método no permitido', 405);

$body           = json_decode(file_get_contents('php://input'), true) ?? [];
$idTarea        = (int)($body['id_tarea']       ?? 0);
$codOperario    = (int)($body['cod_operario']    ?? 0);
$estadoDestino  = trim($body['estado_destino']  ?? '');

if (!$idTarea || !$codOperario || !$estadoDestino) {
    respuestaError('Datos incompletos: se requiere id_tarea, cod_operario y estado_destino');
}

if (!in_array($estadoDestino, ['finalizado', 'cancelado'])) {
    respuestaError('estado_destino debe ser "finalizado" o "cancelado"');
}

try {
    // Verificar que la tarea pertenece al operario y está activa
    $check = $conn->prepare("
        SELECT id, titulo, estado FROM gestion_tareas_reuniones_items
        WHERE id = :idTarea AND cod_operario_creador = :codOperario AND tipo = 'tarea'
        LIMIT 1
    ");
    $check->execute([':idTarea' => $idTarea, ':codOperario' => $codOperario]);
    $tarea = $check->fetch(PDO::FETCH_ASSOC);

    if (!$tarea) {
        respuestaError('Tarea no encontrada o no tienes acceso a ella', 404);
    }

    if (in_array($tarea['estado'], ['finalizado', 'cancelado'])) {
        respuestaError("La tarea ya está en estado '{$tarea['estado']}'", 400);
    }

    // Actualizar estado y registrar fecha si es finalizado
    $campsFecha   = $estadoDestino === 'finalizado' ? ', progreso = 100' : '';
    $stmt = $conn->prepare("
        UPDATE gestion_tareas_reuniones_items
        SET estado = :estado $campsFecha
        WHERE id = :idTarea
    ");
    $stmt->execute([':estado' => $estadoDestino, ':idTarea' => $idTarea]);

    $emoji  = $estadoDestino === 'finalizado' ? '✅' : '🚫';
    $verbo  = $estadoDestino === 'finalizado' ? 'finalizada' : 'cancelada';

    respuestaOk([
        'data'    => ['id' => $idTarea, 'titulo' => $tarea['titulo'], 'estado' => $estadoDestino],
        'message' => "$emoji Tarea '{$tarea['titulo']}' marcada como $verbo."
    ]);
} catch (Exception $e) {
    error_log('Error tareas/cambiar_estado.php: ' . $e->getMessage());
    respuestaError('Error interno al cambiar estado de la tarea', 500);
}
