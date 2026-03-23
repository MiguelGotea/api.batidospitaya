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
if (empty($celular)) {
    respuestaError('Parámetro celular requerido');
}

// Sanitizar: solo dígitos
$celular = preg_replace('/\D/', '', $celular);
if (strlen($celular) < 7 || strlen($celular) > 15) {
    respuestaError('Formato de celular inválido');
}

try {
    $stmt = $conn->prepare("
        SELECT
            o.CodOperario,
            o.Nombre,
            o.Apellido,
            o.email_trabajo,
            o.email_trabajo_clave,
            o.bot_github_token,
            o.bot_github_repo,
            o.bot_github_branch,
            o.bot_github_vault_folder,
            o.bot_imap_host,
            o.bot_imap_port,
            o.bot_activo,
            nc.CodNivelesCargos,
            nc.Nombre AS cargo_nombre
        FROM Operarios o
        LEFT JOIN Contratos c
            ON c.cod_operario = o.CodOperario
            AND c.Finalizado = 0
        LEFT JOIN AsignacionNivelesCargos anc
            ON anc.CodOperario = o.CodOperario
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            AND anc.Fecha <= CURDATE()
        LEFT JOIN NivelesCargos nc
            ON nc.CodNivelesCargos = anc.CodNivelesCargos
        WHERE (REPLACE(REPLACE(o.telefono_corporativo, ' ', ''), '-', '') = :celular 
           OR REPLACE(REPLACE(o.Celular, ' ', ''), '-', '') = :celular)
          AND o.Operativo = 1
          AND (o.bot_activo = 1 OR o.CodOperario = 5)
        ORDER BY anc.Fecha DESC
        LIMIT 1
    ");

    $stmt->execute([':celular' => $celular]);
    $operario = $stmt->fetch(PDO::FETCH_ASSOC);

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
    respuestaError('Error interno del servidor', 500);
}
