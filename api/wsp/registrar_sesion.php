<?php
/**
 * registrar_sesion.php — El VPS reporta su estado de conexión WhatsApp
 * POST /api/wsp/registrar_sesion.php
 * Requiere: Header X-WSP-Token
 *
 * El VPS envía en el body:
 *   estado           → conectado | qr_pendiente | desconectado
 *   instancia        → nombre PM2 (wsp-clientes, wsp-rrhh, ...)
 *   qr_base64        → imagen QR en base64 (solo en qr_pendiente)
 *   numero_telefono  → número vinculado (solo en conectado)
 */


require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../core/database/conexion.php';

header('Content-Type: application/json; charset=utf-8');

verificarTokenVPS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    respuestaError('Método no permitido', 405);

$body = json_decode(file_get_contents('php://input'), true);
$estado = $body['estado'] ?? '';
$instancia = $body['instancia'] ?? 'wsp-clientes';
$qr = $body['qr_base64'] ?? null;
$numero = $body['numero_telefono'] ?? null;

$estadosValidos = ['desconectado', 'qr_pendiente', 'conectado', 'inicializando', 'error'];
if (!in_array($estado, $estadosValidos)) {
    respuestaError('Estado inválido. Valores: ' . implode(', ', $estadosValidos));
}

try {
    $ipVPS = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

    // Si se conectó, limpiar el QR; si se desconectó, limpiar el número
    if ($estado === 'conectado')
        $qr = null;
    if ($estado === 'desconectado')
        $numero = null;

    // Upsert: actualizar la fila de esta instancia, o insertar si no existe
    // NOTA: Se usa NOW() directamente porque el servidor MySQL ya está en CST (UTC-6).
    // CONVERT_TZ(NOW(),'+00:00','-06:00') restaba 6h extra de forma incorrecta.
    $stmt = $conn->prepare("
        INSERT INTO wsp_sesion_vps_ (instancia, estado, qr_base64, numero_telefono, ultimo_ping, ip_vps)
        VALUES (:instancia, :estado, :qr, :numero, NOW(), :ip)
        ON DUPLICATE KEY UPDATE
            estado          = VALUES(estado),
            qr_base64       = VALUES(qr_base64),
            numero_telefono = COALESCE(VALUES(numero_telefono), numero_telefono),
            ultimo_ping     = VALUES(ultimo_ping),
            ip_vps          = VALUES(ip_vps)
    ");
    $stmt->execute([
        ':instancia' => $instancia,
        ':estado' => $estado,
        ':qr' => $qr,
        ':numero' => $numero,
        ':ip' => $ipVPS
    ]);

    // Verificar si hay un reset solicitado para esta instancia (leer la fila más reciente)
    $stmtCheck = $conn->prepare("SELECT reset_solicitado FROM wsp_sesion_vps_ WHERE instancia = :inst ORDER BY ultimo_ping DESC LIMIT 1");
    $stmtCheck->execute([':inst' => $instancia]);
    $resetSolicitado = (int) ($stmtCheck->fetchColumn() ?: 0) === 1;

    // Si hay reset pendiente, lo limpiamos ahora mismo para que el VPS solo lo vea una vez
    if ($resetSolicitado) {
        $stmtClear = $conn->prepare("UPDATE wsp_sesion_vps_ SET reset_solicitado = 0 WHERE instancia = :inst");
        $stmtClear->execute([':inst' => $instancia]);
    }

    respuestaOk([
        'estado' => $estado,
        'instancia' => $instancia,
        'reset_solicitado' => $resetSolicitado
    ]);

} catch (Exception $e) {
    respuestaError('Error interno: ' . $e->getMessage(), 500);
}
