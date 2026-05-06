<?php
// Al inicio del archivo, verificar autenticación y acceso al módulo
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/auth/auth.php'; // Cambiado: anteriormente llamaba al auth de auditorías, ahora llama al auth del core
require_once '../../../../core/helpers/funciones.php'; // Antes llamaba a ../funciones.php de auditora

// Obtener información del usuario actual
$usuario = obtenerUsuarioActual();
$esAdmin = isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u839374897_erp');
define('DB_USER', 'u839374897_erp');
define('DB_PASS', 'ERpPitHay2025$');

// Establecer zona horaria
date_default_timezone_set('America/Managua');

// Función para conectar a la base de datos
function conectarDB() {
    try {
        $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $db;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función para formatear fecha en español
function formatFechaEspanol($fecha = 'now') {
    $meses = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr',
        5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago',
        9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'
    ];
    
    $date = new DateTime($fecha, new DateTimeZone('America/Managua'));
    return $date->format('d').'-'.$meses[$date->format('n')].'-'.$date->format('y').' '.$date->format('h:i a');
}

// Función para formatear fecha de reporte
function formatFechaReporte($fecha) {
    $date = new DateTime($fecha);
    return $date->format('d/m/Y');
}
