<?php
/**
 * obtener_config.php — Retorna la config GitHub del vault de Obsidian del operario
 *
 * GET ?cod_operario=N
 * Retorna: { github_token_enc, github_repo, github_branch, github_vault_folder }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$codOperario = (int)($_GET['cod_operario'] ?? 0);
if (!$codOperario) {
    respuestaError('Se requiere cod_operario');
}

try {
    $stmt = $conn->prepare("
        SELECT
            bot_github_token        AS github_token_enc,
            bot_github_repo         AS github_repo,
            bot_github_branch       AS github_branch,
            bot_github_vault_folder AS github_vault_folder
        FROM Operarios
        WHERE CodOperario = :cod
    ");
    $stmt->execute([':cod' => $codOperario]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || empty($config['github_token_enc'])) {
        respuestaError('El operario no tiene configurado el vault de Obsidian', 404);
    }

    respuestaOk(['data' => $config]);

} catch (Exception $e) {
    error_log('Error notas/obtener_config.php: ' . $e->getMessage());
    respuestaError('Error obteniendo configuracion', 500);
}
