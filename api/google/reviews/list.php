<?php
/**
 * list.php — Lista reseñas existentes de una location para el diff
 * GET /api/google/reviews/list.php?locationId=XXX
 * Header: X-WSP-Token
 *
 * Devuelve solo los campos necesarios para comparar contra Google API.
 * Excluye registros con deleted_at (soft-deleted).
 */

require_once __DIR__ . '/../auth.php';

verificarTokenGMB();

$locationId = isset($_GET['locationId']) ? trim($_GET['locationId']) : '';

if (empty($locationId)) {
    hikErr('Parámetro requerido: locationId');
}

try {
    // Asegurarse de que la columna deleted_at existe antes de usarla
    $col = $conn->query("SHOW COLUMNS FROM ResenasGoogle LIKE 'deleted_at'")->fetch();
    if (!$col) {
        $conn->exec("ALTER TABLE ResenasGoogle ADD COLUMN deleted_at DATETIME DEFAULT NULL");
    }

    $stmt = $conn->prepare("
        SELECT
            reviewId,
            comment,
            starRating,
            updateTime,
            reviewReplyComment,
            reviewReplyUpdateTime,
            deleted_at
        FROM ResenasGoogle
        WHERE locationId = :locationId
          AND (deleted_at IS NULL)
    ");
    $stmt->execute([':locationId' => $locationId]);
    $reviews = $stmt->fetchAll();

    hikOk([
        'locationId' => $locationId,
        'reviews'    => $reviews,
        'total'      => count($reviews)
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
