<?php
/**
 * pendientes.php — Correos no leídos de los últimos 7 días
 *
 * POST JSON: { cod_operario: int }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

define('IMAP_HOST_PEND', 'mail.batidospitaya.com');
define('IMAP_PORT_PEND', 993);

$body        = json_decode(file_get_contents('php://input'), true);
$codOperario = (int)($body['cod_operario'] ?? 0);

if (!$codOperario) {
    respuestaError('Se requiere cod_operario');
}

try {
    $stmt = $conn->prepare("
        SELECT email_trabajo, email_trabajo_clave
        FROM Operarios
        WHERE CodOperario = ? AND email_trabajo IS NOT NULL AND email_trabajo_clave IS NOT NULL
    ");
    $stmt->execute([$codOperario]);
    $creds = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$creds) {
        respuestaError('El operario no tiene correo corporativo configurado', 404);
    }

    $mailbox = '{' . IMAP_HOST_PEND . ':' . IMAP_PORT_PEND . '/imap/ssl}INBOX';
    $mbox    = @imap_open(
        $mailbox,
        $creds['email_trabajo'],
        $creds['email_trabajo_clave'],
        0, 1,
        ['DISABLE_AUTHENTICATOR' => 'GSSAPI']
    );

    if (!$mbox) {
        respuestaError('No se pudo conectar al servidor de correo', 503);
    }

    // Buscar no leídos de los últimos 7 días
    $fechaDesde = date('d-M-Y', strtotime('-7 days'));
    $uids       = imap_search($mbox, "UNSEEN SINCE $fechaDesde", SE_UID);
    $resultados = [];

    if ($uids) {
        // Hasta 10 más recientes
        $uids = array_slice(array_reverse($uids), 0, 10);

        foreach ($uids as $uid) {
            $msgNo  = imap_msgno($mbox, $uid);
            $header = imap_headerinfo($mbox, $msgNo);

            // Calcular hace cuánto llegó
            $fechaTS  = $header->udate ?? 0;
            $diasAtras = floor((time() - $fechaTS) / 86400);
            $cuando   = $diasAtras === 0
                ? 'hoy'
                : ($diasAtras === 1 ? 'hace 1 día' : "hace $diasAtras días");

            $resultados[] = [
                'uid'    => $uid,
                'asunto' => imap_utf8($header->subject ?? '(sin asunto)'),
                'de'     => imap_utf8($header->fromaddress ?? ''),
                'fecha'  => $header->date ?? '',
                'cuando' => $cuando
            ];
        }
    }

    imap_close($mbox);

    respuestaOk(['data' => $resultados, 'total' => count($resultados)]);

} catch (Exception $e) {
    error_log('Error correos/pendientes.php: ' . $e->getMessage());
    respuestaError('Error obteniendo correos pendientes: ' . $e->getMessage(), 500);
}
