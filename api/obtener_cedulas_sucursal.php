<?php
// Usar conexión centralizada
require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/obtener_cedulas_sucursal.log');

// Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function logMessage($message)
{
    if (!file_exists(dirname(LOG_FILE))) {
        mkdir(dirname(LOG_FILE), 0777, true);
    }
    file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);
}

function verifyToken()
{
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if ($token !== API_TOKEN) {
        logMessage("ERROR: Token inválido");
        return false;
    }
    return true;
}

function obtenerCedulasSucursal()
{
    try {
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            return;
        }

        $sucursal = $_GET['sucursal'] ?? $_POST['sucursal'] ?? null;

        // Validar parámetros
        if ($sucursal === null || !is_numeric($sucursal)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetro sucursal inválido o faltante']);
            logMessage("ERROR: Sucursal inválida: $sucursal");
            return;
        }

        // Usar conexión global de conexion.php
        global $conn;
        $pdo = $conn;

        // Buscar cedulas de clientes de la sucursal
        $stmt = $pdo->prepare("SELECT membresia, cedula FROM clientesclub WHERE sucursal = ? AND cedula IS NOT NULL AND cedula <> ''");
        $stmt->execute([$sucursal]);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        logMessage("SUCCESS: Consulta masiva cedulas - Sucursal: $sucursal, Registros: " . count($datos));

        echo json_encode([
            'success' => true,
            'sucursal' => intval($sucursal),
            'total_registros' => count($datos),
            'datos' => $datos
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        logMessage("ERROR PDO: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error de BD: ' . $e->getMessage()]);
    } catch (Exception $e) {
        logMessage("ERROR: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

if (in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    obtenerCedulasSucursal();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
