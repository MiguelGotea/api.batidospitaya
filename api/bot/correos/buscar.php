<?php
/**
 * buscar.php — Búsqueda IMAP en el correo del operario
 *
 * POST JSON: {
 *   cod_operario: int,
 *   remitente?: string,
 *   palabras_clave?: string[]
 * }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';

verificarTokenBot();

// Hostinger usa imap.hostinger.com para IMAP, o el mismo host del dominio
// La dirección correcta en Hostinger Business/Premium es el hostname del servidor
define('IMAP_HOST', 'imap.hostinger.com');
define('IMAP_PORT', 993);

$body        = json_decode(file_get_contents('php://input'), true);
$codOperario = (int)($body['cod_operario'] ?? 0);

if (!$codOperario) {
    respuestaError('Se requiere cod_operario');
}

try {
    // Obtener credenciales del operario
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

    $mailbox = '{' . IMAP_HOST . ':' . IMAP_PORT . '/imap/ssl/novalidate-cert}INBOX';
    $mbox    = @imap_open(
        $mailbox,
        $creds['email_trabajo'],
        $creds['email_trabajo_clave'],
        0, 1,
        ['DISABLE_AUTHENTICATOR' => 'GSSAPI']
    );

    if (!$mbox) {
        $imapErr = imap_last_error() ?: 'Error desconocido de conexión IMAP';
        respuestaError('No se pudo conectar al servidor de correo: ' . $imapErr, 503);
    }

    // Construir criterio de búsqueda
    $criterio = 'ALL';
    $remitente  = trim($body['remitente'] ?? '');
    $palabras   = $body['palabras_clave'] ?? [];

    if ($remitente) {
        $criterio = "FROM \"$remitente\"";
    }
    if (!empty($palabras)) {
        foreach ($palabras as $kw) {
            $criterio .= ' SUBJECT "' . addslashes(trim($kw)) . '"';
        }
    }
    if ($criterio === 'ALL' && empty($remitente) && empty($palabras)) {
        // Sin filtros → buscar recientes no leídos
        $criterio = 'UNSEEN';
    }

    $uids = imap_search($mbox, $criterio, SE_UID);
    $resultados = [];

    if ($uids) {
        // Tomar los 5 más recientes (últimos UIDs = más recientes)
        $uids = array_slice(array_reverse($uids), 0, 5);

        foreach ($uids as $uid) {
            $msgNo   = imap_msgno($mbox, $uid);
            $header  = imap_headerinfo($mbox, $msgNo);
            $preview = '';

            // Intentar obtener cuerpo de texto plano (parte 1)
            try {
                $raw     = imap_fetchbody($mbox, $uid, '1', FT_UID);
                $enc     = $header->encoding ?? 0;
                if ($enc == 3) {
                    $raw = base64_decode($raw);
                } elseif ($enc == 4) {
                    $raw = quoted_printable_decode($raw);
                }
                $preview = mb_substr(strip_tags($raw), 0, 300);
            } catch (Throwable $_) { }

            $resultados[] = [
                'uid'     => $uid,
                'asunto'  => imap_utf8($header->subject ?? '(sin asunto)'),
                'de'      => imap_utf8($header->fromaddress ?? ''),
                'fecha'   => $header->date ?? '',
                'preview' => trim($preview)
            ];
        }
    }

    imap_close($mbox);

    respuestaOk(['data' => $resultados, 'total' => count($resultados)]);

} catch (Exception $e) {
    error_log('Error correos/buscar.php: ' . $e->getMessage());
    respuestaError('Error buscando correos: ' . $e->getMessage(), 500);
}
