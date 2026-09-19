<?php
/**
 * api/wear/resumen_sucursales.php
 * GET /api/wear/resumen_sucursales.php
 *
 * Devuelve lista de todas las sucursales activas con su color de cumplimiento
 * del mes actual (hasta hoy, sin restricción hoy-1).
 *
 * Headers requeridos:
 *   X-Api-Key: <token>
 *
 * Respuesta exitosa:
 * {
 *   "success": true,
 *   "mes": "Sep",
 *   "anio": 2026,
 *   "sucursales": [
 *     {
 *       "codigo": "ALT",
 *       "nombre": "Altamira",
 *       "cumplimiento": 144.9,
 *       "color": "verde"
 *     },
 *     ...
 *   ]
 * }
 *
 * Colores: verde >= 100%, amarillo 85-99%, rojo < 85%, gris = sin datos
 */

require_once __DIR__ . '/auth.php';

try {
    require_once __DIR__ . '/../../core/database/conexion.php';

    $hoy          = date('Y-m-d');
    $primerDiaMes = date('Y-m-01');
    $meses        = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $mesActual    = $meses[(int)date('m') - 1];
    $anioActual   = (int)date('Y');

    // Obtener todas las sucursales activas (igual que desempeno_sucursales_v2.php)
    $stmtSuc = $conn->prepare(
        "SELECT codigo, nombre FROM sucursales
         WHERE activa = 1 AND sucursal = 1
         ORDER BY nombre ASC"
    );
    $stmtSuc->execute();
    $sucursales = $stmtSuc->fetchAll(PDO::FETCH_ASSOC);

    // Total ventas por sucursal del mes hasta hoy
    $stmtVentas = $conn->prepare(
        "SELECT local AS codigo, SUM(Precio) AS total_ventas
         FROM VentasGlobalesAccessCSV
         WHERE MONTH(Fecha) = :mes AND YEAR(Fecha) = :anio
           AND DATE(Fecha) <= :hoy
           AND (Anulado IS NULL OR Anulado = 0)
         GROUP BY local"
    );
    $stmtVentas->execute([
        ':mes'  => (int)date('m'),
        ':anio' => $anioActual,
        ':hoy'  => $hoy
    ]);
    $ventasMap = [];
    foreach ($stmtVentas->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ventasMap[$row['codigo']] = (float)$row['total_ventas'];
    }

    // Total meta por sucursal del mes hasta hoy
    $stmtMeta = $conn->prepare(
        "SELECT cod_sucursal AS codigo, SUM(meta) AS total_meta
         FROM ventas_meta
         WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio
           AND DATE(fecha) <= :hoy
         GROUP BY cod_sucursal"
    );
    $stmtMeta->execute([
        ':mes'  => (int)date('m'),
        ':anio' => $anioActual,
        ':hoy'  => $hoy
    ]);
    $metaMap = [];
    foreach ($stmtMeta->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $metaMap[$row['codigo']] = (float)$row['total_meta'];
    }

    // Construir respuesta combinada
    $resultado = [];
    foreach ($sucursales as $suc) {
        $cod          = $suc['codigo'];
        $ventas       = $ventasMap[$cod] ?? 0;
        $meta         = $metaMap[$cod]   ?? 0;
        $cumplimiento = $meta > 0 ? round(($ventas / $meta) * 100, 1) : 0;

        if ($meta <= 0) {
            $color = 'gris';
        } elseif ($cumplimiento >= 100) {
            $color = 'verde';
        } elseif ($cumplimiento >= 85) {
            $color = 'amarillo';
        } else {
            $color = 'rojo';
        }

        $resultado[] = [
            'codigo'       => $cod,
            'nombre'       => $suc['nombre'],
            'cumplimiento' => $cumplimiento,
            'color'        => $color
        ];
    }

    echo json_encode([
        'success'    => true,
        'mes'        => $mesActual,
        'anio'       => $anioActual,
        'sucursales' => $resultado,
        'total'      => count($resultado),
        'timestamp'  => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("[wear/resumen_sucursales.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
?>
