<?php
/**
 * core/wsp/whatsapp_ping_directo.php
 * Archivo CORE — Prueba de envío directo a cualquier instancia WhatsApp del VPS
 *
 * POST — body: { instancia, numero, mensaje }
 *
 * Referenciado desde:
 *   modulos/sistemas/js/crm_bot.js
 *   modulos/marketing/js/campanas_wsp.js
 *   modulos/rh/js/planilla_wsp.js
 *   modulos/gerencia/js/gestion_tareas_reuniones.js  (PitayaBot)
 */
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../permissions/permissions.php';

header('Content-Type: application/json; charset=utf-8');
$usuario = obtenerUsuarioActual();
$cargo   = $usuario['CodNivelesCargos'];

// Cualquier módulo WSP con permiso de envío puede usar este endpoint
$puedeUsar =
    tienePermiso('crm_bot',           'responder',         $cargo) ||
    tienePermiso('envio_wsp_planilla', 'nueva_programacion',$cargo) ||
    tienePermiso('campanas_wsp',       'nueva_campana',     $cargo) ||
    tienePermiso('pitayabot',          'prueba_envio',      $cargo);

if (!$puedeUsar) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sin permisos para realizar pruebas de envío']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$instancia = trim($body['instancia'] ?? '');
$numero    = trim($body['numero']    ?? '');
$mensaje   = trim($body['mensaje']   ?? 'Prueba de conexión Pitaya WhatsApp ⚡');

if (!$instancia || !$numero) {
    echo json_encode(['success' => false, 'error' => 'Instancia y número son requeridos']);
    exit;
}

// Limpiar número y asegurar prefijo de país (505 para Nicaragua si son 8 dígitos)
$numeroLimpio = preg_replace('/\D/', '', $numero);
if (strlen($numeroLimpio) === 8) {
    $numeroLimpio = '505' . $numeroLimpio;
}

if (strlen($numeroLimpio) < 8) {
    echo json_encode(['success' => false, 'error' => 'Número de teléfono inválido (mínimo 8 dígitos)']);
    exit;
}

// ── Configuración del VPS ─────────────────────────────────────────
// Token compartido entre todas las instancias (X-WSP-Token en cada .env)
$token  = 'c5b155ba8f6877a2eefca0183ab18e37fe9a6accde340cf5c88af724822cbf50';
$vps_ip = '198.211.97.243';

// Tabla de puertos — agregar nuevas instancias aquí
$puertos = [
    'wsp-clientes'  => 3001,
    'wsp-crmbot'    => 3003,
    'wsp-planilla'  => 3005,
    'wsp-pitayabot' => 3007,
];

$puerto = $puertos[$instancia] ?? null;
if (!$puerto) {
    echo json_encode(['success' => false, 'error' => "Instancia '{$instancia}' no reconocida"]);
    exit;
}

try {
    $destino = $numeroLimpio . '@c.us';
    $payload = json_encode([
        'to'      => $destino,
        'message' => $mensaje,
        'agente'  => trim(($usuario['Nombre'] ?? '') . ' ' . ($usuario['Apellido'] ?? ''))
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nX-WSP-Token: {$token}\r\n",
            'content'       => $payload,
            'timeout'       => 15,
            'ignore_errors' => true
        ]
    ]);

    $url  = "http://{$vps_ip}:{$puerto}/ping";
    $resp = @file_get_contents($url, false, $ctx);

    if ($resp) {
        $respData = json_decode($resp, true);
        if ($respData['success'] ?? false) {
            echo json_encode(['success' => true, 'mensaje' => 'Ping enviado con éxito']);
        } else {
            echo json_encode(['success' => false, 'error' => $respData['error'] ?? 'Error desconocido en el VPS']);
        }
    } else {
        $phpError = error_get_last();
        $errMsg = 'El VPS no respondió. ';
        if ($phpError && str_contains($phpError['message'], 'file_get_contents')) {
            $errMsg .= 'Detalle: ' . $phpError['message'];
        } else {
            $errMsg .= 'Verifica que el bot esté en línea (puerto ' . $puerto . ')';
        }
        echo json_encode(['success' => false, 'error' => $errMsg]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()]);
}
