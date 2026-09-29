<?php
date_default_timezone_set('America/Managua');

// ============================================================
// SISTEMA DE CONFIGURACIÓN POR ENTORNO
// ============================================================
// Este archivo NO contiene credenciales. Todas vienen de env.php.
//
// env.php NUNCA está en el repositorio (ver .gitignore).
// Cada entorno tiene su propio env.php:
//
//   LOCAL (desarrollo):
//     → Creado automáticamente por .scripts/setup_php_portable.ps1
//     → Apunta a la BD espejo local con usuario restringido (≠ producción)
//
//   PRODUCCIÓN (host Hostinger):
//     → Subido manualmente por el admin al servidor
//     → O generado por GitHub Actions desde Secrets
//
// Si env.php no existe → falla con mensaje claro. Sin fallback.
// ============================================================

$_envFile = __DIR__ . '/env.php';

if (!file_exists($_envFile)) {
    error_log("CRÍTICO: env.php no encontrado en " . __DIR__);
    http_response_code(503);
    die(implode("\n", [
        "⚠️ Configuración de entorno no encontrada.",
        "",
        "El archivo env.php no existe en este entorno.",
        "",
        "  → Si eres desarrollador: ejecuta .scripts/setup_php_portable.ps1",
        "  → Si eres el administrador del host: sube el env.php de producción",
    ]));
}

require_once $_envFile;

// Validar que env.php tiene todo lo necesario
$_required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($_required as $_const) {
    if (!defined($_const)) {
        die("⚠️ env.php incompleto: falta definir la constante '{$_const}'.");
    }
}

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

// Verifica si se puede conectar, caso contrario manda error
try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    // Sincronizar zona horaria de MySQL con Nicaragua
    $conn->exec("SET time_zone = '-06:00'");
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());

    $esLocal = defined('APP_ENV') && APP_ENV === 'local';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($esLocal || str_contains($host, 'localhost') || str_contains($host, '.local')) {
        die("❌ Error de conexión LOCAL: " . $e->getMessage());
    } else {
        die("Error al conectar con la base de datos. Por favor intente más tarde.");
    }
}

// Función para ejecutar consultas seguras
function ejecutarConsulta($sql, $params = [])
{
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage() . " - SQL: " . $sql);
        return false;
    }
}
?>