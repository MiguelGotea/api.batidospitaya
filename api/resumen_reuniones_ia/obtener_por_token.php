<?php
/**
 * obtener_por_token.php — Valida un token y retorna datos de la reunión
 * GET /api/resumen_reuniones_ia/obtener_por_token.php?token=XYZ
 * Header: X-Resumen-Token (VPS)
 *
 * Usado por el VPS para validar el acceso antes de cada operación.
 *
 * Respuestas de error:
 *   401 — token de autenticación VPS inválido
 *   404 — token de reunión no encontrado
 *   403 — token de reunión expirado (>6 horas)
 *   410 — reunión cerrada (audio borrado, acceso revocado)
 */

require_once __DIR__ . '/auth.php';

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    reunionErr('Método no permitido', 405);
}

$tokenReunion = trim($_GET['token'] ?? '');

if (empty($tokenReunion)) {
    reunionErr('Parámetro "token" requerido', 422);
}

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            titulo,
            descripcion,
            colaboradores,
            creado_por,
            token,
            token_expira,
            estado,
            ruta_audio,
            audio_borrado,
            fecha_creacion
        FROM resumen_reuniones_ia
        WHERE token = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenReunion]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        reunionErr('Token de reunión no encontrado', 404);
    }

    // Verificar si está cerrada
    if ($reunion['estado'] === 'cerrada') {
        reunionErr('Esta reunión ha sido cerrada y aprobada. El acceso ha sido revocado.', 410);
    }

    // Verificar expiración
    $ahora     = new DateTime();
    $expiracion = new DateTime($reunion['token_expira']);
    if ($ahora > $expiracion) {
        reunionErr('El token de esta reunión ha expirado (6 horas desde la creación).', 403);
    }

    // Decodificar colaboradores JSON
    $colaboradores = json_decode($reunion['colaboradores'] ?? '[]', true) ?: [];

    reunionOk([
        'reunion_id'     => (int) $reunion['id'],
        'titulo'         => $reunion['titulo'],
        'descripcion'    => $reunion['descripcion'] ?? '',
        'colaboradores'  => $colaboradores,
        'creado_por'     => (int) $reunion['creado_por'],
        'estado'         => $reunion['estado'],
        'token_expira'   => $reunion['token_expira'],
        'ruta_audio'     => $reunion['ruta_audio'],
        'audio_borrado'  => (bool) $reunion['audio_borrado'],
        'fecha_creacion' => $reunion['fecha_creacion'],
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] obtener_por_token.php error: ' . $e->getMessage());
    reunionErr('Error interno al obtener la reunión', 500);
}
