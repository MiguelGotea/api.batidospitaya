<?php
/**
 * actualizar_notificacion — Reporta el resultado del envío de una notificación transaccional
 * POST /api/wsp/actualizar_notificacion.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

verificarTokenVPS();

// Recibir datos POST
$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$resultado = $data['resultado'] ?? null; // 'exito' o 'error'
$detalle = $data['detalle'] ?? null;

if (!$id || !$resultado) {
    respuestaError('Faltan parámetros requeridos: id, resultado');
}

try {
    $nuevoEstado = ($resultado === 'exito') ? 'enviado' : 'error';
    $enviadoAt = ($resultado === 'exito') ? date('Y-m-d H:i:s') : null;

    $stmt = $conn->prepare("
        UPDATE `wsp_notificaciones_clientesclub_pendientes_` 
        SET estado = :estado, 
            enviado_at = :enviado_at, 
            error_detalle = :error_detalle 
        WHERE id = :id
    ");

    $stmt->execute([
        ':estado' => $nuevoEstado,
        ':enviado_at' => $enviadoAt,
        ':error_detalle' => $detalle,
        ':id' => $id
    ]);

    respuestaOk(['actualizado' => true]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
