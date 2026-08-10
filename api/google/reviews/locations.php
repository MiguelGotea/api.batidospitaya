<?php
/**
 * locations.php — Lista sucursales con cod_googlebusiness
 * GET /api/google/reviews/locations.php
 * Header: X-WSP-Token
 *
 * Usado por el worker para mapear locationId → nombre local de sucursal.
 * El worker sincroniza TODAS las locations de Google; este endpoint
 * solo provee los nombres amigables para las que tengamos mapeadas.
 */

require_once __DIR__ . '/../auth.php';

verificarTokenGMB();

try {
    $stmt = $conn->prepare("
        SELECT
            cod_googlebusiness AS locationId,
            nombre             AS locationName
        FROM sucursales
        WHERE cod_googlebusiness IS NOT NULL
          AND cod_googlebusiness != ''
        ORDER BY nombre ASC
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll();

    hikOk([
        'locations' => $locations,
        'total'     => count($locations)
    ]);

} catch (Exception $e) {
    hikErr('Error interno: ' . $e->getMessage(), 500);
}
