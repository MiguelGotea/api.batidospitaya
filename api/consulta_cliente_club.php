<?php

// === CONFIGURACIÓN ===
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/consulta_cliente_club.log');

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

function logMessage($message) {
    if (!file_exists(dirname(LOG_FILE))) {
        mkdir(dirname(LOG_FILE), 0777, true);
    }
    file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND);
}

function verifyToken() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? $_POST['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if ($token !== API_TOKEN) {
        logMessage("ERROR: Token inválido");
        return false;
    }
    return true;
}

function consultarClienteClub() {
    try {
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            return;
        }
        
        $membresia = $_GET['membresia'] ?? $_POST['membresia'] ?? null;
        $sucursal = $_GET['sucursal'] ?? $_POST['sucursal'] ?? null;
        
        // Validar parámetros
        if ($membresia === null || !is_numeric($membresia)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetro membresia inválido o faltante']);
            logMessage("ERROR: Membresia inválida: $membresia");
            return;
        }
        
        if ($sucursal === null || !is_numeric($sucursal)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parámetro sucursal inválido o faltante']);
            logMessage("ERROR: Sucursal inválida: $sucursal");
            return;
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Buscar cliente en clientesclub
        $stmtCliente = $pdo->prepare("SELECT membresia, nombre, apellido, puntos_iniciales FROM clientesclub WHERE membresia = ? LIMIT 1");
        $stmtCliente->execute([$membresia]);
        $cliente = $stmtCliente->fetch();
        
        // Si no existe el cliente
        if (!$cliente) {
            logMessage("INFO: Cliente no encontrado - Membresia: $membresia");
            echo json_encode([
                'success' => true,
                'membresia' => intval($membresia),
                'sucursal' => intval($sucursal),
                'existe' => 0,
                'datos' => [
                    'membresia' => 0,
                    'nombre' => '',
                    'puntos' => 0,
                    'puntos_iniciales' => 0
                ]
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Cliente existe, calcular puntos
        $stmtPuntos = $pdo->prepare("
            SELECT COALESCE(SUM(Puntos*Cantidad), 0) as total_puntos 
            FROM VentasGlobalesAccessCSV 
            WHERE CodCliente = ? AND local <> ? AND Anulado = 0
        ");
        $stmtPuntos->execute([$membresia, $sucursal]);
        $resultPuntos = $stmtPuntos->fetch();
        $puntos = floatval($resultPuntos['total_puntos']);
        
        // Construir nombre completo
        $nombreCompleto = trim($cliente['nombre']);
        
        // Obtener puntos iniciales
        $puntosIniciales = intval($cliente['puntos_iniciales'] ?? 0);
        
        logMessage("SUCCESS: Cliente encontrado - Membresia: $membresia, Nombre: $nombreCompleto, Puntos: $puntos, Puntos Iniciales: $puntosIniciales");
        
        echo json_encode([
            'success' => true,
            'membresia' => intval($membresia),
            'sucursal' => intval($sucursal),
            'existe' => 1,
            'datos' => [
                'membresia' => intval($cliente['membresia']),
                'nombre' => $nombreCompleto,
                'puntos' => $puntos,
                'puntos_iniciales' => $puntosIniciales
            ]
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
    consultarClienteClub();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>