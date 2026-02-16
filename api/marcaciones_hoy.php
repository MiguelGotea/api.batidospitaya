<?php
// === CONFIGURACIÓN ===
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/marcaciones_hoy.log');

// Habilitar CORS para Access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// HEADERS ANTI-CACHE - IMPORTANTE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Fecha pasada

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para registrar logs
function logMessage($message) {
    if (!file_exists(dirname(LOG_FILE))) {
        mkdir(dirname(LOG_FILE), 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

// Verificar token de seguridad
function verifyToken() {
    $headers = getallheaders();
    $token = '';
    
    // Buscar token en headers
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    } elseif (isset($_POST['token'])) {
        $token = $_POST['token'];
    }
    
    if ($token !== API_TOKEN) {
        logMessage("ERROR: Token inválido recibido: " . substr($token, 0, 10) . "...");
        return false;
    }
    
    return true;
}

// Función principal
function getMarcacionesHoy() {
    try {
        // Verificar token
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Token de autenticación inválido'
            ]);
            return;
        }
        
        // Obtener parámetros
        $sucursal_codigo = isset($_GET['sucursal']) ? $_GET['sucursal'] : null;
        $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
        
        // Validar parámetro sucursal
        if (!$sucursal_codigo || !is_numeric($sucursal_codigo)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Parámetro sucursal inválido o faltante'
            ]);
            logMessage("ERROR: Parámetro sucursal inválido: $sucursal_codigo");
            return;
        }
        
        // Conectar a la base de datos
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Consulta SQL para obtener marcaciones del día actual
        $sql = "
            SELECT 
                CodOperario,
                sucursal_codigo,
                fecha,
                hora_ingreso,
                hora_salida
            FROM marcaciones 
            WHERE fecha = :fecha 
                AND sucursal_codigo = :sucursal_codigo
            ORDER BY CodOperario, hora_ingreso
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':sucursal_codigo' => $sucursal_codigo
        ]);
        
        $resultados = $stmt->fetchAll();
        
        // Registrar en log
        logMessage("SUCCESS: Consulta exitosa - Sucursal: $sucursal_codigo, Fecha: $fecha, Registros: " . count($resultados));
        
        // Devolver resultados
        echo json_encode([
            'success' => true,
            'fecha_consulta' => $fecha,
            'sucursal' => $sucursal_codigo,
            'total_registros' => count($resultados),
            'marcaciones' => $resultados
        ]);
        
    } catch (PDOException $e) {
        logMessage("ERROR PDO: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error de base de datos: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        logMessage("ERROR General: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error del sistema: ' . $e->getMessage()
        ]);
    }
}

// Manejar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getMarcacionesHoy();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // También aceptar POST por si Access lo requiere
    getMarcacionesHoy();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}
?>