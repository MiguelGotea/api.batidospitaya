<?php
/**
 * check_all.php — Orquestador de alertas WhatsApp para PitayaBot
 *
 * PitayaBot (DigitalOcean) llama a este endpoint cada 1 minuto.
 * Agrega todas las alertas activas de todos los tipos y las retorna
 * en un solo response para que el bot las procese y envíe.
 *
 * GET /api/alertas/check_all.php
 * Header requerido: X-WSP-Token: <token>
 *
 * Response:
 * {
 *   "success": true,
 *   "alertas": [
 *     {
 *       "tipo": "conexion_pc",
 *       "key_unica": "S01-ADMIN-PC-2026-04-22 18:00:05",
 *       "mensaje": "🔴 *Alerta: PC Sin Conexión*\n...",
 *       "destinatarios": ["88001234", "88005678"]
 *     }
 *   ],
 *   "total": 1,
 *   "hora_api": "2026-04-22 20:07:00"
 * }
 */

require_once __DIR__ . '/../bot/auth/auth_bot.php';
require_once __DIR__ . '/../../core/database/conexion.php';

verificarTokenBot();

$todasLasAlertas = [];

// ── Alerta 1: PC sin conexión ≥ 60 min ───────────────────────────────────
try {
    $resultado = require __DIR__ . '/alerta_conexion_pc.php';
    if (!empty($resultado['alertas'])) {
        $todasLasAlertas = array_merge($todasLasAlertas, $resultado['alertas']);
    }
} catch (Throwable $e) {
    error_log('[check_all] alerta_conexion_pc falló: ' . $e->getMessage());
}

// ── Alerta 2: Anulación web pendiente del día ─────────────────────────────
try {
    $resultado = require __DIR__ . '/alerta_anulacion_web.php';
    if (!empty($resultado['alertas'])) {
        $todasLasAlertas = array_merge($todasLasAlertas, $resultado['alertas']);
    }
} catch (Throwable $e) {
    error_log('[check_all] alerta_anulacion_web falló: ' . $e->getMessage());
}

// ── Alerta 3: IA auto-aprobó una anulación ───────────────────────────────
try {
    $resultado = require __DIR__ . '/alerta_ia_aprobacion.php';
    if (!empty($resultado['alertas'])) {
        $todasLasAlertas = array_merge($todasLasAlertas, $resultado['alertas']);
    }
} catch (Throwable $e) {
    error_log('[check_all] alerta_ia_aprobacion falló: ' . $e->getMessage());
}

// ── Futuras alertas: agregar require aquí ─────────────────────────────────
// try {
//     $resultado = require __DIR__ . '/alerta_nombre_nueva.php';
//     if (!empty($resultado['alertas'])) {
//         $todasLasAlertas = array_merge($todasLasAlertas, $resultado['alertas']);
//     }
// } catch (Throwable $e) {
//     error_log('[check_all] alerta_nombre_nueva falló: ' . $e->getMessage());
// }

respuestaOk([
    'alertas'  => $todasLasAlertas,
    'total'    => count($todasLasAlertas),
    'hora_api' => date('Y-m-d H:i:s'),
]);
