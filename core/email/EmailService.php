<?php
/**
 * Servicio de envío de correos corporativos
 * Sistema ERP Batidos Pitaya
 * Ubicación: /public_html/core/email/EmailService.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService
{

    private $conn;
    private $mail;

    // Configuración SMTP Hostinger
    const SMTP_HOST = 'smtp.hostinger.com';
    const SMTP_PORT = 587;
    const SMTP_SECURE = PHPMailer::ENCRYPTION_STARTTLS;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }

    private function configurarSMTP()
    {
        $this->mail->isSMTP();
        $this->mail->Host = self::SMTP_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->SMTPSecure = self::SMTP_SECURE;
        $this->mail->Port = self::SMTP_PORT;
        $this->mail->CharSet = 'UTF-8';
        $this->mail->isHTML(true);
    }

    /**
     * Obtener credenciales del usuario
     */
    private function obtenerCredencialesUsuario($codOperario)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    email_trabajo, 
                    email_trabajo_clave, 
                    Nombre, 
                    Apellido 
                FROM Operarios 
                WHERE CodOperario = ? 
                AND email_trabajo IS NOT NULL 
                AND email_trabajo_clave IS NOT NULL
            ");
            $stmt->execute([$codOperario]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                return null;
            }

            return [
                'email' => $usuario['email_trabajo'],
                'password' => $usuario['email_trabajo_clave'],
                'nombre' => trim($usuario['Nombre'] . ' ' . $usuario['Apellido'])
            ];

        } catch (\PDOException $e) {
            error_log("Error obteniendo credenciales: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener email por cargo
     */
    public function obtenerEmailPorCargo($codNivelCargo)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.email_trabajo
                FROM Operarios o
                INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
                WHERE anc.CodNivelesCargos = ?
                AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                AND anc.Fecha <= CURDATE()
                AND o.email_trabajo IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$codNivelCargo]);
            $result = $stmt->fetch();

            return $result['email_trabajo'] ?? null;

        } catch (\PDOException $e) {
            error_log("Error obteniendo email por cargo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Enviar correo genérico
     */
    public function enviarCorreo($remitenteId, $destinatarios, $asunto, $cuerpoHtml, $archivos = [])
    {
        try {
            // Obtener credenciales del remitente
            $credenciales = $this->obtenerCredencialesUsuario($remitenteId);

            if (!$credenciales) {
                throw new Exception('Credenciales de correo no configuradas para este usuario');
            }

            // Configurar autenticación
            $this->mail->Username = $credenciales['email'];
            $this->mail->Password = $credenciales['password'];

            // Limpiar destinatarios previos
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            // Configurar remitente
            $this->mail->setFrom($credenciales['email'], $credenciales['nombre']);

            // Agregar destinatarios
            foreach ($destinatarios as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->mail->addAddress($email);
                }
            }

            // Configurar contenido
            $this->mail->Subject = $asunto;
            $this->mail->Body = $cuerpoHtml;
            $this->mail->AltBody = strip_tags($cuerpoHtml);

            // Agregar archivos adjuntos
            foreach ($archivos as $rutaArchivo) {
                if (file_exists($rutaArchivo)) {
                    $this->mail->addAttachment($rutaArchivo);
                }
            }

            // Enviar
            $this->mail->send();

            return [
                'success' => true,
                'message' => 'Correo enviado exitosamente'
            ];

        } catch (Exception $e) {
            error_log("Error enviando correo: " . $this->mail->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar correo: ' . $this->mail->ErrorInfo
            ];
        }
    }

    /**
     * Enviar invitación de calendario (ICS)
     */
    public function enviarInvitacionCalendario($remitenteId, $destinatarioEmail, $destinatarioNombre, $asunto, $descripcion, $fecha, $hora, $duracionMinutos = 60, $modalidad = 'Presencial')
    {
        try {
            $credenciales = $this->obtenerCredencialesUsuario($remitenteId);
            if (!$credenciales) {
                throw new Exception('Credenciales de correo no configuradas para este usuario');
            }

            // Configurar tiempos
            $startDateTime = new DateTime($fecha . ' ' . $hora, new DateTimeZone('America/Managua'));
            $endDateTime = clone $startDateTime;
            $endDateTime->add(new DateInterval('PT' . $duracionMinutos . 'M'));

            $dtStart = $startDateTime->format('Ymd\THis');
            $dtEnd = $endDateTime->format('Ymd\THis');
            $dtStamp = gmdate('Ymd\THis\Z');
            $uid = md5(uniqid(mt_rand(), true)); // UID único sin sufijos adicionales

            // Crear contenido ICS (RFC 5545)
            $ics_content = "BEGIN:VCALENDAR\r\n" .
                "VERSION:2.0\r\n" .
                "PRODID:-//Batidos Pitaya//ERP Recruitment//ES\r\n" .
                "METHOD:REQUEST\r\n" .
                "BEGIN:VEVENT\r\n" .
                "UID:{$uid}\r\n" .
                "DTSTAMP:{$dtStamp}\r\n" .
                "DTSTART:{$dtStart}\r\n" .
                "DTEND:{$dtEnd}\r\n" .
                "SUMMARY:{$asunto}\r\n" .
                "DESCRIPTION:{$descripcion}\r\n" .
                "LOCATION:{$modalidad}\r\n" .
                "ORGANIZER;CN=\"{$credenciales['nombre']}\":mailto:{$credenciales['email']}\r\n" .
                "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=\"{$destinatarioNombre}\":mailto:{$destinatarioEmail}\r\n" .
                "BEGIN:VALARM\r\n" .
                "TRIGGER:-PT15M\r\n" .
                "ACTION:DISPLAY\r\n" .
                "DESCRIPTION:Recordatorio de Entrevista\r\n" .
                "END:VALARM\r\n" .
                "END:VEVENT\r\n" .
                "END:VCALENDAR";

            // Configurar PHPMailer para invitación
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            $this->mail->clearCustomHeaders();

            $this->mail->Username = $credenciales['email'];
            $this->mail->Password = $credenciales['password'];
            $this->mail->setFrom($credenciales['email'], $credenciales['nombre']);
            $this->mail->addAddress($destinatarioEmail, $destinatarioNombre);

            // También agregar al propio reclutador como destinatario para que le quede en su calendario
            $this->mail->addAddress($credenciales['email'], $credenciales['nombre']);

            $this->mail->Subject = $asunto;
            $this->mail->ContentType = 'text/calendar; charset=utf-8; method=REQUEST';
            $this->mail->Body = $ics_content;

            // Adjuntar como archivo para mayor compatibilidad con clientes classic
            $this->mail->addStringAttachment($ics_content, 'invite.ics', 'base64', 'text/calendar; method=REQUEST');

            // Headers específicos para Outlook
            $this->mail->addCustomHeader('MIME-version', '1.0');
            $this->mail->addCustomHeader('Content-class', 'urn:content-classes:calendarmessage');

            $this->mail->send();

            return [
                'success' => true,
                'message' => 'Invitación de calendario enviada exitosamente'
            ];

        } catch (Exception $e) {
            error_log("Error enviando invitación: " . $this->mail->ErrorInfo);
            return [
                'success' => false,
                'message' => 'Error al enviar invitación: ' . $this->mail->ErrorInfo
            ];
        }
    }

}
?>