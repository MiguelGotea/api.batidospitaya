<?php
require 'core/database/conexion.php';
try {
    $stmt = $conn->query("DESCRIBE conversations");
    echo "conversations columns:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    $stmt2 = $conn->query("DESCRIBE messages");
    echo "\nmessages columns:\n";
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

    $stmt3 = $conn->query("DESCRIBE wsp_sesion_vps_");
    echo "\nwsp_sesion_vps_ columns:\n";
    print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
