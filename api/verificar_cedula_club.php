<?php
// Usar conexión centralizada
require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/verificar_cedula_club.log');

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

function verificarCedulaClub()
{
    try {
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            return;
        }

        $membresia = $_GET['membresia'] ?? $_POST['membresia'] ?? null;

        // Validar parámetros
        if ($membresia === null || !is_numeric($membresia)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetro membresia inválido o faltante']);
            logMessage("ERROR: Membresia inválida: $membresia");
            return;
        }

        // Usar conexión global de conexion.php
        global $conn;
        $pdo = $conn;

        // Buscar cliente en clientesclub
        $stmt = $pdo->prepare("SELECT cedula FROM clientesclub WHERE membresia = ? LIMIT 1");
        $stmt->execute([$membresia]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            logMessage("INFO: Miembro no encontrado - Membresia: $membresia");
            echo json_encode([
                'success' => true,
                'existe' => false,
                'cedula' => null
            ]);
            return;
        }

        $cedula = $cliente['cedula'];

        logMessage("SUCCESS: Consulta cedula - Membresia: $membresia, Cedula: " . ($cedula ?? 'NULL'));

        echo json_encode([
            'success' => true,
            'existe' => true,
            'cedula' => $cedula
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
    verificarCedulaClub();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
