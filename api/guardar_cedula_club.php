<?php
// Usar conexión centralizada
require_once __DIR__ . '/../core/database/conexion.php';

// === CONFIGURACIÓN ===
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/guardar_cedula_club.log');

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

function guardarCedulaClub()
{
    try {
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            return;
        }

        $membresia = $_GET['membresia'] ?? $_POST['membresia'] ?? null;
        $cedula = $_GET['cedula'] ?? $_POST['cedula'] ?? null;

        // Validar parámetros
        if ($membresia === null || !is_numeric($membresia)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetro membresia inválido o faltante']);
            logMessage("ERROR: Membresia inválida: $membresia");
            return;
        }

        if ($cedula === null || trim($cedula) === "") {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetro cedula inválido o faltante']);
            logMessage("ERROR: Cedula vacía para membresia: $membresia");
            return;
        }

        // Usar conexión global de conexion.php
        global $conn;
        $pdo = $conn;

        // Actualizar cedula en clientesclub
        $stmt = $pdo->prepare("UPDATE clientesclub SET cedula = ? WHERE membresia = ?");
        $stmt->execute([trim($cedula), $membresia]);

        if ($stmt->rowCount() > 0) {
            logMessage("SUCCESS: Cedula actualizada - Membresia: $membresia, Cedula: $cedula");
            echo json_encode([
                'success' => true,
                'message' => 'Cedula guardada correctamente'
            ]);
        } else {
            // Verificar si el registro existe (tal vez ya tenía esa cédula)
            $stmtCheck = $pdo->prepare("SELECT id_clienteclub FROM clientesclub WHERE membresia = ?");
            $stmtCheck->execute([$membresia]);
            if ($stmtCheck->rowCount() > 0) {
                logMessage("INFO: Cedula no se actualizó (posiblemente era el mismo valor) - Membresia: $membresia");
                echo json_encode([
                    'success' => true,
                    'message' => 'El valor de cedula es el mismo o no se requirió cambios'
                ]);
            } else {
                logMessage("ERROR: Membresia no encontrada - Membresia: $membresia");
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Membresia no encontrada']);
            }
        }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    guardarCedulaClub();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>
