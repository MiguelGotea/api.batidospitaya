<?php
/**
 * FIX DB ENUM - Agrega el estado 'inicializando' a la tabla wsp_sesion_vps_
 * URL: https://api.batidospitaya.com/api/wsp/fix_db_enum.php
 */

require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: text/plain');

try {
    echo "--- Iniciando actualización de base de datos ---\n\n";

    // SQL para modificar el ENUM
    $sql = "ALTER TABLE wsp_sesion_vps_ 
            MODIFY COLUMN estado ENUM('desconectado', 'qr_pendiente', 'conectado', 'inicializando', 'error') 
            DEFAULT 'desconectado'";

    $conn->exec($sql);

    echo "✅ ÉXITO: Columna 'estado' actualizada correctamente.\n";
    echo "Estados permitidos ahora: desconectado, qr_pendiente, conectado, inicializando, error\n\n";

    // Verificar el cambio
    $stmt = $conn->query("DESCRIBE wsp_sesion_vps_ estado");
    $col = $stmt->fetch();
    echo "Configuración actual:\n";
    print_r($col);

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- Fin del proceso ---";
?>