<?php
/**
 * api/wear/sucursales.php
 * GET /api/wear/sucursales.php
 *
 * Devuelve lista de todas las sucursales activas (activa=1, sucursal=1),
 * igual que las que aparecen en la tabla de desempeño de sucursales del ERP.
 *
 * Headers requeridos:
 *   X-Api-Key: <token>
 *
 * Respuesta exitosa:
 * {
 *   "success": true,
 *   "sucursales": [
 *     { "codigo": "ALT", "nombre": "Altamira" },
 *     ...
 *   ]
 * }
 */

require_once __DIR__ . '/auth.php';

try {
    require_once __DIR__ . '/../../core/database/conexion.php';

    $stmt = $conn->prepare(
        "SELECT codigo, nombre
         FROM sucursales
         WHERE activa = 1 AND sucursal = 1
         ORDER BY nombre ASC"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'    => true,
        'sucursales' => $rows,
        'total'      => count($rows),
        'timestamp'  => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("[wear/sucursales.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor.'
    ]);
}
?>
