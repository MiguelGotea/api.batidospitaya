<?php
/**
 * api/wear/ventas_meta.php
 * GET /api/wear/ventas_meta.php?sucursal=ALT
 *
 * Devuelve datos de Ventas vs Meta para una sucursal,
 * desde el primer día del mes actual hasta HOY (sin restricción hoy-1).
 *
 * Headers requeridos:
 *   X-Api-Key: <token>
 *
 * Parámetros GET:
 *   sucursal  : Código de la sucursal (requerido)
 *
 * Respuesta exitosa:
 * {
 *   "success": true,
 *   "sucursal": "ALT",
 *   "nombre_sucursal": "Altamira",
 *   "mes": "Sep",
 *   "anio": 2026,
 *   "meta_diaria": 27.5,
 *   "datos": [
 *     {
 *       "dia": 19,
 *       "fecha": "2026-09-19",
 *       "ventas_reales": 39.8,
 *       "meta": 27.5,
 *       "cumplimiento": 144.9,
 *       "color": "verde"
 *     },
 *     ...
 *   ],
 *   "resumen_mes": {
 *     "total_ventas": 520.3,
 *     "total_meta": 467.5,
 *     "cumplimiento_mes": 111.3,
 *     "color": "verde"
 *   }
 * }
 *
 * Colores: verde >= 100%, amarillo 85-99%, rojo < 85%
 */

require_once __DIR__ . '/auth.php';

try {
    require_once __DIR__ . '/../../core/database/conexion.php';

    // Validar parámetro sucursal
    $sucursalCodigo = isset($_GET['sucursal']) ? trim($_GET['sucursal']) : '';

    if (empty($sucursalCodigo)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Parámetro "sucursal" es requerido.'
        ]);
        exit;
    }

    // Verificar que la sucursal exista y esté activa
    $stmtCheck = $conn->prepare(
        "SELECT codigo, nombre FROM sucursales
         WHERE codigo = ? AND activa = 1 AND sucursal = 1
         LIMIT 1"
    );
    $stmtCheck->execute([$sucursalCodigo]);
    $sucursalData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$sucursalData) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "Sucursal '$sucursalCodigo' no encontrada o no activa."
        ]);
        exit;
    }

    $nombreSucursal = $sucursalData['nombre'];

    // Calcular rango de fechas: primer día del mes actual → hoy (inclusive)
    $hoy           = date('Y-m-d');
    $primerDiaMes  = date('Y-m-01');
    $diaHoy        = (int) date('d');
    $yearMonth     = date('Y-m-');

    // Nombres de mes en español
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $mesActual = $meses[(int) date('m') - 1];
    $anioActual = (int) date('Y');

    // Obtener la meta diaria de referencia (primera encontrada del mes)
    $stmtMetaRef = $conn->prepare(
        "SELECT meta FROM ventas_meta
         WHERE cod_sucursal = ? AND fecha >= ? AND fecha <= ?
         LIMIT 1"
    );
    $stmtMetaRef->execute([$sucursalCodigo, $primerDiaMes, $hoy]);
    $metaRef = $stmtMetaRef->fetch(PDO::FETCH_ASSOC);
    $metaDiariaRef = $metaRef ? round($metaRef['meta'] / 1000, 1) : 0;

    // Recorrer los días desde 1 hasta hoy (en orden descendente = día más reciente primero)
    $datos         = [];
    $totalVentas   = 0;
    $totalMeta     = 0;
    $diasConDatos  = 0;

    for ($dia = $diaHoy; $dia >= 1; $dia--) {
        $fecha = $yearMonth . str_pad($dia, 2, '0', STR_PAD_LEFT);

        // Ventas reales del día
        $stmtVentas = $conn->prepare(
            "SELECT COALESCE(SUM(Precio), 0) AS Total_Ventas
             FROM VentasGlobalesAccessCSV
             WHERE local = ? AND Anulado = 0 AND Fecha = ?"
        );
        $stmtVentas->execute([$sucursalCodigo, $fecha]);
        $ventasData   = $stmtVentas->fetch(PDO::FETCH_ASSOC);
        $ventasReales = $ventasData ? round($ventasData['Total_Ventas'] / 1000, 1) : 0;

        // Meta del día
        $stmtMetaDia = $conn->prepare(
            "SELECT meta FROM ventas_meta
             WHERE cod_sucursal = ? AND fecha = ?
             LIMIT 1"
        );
        $stmtMetaDia->execute([$sucursalCodigo, $fecha]);
        $metaDiaData = $stmtMetaDia->fetch(PDO::FETCH_ASSOC);
        $metaDia     = $metaDiaData ? round($metaDiaData['meta'] / 1000, 1) : 0;

        // Cumplimiento
        $cumplimiento = $metaDia > 0 ? round(($ventasReales / $metaDia) * 100, 1) : 0;

        // Color según umbral
        if ($cumplimiento < 85) {
            $color = 'rojo';
        } elseif ($cumplimiento >= 100) {
            $color = 'verde';
        } else {
            $color = 'amarillo';
        }

        $datos[] = [
            'dia'            => $dia,
            'fecha'          => $fecha,
            'ventas_reales'  => $ventasReales,
            'meta'           => $metaDia,
            'cumplimiento'   => $cumplimiento,
            'color'          => $color
        ];

        $totalVentas  += $ventasReales;
        $totalMeta    += $metaDia;
        $diasConDatos++;
    }

    // Resumen mensual
    $cumplimientoMes = $totalMeta > 0 ? round(($totalVentas / $totalMeta) * 100, 1) : 0;

    if ($cumplimientoMes < 85) {
        $colorMes = 'rojo';
    } elseif ($cumplimientoMes >= 100) {
        $colorMes = 'verde';
    } else {
        $colorMes = 'amarillo';
    }

    echo json_encode([
        'success'         => true,
        'sucursal'        => $sucursalCodigo,
        'nombre_sucursal' => $nombreSucursal,
        'mes'             => $mesActual,
        'anio'            => $anioActual,
        'meta_diaria'     => $metaDiariaRef,
        'datos'           => $datos,
        'resumen_mes'     => [
            'total_ventas'     => round($totalVentas, 1),
            'total_meta'       => round($totalMeta, 1),
            'cumplimiento_mes' => $cumplimientoMes,
            'color'            => $colorMes,
            'dias_con_datos'   => $diasConDatos
        ],
        'timestamp'       => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("[wear/ventas_meta.php] " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor.'
    ]);
}
?>
