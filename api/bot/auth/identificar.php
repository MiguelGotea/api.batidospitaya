<?php
/**
 * identificar.php — Identifica un operario por número de teléfono corporativo o LID
 *
 * GET ?celular=88112233&lid=...
 * Retorna datos del operario si está activo y tiene bot_activo = 1
 */

require_once __DIR__ . '/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$celularInput = trim($_GET['celular'] ?? '');
$lid          = trim($_GET['lid']     ?? '');

if (empty($celularInput) && empty($lid)) {
    respuestaError('Se requiere celular o lid');
}

// Sanitizar y normalizar celular: dejar solo los últimos 8 dígitos si tiene prefijo
$numLimpio = preg_replace('/\D/', '', $celularInput);
$celular8  = (strlen($numLimpio) > 8) ? substr($numLimpio, -8) : $numLimpio;

try {
    // ── 0. Verificar si existe la identidad técnica (bot_lid) ──
    $hasLidColumn = false;
    $checkCol = $conn->query("SHOW COLUMNS FROM Operarios LIKE 'bot_lid'");
    if ($checkCol && $checkCol->rowCount() > 0) {
        $hasLidColumn = true;
    }

    $operario = null;

    // ── 1. Identificación por LID (Instantánea y robusta) ──
    if ($hasLidColumn && !empty($lid)) {
        $stmt = $conn->prepare("
            SELECT o.*, nc.Nombre AS cargo_nombre
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc ON anc.CodOperario = o.CodOperario AND (anc.Fin IS NULL OR anc.Fin >= CURDATE()) AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos
            WHERE o.bot_lid = :lid AND o.Operativo = 1 AND o.bot_activo = 1
            LIMIT 1
        ");
        $stmt->execute([':lid' => $lid]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── 2. Identificación por Teléfono (Fallback con auto-sincronización de LID) ──
    if (!$operario && !empty($celular8)) {
        $stmt = $conn->prepare("
            SELECT o.*, nc.Nombre AS cargo_nombre
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc ON anc.CodOperario = o.CodOperario AND (anc.Fin IS NULL OR anc.Fin >= CURDATE()) AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos
            WHERE (
                    REPLACE(REPLACE(REPLACE(o.telefono_corporativo, ' ', ''), '-', ''), '+505', '') LIKE :c8a
                 OR REPLACE(REPLACE(REPLACE(o.Celular, ' ', ''), '-', ''), '+505', '') LIKE :c8b
            )
              AND o.Operativo = 1
              AND o.bot_activo = 1
            ORDER BY anc.Fecha DESC
            LIMIT 1
        ");
        $stmt->execute([':c8a' => '%' . $celular8, ':c8b' => '%' . $celular8]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si se encontró por Teléfono y tenemos un LID nuevo, lo guardamos para el futuro
        if ($hasLidColumn && $operario && !empty($lid) && ($operario['bot_lid'] !== $lid)) {
            $conn->prepare("UPDATE Operarios SET bot_lid = :lid WHERE CodOperario = :id")
                 ->execute([':lid' => $lid, ':id' => $operario['CodOperario']]);
            $operario['bot_lid'] = $lid;
        }
    }

    if (!$operario) {
        echo json_encode(['success' => false, 'registrado' => false, 'message' => 'No registrado o acceso inactivo']);
        exit;
    }

    // No exponer tokens ni claves sensibles
    unset($operario['email_trabajo_clave'], $operario['bot_github_token']);

    respuestaOk(['data' => $operario]);

} catch (Exception $e) {
    error_log('Error identificar.php: ' . $e->getMessage());
    respuestaError('Error interno del servidor', 500);
}
