<?php
/**
 * marcar_enviado.php — Registra una alerta como enviada en alertas_wsp_estado
 *
 * Llamado por PitayaBot DESPUÉS de confirmar que el mensaje WhatsApp fue
 * entregado exitosamente al menos a un destinatario.
 * Solo entonces se persiste el registro, garantizando que alertas no
 * entregadas sean reintentadas en el siguiente ciclo (1 minuto).
 *
 * POST /api/alertas/marcar_enviado.php
 * Header requerido: X-WSP-Token: <token>
 * Body JSON: { "tipo_alerta": "...", "key_unica": "...", "datos_json": {...} }
 */

require_once __DIR__ . '/../bot/auth/auth_bot.php';
require_once __DIR__ . '/../../core/database/conexion.php';

verificarTokenBot();

$body = json_decode(file_get_contents('php://input'), true);

$tipo  = trim($body['tipo_alerta'] ?? '');
$key   = trim($body['key_unica']   ?? '');
$datos = $body['datos_json']       ?? '';

if (!$tipo || !$key) {
    respuestaError('tipo_alerta y key_unica son requeridos', 400);
}

// datos_json puede llegar como array o como string JSON
if (is_array($datos)) {
    $datos = json_encode($datos, JSON_UNESCAPED_UNICODE);
}

try {
    $stmt = $conn->prepare("
        INSERT IGNORE INTO alertas_wsp_estado (tipo_alerta, key_unica, datos_json)
        VALUES (:tipo, :key, :datos)
    ");
    $stmt->execute([
        ':tipo'  => $tipo,
        ':key'   => $key,
        ':datos' => $datos ?: '{}',
    ]);

    $registrado = $stmt->rowCount() > 0;

    if (!$registrado) {
        // Ya existía (INSERT IGNORE no hizo nada) — no es un error
        error_log("[marcar_enviado] key ya existía: tipo={$tipo} key={$key}");
    }

    respuestaOk([
        'registrado' => $registrado,
        'tipo'       => $tipo,
        'key'        => $key,
    ]);

} catch (Exception $e) {
    error_log('[marcar_enviado] ' . $e->getMessage());
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
