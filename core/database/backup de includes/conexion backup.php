<?php
date_default_timezone_set('America/Managua');

$servername = "localhost";
$username = "u839374897_erp";
$password = "ERpPitHay2025$";
$dbname = "u839374897_erp";

// verifica si se puede conectar  al abse de datos local, caso contrairo manda error
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
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        die("Error al conectar con la base de datos. Por favor intente más tarde.");
    }
}

// Función para ejecutar consultas seguras
function ejecutarConsulta($sql, $params = []) {
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