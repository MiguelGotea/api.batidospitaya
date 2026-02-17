<?php
/**
 * API para recibir boletas de aguinaldo desde Excel
 * Archivo: api_aguinaldo.php
 * Ubicación: public_html/api/api_aguinaldo.php
 */


// Usar conexión centralizada (ya incluye timezone America/Managua)
require_once __DIR__ . '/../core/database/conexion.php';

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
// Token de seguridad (debe coincidir con el del módulo VBA)
define('API_TOKEN', 'a8f5e2d9c4b7a1e6f3d8c5b2a9e6d3f0c7a4b1e8d5c2a9f6e3d0c7b4a1e8f5d2');

// Usuario que registra (siempre 489)
define('REGISTRADO_POR', 489);

// Archivo de log
define('LOG_FILE', __DIR__ . '/logs/api_aguinaldo.log');

// === FUNCIONES AUXILIARES ===

function logMessage($message, $type = 'INFO')
{
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

function sendResponse($data, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message, $httpCode = 400)
{
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
// Usar conexión global de conexion.php
global $conn;
$pdo = $conn;
logMessage('Usando conexión centralizada de base de datos');

// === PREPARAR CONSULTA SQL ===
$sql = "INSERT INTO BoletaAguinaldo (
    cod_operario,
    cod_contrato,
    empleado_nombre,
    fecha_ingreso,
    fecha_inicio_periodo,
    fecha_final_periodo,
    periodo_laborado_meses,
    salario_basico,
    total_aguinaldo,
    fecha_emision,
    registrado_por,
    fecha_registro
) VALUES (
    :cod_operario,
    :cod_contrato,
    :empleado_nombre,
    :fecha_ingreso,
    :fecha_inicio_periodo,
    :fecha_final_periodo,
    :periodo_laborado_meses,
    :salario_basico,
    :total_aguinaldo,
    :fecha_emision,
    :registrado_por,
    NOW()
) ON DUPLICATE KEY UPDATE
    empleado_nombre = VALUES(empleado_nombre),
    fecha_ingreso = VALUES(fecha_ingreso),
    fecha_inicio_periodo = VALUES(fecha_inicio_periodo),
    fecha_final_periodo = VALUES(fecha_final_periodo),
    periodo_laborado_meses = VALUES(periodo_laborado_meses),
    salario_basico = VALUES(salario_basico),
    total_aguinaldo = VALUES(total_aguinaldo),
    fecha_emision = VALUES(fecha_emision),
    fecha_registro = NOW()";

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
            $camposRequeridos = ['cod_operario', 'cod_contrato', 'fecha_ingreso'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($boleta[$campo]) || $boleta[$campo] === '' || $boleta[$campo] === 0) {
                    throw new Exception("Campo requerido '$campo' faltante o vacío en boleta #$index");
                }
            }

            // Validar que cod_operario y cod_contrato sean números válidos
            if (!is_numeric($boleta['cod_operario']) || intval($boleta['cod_operario']) <= 0) {
                throw new Exception("cod_operario debe ser un número válido mayor a 0 en boleta #$index");
            }

            if (!is_numeric($boleta['cod_contrato']) || intval($boleta['cod_contrato']) <= 0) {
                throw new Exception("cod_contrato debe ser un número válido mayor a 0 en boleta #$index");
            }

            // Ejecutar INSERT/UPDATE
            $resultado = $stmt->execute([
                ':cod_operario' => intval($boleta['cod_operario']),
                ':cod_contrato' => intval($boleta['cod_contrato']),
                ':empleado_nombre' => $boleta['empleado_nombre'] ?? '',
                ':fecha_ingreso' => $boleta['fecha_ingreso'],
                ':fecha_inicio_periodo' => $boleta['fecha_inicio_periodo'] ?? null,
                ':fecha_final_periodo' => $boleta['fecha_final_periodo'] ?? null,
                ':periodo_laborado_meses' => floatval($boleta['periodo_laborado_meses'] ?? 0),
                ':salario_basico' => floatval($boleta['salario_basico'] ?? 0),
                ':total_aguinaldo' => floatval($boleta['total_aguinaldo'] ?? 0),
                ':fecha_emision' => $boleta['fecha_emision'] ?? date('Y-m-d'),
                ':registrado_por' => REGISTRADO_POR
            ]);

            if ($resultado) {
                // Verificar si fue inserción o actualización
                // Si rowCount es 1, fue INSERT; si es 2, fue UPDATE
                $rowCount = $stmt->rowCount();
                if ($rowCount == 1) {
                    $insertados++;
                } elseif ($rowCount == 2) {
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