<?php
/**
 * Endpoint AJAX para datos del balance Ventas vs Meta
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/auth/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/components/ComponentRegistry.php';

// Verificar sesión
$usuario = obtenerUsuarioActual();
if (!$usuario) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

$codOperario = $usuario['CodOperario'];

try {
    // Usar la clase del balance para obtener los datos
    $registry = new Core\Components\ComponentRegistry($conn);

    // Buscar el balance específico
    $stmt = $conn->prepare("SELECT * FROM tools_erp WHERE class_name = 'VentasVsMetaBalance' AND activo = 1 LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        throw new Exception("Configuración de balance no encontrada");
    }

    $className = "Core\\Components\\Balances\\List\\VentasVsMetaBalance";
    $balance = new $className($conn, $row['config_json']);

    $data = $balance->fetchData($codOperario);

    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    error_log("Error en get_ventas_vs_meta_data.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
