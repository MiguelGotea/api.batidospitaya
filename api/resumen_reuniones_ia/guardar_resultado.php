<?php
/**
 * guardar_resultado.php — Guarda el resumen generado por Gemini
 * POST /api/resumen_reuniones_ia/guardar_resultado.php
 * Header: X-Resumen-Token (VPS)
 *
 * Body JSON:
 *   token            string   token de la reunión
 *   resultado_final  string   texto Markdown generado por Gemini
 *   ruta_audio       string   ruta física del archivo de audio en el VPS
 *
 * Transición de estado: procesando → completada
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

$tokenReunion   = trim($body['token']            ?? '');
$resultadoFinal = trim($body['resultado_final']  ?? '');
$rutaAudio      = trim($body['ruta_audio']       ?? '');

if (empty($tokenReunion)) {
    reunionErr('El campo "token" es requerido', 422);
}
if (empty($resultadoFinal)) {
    reunionErr('El campo "resultado_final" es requerido', 422);
}

try {
    // Obtener reunión
    $stmt = $conn->prepare("
        SELECT id, estado
        FROM resumen_reuniones_ia
        WHERE token = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenReunion]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        reunionErr('Token de reunión no encontrado', 404);
    }

    // Solo se puede guardar resultado si está en estado 'procesando'
    if ($reunion['estado'] !== 'procesando') {
        reunionErr(
            "Estado inválido para guardar resultado: '{$reunion['estado']}'. Se esperaba 'procesando'.",
            422
        );
    }

    $update = $conn->prepare("
        UPDATE resumen_reuniones_ia
        SET
            resultado_final  = :resultado_final,
            ruta_audio       = :ruta_audio,
            estado           = 'completada',
            fecha_completada = NOW()
        WHERE id = :id
    ");

    $update->execute([
        ':resultado_final' => $resultadoFinal,
        ':ruta_audio'      => $rutaAudio ?: null,
        ':id'              => (int) $reunion['id'],
    ]);

    reunionOk([
        'reunion_id' => (int) $reunion['id'],
        'estado'     => 'completada',
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] guardar_resultado.php error: ' . $e->getMessage());
    reunionErr('Error interno al guardar el resultado', 500);
}
