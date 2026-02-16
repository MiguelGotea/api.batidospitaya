<?php
// === CONFIGURACIÓN ===
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/descarga_tabla.log');

header('CF-Connecting-IP: ' . $_SERVER['REMOTE_ADDR']);
header('X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR']);
header('X-Real-IP: ' . $_SERVER['REMOTE_ADDR']);

// Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
// También añade un "challenge bypass" especial
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

function mapearTipoAccess($tipo) {
    $tipo = strtolower($tipo);
    
    // Verificar boolean primero (antes que int)
    if (strpos($tipo, 'bool') !== false || strpos($tipo, 'tinyint(1)') !== false || strpos($tipo, 'bit(1)') !== false) {
        return 'YESNO';
    }
    if (strpos($tipo, 'varchar') !== false || strpos($tipo, 'text') !== false || strpos($tipo, 'char') !== false) {
        return 'TEXT';
    }
    if (strpos($tipo, 'int') !== false) {
        return 'INTEGER';
    }
    if (strpos($tipo, 'decimal') !== false || strpos($tipo, 'double') !== false || strpos($tipo, 'float') !== false) {
        return 'DOUBLE';
    }
    if (strpos($tipo, 'date') !== false || strpos($tipo, 'time') !== false) {
        return 'DATETIME';
    }
    
    return 'TEXT'; // Por defecto
}

function descargarTabla() {
    try {
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            return;
        }
        
        $tabla = $_GET['tabla'] ?? $_POST['tabla'] ?? null;
        $filtro = $_GET['filtro'] ?? $_POST['filtro'] ?? '';
        
        if (!$tabla || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nombre de tabla inválido']);
            logMessage("ERROR: Tabla inválida: $tabla");
            return;
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Verificar que la tabla existe (sin prepared statement)
        $checkTable = $pdo->query("SHOW TABLES LIKE '$tabla'")->fetch();
        if (!$checkTable) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "La tabla '$tabla' no existe"]);
            logMessage("ERROR: Tabla no encontrada: $tabla");
            return;
        }
        
        // Obtener estructura de la tabla
        $columnas = $pdo->query("DESCRIBE $tabla")->fetchAll();
        
        $estructura = [];
        foreach ($columnas as $col) {
            $estructura[] = [
                'nombre' => $col['Field'],
                'tipo_mysql' => $col['Type'],
                'tipo_access' => mapearTipoAccess($col['Type']),
                'nullable' => $col['Null'] === 'YES'
            ];
        }
        
        // Construir consulta con filtro opcional
        $sql = "SELECT * FROM $tabla";
        if (!empty($filtro)) {
            $sql .= " WHERE " . $filtro;
        }
        
        // Obtener datos
        $datos = $pdo->query($sql)->fetchAll();
        
        $filtroLog = !empty($filtro) ? " con filtro: $filtro" : " sin filtro";
        logMessage("SUCCESS: Tabla '$tabla' descargada$filtroLog - " . count($datos) . " registros");
        
        echo json_encode([
            'success' => true,
            'tabla' => $tabla,
            'filtro' => $filtro,
            'estructura' => $estructura,
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
    descargarTabla();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>