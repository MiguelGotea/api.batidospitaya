<?php
/**
 * obtener.php — Obtiene el estado de confirmación pendiente de un celular
 *
 * GET ?celular=88112233
 * Retorna el estado pendiente si no ha expirado, null en caso contrario.
 * También hace limpieza automática de estados expirados.
 *
 * Llamado por: wsp-pitayabot/src/bot/confirmManager.js
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

$celular = trim($_GET['celular'] ?? '');
$celular = preg_replace('/\D/', '', $celular);

if (empty($celular)) {
    respuestaError('Parámetro celular requerido');
}

try {
    // Limpiar estados expirados (housekeeping oportunístico)
    $conn->prepare("DELETE FROM bot_estado_confirmacion WHERE expira_en < NOW()")
         ->execute();

    // Buscar estado activo para este celular
    $stmt = $conn->prepare("
        SELECT
            id, cod_operario, celular, intent, payload,
            frase_resumen, paso_actual, datos_parciales,
            creado_en, expira_en
        FROM bot_estado_confirmacion
        WHERE celular = :celular
          AND expira_en > NOW()
        ORDER BY creado_en DESC
        LIMIT 1
    ");
    $stmt->execute([':celular' => $celular]);
    $estado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estado) {
        echo json_encode(['success' => true, 'data' => null]);
        exit;
    }

    // Deserializar JSON almacenados
    $estado['payload']         = json_decode($estado['payload'],         true) ?? [];
    $estado['datos_parciales'] = json_decode($estado['datos_parciales'], true);

    respuestaOk(['data' => $estado]);

} catch (Exception $e) {
    error_log('Error obtener confirmacion: ' . $e->getMessage());
    respuestaError('Error obteniendo estado de confirmación', 500);
}
