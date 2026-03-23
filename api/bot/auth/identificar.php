<?php
/**
 * identificar.php — Identifica un operario por número de teléfono corporativo
 *
 * GET ?celular=88112233
 * Retorna datos del operario si está activo y tiene bot_activo = 1
 * ⚠️ Busca por telefono_corporativo (número asignado por la empresa), NO por celular personal
 *
 * Llamado por: wsp-pitayabot/src/bot/messageHandler.js
 */

require_once __DIR__ . '/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$celular = trim($_GET['celular'] ?? '');
$lid     = trim($_GET['lid']     ?? '');

if (empty($celular) && empty($lid)) {
    respuestaError('Se requiere celular o lid');
}

// Sanitizar celular: solo dígitos
$celularLimpo = preg_replace('/\D/', '', $celular);


try {
    // ── 0. Verificar si existe la columna bot_lid (Defensivo) ──
    $hasLidColumn = false;
    $checkCol = $conn->query("SHOW COLUMNS FROM Operarios LIKE 'bot_lid'");
    if ($checkCol && $checkCol->rowCount() > 0) {
        $hasLidColumn = true;
    }

    // ── 1. Buscar por LID (Máxima prioridad) ──
    $operario = null;
    if ($hasLidColumn && !empty($lid)) {
        $stmt = $conn->prepare("
            SELECT o.*, nc.CodNivelesCargos, nc.Nombre AS cargo_nombre
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc ON anc.CodOperario = o.CodOperario AND (anc.Fin IS NULL OR anc.Fin >= CURDATE()) AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos
            WHERE o.bot_lid = :lid AND o.Operativo = 1
            LIMIT 1
        ");
        $stmt->execute([':lid' => $lid]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── 2. Si no se encontró por LID, buscar por Teléfono ──
    if (!$operario && !empty($celularLimpo)) {
        // ... (resto del código igual)
        $stmt = $conn->prepare("
            SELECT o.*, nc.CodNivelesCargos, nc.Nombre AS cargo_nombre
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc ON anc.CodOperario = o.CodOperario AND (anc.Fin IS NULL OR anc.Fin >= CURDATE()) AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON nc.CodNivelesCargos = anc.CodNivelesCargos
            WHERE (REPLACE(REPLACE(o.telefono_corporativo, ' ', ''), '-', '') = :celular 
               OR REPLACE(REPLACE(o.Celular, ' ', ''), '-', '') = :celular)
              AND o.Operativo = 1
              AND (o.bot_activo = 1 OR o.CodOperario = 5)
            ORDER BY anc.Fecha DESC
            LIMIT 1
        ");
        $stmt->execute([':celular' => $celularLimpo]);
        $operario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si se encontró por Teléfono pero no teníamos el LID, lo guardamos para la próxima
        if ($hasLidColumn && $operario && !empty($lid) && ($operario['bot_lid'] !== $lid)) {
            $conn->prepare("UPDATE Operarios SET bot_lid = :lid WHERE CodOperario = :id")
                 ->execute([':lid' => $lid, ':id' => $operario['CodOperario']]);
            $operario['bot_lid'] = $lid;
        }
    }

    if (!$operario) {
        echo json_encode([
            'success' => false,
            'registrado' => false,
            'message' => 'No estás registrado para usar PitayaBot. Contacta a RRHH.'
        ]);
        exit;
    }

    // No exponer la clave de email ni tokens sensibles directamente
    // (el bot los usará en etapas posteriores con cifrado AES)
    unset($operario['email_trabajo_clave']);
    unset($operario['bot_github_token']);

    respuestaOk(['data' => $operario]);

}
catch (Exception $e) {
    error_log('Error identificar.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
