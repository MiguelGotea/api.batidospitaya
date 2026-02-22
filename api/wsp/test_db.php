<?php
/**
 * TEST DB PDO - Verificar conexión a la base de datos usando PDO
 * URL: https://api.batidospitaya.com/api/wsp/test_db.php
 */

// Cargar la conexión PDO
require_once __DIR__ . '/../../core/database/conexion.php';


header('Content-Type: text/html; charset=utf-8');

// Función para mostrar resultados bonitos
function mostrarResultado($titulo, $exito, $mensaje = '')
{
    $color = $exito ? '#d4edda' : '#f8d7da';
    $icono = $exito ? '✅' : '❌';
    echo "<tr style='background: $color'>";
    echo "<td>$icono $titulo</td>";
    echo "<td>" . ($exito ? 'ÉXITO' : 'ERROR') . "</td>";
    echo "<td><small>$mensaje</small></td>";
    echo "</tr>";
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Test DB PDO - Conexión a Base de Datos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        h1 {
            color: #0E544C;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        th {
            background: #0E544C;
            color: white;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
            max-height: 300px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <h1>🔍 Test de Conexión PDO a Base de Datos</h1>
    <p><strong>Fecha:</strong> <?= date('Y-m-d H:i:s') ?></p>

    <div class="info-box">
        <strong>ℹ️ Información:</strong><br>
        - Archivo conexion.php: <?= __DIR__ . '/../../core/database/conexion.php' ?><br>
        - Método de conexión: PDO (PHP Data Objects)
    </div>

    <table>
        <thead>
            <tr>
                <th>Prueba</th>
                <th>Estado</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Prueba 1: Verificar que la variable $conn existe
            if (isset($conn)) {
                mostrarResultado('Variable $conn existe', true, '$conn está definida');
            } else {
                mostrarResultado('Variable $conn existe', false, '$conn NO está definida');
            }

            // Prueba 2: Verificar tipo de objeto
            if (isset($conn)) {
                $tipo = gettype($conn);
                $clase = is_object($conn) ? get_class($conn) : 'N/A';
                $esPDO = ($conn instanceof PDO);

                if ($esPDO) {
                    mostrarResultado('Tipo de conexión', true, "Es PDO (Clase: $clase)");
                } else {
                    mostrarResultado('Tipo de conexión', false, "No es PDO - Tipo: $tipo, Clase: $clase");
                }
            }

            // Prueba 3: Probar query simple
            try {
                if (isset($conn) && $conn instanceof PDO) {
                    $stmt = $conn->query("SELECT 1 as prueba");
                    $resultado = $stmt->fetch();

                    if ($resultado && $resultado['prueba'] == 1) {
                        mostrarResultado('Query simple', true, 'SELECT 1 ejecutado correctamente');
                    } else {
                        mostrarResultado('Query simple', false, 'No se pudo ejecutar SELECT 1');
                    }
                } else {
                    mostrarResultado('Query simple', false, 'No se puede ejecutar: $conn no es PDO');
                }
            } catch (PDOException $e) {
                mostrarResultado('Query simple', false, 'Error: ' . $e->getMessage());
            }

            // Prueba 4: Listar tablas
            try {
                if (isset($conn) && $conn instanceof PDO) {
                    $stmt = $conn->query("SHOW TABLES");
                    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $listaTablas = implode(', ', $tablas);
                    $cantidad = count($tablas);

                    mostrarResultado('Listar tablas', true, "$cantidad tablas encontradas: $listaTablas");
                }
            } catch (PDOException $e) {
                mostrarResultado('Listar tablas', false, 'Error: ' . $e->getMessage());
            }

            // Prueba 5: Buscar tabla wsp_sesion_vps_
            try {
                if (isset($conn) && $conn instanceof PDO) {
                    $stmt = $conn->query("SHOW TABLES LIKE 'wsp_sesion_vps_'");
                    $existe = $stmt->rowCount() > 0;

                    if ($existe) {
                        mostrarResultado('Tabla wsp_sesion_vps_', true, 'La tabla existe ✅');
                    } else {
                        mostrarResultado('Tabla wsp_sesion_vps_', false, 'La tabla NO existe ⚠️');
                    }
                }
            } catch (PDOException $e) {
                mostrarResultado('Tabla wsp_sesion_vps_', false, 'Error al verificar: ' . $e->getMessage());
            }

            // Prueba 6: Si existe la tabla, mostrar contenido
            try {
                if (isset($conn) && $conn instanceof PDO) {
                    $stmt = $conn->query("SHOW TABLES LIKE 'wsp_sesion_vps_'");
                    if ($stmt->rowCount() > 0) {
                        $datos = $conn->query("SELECT * FROM wsp_sesion_vps_ WHERE id = 1")->fetch();

                        if ($datos) {
                            mostrarResultado('Contenido de la tabla', true, 'Registro encontrado');
                            echo "<tr><td colspan='3'><pre>" . htmlspecialchars(print_r($datos, true)) . "</pre></td></tr>";
                        } else {
                            mostrarResultado('Contenido de la tabla', false, 'No hay registros en la tabla');
                        }
                    }
                }
            } catch (PDOException $e) {
                mostrarResultado('Contenido de la tabla', false, 'Error: ' . $e->getMessage());
            }

            // Prueba 7: Información de la conexión
            try {
                if (isset($conn) && $conn instanceof PDO) {
                    $info = [
                        'Driver' => $conn->getAttribute(PDO::ATTR_DRIVER_NAME),
                        'Server Version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
                        'Client Version' => $conn->getAttribute(PDO::ATTR_CLIENT_VERSION),
                        'Connection Status' => $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS)
                    ];

                    mostrarResultado('Información PDO', true, 'Detalles abajo');
                    echo "<tr><td colspan='3'><pre>" . htmlspecialchars(print_r($info, true)) . "</pre></td></tr>";
                }
            } catch (Exception $e) {
                mostrarResultado('Información PDO', false, 'No se pudo obtener información');
            }
            ?>
        </tbody>
    </table>

    <?php
    // Prueba 8: Crear tabla si no existe (opción con PDO)
    if (isset($conn) && $conn instanceof PDO) {
        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'wsp_sesion_vps_'");
            if ($stmt->rowCount() == 0) {
                echo "<div style='margin-top:20px; background:#fff3cd; border-left:4px solid #ffc107; padding:15px;'>";
                echo "<strong>⚠️ La tabla 'wsp_sesion_vps_' no existe</strong><br>";
                echo "<p>Puedes crearla ejecutando el siguiente SQL:</p>";
                echo "<pre style='background:#333; color:#fff; padding:10px;'>";
                echo "CREATE TABLE IF NOT EXISTS `wsp_sesion_vps_` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `estado` varchar(50) NOT NULL DEFAULT 'desconectado',
    `qr_base64` text,
    `ultimo_ping` datetime DEFAULT NULL,
    `ip_vps` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wsp_sesion_vps_` (`id`, `estado`) VALUES (1, 'desconectado')
ON DUPLICATE KEY UPDATE `id` = `id`;";
                echo "</pre>";
                echo "<form method='POST' style='margin-top:10px;'>";
                echo "<button type='submit' name='crear_tabla' style='background:#28a745; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;'>Crear Tabla Automáticamente</button>";
                echo "</form>";
                echo "</div>";

                // Procesar creación de tabla
                if (isset($_POST['crear_tabla'])) {
                    try {
                        $sql = "
                        CREATE TABLE IF NOT EXISTS `wsp_sesion_vps_` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `estado` varchar(50) NOT NULL DEFAULT 'desconectado',
                            `qr_base64` text,
                            `ultimo_ping` datetime DEFAULT NULL,
                            `ip_vps` varchar(45) DEFAULT NULL,
                            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                        
                        INSERT INTO `wsp_sesion_vps_` (`id`, `estado`) VALUES (1, 'desconectado')
                        ON DUPLICATE KEY UPDATE `id` = `id`;
                        ";

                        $conn->exec($sql);
                        echo "<div style='background:#d4edda; border-left:4px solid #28a745; padding:15px; margin-top:10px;'>";
                        echo "<strong>✅ Tabla creada exitosamente!</strong>";
                        echo "</div>";

                        // Recargar la página después de 2 segundos
                        echo "<script>setTimeout(() => location.reload(), 2000);</script>";
                    } catch (PDOException $e) {
                        echo "<div style='background:#f8d7da; border-left:4px solid #dc3545; padding:15px; margin-top:10px;'>";
                        echo "<strong>❌ Error al crear tabla:</strong> " . $e->getMessage();
                        echo "</div>";
                    }
                }
            }
        } catch (Exception $e) {
            // Silenciar error
        }
    }
    ?>

    <div style="margin-top: 20px; padding: 15px; background: #e2e3e5; border-radius: 5px;">
        <strong>📋 Resumen:</strong><br>
        - ¿Conexión PDO funcionando?
        <?= (isset($conn) && $conn instanceof PDO) ? '✅ SÍ' : '❌ NO' ?><br>
        - ¿Tabla wsp_sesion_vps_ existe?
        <?php
        try {
            if (isset($conn) && $conn instanceof PDO) {
                $stmt = $conn->query("SHOW TABLES LIKE 'wsp_sesion_vps_'");
                echo ($stmt->rowCount() > 0) ? '✅ SÍ' : '❌ NO';
            } else {
                echo '❓ NO VERIFICADO';
            }
        } catch (Exception $e) {
            echo '❌ ERROR';
        }
        ?>
    </div>

    <p style="margin-top:20px; color:#888; font-size:12px;">
        ⚠️ Este archivo es solo para diagnóstico - Eliminar después de las pruebas
    </p>
</body>

</html>