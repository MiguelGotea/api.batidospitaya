<?php
/**
 * API para recibir boletas de pago desde Excel
 * Archivo: api_boleta.php
 * Ubicación: public_html/api/api_boleta.php
 */

// Establecer zona horaria de Nicaragua (America/Managua)
date_default_timezone_set('America/Managua');

// Headers de seguridad
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido. Use POST']));
}

// === CONFIGURACIÓN ===
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');

// Token de seguridad (genera uno nuevo ejecutando: bin2hex(random_bytes(32)))
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');

// Archivo de log
define('LOG_FILE', __DIR__ . '/logs/api_boleta.log');

// === FUNCIONES AUXILIARES ===

function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $logEntry = "[$timestamp] [$type] [IP: $ip] $message" . PHP_EOL;
    
    // Crear carpeta logs si no existe
    $logDir = dirname(LOG_FILE);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

function sendResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $httpCode = 400) {
    logMessage($message, 'ERROR');
    sendResponse(['error' => $message], $httpCode);
}

// === VALIDACIÓN DE TOKEN ===
$tokenRecibido = $_POST['token'] ?? '';

if (empty($tokenRecibido)) {
    sendError('Token no proporcionado', 401);
}

if ($tokenRecibido !== API_TOKEN) {
    sendError('Token inválido', 403);
}

logMessage('Token validado correctamente');

// === VALIDACIÓN DE DATOS ===
if (!isset($_POST['datos'])) {
    sendError('No se recibieron datos');
}

$datosJSON = $_POST['datos'];
$datos = json_decode($datosJSON, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendError('JSON inválido: ' . json_last_error_msg());
}

if (!is_array($datos) || empty($datos)) {
    sendError('Los datos deben ser un array no vacío');
}

logMessage('Datos JSON recibidos correctamente. Total de boletas: ' . count($datos));

// === CONEXIÓN A BASE DE DATOS ===
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    logMessage('Conexión a base de datos exitosa');
    
} catch (PDOException $e) {
    sendError('Error de conexión: ' . $e->getMessage(), 500);
}

// === PREPARAR CONSULTA SQL ===
$sql = "INSERT INTO BoletaPago (
    cod_operario,
    empleado_nombre,
    salario_basico,
    fecha_planilla,
    salario_basico_quincenal_dias,
    salario_basico_quincenal_monto,
    feriados_laborados_horas,
    feriados_laborados_monto,
    horas_extras_horas,
    horas_extras_monto,
    faltas_septimo_dia_dias,
    faltas_septimo_dia_monto,
    inss_empleado_porcentaje,
    inss_empleado_monto,
    vacaciones_dias,
    Deducciones
) VALUES (
    :cod_operario,
    :empleado_nombre,
    :salario_basico,
    :fecha_planilla,
    :salario_quincenal_dias,
    :salario_quincenal_monto,
    :feriados_horas,
    :feriados_monto,
    :extras_horas,
    :extras_monto,
    :faltas_dias,
    :faltas_monto,
    :inss_porcentaje,
    :inss_monto,
    :vacaciones_dias,
    :Deducciones
) ON DUPLICATE KEY UPDATE
    empleado_nombre = VALUES(empleado_nombre),
    salario_basico = VALUES(salario_basico),
    salario_basico_quincenal_dias = VALUES(salario_basico_quincenal_dias),
    salario_basico_quincenal_monto = VALUES(salario_basico_quincenal_monto),
    feriados_laborados_horas = VALUES(feriados_laborados_horas),
    feriados_laborados_monto = VALUES(feriados_laborados_monto),
    horas_extras_horas = VALUES(horas_extras_horas),
    horas_extras_monto = VALUES(horas_extras_monto),
    faltas_septimo_dia_dias = VALUES(faltas_septimo_dia_dias),
    faltas_septimo_dia_monto = VALUES(faltas_septimo_dia_monto),
    inss_empleado_porcentaje = VALUES(inss_empleado_porcentaje),
    inss_empleado_monto = VALUES(inss_empleado_monto),
    vacaciones_dias = VALUES(vacaciones_dias),
    Deducciones = VALUES(Deducciones)";

try {
    $stmt = $pdo->prepare($sql);
    
    // === PROCESAR CADA BOLETA ===
    $pdo->beginTransaction();
    
    $insertados = 0;
    $actualizados = 0;
    $errores = [];
    
    foreach ($datos as $index => $boleta) {
        try {
            // Validar campos requeridos
            $camposRequeridos = ['cod_operario', 'empleado_nombre', 'salario_basico', 'fecha_planilla'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($boleta[$campo]) || $boleta[$campo] === '') {
                    throw new Exception("Campo requerido '$campo' faltante en boleta #$index");
                }
            }
            
            // Ejecutar INSERT/UPDATE
            $resultado = $stmt->execute([
                ':cod_operario' => $boleta['cod_operario'],
                ':empleado_nombre' => $boleta['empleado_nombre'],
                ':salario_basico' => floatval($boleta['salario_basico']),
                ':fecha_planilla' => $boleta['fecha_planilla'],
                ':salario_quincenal_dias' => floatval($boleta['salario_quincenal_dias'] ?? 0),
                ':salario_quincenal_monto' => floatval($boleta['salario_quincenal_monto'] ?? 0),
                ':feriados_horas' => floatval($boleta['feriados_horas'] ?? 0),
                ':feriados_monto' => floatval($boleta['feriados_monto'] ?? 0),
                ':extras_horas' => floatval($boleta['extras_horas'] ?? 0),
                ':extras_monto' => floatval($boleta['extras_monto'] ?? 0),
                ':faltas_dias' => floatval($boleta['faltas_dias'] ?? 0),
                ':faltas_monto' => floatval($boleta['faltas_monto'] ?? 0),
                ':inss_porcentaje' => floatval($boleta['inss_porcentaje'] ?? 7),
                ':inss_monto' => floatval($boleta['inss_monto'] ?? 0),
                ':vacaciones_dias' => floatval($boleta['vacaciones_dias'] ?? 0),
                ':Deducciones' => floatval($boleta['Deducciones'] ?? 0)
            ]);
            
            if ($resultado) {
                if ($stmt->rowCount() > 0) {
                    $insertados++;
                } else {
                    $actualizados++;
                }
            }
            
        } catch (Exception $e) {
            $errores[] = "Boleta #$index: " . $e->getMessage();
            logMessage("Error en boleta #$index: " . $e->getMessage(), 'WARNING');
        }
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    $mensaje = "Procesamiento completado. Insertados: $insertados, Actualizados: $actualizados";
    if (!empty($errores)) {
        $mensaje .= ". Errores: " . count($errores);
    }
    
    logMessage($mensaje, 'SUCCESS');
    
    // Respuesta exitosa
    sendResponse([
        'success' => true,
        'mensaje' => $mensaje,
        'insertados' => $insertados,
        'actualizados' => $actualizados,
        'errores' => $errores,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    sendError('Error en base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Error inesperado: ' . $e->getMessage(), 500);
}
?>