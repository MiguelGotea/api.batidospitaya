<?php
/**
 * Servicio de envío de correos corporativos
 * Sistema ERP Batidos Pitaya
 * Ubicación: /public_html/core/email/EmailService.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    
    private $conn;
    private $mail;
    
    // Configuración SMTP Hostinger
    const SMTP_HOST = 'smtp.hostinger.com';
    const SMTP_PORT = 587;
    const SMTP_SECURE = PHPMailer::ENCRYPTION_STARTTLS;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    private function configurarSMTP() {
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
    private function obtenerCredencialesUsuario($codOperario) {
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
    public function obtenerEmailPorCargo($codNivelCargo) {
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
    public function enviarCorreo($remitenteId, $destinatarios, $asunto, $cuerpoHtml, $archivos = []) {
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
    

}
?>