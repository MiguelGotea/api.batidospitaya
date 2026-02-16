<?php
// === CONFIGURACIÓN ===
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');
define('LOG_FILE', __DIR__ . '/logs/aplicar_cupon.log');

// Configurar zona horaria de México
date_default_timezone_set('America/Mexico_City');

// Headers
header('CF-Connecting-IP: ' . $_SERVER['REMOTE_ADDR']);
header('X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR']);
header('X-Real-IP: ' . $_SERVER['REMOTE_ADDR']);

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

function aplicarCupon() {
    try {
        if (!verifyToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            return;
        }
        
        $numero_cupon = $_GET['numero_cupon'] ?? $_POST['numero_cupon'] ?? null;
        $cod_sucursal = $_GET['cod_sucursal'] ?? $_POST['cod_sucursal'] ?? null;
        $cod_pedido = $_GET['cod_pedido'] ?? $_POST['cod_pedido'] ?? null;
        $hora_activacion = date('Y-m-d H:i:s'); // Siempre usar hora actual del servidor
        
        if (!$numero_cupon) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Número de cupón requerido']);
            logMessage("ERROR: Número de cupón no proporcionado");
            return;
        }
        
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Verificar si el cupón existe
        $stmt = $pdo->prepare("SELECT id, numero_cupon, aplicado FROM cupones_sucursales WHERE numero_cupon = ?");
        $stmt->execute([$numero_cupon]);
        $cupon = $stmt->fetch();
        
        if (!$cupon) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Cupón no encontrado']);
            logMessage("ERROR: Cupón no encontrado - $numero_cupon");
            return;
        }
        
        // Construir query dinámica
        $updateData = [
            'aplicado' => 1,
            'hora_activacion' => $hora_activacion
        ];
        
        // Solo agregar si se proporcionaron valores
        if ($cod_sucursal !== null && is_numeric($cod_sucursal)) {
            $updateData['cod_sucursal'] = (int)$cod_sucursal;
        }
        
        if ($cod_pedido !== null && is_numeric($cod_pedido)) {
            $updateData['cod_pedido'] = (int)$cod_pedido;
        }
        
        $setClauses = [];
        $params = [];
        
        foreach ($updateData as $field => $value) {
            $setClauses[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $numero_cupon; // Para el WHERE
        
        $sql = "UPDATE cupones_sucursales SET " . implode(', ', $setClauses) . " WHERE numero_cupon = ?";
        
        // Actualizar el cupón
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $registros_afectados = $stmt->rowCount();
        
        // Obtener datos actualizados
        $stmt = $pdo->prepare("SELECT * FROM cupones_sucursales WHERE numero_cupon = ?");
        $stmt->execute([$numero_cupon]);
        $cupon_actualizado = $stmt->fetch();
        
        // Log detallado
        $logExtra = "";
        if (isset($updateData['cod_sucursal'])) {
            $logExtra .= " Sucursal: " . $updateData['cod_sucursal'];
        }
        if (isset($updateData['cod_pedido'])) {
            $logExtra .= " Pedido: " . $updateData['cod_pedido'];
        }
        
        logMessage("SUCCESS: Cupón aplicado - $numero_cupon (ID: {$cupon['id']}) - Hora: $hora_activacion$logExtra");
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Cupón marcado como aplicado',
            'cupon' => [
                'id' => $cupon_actualizado['id'],
                'numero_cupon' => $cupon_actualizado['numero_cupon'],
                'aplicado_anteriormente' => (bool)$cupon['aplicado'],
                'aplicado_ahora' => true,
                'cod_sucursal' => $cupon_actualizado['cod_sucursal'],
                'cod_pedido' => $cupon_actualizado['cod_pedido'],
                'hora_activacion' => $cupon_actualizado['hora_activacion'],
                'fecha_caducidad' => $cupon_actualizado['fecha_caducidad'],
                'monto' => $cupon_actualizado['monto']
            ],
            'registros_afectados' => $registros_afectados
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
    aplicarCupon();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>