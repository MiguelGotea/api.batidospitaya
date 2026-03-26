<?php
/**
 * enviar.php — Envía correo vía EmailService del ERP
 *
 * POST JSON: {
 *   cod_operario: int,
 *   destinatario_nombre: string,
 *   asunto: string,
 *   cuerpo: string,
 *   adjunto?: { datos: base64, mimetype: string, filename: string }
 * }
 */

require_once __DIR__ . '/../auth/auth_bot.php';
require_once __DIR__ . '/../../../core/database/conexion.php';
require_once __DIR__ . '/../../../core/email/EmailService.php';

verificarTokenBot();

$body        = json_decode(file_get_contents('php://input'), true);
$codOperario = (int)($body['cod_operario'] ?? 0);
$destNombre  = trim($body['destinatario_nombre'] ?? '');
$asunto      = trim($body['asunto'] ?? '');
$cuerpo      = trim($body['cuerpo'] ?? '');

if (!$codOperario || !$destNombre || !$asunto || !$cuerpo) {
    respuestaError('Faltan campos: cod_operario, destinatario_nombre, asunto, cuerpo');
}

try {
    $destinatario = null;

    // Si el destinatario ya es un email, usarlo directamente
    if (filter_var($destNombre, FILTER_VALIDATE_EMAIL)) {
        // Intentar encontrar el nombre en Operarios para el display
        $stmtEmail = $conn->prepare("
            SELECT CONCAT(TRIM(Nombre),' ',TRIM(Apellido)) AS nombre_completo
            FROM Operarios WHERE email_trabajo = ? LIMIT 1
        ");
        $stmtEmail->execute([$destNombre]);
        $rowEmail = $stmtEmail->fetch(PDO::FETCH_ASSOC);
        $destinatario = [
            'email_trabajo'  => $destNombre,
            'nombre_completo'=> $rowEmail['nombre_completo'] ?? $destNombre
        ];
    } else {
        // Búsqueda fuzzy por nombre
        $partes      = array_filter(explode(' ', $destNombre));
        $condiciones = [];
        $params      = [];
        foreach ($partes as $parte) {
            $condiciones[] = "(Nombre LIKE ? OR Apellido LIKE ? OR Nombre2 LIKE ? OR Apellido2 LIKE ?)";
            $params        = array_merge($params, ["%$parte%", "%$parte%", "%$parte%", "%$parte%"]);
        }
        $where = implode(' OR ', $condiciones);

        $stmt = $conn->prepare("
            SELECT o.CodOperario, o.email_trabajo,
                   CONCAT(TRIM(o.Nombre),' ',TRIM(o.Apellido)) AS nombre_completo
            FROM Operarios o
            INNER JOIN Contratos c ON c.cod_operario = o.CodOperario AND c.Finalizado = 0
            WHERE ($where)
              AND o.email_trabajo IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute($params);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destinatario) {
            respuestaError("No se encontró operario activo llamado '$destNombre' con correo configurado", 404);
        }
    }

    // Manejar adjunto opcional
    $archivosAdjuntos = [];
    $tmpAdjunto       = null;
    if (!empty($body['adjunto'])) {
        $adj      = $body['adjunto'];
        $ext      = pathinfo($adj['filename'] ?? 'adjunto.bin', PATHINFO_EXTENSION) ?: 'bin';
        $tmpFile  = tempnam(sys_get_temp_dir(), 'pitayabot_') . '.' . $ext;
        file_put_contents($tmpFile, base64_decode($adj['datos']));
        $archivosAdjuntos[] = $tmpFile;
        $tmpAdjunto         = $tmpFile;
    }

    // Enviar correo vía EmailService
    $service    = new EmailService($conn);
    $cuerpoHtml = nl2br(htmlspecialchars($cuerpo));
    $resultado  = $service->enviarCorreo(
        $codOperario,
        [$destinatario['email_trabajo']],
        $asunto,
        $cuerpoHtml,
        $archivosAdjuntos
    );

    // Limpiar tmp
    if ($tmpAdjunto && file_exists($tmpAdjunto)) {
        unlink($tmpAdjunto);
    }

    if (!$resultado['success']) {
        respuestaError($resultado['message'], 500);
    }

    respuestaOk([
        'para'           => $destinatario['nombre_completo'],
        'email_enviado'  => $destinatario['email_trabajo'],
        'asunto'         => $asunto,
        'con_adjunto'    => !empty($body['adjunto'])
    ]);

} catch (Exception $e) {
    error_log('Error correos/enviar.php: ' . $e->getMessage());
    respuestaError('Error enviando correo: ' . $e->getMessage(), 500);
}
