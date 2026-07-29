<?php
/**
 * AJAX Endpoint: Cargar indicadores en lote (batch)
 */

require_once '../../../core/auth/auth.php';
require_once '../ComponentRegistry.php';

header('Content-Type: application/json');

// Recibir JSON del body
$input = json_decode(file_get_contents('php://input'), true);
$codigos = $input['codigos'] ?? [];

if (empty($codigos) || !is_array($codigos)) {
    echo json_encode(['success' => false, 'message' => 'Lista de códigos vacía o inválida']);
    exit;
}

$usuario = obtenerUsuarioActual();
if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}
$cargoId = $usuario['CodNivelesCargos'];
$userId = $usuario['CodOperario'];

$resultados = [];

try {
    $registry = new Core\Components\ComponentRegistry($conn);

    foreach ($codigos as $codigo) {
        try {
            $indicator = $registry->getIndicator($codigo, $userId);

            if (!$indicator) {
                $resultados[$codigo] = [
                    'success' => false,
                    'message' => 'Indicador no encontrado'
                ];
                continue;
            }

            // Verificar permiso
            if (!$indicator->hasPermission($userId, $cargoId)) {
                $resultados[$codigo] = [
                    'success' => false,
                    'message' => 'Sin permisos'
                ];
                continue;
            }

            // Obtener datos
            $data = $indicator->render($userId);

            $resultados[$codigo] = [
                'success' => true,
                'codigo' => $data['codigo'],
                'valor' => $data['valor'],
                'color' => $data['color'],
                'fecha_limite' => $data['fecha_limite'] ?? null,
                'dias_restantes' => $data['dias_restantes'] ?? null,
                'url' => $data['url'] ?? '#',
                'detalles' => $data['detalles'] ?? []
            ];

        } catch (Exception $innerEx) {
            error_log("Error cargando indicador individual '$codigo' en batch: " . $innerEx->getMessage());
            $resultados[$codigo] = [
                'success' => false,
                'message' => 'Error al calcular'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'indicadores' => $resultados
    ]);

} catch (Exception $e) {
    error_log("Error general en load_indicators_batch.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error general del sistema']);
}
