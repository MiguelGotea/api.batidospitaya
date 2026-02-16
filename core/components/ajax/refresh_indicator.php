<?php
/**
 * AJAX Endpoint: Refrescar indicador
 */

require_once '../../../core/auth/auth.php';
require_once '../ComponentRegistry.php';

header('Content-Type: application/json');

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    echo json_encode(['success' => false, 'message' => 'Código de indicador no proporcionado']);
    exit;
}

$usuario = obtenerUsuarioActual();
$cargoId = $usuario['cargo_id'];

try {
    $registry = new Core\Components\ComponentRegistry($conn);
    $indicator = $registry->getIndicator($codigo, $usuario['id']);

    if (!$indicator) {
        echo json_encode(['success' => false, 'message' => 'Indicador no encontrado']);
        exit;
    }

    // Verificar permiso
    if (!$indicator->hasPermission($usuario['id'], $cargoId)) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }

    // Obtener datos actualizados
    $data = $indicator->render($usuario['id']);

    echo json_encode([
        'success' => true,
        'codigo' => $data['codigo'],
        'valor' => $data['valor'],
        'color' => $data['color'],
        'fecha_limite' => $data['fecha_limite'],
        'detalles' => $data['detalles']
    ]);

} catch (Exception $e) {
    error_log("Error refrescando indicador: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al refrescar indicador']);
}
