<?php
/**
 * obtener.php - Obtiene el detalle de una reunion por ID
 * GET /api/resumen_reuniones_ia/obtener.php?id=X&creado_por=5
 * Header: X-Resumen-Token (ERP)
 *
 * Usado por ReunionesWatch (Wear OS) para ver resultado y resumen.
 * Solo retorna la reunion si creado_por coincide (seguridad).
 */

require_once __DIR__ . '/auth.php';

verificarTokenERP();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    reunionErr('Metodo no permitido', 405);
}

$id         = intval($_GET['id']          ?? 0);
$creado_por = intval($_GET['creado_por']  ?? 5);

if ($id <= 0) {
    reunionErr('ID invalido', 422);
}
if ($creado_por <= 0) {
    $creado_por = 5;
}

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            titulo,
            descripcion,
            tipo_reunion,
            estado,
            token,
            resultado_final,
            resumen,
            audio_borrado,
            fecha_creacion,
            fecha_finalizada
        FROM resumen_reuniones_ia
        WHERE id = ? AND creado_por = ?
        LIMIT 1
    ");
    $stmt->execute([$id, $creado_por]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        reunionErr('Reunion no encontrada o sin acceso', 404);
    }

    reunionOk(['data' => $reunion]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] obtener.php error: ' . $e->getMessage());
    reunionErr('Error interno al obtener reunion', 500);
}