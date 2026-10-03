<?php
/**
 * listar.php - Lista las reuniones del CodOperario pasado por query
 * GET /api/resumen_reuniones_ia/listar.php?creado_por=5
 * Header: X-Resumen-Token (ERP)
 *
 * Usado por ReunionesWatch (Wear OS) para mostrar el historial.
 * Retorna: id, titulo, estado, tipo_reunion, fecha_creacion
 */

require_once __DIR__ . '/auth.php';

verificarTokenERP();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    reunionErr('Metodo no permitido', 405);
}

$creado_por = intval($_GET['creado_por'] ?? 5);
if ($creado_por <= 0) {
    $creado_por = 5;
}

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            titulo,
            estado,
            tipo_reunion,
            fecha_creacion
        FROM resumen_reuniones_ia
        WHERE creado_por = ?
        ORDER BY fecha_creacion DESC
        LIMIT 50
    ");
    $stmt->execute([$creado_por]);
    $reuniones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    reunionOk(['data' => $reuniones]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] listar.php error: ' . $e->getMessage());
    reunionErr('Error interno al listar reuniones', 500);
}