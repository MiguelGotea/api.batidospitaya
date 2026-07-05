<?php
/**
 * guardar_transcripcion.php — Guarda SOLO la transcripción generada por Gemini
 * POST /api/resumen_reuniones_ia/guardar_transcripcion.php
 * Header: X-Resumen-Token (VPS)
 *
 * Body JSON:
 *   token            string   token de la reunión
 *   transcripcion    string   texto generado por Gemini
 */

require_once __DIR__ . '/auth.php';

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reunionErr('Método no permitido', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    reunionErr('Body JSON inválido o vacío', 400);
}

$tokenReunion  = trim($body['token'] ?? '');
$transcripcion = trim($body['transcripcion'] ?? '');

if (empty($tokenReunion)) {
    reunionErr('El campo "token" es requerido', 422);
}
if (empty($transcripcion)) {
    reunionErr('El campo "transcripcion" es requerido', 422);
}

try {
    // Obtener reunión
    $stmt = $conn->prepare("
        SELECT id
        FROM resumen_reuniones_ia
        WHERE token = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenReunion]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        reunionErr('Token de reunión no encontrado', 404);
    }

    $update = $conn->prepare("
        UPDATE resumen_reuniones_ia
        SET
            transcripcion = :transcripcion
        WHERE id = :id
    ");

    $update->execute([
        ':transcripcion' => $transcripcion,
        ':id'            => (int) $reunion['id'],
    ]);

    reunionOk([
        'reunion_id' => (int) $reunion['id'],
        'mensaje'    => 'Transcripción guardada correctamente',
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] guardar_transcripcion.php error: ' . $e->getMessage());
    reunionErr('Error interno al guardar la transcripción', 500);
}
