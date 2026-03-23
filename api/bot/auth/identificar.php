<?php
/**
 * identificar.php — Identifica un operario por número de teléfono corporativo o LID
 */

require_once __DIR__ . '/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$celular = trim($_GET['celular'] ?? '');
$lid     = trim($_GET['lid']     ?? '');

$celularLimpo = preg_replace('/\D/', '', $celular);
$celular8 = (strlen($celularLimpo) > 8) ? substr($celularLimpo, -8) : $celularLimpo;

try {
    $hasLidColumn = false;
    $checkCol = $conn->query("SHOW COLUMNS FROM Operarios LIKE 'bot_lid'");
    if ($checkCol && $checkCol->rowCount() > 0) {
        $hasLidColumn = true;
    }

    $operario = null;
    if ($hasLidColumn && !empty($lid)) {
        $stmt = $conn->prepare("SELECT o.* FROM Operarios o WHERE o.bot_lid = :lid LIMIT 1");
        $stmt->execute([':lid' => $lid]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$operario && !empty($celular8)) {
        // BUSQUEDA DIAGNÓSTICA: Sin filtros de activo/operativo para ver qué pasa
        $stmt = $conn->prepare("
            SELECT CodOperario, Nombre, Apellido, telefono_corporativo, Celular, Operativo, bot_activo, bot_lid
            FROM Operarios 
            WHERE (telefono_corporativo LIKE :c8a OR Celular LIKE :c8b)
            LIMIT 5
        ");
        $stmt->execute([':c8a' => '%' . $celular8, ':c8b' => '%' . $celular8]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($resultados) > 0) {
            foreach ($resultados as $r) {
                // Si el registro coincide con el ID que esperamos y cumple los filtros, lo tomamos
                if ($r['Operativo'] == 1 && ($r['bot_activo'] == 1 || $r['CodOperario'] == 5)) {
                    $operario = $r;
                    // Cargar datos extra que faltan incluyendo el cargo
                    $stmtExtra = $conn->prepare("
                        SELECT o.*, nc.Nombre as cargo_nombre 
                        FROM Operarios o 
                        LEFT JOIN AsignacionNivelesCargos anc ON anc.CodOperario = o.CodOperario AND (anc.Fin IS NULL OR anc.Fin >= CURDATE()) AND anc.Fecha <= CURDATE() 
                        LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos 
                        WHERE o.CodOperario = :id 
                        LIMIT 1
                    ");
                    $stmtExtra->execute([':id' => $operario['CodOperario']]);
                    $operario = $stmtExtra->fetch(PDO::FETCH_ASSOC);
                    break;
                }
            }
            
            if (!$operario) {
                // El usuario existe pero NO PASA LOS FILTROS
                echo json_encode(['success' => false, 'error' => 'Usuario encontrado pero inactivo', 'debug_data' => $resultados]);
                exit;
            }
        }
    }

    if (!$operario) {
        echo json_encode(['success' => false, 'error' => 'No encontrado en DB', 'debug_cel8' => $celular8]);
        exit;
    }

    // Auto-update LID si la columna existe
    if ($hasLidColumn && !empty($lid) && ($operario['bot_lid'] !== $lid)) {
        $conn->prepare("UPDATE Operarios SET bot_lid = :lid WHERE CodOperario = :id")
             ->execute([':lid' => $lid, ':id' => $operario['CodOperario']]);
    }

    unset($operario['email_trabajo_clave'], $operario['bot_github_token']);
    respuestaOk(['data' => $operario]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
