<?php
/**
 * TEST DB - Verificar conexión a la base de datos
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json');

$result = [
    'conexion' => false,
    'tabla_wsp_sesion_vps_' => false,
    'error' => null
];

try {
    // Probar conexión
    if ($conn && $conn->ping()) {
        $result['conexion'] = true;

        // Verificar si la tabla existe
        $check = $conn->query("SHOW TABLES LIKE 'wsp_sesion_vps_'");
        $result['tabla_wsp_sesion_vps_'] = ($check && $check->num_rows > 0);

        if (!$result['tabla_wsp_sesion_vps_']) {
            $result['error'] = "La tabla 'wsp_sesion_vps_' no existe";
        }
    } else {
        $result['error'] = "No se pudo conectar a la base de datos";
    }
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);