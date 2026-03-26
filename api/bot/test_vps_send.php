<?php
/**
 * test_vps_send.php — Prueba de ENVÍO REAL con Token
 */
require_once __DIR__ . '/auth/auth_bot.php';
header('Content-Type: text/plain; charset=utf-8');

$numero  = '57416019'; // Tu número de prueba
$mensaje = "⚡ Prueba de conexión PitayaBot — " . date('H:i:s');

echo "🚀 Intentando enviar mensaje real a $numero...\n";

// Llamamos al helper que ya tiene la IP y el Token configurado
$resultado = enviarMensajeWsp($numero, $mensaje);

if ($resultado) {
    echo "✅ ÉXITO: El VPS aceptó el mensaje y debería estar llegando a WhatsApp.\n";
} else {
    echo "❌ FALLO: El envío no fue exitoso.\n";
    echo "Revisa el archivo de error_log en Hostinger para ver el detalle del fallo cURL o HTTP.";
}
