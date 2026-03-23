<?php
/**
 * guardar.php — Guarda o actualiza el estado de confirmación pendiente
 *
 * POST { cod_operario, celular, intent, payload, frase }
 * Expira en 5 minutos desde la creación.
 *
 * Llamado por: wsp-pitayabot/src/bot/confirmManager.js
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaError('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    respuestaError('Body JSON requerido');
}

$codOperario = isset($input['cod_operario']) ? (int)$input['cod_operario'] : null;
$celular     = trim($input['celular'] ?? '');
$intent      = trim($input['intent']  ?? '');
$payload     = isset($input['payload']) ? $input['payload'] : [];
$frase       = trim($input['frase']   ?? '');

if (empty($celular) || empty($intent)) {
    respuestaError('celular e intent son requeridos');
}

try {
    $expiraEn = (new DateTime('+5 minutes', new DateTimeZone('America/Managua')))->format('Y-m-d H:i:s');

    // INSERT or UPDATE si ya existe uno para este celular
    $stmt = $conn->prepare("
        INSERT INTO bot_estado_confirmacion
            (cod_operario, celular, intent, payload, frase_resumen, paso_actual, datos_parciales, expira_en, creado_en)
        VALUES
            (:cod_operario, :celular, :intent, :payload, :frase, 'esperando_confirmacion', :datos_parciales, :expira_en, NOW())
        ON DUPLICATE KEY UPDATE
            cod_operario   = VALUES(cod_operario),
            intent         = VALUES(intent),
            payload        = VALUES(payload),
            frase_resumen  = VALUES(frase_resumen),
            paso_actual    = 'esperando_confirmacion',
            datos_parciales= VALUES(datos_parciales),
            expira_en      = VALUES(expira_en),
            creado_en      = NOW()
    ");
    $datosParciales = null;
    if (!empty($input['subflow'])) {
        $datosParciales = json_encode(['subflow' => $input['subflow']], JSON_UNESCAPED_UNICODE);
    }

    $stmt->execute([
        ':cod_operario'     => $codOperario,
        ':celular'          => $celular,
        ':intent'           => $intent,
        ':payload'          => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ':frase'            => mb_substr($frase, 0, 1000),
        ':expira_en'        => $expiraEn,
        ':datos_parciales'  => $datosParciales,
    ]);

    respuestaOk(['expira_en' => $expiraEn]);

} catch (Exception $e) {
    error_log('Error guardar confirmacion: ' . $e->getMessage());
    respuestaError('Error guardando estado de confirmación', 500);
}
