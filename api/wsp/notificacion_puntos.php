<?php
/**
 * notificacion_puntos.php — Registro de notificación desde MS Access
 * POST /api/wsp/notificacion_puntos.php
 * Requiere: Header X-WSP-Token
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

// Soporte para GET y POST para facilitar pruebas desde el navegador
$tokenRecibido = $_SERVER['HTTP_X_WSP_TOKEN'] ?? $_GET['token'] ?? '';
if (empty($tokenRecibido) || $tokenRecibido !== WSP_TOKEN_SECRETO) {
    respuestaError('No autorizado — token inválido o ausente', 401);
}

// Recibir datos (POST JSON o GET)
$data = json_decode(file_get_contents('php://input'), true);

$membresia = $data['membresia'] ?? $_GET['membresia'] ?? null;
$puntos_usados = $data['puntos'] ?? $_GET['puntos'] ?? null;
$sucursal = $data['sucursal'] ?? $_GET['sucursal'] ?? null;

if (!$membresia || $puntos_usados === null || !$sucursal) {
    respuestaError('Faltan parámetros requeridos: membresia, puntos, sucursal');
}

try {
    // 1. Buscar cliente por membresía
    $stmt = $conn->prepare("SELECT nombre, celular FROM clientesclub WHERE membresia = :membresia LIMIT 1");
    $stmt->execute([':membresia' => $membresia]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        respuestaError("Cliente con membresía $membresia no encontrado", 404);
    }

    if (empty($cliente['celular'])) {
        respuestaError("El cliente no tiene un número de celular registrado", 400);
    }

    $nombre_cliente = $cliente['nombre'];
    $celular = $cliente['celular'];
    
    // Asegurar formato internacional (asumiendo Nicaragua +505 si no tiene código)
    $celular_limpio = preg_replace('/\D/', '', $celular);
    if (strlen($celular_limpio) === 8) {
        $celular_limpio = '505' . $celular_limpio;
    }

    // 2. Construir el mensaje
    $fecha = date('d/m/Y');
    $hora = date('h:i A');

    $mensaje = "🌵 *Pitaya Club — Uso de puntos*\n\n";
    $mensaje .= "Hola, *{$nombre_cliente}* 👋\n\n";
    $mensaje .= "Te informamos que se han *canjeado puntos* en tu cuenta Pitaya Club. Aquí el resumen de tu transacción:\n\n";
    $mensaje .= "📍 *Sucursal:* {$sucursal}\n";
    $mensaje .= "📅 *Fecha:* {$fecha} — {$hora}\n";
    $mensaje .= "🔖 *Puntos utilizados:* {$puntos_usados} pts\n\n";
    $mensaje .= "⚠️ ¿No reconoces esta transacción? Comunícate de inmediato con nuestro equipo de atención al cliente:\n\n";
    $mensaje .= "📞 *+505 7685-9041*\n";
    $mensaje .= "_Este número es exclusivo para soporte y reportes._\n\n";
    $mensaje .= "¡Gracias por ser parte de nuestra comunidad! 🌵✨";

    // 3. Insertar en la cola de notificaciones
    $stmtIns = $conn->prepare("
        INSERT INTO `wsp_notificaciones_clientesclub_pendientes_` (celular, mensaje, estado)
        VALUES (:celular, :mensaje, 'pendiente')
    ");
    $stmtIns->execute([
        ':celular' => $celular_limpio,
        ':mensaje' => $mensaje
    ]);

    respuestaOk([
        'mensaje_registrado' => true,
        'id' => $conn->lastInsertId(),
        'numero' => $celular_limpio
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
