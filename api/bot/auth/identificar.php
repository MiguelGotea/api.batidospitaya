<?php
/**
 * identificar.php — Identifica un operario por número de teléfono corporativo o LID
 *
 * GET ?celular=88112233&lid=...
 * Retorna datos del operario si está activo y tiene permiso 'pitayabot/usar' por cargo.
 */

require_once __DIR__ . '/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';
require_once __DIR__ . '/../../../core/permissions/permissions.php';

verificarTokenBot();

$celularInput = trim($_GET['celular'] ?? '');
$lid          = trim($_GET['lid']     ?? '');

if (empty($celularInput) && empty($lid)) {
    respuestaError('Se requiere celular o lid');
}

// Sanitizar y normalizar celular: conservar solo los últimos 8 dígitos
$numLimpio = preg_replace('/\D/', '', $celularInput);
$celular8  = (strlen($numLimpio) > 8) ? substr($numLimpio, -8) : $numLimpio;

try {
    // ── 0. Verificar si existe la columna bot_lid (Defensivo) ──
    $hasLidColumn = false;
    $checkCol = $conn->query("SHOW COLUMNS FROM Operarios LIKE 'bot_lid'");
    if ($checkCol && $checkCol->rowCount() > 0) {
        $hasLidColumn = true;
    }

    $operario = null;

    // ── 1. Identificación por LID (Instantánea) ──
    if ($hasLidColumn && !empty($lid)) {
        $stmt = $conn->prepare("
            SELECT o.*,
                   anc.CodNivelesCargos,
                   nc.Nombre AS cargo_nombre
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc
                   ON anc.CodOperario = o.CodOperario
                  AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                  AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos
            WHERE o.bot_lid = :lid
            LIMIT 1
        ");
        $stmt->execute([':lid' => $lid]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── 2. Identificación por Teléfono (Fallback + auto-sync LID) ──
    if (!$operario && !empty($celular8)) {
        $stmt = $conn->prepare("
            SELECT o.*,
                   anc.CodNivelesCargos,
                   nc.Nombre AS cargo_nombre
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc
                   ON anc.CodOperario = o.CodOperario
                  AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                  AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos
            WHERE (
                    REPLACE(REPLACE(REPLACE(o.telefono_corporativo, ' ', ''), '-', ''), '+505', '') LIKE :c8a
                 OR REPLACE(REPLACE(REPLACE(o.Celular, ' ', ''), '-', ''), '+505', '') LIKE :c8b
            )
            ORDER BY anc.Fecha DESC
            LIMIT 1
        ");
        $stmt->execute([':c8a' => '%' . $celular8, ':c8b' => '%' . $celular8]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Guardar LID para identificación futura más rápida
        if ($hasLidColumn && $operario && !empty($lid) && ($operario['bot_lid'] !== $lid)) {
            $conn->prepare("UPDATE Operarios SET bot_lid = :lid WHERE CodOperario = :id")
                 ->execute([':lid' => $lid, ':id' => $operario['CodOperario']]);
            $operario['bot_lid'] = $lid;
        }
    }

    // ── 3. Usuario no encontrado en el sistema ──
    if (!$operario) {
        echo json_encode(['success' => false, 'registrado' => false, 'message' => 'No encontrado en el sistema']);
        exit;
    }

    // ── 4. Verificar permiso por cargo (reemplaza bot_activo) ──
    $codCargo = $operario['CodNivelesCargos'] ?? 0;
    if (!$codCargo || !tienePermiso('pitayabot', 'usar', $codCargo)) {
        echo json_encode([
            'success'    => false,
            'registrado' => false,
            'message'    => 'No tienes permiso para usar PitayaBot. Contacta a TI o RRHH.'
        ]);
        exit;
    }

    // No exponer tokens ni claves sensibles
    unset($operario['email_trabajo_clave'], $operario['bot_github_token']);

    respuestaOk(['data' => $operario]);

} catch (Exception $e) {
    error_log('Error identificar.php: ' . $e->getMessage());
    respuestaError('Error interno del servidor', 500);
}
