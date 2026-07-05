<?php
/**
 * aprobar.php — Aprueba una reunión: borra audio en VPS y cierra el registro
 * POST /api/resumen_reuniones_ia/aprobar.php
 * Header: X-Resumen-Token (ERP)
 *
 * Body JSON:
 *   reunion_id               int   ID de la reunión a aprobar
 *   cod_operario_solicitante int   CodOperario del usuario que aprueba (debe ser el creador)
 *
 * Flujo:
 *   1. Verifica que la reunión existe y está en estado 'completada'
 *   2. Verifica que el solicitante es el creador (creado_por == cod_operario_solicitante)
 *   3. Llama al VPS: DELETE https://reuniones.batidospitaya.com/api/audio/{reunion_id}
 *      con header X-Resumen-Token: RESUMEN_TOKEN_ERP
 *   4. Solo si el VPS responde 200: actualiza estado='cerrada', audio_borrado=1
 *   5. Si el VPS falla: retorna error y NO modifica la BD
 */

require_once __DIR__ . '/auth.php';

verificarTokenERP();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reunionErr('Método no permitido', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

if (!$body) {
    reunionErr('Body JSON inválido o vacío', 400);
}

$reunionId             = intval($body['reunion_id']               ?? 0);
$codOperarioSolicitante = intval($body['cod_operario_solicitante'] ?? 0);

if ($reunionId <= 0) {
    reunionErr('El campo "reunion_id" es requerido y debe ser un ID válido', 422);
}
if ($codOperarioSolicitante <= 0) {
    reunionErr('El campo "cod_operario_solicitante" es requerido', 422);
}

try {
    // Obtener reunión
    $stmt = $conn->prepare("
        SELECT id, titulo, estado, creado_por, ruta_audio, audio_borrado
        FROM resumen_reuniones_ia
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$reunionId]);
    $reunion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        reunionErr("Reunión #{$reunionId} no encontrada", 404);
    }

    // El aprobador debe ser el creador
    if ((int) $reunion['creado_por'] !== $codOperarioSolicitante) {
        reunionErr('Solo el creador de la reunión puede aprobarla', 403);
    }

    // Solo se puede aprobar si está completada
    if ($reunion['estado'] !== 'completada') {
        reunionErr(
            "Solo se pueden aprobar reuniones en estado 'completada'. Estado actual: '{$reunion['estado']}'.",
            422
        );
    }

    // Verificar si el audio ya fue borrado (aprobación duplicada)
    if ($reunion['audio_borrado']) {
        reunionErr('Esta reunión ya fue aprobada y el audio ya fue eliminado.', 409);
    }

    // ── Llamar al VPS para borrar el audio físico ──────────────────────────────
    $vpsDeleteUrl = "https://reuniones.batidospitaya.com/api/audio/{$reunionId}";

    $ch = curl_init($vpsDeleteUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_HTTPHEADER     => [
            'X-Resumen-Token: ' . RESUMEN_TOKEN_ERP,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $vpsResponse = curl_exec($ch);
    $vpsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError   = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);

    if ($curlError) {
        error_log("[resumen_reuniones_ia] aprobar.php: cURL error al llamar al VPS: {$curlError}");
        reunionErr("Error de conectividad al comunicarse con el VPS: {$curlError}", 502);
    }

    if ($vpsHttpCode !== 200) {
        $vpsData = json_decode($vpsResponse, true);
        $vpsMsg  = $vpsData['error'] ?? "HTTP {$vpsHttpCode}";
        error_log("[resumen_reuniones_ia] aprobar.php: VPS respondió {$vpsHttpCode}: {$vpsResponse}");
        reunionErr("El VPS rechazó el borrado del audio: {$vpsMsg}", 502);
    }

    // ── VPS confirmó el borrado: actualizar BD ─────────────────────────────────
    $update = $conn->prepare("
        UPDATE resumen_reuniones_ia
        SET
            estado         = 'cerrada',
            audio_borrado  = 1,
            ruta_audio     = NULL,
            fecha_aprobada = NOW()
        WHERE id = ?
    ");
    $update->execute([$reunionId]);

    reunionOk([
        'reunion_id' => $reunionId,
        'estado'     => 'cerrada',
        'mensaje'    => 'Reunión aprobada. El audio ha sido eliminado del servidor.',
    ]);

} catch (Exception $e) {
    error_log('[resumen_reuniones_ia] aprobar.php error: ' . $e->getMessage());
    reunionErr('Error interno al procesar la aprobación', 500);
}
