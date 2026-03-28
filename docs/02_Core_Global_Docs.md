# Archivos Globales del Sistema

## /public_html/core/email/EmailService.php

```php
<<?php
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
```

## /public_html/core/permissions/permissions.php
```php
<?php
/**
 * Sistema de Permisos para Tools ERP
 * 
 * Verifica permisos de acceso basados en:
 * - Herramienta (tools_erp)
 * - Acción (acciones_tools_erp)
 * - Cargo (NivelesCargos)
 * 
 * NOTA: Este archivo debe ser incluido DESPUÉS de conexion.php
 */

/**
 * Verifica si un cargo tiene permiso para realizar una acción en una herramienta
 * 
 * @param string $nombreHerramienta Nombre de la herramienta (campo 'nombre' de tools_erp)
 * @param string $nombreAccion Nombre de la acción (ej: 'vista', 'nuevo', 'edicion', 'eliminar')
 * @param int $codNivelCargo Código del nivel de cargo del usuario
 * @return bool True si tiene permiso, False si no tiene permiso
 */
function tienePermiso($nombreHerramienta, $nombreAccion, $codNivelCargo) {
    global $conn;
    
    // Validar parámetros
    if (empty($nombreHerramienta) || empty($nombreAccion) || empty($codNivelCargo)) {
        error_log("tienePermiso: Parámetros inválidos - Herramienta: $nombreHerramienta, Acción: $nombreAccion, Cargo: $codNivelCargo");
        return false;
    }
    
    try {
        // Consulta que une las 4 tablas para verificar el permiso
        $sql = "
            SELECT p.permiso
            FROM tools_erp t
            INNER JOIN acciones_tools_erp a ON t.id = a.tool_erp_id
            INNER JOIN permisos_tools_erp p ON a.id = p.accion_tool_erp_id
            WHERE t.nombre = :nombreHerramienta
              AND a.nombre_accion = :nombreAccion
              AND p.CodNivelesCargos = :codNivelCargo
            LIMIT 1
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombreHerramienta' => $nombreHerramienta,
            ':nombreAccion' => $nombreAccion,
            ':codNivelCargo' => $codNivelCargo
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no existe registro, no tiene permiso
        if (!$resultado) {
            return false;
        }
        
        // Si existe registro, verificar si es 'allow' o 'deny'
        return $resultado['permiso'] === 'allow';
        
    } catch (PDOException $e) {
        error_log("Error en tienePermiso: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica permiso y redirige si no tiene acceso
 * Útil para proteger páginas completas
 * 
 * @param string $nombreHerramienta Nombre de la herramienta
 * @param string $nombreAccion Nombre de la acción (generalmente 'vista')
 * @param int $codNivelCargo Código del nivel de cargo del usuario
 * @param string $urlRedireccion URL a donde redirigir si no tiene permiso (default: index.php)
 */
function verificarPermisoORedireccionar($nombreHerramienta, $nombreAccion, $codNivelCargo, $urlRedireccion = '../../../index.php') {
    if (!tienePermiso($nombreHerramienta, $nombreAccion, $codNivelCargo)) {
        header("Location: $urlRedireccion");
        exit();
    }
}

/**
 * Obtiene todos los permisos de un cargo para una herramienta específica
 * Útil para verificar múltiples acciones de una vez
 * 
 * @param string $nombreHerramienta Nombre de la herramienta
 * @param int $codNivelCargo Código del nivel de cargo del usuario
 * @return array Array asociativo con nombre_accion => permiso (allow/deny)
 */
function obtenerPermisosHerramienta($nombreHerramienta, $codNivelCargo) {
    global $conn;
    
    try {
        $sql = "
            SELECT a.nombre_accion, p.permiso
            FROM tools_erp t
            INNER JOIN acciones_tools_erp a ON t.id = a.tool_erp_id
            LEFT JOIN permisos_tools_erp p ON a.id = p.accion_tool_erp_id 
                AND p.CodNivelesCargos = :codNivelCargo
            WHERE t.nombre = :nombreHerramienta
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombreHerramienta' => $nombreHerramienta,
            ':codNivelCargo' => $codNivelCargo
        ]);
        
        $permisos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Si no existe permiso o es 'deny', se considera sin permiso
            $permisos[$row['nombre_accion']] = ($row['permiso'] === 'allow');
        }
        
        return $permisos;
        
    } catch (PDOException $e) {
        error_log("Error en obtenerPermisosHerramienta: " . $e->getMessage());
        return [];
    }
}

/**
 * Verifica si existe una herramienta y acción en el sistema
 * Útil para debugging
 * 
 * @param string $nombreHerramienta Nombre de la herramienta
 * @param string $nombreAccion Nombre de la acción
 * @return bool True si existe, False si no existe
 */
function existeAccionHerramienta($nombreHerramienta, $nombreAccion) {
    global $conn;
    
    try {
        $sql = "
            SELECT COUNT(*) as total
            FROM tools_erp t
            INNER JOIN acciones_tools_erp a ON t.id = a.tool_erp_id
            WHERE t.nombre = :nombreHerramienta
              AND a.nombre_accion = :nombreAccion
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombreHerramienta' => $nombreHerramienta,
            ':nombreAccion' => $nombreAccion
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error en existeAccionHerramienta: " . $e->getMessage());
        return false;
    }
}
?>
```

## /public_html/core/layout/menu_lateral.php
```php
<?php
/**
 * Menú Lateral Universal para Módulos ERP - Sistema de Permisos
 * Sidebar colapsable con acordeón vertical
 * Incluir este archivo en cada index: require_once '../../includes/menu_lateral.php';
 * Uso: renderMenuLateral($cargoOperario, 'index.php');
 */

// Configuración global del menú basado en permisos por cargo
$menuGlobal = [
    [
        'nombre' => 'Inicio',
        'icon' => 'fas fa-home',
        'cargos_permitidos' => [],
        'url' => 'index.php', // Añade esta línea
        'items' => [] // Vacía el array de items
    ],
    [
        'nombre' => 'Comunicación Interna',
        'icon' => 'fas fa-comments',
        'cargos_permitidos' => [],
        'items' => [
            [
                'nombre' => 'Avisos Internos', 
                'url' => 'supervision/auditorias_original/index_avisos_publico.php',
                'cargos_permitidos' => []
            ],
            [ //exclusivo para atencion cliente y auxilir 
                'nombre' => 'Avisos Sucursales', 
                'url' => 'marketing/auditorias_original/index_avisos_publico.php',
                'cargos_permitidos' => [49, 13,  22, 26, 28, 42, 36]
            ],
            [
                'nombre' => 'Auditorías', 
                'url' => 'supervision/auditorias_original/index_auditorias_publico.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Promedios', 
                'url' => 'supervision/auditorias_original/promedio.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Reclamos', 
                'url' => 'supervision/auditorias_original/index_reclamos_publico.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'KPI Sucursales', 
                'url' => 'sucursales/kpi_sucursales.php',
                'cargos_permitidos' => [49, 5, 43, 11, 27, 26, 42]
            ],
            [
                'nombre' => 'Registrar KPIs', 
                'url' => 'supervision/auditorias_original/kpi.php',
                'cargos_permitidos' => [49, 11]
            ],
            [
                'nombre' => 'Nuevo Aviso', 
                'url' => 'supervision/auditorias_original/agregarAviso.php',
                'cargos_permitidos' => [49, 11, 13, 39, 30, 37, 42, 26]
            ],
            [
                'nombre' => 'Editar Avisos', 
                'url' => 'supervision/auditorias_original/index_avisos.php',
                'cargos_permitidos' => [49, 11, 13, 39, 30, 37, 42, 26]
            ]
        ]
    ],
    [
        'nombre' => 'Recursos Humanos',
        'icon' => 'fas fa-users',
        'cargos_permitidos' => [49, 11, 5, 43, 21, 42, 36, 13, 28, 30, 37, 39, 27, 8, 12],
        'items' => [
            [
                'nombre' => 'Marcacion', 
                'url' => '../../marcacion.php',
                'cargos_permitidos' => [49, 27, 16]
            ],
            [
                'nombre' => 'Historial Marcaciones', 
                'url' => 'sucursales/historial_marcaciones_sucursales.php',
                'cargos_permitidos' => [49, 27, 16]
            ],
            [
                'nombre' => 'Tardanzas', 
                'url' => 'operaciones/tardanzas_manual.php',
                'cargos_permitidos' => [49, 5, 43, 16, 21, 13, 28, 30, 37, 39, 8]
            ],
            [
                'nombre' => 'Faltas/Ausencias', 
                'url' => 'lideres/faltas_manual.php',
                'cargos_permitidos' => [49, 5, 43, 13, 28, 30, 37, 39, 8]
            ],
            [
                'nombre' => 'Viaticos', 
                'url' => 'operaciones/viaticos.php',
                'cargos_permitidos' => [49, 16, 8]
            ],
            [
                'nombre' => 'Vacaciones', 
                'url' => 'lideres/vacaciones.php',
                'cargos_permitidos' => [49, 13, 16, 39, 30, 37, 28]
            ],
            [
                'nombre' => 'Horas Extras', 
                'url' => 'operaciones/horas_extras_manual.php',
                'cargos_permitidos' => [49, 11, 16, 8]
            ],
            [
                'nombre' => 'Feriados', 
                'url' => 'operaciones/feriados.php',
                'cargos_permitidos' => [49, 11, 16, 8, 13]
            ],
            [
                'nombre' => 'Reportes de Personal', 
                'url' => 'rrhh/reportes.php',
                'cargos_permitidos' => [49, 16, 21]
            ],
            [
                'nombre' => 'Generar Horarios', 
                'url' => 'lideres/programar_horarios_lider2.php',
                'cargos_permitidos' => [49, 5, 43]
            ],
            [
                'nombre' => 'Horarios Programados', 
                'url' => 'supervision/ver_horarios_compactos.php',
                'cargos_permitidos' => [49, 16, 11, 5, 43, 21, 42, 36, 13, 28, 30, 37, 39, 27, 8]
            ],
            [
                'nombre' => 'Marcaciones', 
                'url' => 'rh/ver_marcaciones_todas.php',
                'cargos_permitidos' => [49, 13, 5, 43, 8, 11, 21, 22, 36, 13, 28, 30, 37, 39, 8, 12]
            ],
            [
                'nombre' => 'Gestion Sucursales', 
                'url' => 'operaciones/gestion_colaboradores.php',
                'cargos_permitidos' => [49, 16, 36]
            ],
            [
                'nombre' => 'Colaboradores', 
                'url' => 'rh/colaboradores.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
            [
                'nombre' => 'Nuevo Colaborador', 
                'url' => 'rh/nuevo_colaborador.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
            [
                'nombre' => 'Agenda Colaboradores', 
                'url' => 'rh/contactos_colaboradores.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
            [
                'nombre' => 'Cumpleaños Colaboradores', 
                'url' => 'rh/cumpleanos_colaboradores.php',
                'cargos_permitidos' => [49, 16, 13, 28, 30, 37, 39]
            ],
        ]
    ],
    [
        'nombre' => 'Supervisión',
        'icon' => 'fas fa-eye',
        'cargos_permitidos' => [49, 11, 21, 8],
        'items' => [
            [
                'nombre' => 'Auditorías de Efectivo', 
                'url' => 'supervision/auditorias_original/auditinternas/auditorias_consolidadas.php',
                'cargos_permitidos' => [49, 11, 21, 16]
            ],
            [
                'nombre' => 'Deducciones', 
                'url' => 'supervision/auditorias_original/auditinternas/deducciones_total.php',
                'cargos_permitidos' => [49, 8, 11, 16]
            ],
            [
                'nombre' => 'Control de Inventario', 
                'url' => 'supervision/inventario.php',
                'cargos_permitidos' => [49, 21, 16]
            ],
            [
                'nombre' => 'Faltantes de Caja', 
                'url' => 'supervision/auditorias_original/auditinternas/faltante_caja.php',
                'cargos_permitidos' => [49, 8, 16]
            ],
        ]
    ],
    
    [
        'nombre' => 'Atencion al Cliente',
        'icon' => 'fas fa-headset',
        'cargos_permitidos' => [49, 11, 21, 28, 50],
        'items' => [
            [
                'nombre' => 'Nuevo Reclamo', 
                'url' => 'supervision/auditorias_original/nuevoreclamo.php',
                'cargos_permitidos' => [49, 16, 21, 28, 50]
            ],
            [
                'nombre' => 'Procesar Reclamos', 
                'url' => 'supervision/auditorias_original/reclamospend.php',
                'cargos_permitidos' => [49, 16, 11]
            ],
            [
                'nombre' => 'Reseñas Google', 
                'url' => 'atencioncliente/resenas_google.php',
                'cargos_permitidos' => [49, 16, 21, 28, 50]
            ],
        ]
    ],
    [
        'nombre' => 'Club Pitaya',
        'icon' => 'fas fa-star"',
        'cargos_permitidos' => [49, 16, 22, 28, 27, 42, 26, 50],
        'items' => [
            [
                'nombre' => 'Cumpleaños', 
                'url' => 'atencioncliente/cumpleanos_clientes.php',
                'cargos_permitidos' => [49, 22, 28, 50]
            ],
            [
                'nombre' => 'Clientes Club', 
                'url' => 'atencioncliente/historial_clientes.php',
                'cargos_permitidos' => [49, 16, 22, 28, 27, 42, 26]
            ]
        ]
    ],
    [
        'nombre' => 'Compras',
        'icon' => 'fas fa-shopping-cart"',
        'cargos_permitidos' => [9, 15, 16, 49],
        'items' => [
            [
                'nombre' => 'Solicitudes Cotización', 
                'url' => 'compras/historial_solicitudes_cotizacion.php',
                'cargos_permitidos' => [9, 15, 16, 49]
            ],
            [
                'nombre' => 'Nueva Solicitud de Cotización', 
                'url' => 'compras/solicitud_cotizacion.php',
                'cargos_permitidos' => [9, 15, 16, 49]
            ],
        ]
    ],
    [
        'nombre' => 'Mantenimiento',
        'icon' => 'fas fa-tools',
        'cargos_permitidos' => [49, 11, 14, 21, 5, 43, 35, 12, 26, 42],
        'items' => [
            [
                'nombre' => 'Solicitudes', 
                'url' => 'mantenimiento/historial_solicitudes.php',
                'cargos_permitidos' => [49, 11, 16, 5, 43, 35, 14, 12, 26, 42]
            ],
            [
                'nombre' => 'Agenda Diaria', 
                'url' => 'mantenimiento/agenda_colaborador.php',
                'cargos_permitidos' => [49, 14, 16, 35]
            ],
            [
                'nombre' => 'Calendario', 
                'url' => 'mantenimiento/programacion_solicitudes.php',
                'cargos_permitidos' => [49, 21, 16, 35]
            ],
            [
                'nombre' => 'Mantenimiento', 
                'url' => 'mantenimiento/formulario_mantenimiento.php',
                'cargos_permitidos' => [49, 5, 43, 35, 16, 12]
            ],
            [
                'nombre' => 'Equipo', 
                'url' => 'mantenimiento/formulario_equipos.php',
                'cargos_permitidos' => [49, 5, 43, 16, 35, 12]
            ]
        ]

    ],
    [
        'nombre' => 'Activos',
        'icon' => 'fas fa-tools',
        'cargos_permitidos' => [49, 14, 35],
        'items' => [
            [
                'nombre' => 'Historial Equipos', 
                'url' => 'mantenimiento/equipos_lista.php',
                'cargos_permitidos' => [49, 16, 14, 35]
            ],
            [
                'nombre' => 'Nuevo Equipo', 
                'url' => 'mantenimiento/equipos_registro.php',
                'cargos_permitidos' => [49, 16, 14, 35]
            ],
        ]
    ],
    [
        'nombre' => 'Sistemas',
        'icon' => 'fas fa-laptop-code',
        'cargos_permitidos' => [49, 15],
        'items' => [
            [
                'nombre' => 'Permisos', 
                'url' => 'sistemas/gestion_permisos.php',
                'cargos_permitidos' => [49, 15]
            ],
        ]
    ],
    [
        'nombre' => 'Ventas',
        'icon' => 'fas fa-shopping-cart',
        'cargos_permitidos' => [49, 16, 27, 42, 26],
        'items' => [
            [
                'nombre' => 'Gestión de Ferias', 
                'url' => 'sucursales/ferias/index.php',
                'cargos_permitidos' => [49, 27, 26]
            ],
            [
                'nombre' => 'Cupones', 
                'url' => 'marketing/cupones.php',
                'cargos_permitidos' => [49, 16, 42, 26]
            ],
            [
                'nombre' => 'Historial Ventas', 
                'url' => 'ventas/historial_ventas.php',
                'cargos_permitidos' => [49, 16, 42, 26, 42]
            ],
        ]
    ],
    [
        'nombre' => 'KPI Liderazgo',
        'icon' => 'fas fa-chart-line',
        'cargos_permitidos' => [49, 11, 12, 13, 42, 16],
        'items' => [
            [
                'nombre' => 'Resultado de Indicadores', 
                'url' => 'gerencia/indicadores_resultado.php',
                'cargos_permitidos' => []
            ],
            [
                'nombre' => 'Edición de Indicadores', 
                'url' => 'gerencia/indicadores_edicion.php',
                'cargos_permitidos' => []
            ]
        ]
    ],
    [
        'nombre' => 'Cerrar Sesion',
        'icon' => 'fas fa-sign-out-alt',
        'cargos_permitidos' => [],
        'url' => 'logout.php', // Añade esta línea
        'items' => [] // Vacía el array de items
    ],
];

/**
 * Detecta la ruta base automáticamente basado en la estructura de módulos
 */
function detectarRutaBase() {
    // Obtener la ruta del script actual
    $scriptActual = $_SERVER['SCRIPT_FILENAME'];
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    // Convertir a ruta relativa desde el document root
    $rutaRelativa = str_replace($documentRoot, '', $scriptActual);
    
    // Buscar la posición de '/modulos/' en la ruta
    $posModulos = strpos($rutaRelativa, '/modulos/');
    
    if ($posModulos !== false) {
        // Extraer la parte de la ruta hasta /modulos/
        $rutaHastaModulos = substr($rutaRelativa, 0, $posModulos + 9); // +9 para incluir '/modulos/'
        
        // Contar cuántos directorios hay después de /modulos/
        $rutaDespuesModulos = substr($rutaRelativa, $posModulos + 9);
        $nivelesProfundidad = substr_count($rutaDespuesModulos, '/');
        
        // Generar la ruta base (../../ etc.)
        if ($nivelesProfundidad === 0) {
            return './';
        } else {
            return str_repeat('../', $nivelesProfundidad);
        }
    }
    
    // Si no se encuentra /modulos/, asumir que estamos en la raíz
    return './';
}

/**
 * Genera la URL correcta para cualquier archivo en la estructura de módulos
 */
function generarUrlModulo($rutaDestino) {
    $rutaBase = detectarRutaBase();
    
    // Si el destino es solo "index.php", apuntar al index del módulo actual
    if ($rutaDestino === 'index.php') {
        return $rutaBase . 'index.php';
    }
    
    // Caso especial para logout.php - usar ruta absoluta desde la raíz del dominio
    if ($rutaDestino === 'logout.php') {
        return '/logout.php';
    }
    
    // Para otras rutas, construir la ruta completa
    return $rutaBase . $rutaDestino;
}

/**
 * Detecta el módulo actual basado en la ruta
 */
function detectarModuloActual() {
    $scriptActual = $_SERVER['SCRIPT_FILENAME'];
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    
    $rutaRelativa = str_replace($documentRoot, '', $scriptActual);
    $posModulos = strpos($rutaRelativa, '/modulos/');
    
    if ($posModulos !== false) {
        $rutaDespuesModulos = substr($rutaRelativa, $posModulos + 9); // +9 para saltar '/modulos/'
        $partes = explode('/', $rutaDespuesModulos);
        return $partes[0] ?? 'desconocido';
    }
    
    return 'raiz';
}


/**
 * Función para verificar si un cargo tiene acceso a un elemento
 */
function tieneAcceso($cargoOperario, $cargosPermitidos) {
    if (empty($cargosPermitidos)) {
        return true;
    }
    return in_array($cargoOperario, $cargosPermitidos);
}

/**
 * Función para filtrar el menú según los permisos del cargo
 */
function filtrarMenuPorPermisos($menu, $cargoOperario) {
    $menuFiltrado = [];
    
    foreach ($menu as $grupo) {
        if (tieneAcceso($cargoOperario, $grupo['cargos_permitidos'])) {
            $grupoFiltrado = $grupo;
            
            // Si el grupo tiene items, filtrarlos
            if (!empty($grupo['items'])) {
                $itemsFiltrados = [];
                
                foreach ($grupo['items'] as $item) {
                    if (tieneAcceso($cargoOperario, $item['cargos_permitidos'])) {
                        $itemsFiltrados[] = $item;
                    }
                }
                
                // Solo incluir el grupo si tiene items filtrados o si tiene URL directa
                if (!empty($itemsFiltrados)) {
                    $grupoFiltrado['items'] = $itemsFiltrados;
                    $menuFiltrado[] = $grupoFiltrado;
                }
            } else {
                // Grupo sin items pero con URL directa (como Inicio)
                $menuFiltrado[] = $grupoFiltrado;
            }
        }
    }
    
    return $menuFiltrado;
}

/**
 * Función principal para renderizar el menú lateral
 * @param int $cargoOperario - Código del cargo del usuario
 * @return string HTML del menú lateral
 */
function renderMenuLateral($cargoOperario) {
    global $menuGlobal;
    
    if (!$cargoOperario) {
        return '';
    }
    
    // Detectar automáticamente la página actual
    $paginaActual = basename($_SERVER['SCRIPT_NAME']);
    
    $menuFiltrado = filtrarMenuPorPermisos($menuGlobal, $cargoOperario);
    
    if (empty($menuFiltrado)) {
        return '';
    }
    
    // Detectar módulo actual para el Dashboard
    $moduloActual = detectarModuloActual();
    
    ob_start();
    ?>
    
    <!-- CSS COMPLETO del Menú Lateral -->
    <style>
        /* ==================== SIDEBAR BASE ==================== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 70px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1000;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar:hover {
            width: 260px;
        }
        
        /* ==================== HEADER ==================== */
        .sidebar-header {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid #e0e0e0;
            padding: 0 15px;
            overflow: hidden;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .sidebar-header .logo {
            height: 40px;
            width: auto;
            opacity: 1;
            transition: all 0.3s ease 0.15s;
        }
        
        /* ==================== GRUPOS ==================== */
        .menu-group {
            position: relative;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .menu-group-title {
            height: 60px;
            padding: 0;
            color: #0E544C;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: white;
        }
        
        .menu-group-title:hover {
            background: #f8f9fa;
        }
        
        .menu-group-title.active {
            background: #e8f5f3;
            border-right: 4px solid #51B8AC;
        }
        
        .menu-icon-wrapper {
            width: 70px;
            min-width: 70px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem !important;
            color: #51B8AC;
            transition: transform 0.3s ease;
        }
        
        .menu-group-title:hover .menu-icon-wrapper {
            transform: scale(1.1);
        }
        
        .menu-group-title.active .menu-icon-wrapper {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        
        .menu-group-name {
            white-space: nowrap;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease 0.1s;
            font-weight: 600;
            font-size: 0.95rem !important;
            flex: 1;
            text-align: left; /* Alinea el texto a la izquierda */
        }
        
        .sidebar:hover .menu-group-name {
            opacity: 1;
            transform: translateX(0);
        }
        
        .chevron-icon {
            margin-right: 15px;
            opacity: 0;
            transition: all 0.3s ease 0.1s;
            font-size: 0.8rem !important;
            color: #666;
        }
        
        .sidebar:hover .chevron-icon {
            opacity: 1;
        }
        
        .menu-group.active .chevron-icon {
            transform: rotate(90deg);
        }
        
        /* ==================== SUBGRUPOS (ACORDEÓN) ==================== */
        .menu-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fafafa;
        }
        
        /* Solo mostrar subgrupos cuando el sidebar está expandido Y el grupo está activo */
        .sidebar:hover .menu-group.active .menu-items {
            max-height: 600px;
        }
        
        .menu-item {
            padding: 12px 20px 12px 70px;
            color: #666;
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
            font-size: 0.9rem !important;
            border-left: 3px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: left; /* Alinea el texto a la izquierda */
        }
        
        .sidebar:hover .menu-item {
            padding-left: 80px;
        }
        
        .menu-item:hover {
            background: #f0f0f0;
            color: #51B8AC;
            border-left-color: #51B8AC;
            padding-left: 85px;
        }
        
        .menu-item.active {
            background: #e8f5f3;
            color: #0E544C;
            border-left-color: #51B8AC;
            font-weight: 600;
        }
        
        /* ==================== TOOLTIP ==================== */
        .menu-group-title::before {
            content: attr(data-tooltip);
            position: absolute;
            left: 80px;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem !important;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 1001;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .menu-group-title::after {
            content: '';
            position: absolute;
            left: 70px;
            border: 5px solid transparent;
            border-right-color: #333;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .sidebar:not(:hover) .menu-group-title:hover::before,
        .sidebar:not(:hover) .menu-group-title:hover::after {
            opacity: 0.95;
            visibility: visible;
        }
        
        .sidebar:hover .menu-group-title::before,
        .sidebar:hover .menu-group-title::after {
            display: none;
        }
        
        /* ==================== BOTÓN TOGGLE MÓVIL ==================== */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1002;
            background: #51B8AC;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: #0E544C;
            transform: scale(1.05);
        }
        
        .menu-toggle:active {
            transform: scale(0.95);
        }
        
        .menu-toggle i {
            font-size: 1.2rem !important;
        }
        
        /* ==================== OVERLAY MÓVIL ==================== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* ==================== CONTENEDOR PRINCIPAL ==================== */
        .main-container {
            margin-left: 70px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }
        
        .contenedor-principal {
            width: 100%;
            margin: 0 auto;
            padding: 20px; /* Cambiar de 0 1px a 20px */
        }
        
        /* ==================== SCROLLBAR PERSONALIZADA ==================== */
        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #51B8AC;
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #0E544C;
        }
        
        /* Añade este CSS para los enlaces directos */
        .menu-group-title.direct-link {
            text-decoration: none;
            cursor: pointer;
        }
        
        .menu-group-title.direct-link:hover {
            background: #f8f9fa;
        }
        
        .menu-group-title.direct-link.active {
            background: #e8f5f3;
            border-right: 4px solid #51B8AC;
        }
        
        /* ==================== RESPONSIVE - MÓVIL ==================== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-70px);
                width: 70px;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                            width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .sidebar.show {
                transform: translateX(0);
                width: 260px;
            }
            
            /* Forzar expansión en móvil cuando está abierto */
            .sidebar.show .sidebar-header .logo {
                opacity: 1;
                transform: translateX(0);
            }
            
            .sidebar.show .menu-group-name {
                opacity: 1;
                transform: translateX(0);
            }
            
            .sidebar.show .chevron-icon {
                opacity: 1;
            }
            
            .sidebar.show .menu-item {
                padding-left: 80px;
            }
            
            /* Deshabilitar hover en móvil */
            .sidebar:hover {
                width: 70px;
            }
            
            .sidebar.show:hover {
                width: 260px;
            }
            
            /* Tooltips deshabilitados en móvil */
            .menu-group-title::before,
            .menu-group-title::after {
                display: none !important;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .main-container {
                margin-left: 0;
            }
            
        }
        
        /* ==================== ANIMACIONES ADICIONALES ==================== */
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .menu-item {
            animation: slideInFromLeft 0.3s ease-out;
        }
        
        /* ==================== ESTADOS DE CARGA ==================== */
        .sidebar.loading {
            pointer-events: none;
            opacity: 0.6;
        }
        
        /* ==================== MEJORAS VISUALES ==================== */
        .menu-group:last-child {
            border-bottom: none;
        }
        
        .menu-items:empty {
            display: none;
        }
        
        /* Efecto de resaltado al hacer click */
        .menu-item:active {
            background: #daf3f0;
            transform: scale(0.98);
        }
        
        /* ==================== ACCESIBILIDAD ==================== */
        .menu-group-title:focus,
        .menu-item:focus {
            outline: 2px solid #51B8AC;
            outline-offset: -2px;
        }
        
        /* ==================== SOPORTE PARA NAVEGADORES ==================== */
        @supports not (backdrop-filter: blur(10px)) {
            .sidebar-overlay {
                background: rgba(0,0,0,0.7);
            }
        }
    </style>
    
    <!-- Toggle del menú (móvil) -->
    <button class="menu-toggle" onclick="toggleSidebarMobile()" aria-label="Abrir menú">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para cerrar menú en móvil -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebarMobile()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo generarUrlModulo('../../assets/img/icon12.png'); ?>" alt="Batidos Pitaya" class="logo">
        </div>
        
        <?php foreach ($menuFiltrado as $index => $grupo): ?>
            <div class="menu-group" id="grupo-<?php echo $index; ?>">
                <?php if (!empty($grupo['items'])): ?>
                    <!-- Grupo con subitems (acordeón) -->
                    <div class="menu-group-title" 
                         onclick="toggleMenuGroup(<?php echo $index; ?>)"
                         data-tooltip="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                         role="button"
                         aria-expanded="false"
                         aria-controls="items-<?php echo $index; ?>">
                        <div class="menu-icon-wrapper">
                            <i class="<?php echo $grupo['icon']; ?>"></i>
                        </div>
                        <span class="menu-group-name"><?php echo htmlspecialchars($grupo['nombre']); ?></span>
                        <i class="fas fa-chevron-right chevron-icon"></i>
                    </div>
                    <div class="menu-items" id="items-<?php echo $index; ?>">
                        <?php foreach ($grupo['items'] as $item): ?>
                            <?php 
                            $isActive = '';

                            $urlFile = basename($item['url']);
                            if ($urlFile === $paginaActual) {
                                $isActive = 'active';
                            }

                            ?>
                            <a href="<?php echo htmlspecialchars(generarUrlModulo($item['url'])); ?>" 
                               class="menu-item <?php echo $isActive; ?>"
                               title="<?php echo htmlspecialchars($item['nombre']); ?>">
                                <?php echo htmlspecialchars($item['nombre']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Grupo sin subitems (enlace directo) -->
                    <?php 
                    $isActiveInicio = '';
                    if ($grupo['url'] && basename($grupo['url']) === $paginaActual) {
                        $isActiveInicio = 'active';
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars(generarUrlModulo($grupo['url'])); ?>" 
                       class="menu-group-title direct-link <?php echo $isActiveInicio; ?>"
                       data-tooltip="<?php echo htmlspecialchars($grupo['nombre']); ?>"
                       title="<?php echo htmlspecialchars($grupo['nombre']); ?>">
                        <div class="menu-icon-wrapper">
                            <i class="<?php echo $grupo['icon']; ?>"></i>
                        </div>
                        <span class="menu-group-name"><?php echo htmlspecialchars($grupo['nombre']); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- JavaScript del menú -->
    <script>
        (function() {
            'use strict';
            
            let activeGroupIndex = null;
            
            // Función para toggle de grupo (acordeón)
            window.toggleMenuGroup = function(index) {
                const grupo = document.getElementById('grupo-' + index);
                const allGroups = document.querySelectorAll('.menu-group');
                const titulo = grupo.querySelector('.menu-group-title');
                
                // Cerrar otros grupos
                allGroups.forEach((g, i) => {
                    if (i !== index) {
                        g.classList.remove('active');
                        const t = g.querySelector('.menu-group-title');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Toggle del grupo actual
                const isActive = grupo.classList.toggle('active');
                titulo.setAttribute('aria-expanded', isActive);
                activeGroupIndex = isActive ? index : null;
            };
            
            // Función para abrir sidebar en móvil
            window.toggleSidebarMobile = function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                
                // Actualizar aria-label
                const toggle = document.querySelector('.menu-toggle');
                if (sidebar.classList.contains('show')) {
                    toggle.setAttribute('aria-label', 'Cerrar menú');
                } else {
                    toggle.setAttribute('aria-label', 'Abrir menú');
                }
            };
            
            // Función para cerrar sidebar en móvil
            window.closeSidebarMobile = function() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                
                // Restaurar aria-label
                const toggle = document.querySelector('.menu-toggle');
                toggle.setAttribute('aria-label', 'Abrir menú');
                
                // Cerrar todos los grupos
                document.querySelectorAll('.menu-group').forEach(g => {
                    g.classList.remove('active');
                    const t = g.querySelector('.menu-group-title');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
                activeGroupIndex = null;
            };
            
            // Cerrar menú en móvil al hacer clic en un enlace
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebarMobile();
                    }
                });
            });
            
            // Marcar grupo activo si hay una página activa
            document.addEventListener('DOMContentLoaded', function() {
                const activeItem = document.querySelector('.menu-item.active');
                if (activeItem) {
                    const parentGroup = activeItem.closest('.menu-group');
                    if (parentGroup) {
                        parentGroup.classList.add('active');
                        const titulo = parentGroup.querySelector('.menu-group-title');
                        if (titulo) {
                            titulo.classList.add('active');
                            titulo.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
                
                // Marcar "Inicio" como activo si estamos en index.php
                const currentPage = window.location.pathname.split('/').pop();
                if (currentPage === 'index.php') {
                    const inicioLinks = document.querySelectorAll('.menu-group-title.direct-link');
                    inicioLinks.forEach(link => {
                        if (link.getAttribute('href') && link.getAttribute('href').includes('index.php')) {
                            link.classList.add('active');
                            link.closest('.menu-group').classList.add('active');
                        }
                    });
                }
            });
            
            // Prevenir scroll del body cuando el menú está abierto en móvil
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        if (sidebar.classList.contains('show')) {
                            document.body.style.overflow = 'hidden';
                        } else {
                            document.body.style.overflow = '';
                        }
                    }
                });
            });
            
            observer.observe(sidebar, { attributes: true });
            
            // Soporte para teclado (accesibilidad)
            document.addEventListener('keydown', function(e) {
                // ESC para cerrar menú en móvil
                if (e.key === 'Escape' && window.innerWidth <= 768) {
                    closeSidebarMobile();
                }
            });
            
        })();
    </script>
    
    <?php
    return ob_get_clean();
}
```

## /public_html/core/layout/header_universal.php
```php
<?php
/**
 * Header Universal para Módulos ERP
 * Incluir este archivo en cada página: require_once '../../includes/header_universal.php';
 * Uso: echo renderHeader($usuario, $esAdmin, 'Título de la Página');
 */

/**
 * Función para renderizar el header universal
 * @param array $usuario - Array con datos del usuario
 * @param bool $esAdmin - Si el usuario es administrador
 * @param string $titulo - Título de la página (opcional)
 * @return string HTML del header
 */
function renderHeader($usuario, $esAdmin = false, $titulo = '') {
    // Obtener cantidad de anuncios no leídos
    $cantidadAnunciosNoLeidos = 0;
    if (isset($_SESSION['usuario_id'])) {
        $cantidadAnunciosNoLeidos = obtenerCantidadAnunciosNoLeidos($_SESSION['usuario_id']);
    }
    
    // Obtener la URL de referencia para retroceder
    $paginaAnterior = $_SERVER['HTTP_REFERER'] ?? '';
    
    ob_start();
    ?>
        
        <!-- CSS COMPLETO del Header -->
        <style>
            /* ==================== HEADER BASE ==================== */
            .main-header {
                position: relative;
                display: flex;
                justify-content: flex-start;
                align-items: center;
                padding: 12px 20px;
                border-bottom: 2px solid #e0e0e0;
                margin-bottom: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                gap: 20px;
            }
            
            /* ==================== CONTENEDOR TÍTULO CON FLECHA ==================== */
            .header-title-container {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-right: auto;
                flex: 1;
                min-width: 0;
            }
            
            /* ==================== TÍTULO CENTRAL ==================== */
            .header-title {
                color: #0E544C;
                font-size: 1.2rem !important;
                font-weight: 600;
                margin: 0;
                text-align: left;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* ==================== TÍTULO DE BIENVENIDA (ALINEADO A LA IZQUIERDA) ==================== */
            .welcome-title {
                text-align: left;
                color: #0E544C;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* ==================== BOTÓN RETROCEDER CIRCULAR ==================== */
            .back-button-circle {
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                transition: all 0.3s ease;
                text-decoration: none;
                color: white;
                background-color: #51B8AC;
                border: none;
                font-weight: 600;
                font-size: 0.9rem !important;
                box-shadow: 0 2px 4px rgba(81, 184, 172, 0.2);
                flex-shrink: 0;
                white-space: nowrap;
                padding: 0;
            }
            
            .back-button-circle:hover {
                background-color: #3d9a8f;
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(81, 184, 172, 0.3);
            }
            
            .back-button-circle:active {
                transform: translateY(0);
            }
            
            .back-button-circle i {
                font-size: 1.2rem !important;
            }

            /* ==================== BOTÓN DE AYUDA ==================== */
            .help-button-circle {
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 26px;
                border-radius: 50%;
                transition: all 0.3s ease;
                text-decoration: none;
                color: white;
                background-color: #51B8AC;
                border: none;
                font-weight: 600;
                font-size: 0.9rem !important;
                box-shadow: 0 2px 4px rgba(81, 184, 172, 0.2);
                flex-shrink: 0;
                white-space: nowrap;
                padding: 0;
            }
            
            .help-button-circle:hover {
                background-color: #3d9a8f;
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(81, 184, 172, 0.4);
            }
            
            .help-button-circle:active {
                transform: translateY(0);
            }
            
            .help-button-circle i {
                font-size: 1.0rem !important;
            }
            
            /* ==================== NOTIFICACIONES ==================== */
            .notification-bell {
                position: relative;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                border-radius: 8px;
                transition: all 0.3s ease;
                text-decoration: none;
                color: inherit;
                margin-left: auto;
            }
            
            .notification-bell:hover {
                background: #f8f9fa;
            }
            
            .bell-icon {
                font-size: 1.3rem !important;
                color: #666;
                transition: all 0.3s ease;
            }
            
            .notification-bell.has-notifications .bell-icon {
                color: #ffc107;
                animation: ring 2s ease-in-out infinite;
            }
            
            @keyframes ring {
                0%, 100% { transform: rotate(0deg); }
                10%, 30% { transform: rotate(-10deg); }
                20%, 40% { transform: rotate(10deg); }
            }
            
            .notification-badge {
                position: absolute;
                top: 2px;
                right: 8px;
                background: #dc3545;
                color: white;
                font-size: 0.7rem !important;
                font-weight: bold;
                padding: 2px 5px;
                border-radius: 10px;
                min-width: 18px;
                text-align: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            .notification-text {
                position: relative;
                font-size: 0.85rem !important;
                color: #666;
                white-space: nowrap;
                background: #ffc107;
                color: #333;
                padding: 4px 12px 4px 10px;
                border-radius: 4px 0 0 4px;
                font-weight: 600;
                margin-right: -12px;
            }
            
            .notification-text::after {
                content: '';
                position: absolute;
                right: -8px;
                top: 50%;
                transform: translateY(-50%);
                width: 0;
                height: 0;
                border-style: solid;
                border-width: 12px 0 12px 8px;
                border-color: transparent transparent transparent #ffc107;
            }
            
            /* ==================== USER INFO ==================== */
            .user-info {
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 1;
            }
            
            .user-avatar {
                width: 45px;
                height: 45px;
                min-width: 45px;
                border-radius: 50%;
                background-color: #51B8AC;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 1.2rem !important;
                box-shadow: 0 2px 8px rgba(81, 184, 172, 0.3);
                text-transform: uppercase;
                overflow: hidden;
            }
            
            .user-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .user-details {
                display: flex;
                flex-direction: column;
                gap: 2px;
                text-align: left;
            }
            
            .user-name {
                font-weight: 600;
                color: #0E544C;
                font-size: 0.95rem !important;
                white-space: nowrap;
            }
            
            .user-role {
                color: #0E544C;
                font-size: 0.85rem !important;
                white-space: nowrap;
            }
            
            /* ==================== RESPONSIVE ==================== */
            @media (max-width: 768px) {
                .main-header {
                    justify-content: flex-start;
                    padding: 12px 15px;
                    flex-wrap: wrap;
                    gap: 15px;
                }
                
                .header-title-container {
                    gap: 10px;
                    order: 1;
                    flex: 0 0 100%;
                    margin-bottom: 10px;
                }
                
                .header-title {
                    font-size: 0.95rem !important;
                    flex: 1;
                    line-height: 1.3;
                    white-space: normal;
                    word-wrap: break-word;
                }
                
                .welcome-title {
                    font-size: 0.95rem !important;
                    flex: 1;
                    line-height: 1.3;
                    white-space: normal;
                    word-wrap: break-word;
                }
                
                .back-button-circle {
                    width: 36px;
                    height: 36px;
                    order: 0;
                }
                
                .back-button-circle i {
                    font-size: 1rem !important;
                }

                .help-button-circle {
                    width: 24px;
                    height: 24px;
                }

                .help-button-circle i {
                    font-size: 0.85rem !important;
                }
                
                .notification-bell {
                    padding: 6px 8px;
                    gap: 5px;
                    order: 2;
                    margin-left: 0;
                }
                
                .bell-icon {
                    font-size: 1.2rem !important;
                }
                
                .notification-text {
                    display: none;
                }
                
                .notification-badge {
                    top: 0;
                    right: 4px;
                }
                
                .user-info {
                    gap: 10px;
                    order: 3;
                    margin-left: auto;
                }
                
                .user-avatar {
                    width: 40px;
                    height: 40px;
                    min-width: 40px;
                    font-size: 1.1rem !important;
                }
                
                .user-details {
                    display: none;
                }
            }
            
            @media (max-width: 480px) {
                .header-title {
                    font-size: 0.85rem !important;
                }
                
                .welcome-title {
                    font-size: 0.85rem !important;
                }
                
                .back-button-circle {
                    width: 34px;
                    height: 34px;
                }
                
                .bell-icon {
                    font-size: 1.1rem !important;
                }
                
                .notification-badge {
                    font-size: 0.65rem !important;
                    padding: 1px 4px;
                    min-width: 16px;
                }
                
                .user-avatar {
                    width: 38px;
                    height: 38px;
                    min-width: 38px;
                    font-size: 1rem !important;
                }
            }
            
            /* ==================== ANIMACIONES ==================== */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .main-header {
                animation: fadeIn 0.3s ease-out;
            }
        </style>
        
        <!-- Header HTML -->
        <header class="main-header">
            <div class="header-title-container">
                <!-- Botón circular para retroceder -->
                <?php if (!empty($paginaAnterior) && parse_url($paginaAnterior, PHP_URL_HOST) === $_SERVER['HTTP_HOST'] && basename($_SERVER['PHP_SELF']) !== 'index.php'): ?>
                    <button class="back-button-circle" onclick="window.history.back()" title="Volver a la página anterior">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                <?php endif; ?>
                
                <?php if (!empty($titulo)): ?>
                    <h1 class="header-title"><?php echo htmlspecialchars($titulo); ?></h1>
                <?php else: ?>

                    <h1 class="header-title welcome-title">
                        ¡<?= 
                            (isset($usuario['Genero']) && strtoupper($usuario['Genero']) === 'F') 
                                ? 'Bienvenida' 
                                : (isset($usuario['Genero']) && strtoupper($usuario['Genero']) === 'M' 
                                    ? 'Bienvenido' 
                                    : 'Bienvenid@')
                        ?> <?= $esAdmin ? 
                            htmlspecialchars($usuario['nombre']) : 
                            htmlspecialchars($usuario['Nombre']) ?>!
                    </h1>
                
                <?php endif; ?>

                <!-- Botón de Ayuda Universal -->
                <button id="mainPageHelpBtn" class="help-button-circle" onclick="openPageHelp()" title="Ayuda y documentación de esta página" style="display: none;">
                    <i class="fas fa-info"></i>
                </button>
            </div>
            
            <!-- Notificaciones -->
            <div class="notification-bell <?= $cantidadAnunciosNoLeidos > 0 ? 'has-notifications' : '' ?>" 
                 id="notificationBell"
                 onclick="irAAnuncios()"
                 title="<?= $cantidadAnunciosNoLeidos > 0 ? $cantidadAnunciosNoLeidos . ' anuncio(s) pendiente(s)' : 'Sin anuncios nuevos' ?>">
                <?php if ($cantidadAnunciosNoLeidos > 0): ?>
                    <span class="notification-text">Anuncios por Revisar</span>
                <?php endif; ?>
                <span></span>
                <span></span>
                <i class="fas fa-bell bell-icon"></i>
                <span></span>
                <span class="notification-badge" id="notificationBadge"><?= $cantidadAnunciosNoLeidos ?></span>
            </div>
            
            <div class="user-info">
                <div class="user-avatar" title="<?php echo $esAdmin ? htmlspecialchars($usuario['Nombre']) : htmlspecialchars($usuario['Nombre'].' '.$usuario['Apellido']); ?>">
                    <?php 
                    // Verificar si existe foto de perfil
                    $fotoPerfil = $esAdmin ? ($usuario['foto_perfil'] ?? null) : ($usuario['foto_perfil'] ?? null);
                    
                    if (!empty($fotoPerfil) && file_exists('../../' . $fotoPerfil)):
                    ?>
                        <img src="../../<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?= $esAdmin ? 
                            strtoupper(substr($usuario['nombre'], 0, 1)) : 
                            strtoupper(substr($usuario['Nombre'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name">
                       <?= $esAdmin ? 
                        htmlspecialchars($usuario['nombre']) : 
                        htmlspecialchars($usuario['Nombre'].' '.$usuario['Apellido']) ?>
                    </div>
                    <small class="user-role">
                        <?= $esAdmin ? 
                            'Administrador' : 
                            htmlspecialchars($usuario['cargo_nombre'] ?? 'Sin cargo definido') ?>
                    </small>
                </div>
            </div>
        </header>
        
       <!-- JavaScript para notificaciones -->
        <script>
            // Obtener la URL base del sitio
            function getBaseUrl() {
                return window.location.protocol + '//' + window.location.host;
            }
            
            function irAAnuncios() {
                const baseUrl = getBaseUrl();
                
                // URL para marcar anuncios como leídos
                const marcarLeidosUrl = baseUrl + '/modulos/supervision/auditorias_original/marcar_anuncios_leidos.php';
                
                // URL para ir a anuncios
                const anunciosUrl = baseUrl + '/modulos/supervision/auditorias_original/index_avisos_publico.php';
                
                // Marcar anuncios como leídos
                fetch(marcarLeidosUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar el badge localmente
                            const badge = document.getElementById('notificationBadge');
                            const bell = document.getElementById('notificationBell');
                            if (badge) badge.remove();
                            if (bell) bell.classList.remove('has-notifications');
                            
                            // Remover el texto "Pendientes"
                            const notifText = bell.querySelector('.notification-text');
                            if (notifText) notifText.remove();
                        }
                        // Redirigir a anuncios
                        window.location.href = anunciosUrl;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Redirigir incluso si hay error
                        window.location.href = anunciosUrl;
                    });
            }
        </script>
        
    <?php
    return ob_get_clean();
}

/**
 * Función para obtener la URL base del sitio dinámicamente
 * @return string URL base (ej: https://erp.batidospitaya.com)
 */
function getBaseUrl() {
    // Determinar el protocolo (http o https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    
    // Obtener el host
    $host = $_SERVER['HTTP_HOST'];
    
    // Si estás detrás de un proxy, podrías necesitar ajustar esto
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    }
    
    // Construir la URL base
    $baseUrl = $protocol . '://' . $host;
    
    // Opcional: Si tu sitio está en un subdirectorio, agregarlo
    // Ejemplo: si está en /erp/, descomenta la siguiente línea
    // $baseUrl .= '/erp';
    
    return $baseUrl;
}
?>
```

## /public_html/core/helpers/funciones.php
```php
<?php
/**
 * Formatea una fecha al formato ej: 31-abr-25
 */
function formatoFecha($fecha) {
    if (empty($fecha) || $fecha === null) {
        return '';
    }
    
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    
    try {
        $fechaObj = new DateTime($fecha);
        $mes = $meses[(int)$fechaObj->format('m') - 1];
        return $fechaObj->format('d') . '-' . $mes . '-' . $fechaObj->format('y');
    } catch (Exception $e) {
        // Si hay error al parsear la fecha, devolver string vacío
        return '';
    }
}

/**
 * Obtiene el nombre del mes en español
 */
function obtenerMesEspanol($fecha) {
    $meses = [
        'January' => 'Enero',
        'February' => 'Febrero', 
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ];
    
    $mesIngles = $fecha->format('F');
    return $meses[$mesIngles] ?? $mesIngles;
}

function formatoMesAnio($fecha) {
    if (empty($fecha)) return '';
    
    setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
    $fechaObj = new DateTime($fecha);
    
    return ucfirst(strftime('%B %Y', $fechaObj->getTimestamp()));
}

/**
 * Verifica si un usuario tiene un cargo específico
 */
function tieneCargo($cargoRequerido) {
    if (!isset($_SESSION['cargo_cod'])) {
        return false;
    }
    
    // Los cargos pueden tener jerarquía si es necesario
    $jerarquia = [
        'gerencia' => 16,
        'jefe' => [8,9,10,11,12,13,14,15,17,19,21,22,26],
        // ... definir según necesidades
    ];
    
    // Caso especial para admin
    if ($_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    // Verificar cargo específico
    return $_SESSION['cargo_cod'] == $cargoRequerido || 
           (is_array($cargoRequerido) && in_array($_SESSION['cargo_cod'], $cargoRequerido));
}

/**
 * Redirige si no tiene permiso
 */
function requerirCargo($cargoRequerido) {
    if (!tieneCargo($cargoRequerido)) {
        header('Location: /index.php');
        exit();
    }
}

/**
 * Redirige a la página de inicio según el cargo
 */
function redirigirSegunCargo() {
    // Si es admin, va al index principal
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        header("Location: /index.php");
        exit();
    }

    // Si no tiene cargo definido, va al index principal
    if (!isset($_SESSION['cargo_cod'])) {
        header("Location: /index.php");
        exit();
    }

    // Obtener todos los cargos activos del usuario
    $cargosUsuario = obtenerCargosUsuario($_SESSION['usuario_id']);

    // Mapeo de cargos a rutas
    $redirecciones = [
        2 => '/modulos/operarios/',      // Operario
        5 => '/modulos/lideres/',        // Líder de Sucursal
        8 => '/modulos/contabilidad/',   // Jefe de Contabilidad
        9 => '/modulos/compras/',        // Jefe de Compras
        10 => '/modulos/logistica/',     // Jefe de Logística
        11 => '/modulos/operaciones/',   // Jefe de Operaciones
        12 => '/modulos/produccion/',    // Jefe de Producción
        13 => '/modulos/rh/',            // Jefe de Recursos Humanos
        14 => '/modulos/mantenimiento/', // Jefe de Mantenimiento
        15 => '/modulos/sistemas/',      // Jefe de Sistemas
        16 => '/modulos/gerencia/',      // Gerencia
        17 => '/modulos/almacen/',       // Jefe de Almacén
        19 => '/modulos/cds/',           // Jefe de CDS
        20 => '/modulos/chofer/',        // Chofer
        21 => '/modulos/supervision/',   // Supervisor de Sucursales
        22 => '/modulos/atencioncliente/', // Atencion al Cliente
        23 => '/modulos/almacen/',       // Auxiliar de Almacen
        24 => '/modulos/motorizado/',    // Motorizado
        25 => '/modulos/diseno/',        // Diseñador
        26 => '/modulos/marketing/',     // Jefe de Marketing
        27 => '/modulos/sucursales/'     // Sucursales
    ];

    // Ordenar los cargos para priorizar los que no son 2 (Operario)
    usort($cargosUsuario, function($a, $b) {
        // Si ambos son 2 o ambos no son 2, mantener el orden original
        if (($a == 2 && $b == 2) || ($a != 2 && $b != 2)) {
            return 0;
        }
        // Priorizar el que no es 2
        return ($a == 2) ? 1 : -1;
    });

    // Buscar el primer cargo que tenga un módulo asignado
    foreach ($cargosUsuario as $cargoCod) {
        if (array_key_exists($cargoCod, $redirecciones)) {
            $destino = $redirecciones[$cargoCod];
            
            // Verificar que el archivo del módulo exista
            $rutaArchivo = $_SERVER['DOCUMENT_ROOT'] . $destino . 'index.php';
            if (file_exists($rutaArchivo)) {
                header("Location: $destino");
                exit();
            }
        }
    }

    // Si no se encontró un módulo válido, redirigir al inicio
    header("Location: /index.php");
    exit();
}

/**
 * Verifica si el usuario tiene alguno de los cargos especificados
 */
function verificarAccesoCargo($cargosRequeridos) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    // Si es admin, tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    global $conn;
    $cargosRequeridos = (array)$cargosRequeridos;
    $placeholders = implode(',', array_fill(0, count($cargosRequeridos), '?'));
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tiene_cargo 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND CodNivelesCargos IN ($placeholders)
        AND (Fin IS NULL OR Fin >= NOW())
    ");
    
    $params = array_merge([$_SESSION['usuario_id']], $cargosRequeridos);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['tiene_cargo'] > 0;
}

/**
 * Obtiene el nombre completo del usuario (para mostrar en la interfaz)
 */
function obtenerNombreUsuario() {
    $usuario = obtenerUsuarioActual();
    
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return $usuario['nombre'];
    } else {
        return $usuario['Nombre'].' '.$usuario['Apellido'];
    }
}

/**
 * Verifica si el usuario tiene un cargo específico y está asignado a una sucursal específica
 */
function verificarAccesoSucursalCargo($cargosRequeridos, $sucursalesRequeridas) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    // Si es admin, tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    global $conn;
    $cargosRequeridos = (array)$cargosRequeridos;
    $sucursalesRequeridas = (array)$sucursalesRequeridas;
    
    // Preparamos los placeholders para los IN clauses
    $cargosPlaceholders = implode(',', array_fill(0, count($cargosRequeridos), '?'));
    $sucursalesPlaceholders = implode(',', array_fill(0, count($sucursalesRequeridas), '?'));
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tiene_acceso 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND CodNivelesCargos IN ($cargosPlaceholders)
        AND Sucursal IN ($sucursalesPlaceholders)
        AND (Fin IS NULL OR Fin >= NOW())
    ");
    
    // Combinamos los parámetros
    $params = array_merge(
        [$_SESSION['usuario_id']],
        $cargosRequeridos,
        $sucursalesRequeridas
    );
    
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['tiene_acceso'] > 0;
}

/**
 * Obtiene la semana actual del sistema
 */
function obtenerSemanaActual() {
    global $conn;
    
    $hoy = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM SemanasSistema 
        WHERE fecha_inicio <= ? AND fecha_fin >= ?
        LIMIT 1
    ");
    $stmt->execute([$hoy, $hoy]);
    return $stmt->fetch();
}

/**
 * Obtiene una semana por su ID
 */
function obtenerSemanaPorId($id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM SemanasSistema 
        WHERE numero_semana = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Obtiene las semanas disponibles para programación
 */
function obtenerSemanasDisponibles() {
    global $conn;
    
    $hoy = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT * FROM SemanasSistema 
        WHERE fecha_inicio >= ?
        ORDER BY fecha_inicio ASC
        LIMIT 10
    ");
    $stmt->execute([$hoy]);
    return $stmt->fetchAll();
}

/**
 * Obtiene las sucursales asignadas a un líder
 * MODIFICADA: Prioriza la primera sucursal encontrada
 */
function obtenerSucursalesLider($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT s.codigo, s.nombre 
        FROM AsignacionNivelesCargos anc
        JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE anc.CodOperario = ? 
        AND (anc.CodNivelesCargos = 5 OR anc.CodNivelesCargos = 43) 
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND s.activa = 1
        ORDER BY anc.Fecha ASC, s.nombre
        LIMIT 1  -- Solo la primera sucursal
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetchAll();
}

/**
 * Obtiene los operarios activos de una sucursal para un rango de fechas
 * ACTUALIZADA: Devuelve todos los campos necesarios para la tabla
 */
function obtenerOperariosSucursal($codSucursal, $fechaInicio, $fechaFin) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            o.CodOperario, 
            o.Nombre, 
            o.Nombre2,
            o.Apellido, 
            o.Apellido2,
            NULL as total_horas,  -- Campo adicional para compatibilidad
            NULL as cod_contrato  -- Campo adicional para compatibilidad
        FROM Operarios o
        INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.Sucursal = ?
        AND o.Operativo = 1
        -- Solo validamos fechas en AsignacionNivelesCargos
        AND (anc.Fin IS NULL OR anc.Fin >= ?)
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= ?)
        )
        GROUP BY o.CodOperario, o.Nombre, o.Apellido
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal, $fechaFin, $fechaFin]);
    return $stmt->fetchAll();
}

/**
 * Obtiene TODOS los operarios que tienen horario guardado para una semana/sucursal
 * ACTUALIZADA: Incluye cod_contrato
 */
function obtenerOperariosSucursalConHorario($codSucursal, $idSemana) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            o.CodOperario, 
            o.Nombre, 
            o.Apellido, 
            o.Apellido2,
            hs.total_horas,
            hs.cod_contrato
        FROM Operarios o
        INNER JOIN HorariosSemanales hs ON o.CodOperario = hs.cod_operario
        INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE hs.cod_sucursal = ?
        AND hs.id_semana_sistema = ?
        AND o.Operativo = 1
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        -- AND o.CodOperario NOT IN (566, 567, 568, 569, 570, 571, 572, 573, 574, 575, 576, 590)
        GROUP BY o.CodOperario
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal, $idSemana]);
    return $stmt->fetchAll();
}

/**
 * Obtiene el horario de un operario para una semana y sucursal específica
 * ACTUALIZADA: Incluye cod_contrato
 */
function obtenerHorarioOperario($codOperario, $numeroSemana, $codSucursal) {
    global $conn;
    
    // Primero obtener el ID de la semana
    $semana = obtenerSemanaPorNumero($numeroSemana);
    if (!$semana) return null;
    
    $stmt = $conn->prepare("
        SELECT *, cod_contrato 
        FROM HorariosSemanales
        WHERE cod_operario = ? 
        AND id_semana_sistema = ? 
        AND cod_sucursal = ?
        LIMIT 1
    ");
    $stmt->execute([$codOperario, $semana['id'], $codSucursal]);
    return $stmt->fetch();
}

/**
 * Obtiene una semana por su número de semana
 */
function obtenerSemanaPorNumero($numeroSemana) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM SemanasSistema 
        WHERE numero_semana = ?
        LIMIT 1
    ");
    $stmt->execute([$numeroSemana]);
    return $stmt->fetch();
}

/**
 * Formatea una fecha al formato ej: 31-abr-25 (día-mes-año)
 * MEJORADA: Maneja valores nulos y formatos inválidos
 */
function formatoFechaCorta($fecha) {
    // Si la fecha está vacía o es nula, retornar string vacío
    if (empty($fecha) || $fecha === null || $fecha === '0000-00-00') {
        return '';
    }
    
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    
    try {
        // Verificar si la fecha tiene un formato válido
        $timestamp = strtotime($fecha);
        if ($timestamp === false) {
            return '';
        }
        
        $fechaObj = new DateTime($fecha);
        $mes = $meses[(int)$fechaObj->format('m') - 1];
        return $fechaObj->format('d') . '-' . $mes . '-' . $fechaObj->format('y');
    } catch (Exception $e) {
        // Si hay error al parsear la fecha, devolver string vacío
        error_log("Error formateando fecha: " . $e->getMessage() . " - Fecha: " . $fecha);
        return '';
    }
}

/**
 * Formatea una hora al formato 12 horas con AM/PM
 */
function formatoHoraAmPm($hora) {
    if (empty($hora) || $hora == '00:00:00') {
        return '-';
    }
    return date('h:i A', strtotime($hora));
}

/**
 * Obtiene la semana del sistema para una fecha específica
 */
function obtenerSemanaPorFecha($fecha) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM SemanasSistema 
        WHERE fecha_inicio <= ? AND fecha_fin >= ?
        LIMIT 1
    ");
    $stmt->execute([$fecha, $fecha]);
    return $stmt->fetch();
}

/**
 * Obtiene el horario programado para un operario en una semana, sucursal y día específico
 */
function obtenerHorarioOperacionesPorDia($codOperario, $idSemana, $codSucursal, $fecha) {
    global $conn;
    
    // Primero obtener el día de la semana (0=domingo, 1=lunes, etc.)
    $stmt = $conn->prepare("SELECT DAYOFWEEK(?) as dia_semana");
    $stmt->execute([$fecha]);
    $diaSemana = $stmt->fetch()['dia_semana'];
    
    // Ajustar a nuestro sistema donde 1=lunes, 7=domingo
    $diaSemana = $diaSemana - 1;
    if ($diaSemana == 0) $diaSemana = 7;
    
    // Mapear a los nombres de columna
    $dias = [
        1 => 'lunes',
        2 => 'martes',
        3 => 'miercoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sabado',
        7 => 'domingo'
    ];
    $diaColumna = $dias[$diaSemana];
    
    // Obtener el horario para ese día específico
    $stmt = $conn->prepare("
        SELECT 
            {$diaColumna}_estado as estado,
            {$diaColumna}_entrada as hora_entrada,
            {$diaColumna}_salida as hora_salida
        FROM HorariosSemanalesOperaciones
        WHERE cod_operario = ? 
        AND id_semana_sistema = ? 
        AND cod_sucursal = ?
        LIMIT 1
    ");
    $stmt->execute([$codOperario, $idSemana, $codSucursal]);
    return $stmt->fetch();
}

/**
 * Obtiene todas las sucursales activas del sistema
 */
function obtenerTodasSucursales() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, codigo, nombre, cod_departamento, departamento
        FROM sucursales 
        WHERE activa = 1
        ORDER BY nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene solo las sucursales físicas (donde sucursal = 1), porque hay sucursales en la tabla de la bd sucursales donde no son sucursales en sí y se identifica con el valor 1
 */
function obtenerSucursalesFisicas() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, codigo, nombre, cod_departamento, departamento
        FROM sucursales 
        WHERE activa = 1 AND sucursal = 1
        ORDER BY nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene la IP permitida para la sucursal del usuario actual
 */
function obtenerIpPermitidaSucursal($codSucursal) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT ip_direccion FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();
    
    return $result['ip_direccion'] ?? null;
}

/**
 * Verifica si la IP actual coincide con la IP permitida para la sucursal o cualquier sucursal del mismo departamento
 */
function verificarIpSucursal($codSucursal) {
    $ipPermitida = obtenerIpPermitidaSucursal($codSucursal);
    
    // Si no hay IP definida para la sucursal, permitir acceso
    if (empty($ipPermitida)) {
        return true;
    }
    
    $ipCliente = obtenerIpCliente();
    
    // Primero verificar si coincide con la IP de la sucursal asignada
    if ($ipCliente === $ipPermitida) {
        return true;
    }
    
    // Si no coincide, verificar si pertenece al mismo departamento
    $codDepartamento = obtenerCodigoDepartamentoSucursal($codSucursal);
    if ($codDepartamento === null) {
        return false;
    }
    
    // Obtener todas las IPs de sucursales del mismo departamento
    global $conn;
    $stmt = $conn->prepare("
        SELECT ip_direccion 
        FROM sucursales 
        WHERE cod_departamento = ? 
        AND ip_direccion IS NOT NULL
    ");
    $stmt->execute([$codDepartamento]);
    $ipsDepartamento = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Verificar si la IP del cliente está en alguna de las IPs del departamento
    return in_array($ipCliente, $ipsDepartamento);
}

/**
 * Obtiene la IP del cliente
 */
function obtenerIpCliente() {
    //if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    //    return $_SERVER['HTTP_CLIENT_IP'];
    //} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    //    return $_SERVER['HTTP_X_FORWARDED_FOR'];
    //} else {
        return $_SERVER['REMOTE_ADDR'];
    //}
}

/**
 * Obtiene las sucursales asignadas a un usuario (no necesariamente líder)
 */
function obtenerSucursalesUsuario($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT s.codigo, s.nombre 
        FROM AsignacionNivelesCargos anc
        JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE anc.CodOperario = ? 
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND s.activa = 1
        ORDER BY s.nombre
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetchAll();
}

/**
 * Obtiene el nombre de una sucursal por su código
 */
function obtenerNombreSucursal($codSucursal) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT nombre FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();
    
    return $result['nombre'] ?? 'Desconocida';
}

/**
 * Verifica si el operario tuvo una omisión de marcación el día anterior
 */
function verificarOmisionDiaAnterior($codOperario, $sucursalCodigo) {
    global $conn;
    
    $ayer = date('Y-m-d', strtotime('-1 day'));
    
    $sql = "SELECT 
                COUNT(*) as total_marcaciones,
                MAX(hora_salida) as tiene_salida
            FROM marcaciones 
            WHERE CodOperario = ? 
            AND sucursal_codigo = ?
            AND fecha = ?";
    
    $stmt = ejecutarConsulta($sql, [$codOperario, $sucursalCodigo, $ayer]);
    
    if ($stmt && $resultado = $stmt->fetch()) {
        // Omisión si no hay marcaciones o si no tiene salida
        return $resultado['total_marcaciones'] == 0 || $resultado['tiene_salida'] === null;
    }
    
    return false;
}

/**
 * Obtiene el código de departamento de una sucursal (usando la nueva estructura)
 */
function obtenerCodigoDepartamentoSucursal($codSucursal) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT cod_departamento FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();
    
    return $result['cod_departamento'] ?? null;
}

/**
 * Obtiene los operarios activos de una sucursal para un líder específico
 */
function obtenerOperariosSucursalLider($codSucursal, $codLider) {
    global $conn;
    
    // Verificar que el líder tenga asignada esta sucursal
    $stmt = $conn->prepare("
        SELECT COUNT(*) as es_lider 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND (CodNivelesCargos = 5 OR CodNivelesCargos = 43) 
        AND Sucursal = ?
        AND (Fin IS NULL OR Fin >= CURDATE())
    ");
    $stmt->execute([$codLider, $codSucursal]);
    $result = $stmt->fetch();
    
    if (!$result || $result['es_lider'] == 0) {
        return [];
    }
    
    // Obtener operarios de la sucursal EXCLUYENDO los con cargo 27
    $stmt = $conn->prepare("
        SELECT o.CodOperario, o.Nombre, o.Apellido, o.Apellido2 
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.Sucursal = ?
        AND o.Operativo = 1
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        GROUP BY o.CodOperario, o.Nombre, o.Apellido
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal]);
    return $stmt->fetchAll();
}

/**
 * Obtiene los operarios activos de una sucursal (para líder o RH)
 */
function obtenerOperariosSucursalParaFaltas($codSucursal, $codUsuario = null) {
    global $conn;
    
    // Si se proporciona un código de usuario, verificar si es líder de esa sucursal
    if ($codUsuario) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as es_lider 
            FROM AsignacionNivelesCargos 
            WHERE CodOperario = ? 
            AND (CodNivelesCargos = 5 OR CodNivelesCargos = 43) 
            AND Sucursal = ?
            AND (Fin IS NULL OR Fin >= CURDATE())
        ");
        $stmt->execute([$codUsuario, $codSucursal]);
        $result = $stmt->fetch();
        
        // Si no es líder y no es RH, devolver array vacío
        if ((!$result || $result['es_lider'] == 0) && !verificarAccesoCargo([13, 39, 30,37,28])) {
            return [];
        }
    }
    
    // Obtener operarios de la sucursal EXCLUYENDO los con cargo 27
    $stmt = $conn->prepare("
        SELECT o.CodOperario, o.Nombre, o.Apellido, o.Apellido2 
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.Sucursal = ?
        AND o.Operativo = 1
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        GROUP BY o.CodOperario, o.Nombre, o.Apellido
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal]);
    return $stmt->fetchAll();
}

/**
 * Obtiene el horario programado de un operario para una fecha específica
 */
function obtenerHorarioProgramadoFaltaManual($codOperario, $codSucursal, $fecha) {
    global $conn;
    
    // Primero obtener el día de la semana (1=lunes, 2=martes, ..., 7=domingo)
    $stmt = $conn->prepare("SELECT DAYOFWEEK(?) as dia_semana");
    $stmt->execute([$fecha]);
    $diaSemana = $stmt->fetch()['dia_semana'];
    
    // Ajustar a nuestro sistema donde 1=lunes, 7=domingo
    $diaSemana = $diaSemana - 1;
    if ($diaSemana == 0) $diaSemana = 7;
    
    // Mapear a los nombres de columna
    $dias = [
        1 => 'lunes',
        2 => 'martes',
        3 => 'miercoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sabado',
        7 => 'domingo'
    ];
    $diaColumna = $dias[$diaSemana];
    
    // Obtener el horario para ese día específico
    $stmt = $conn->prepare("
        SELECT 
            hs.{$diaColumna}_entrada as hora_entrada_programada,
            hs.{$diaColumna}_salida as hora_salida_programada
        FROM HorariosSemanalesOperaciones hs
        JOIN SemanasSistema ss ON hs.id_semana_sistema = ss.id
        WHERE hs.cod_operario = ? 
        AND hs.cod_sucursal = ?
        AND ss.fecha_inicio <= ? 
        AND ss.fecha_fin >= ?
        LIMIT 1
    ");
    $stmt->execute([$codOperario, $codSucursal, $fecha, $fecha]);
    
    return $stmt->fetch() ?: null;
}

/**
 * Obtiene las marcaciones de un operario en una fecha específica
 */
function obtenerMarcaciones($codOperario, $codSucursal, $fecha) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT hora_ingreso, hora_salida 
        FROM marcaciones 
        WHERE CodOperario = ? 
        AND sucursal_codigo = ?
        AND fecha = ?
        LIMIT 1
    ");
    $stmt->execute([$codOperario, $codSucursal, $fecha]);
    
    return $stmt->fetch() ?: null;
}
/**
 * Obtiene los nombres completos de operarios/colaboradores en bd por el código del operario
 */
function obtenerDatosCompletosOperario($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT Nombre, Nombre2, Apellido, Apellido2 
        FROM Operarios 
        WHERE CodOperario = ?
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetch();
}

/**
 * Obtiene el nombre completo formateado de un operario
 */
function obtenerNombreCompletoOperario($operario) {
    $nombre = trim($operario['Nombre'] ?? '');
    $nombre2 = trim($operario['Nombre2'] ?? '');
    $apellido = trim($operario['Apellido'] ?? '');
    $apellido2 = trim($operario['Apellido2'] ?? '');
    
    $nombreCompleto = $nombre;
    if (!empty($nombre2)) {
        $nombreCompleto .= ' ' . $nombre2;
    }
    if (!empty($apellido)) {
        $nombreCompleto .= ' ' . $apellido;
    }
    if (!empty($apellido2)) {
        $nombreCompleto .= ' ' . $apellido2;
    }
    
    return $nombreCompleto;
}

/**
 * Obtiene el cargo principal de un usuario (priorizando códigos diferentes a 2)
 */
function obtenerCargoPrincipalUsuario($codOperario) {
    global $conn;
    
    // Primero intentamos obtener un cargo que no sea 2 (Operario)
    $stmt = $conn->prepare("
        SELECT nc.Nombre as cargo_nombre 
        FROM AsignacionNivelesCargos anc
        JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
        WHERE anc.CodOperario = ? 
        AND anc.CodNivelesCargos != 2
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        ORDER BY anc.CodNivelesCargos DESC
        LIMIT 1
    ");
    $stmt->execute([$codOperario]);
    $result = $stmt->fetch();
    
    // Si no encontró otro cargo, buscamos cualquier cargo (incluyendo Operario)
    if (!$result) {
        $stmt = $conn->prepare("
            SELECT nc.Nombre as cargo_nombre 
            FROM AsignacionNivelesCargos anc
            JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
            WHERE anc.CodOperario = ? 
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            LIMIT 1
        ");
        $stmt->execute([$codOperario]);
        $result = $stmt->fetch();
    }
    
    return $result['cargo_nombre'] ?? 'Sin cargo definido';
}

/**
 * Obtiene el código del cargo principal de un usuario (priorizando códigos diferentes a 2)
 */
function obtenerCargoCodigoPrincipalUsuario($codOperario) {
    global $conn;
    
    // Primero intentamos obtener un cargo que no sea 2 (Operario)
    $stmt = $conn->prepare("
        SELECT anc.CodNivelesCargos as cargo_codigo
        FROM AsignacionNivelesCargos anc
        WHERE anc.CodOperario = ? 
        AND anc.CodNivelesCargos != 2
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        ORDER BY anc.CodNivelesCargos DESC
        LIMIT 1
    ");
    $stmt->execute([$codOperario]);
    $result = $stmt->fetch();
    
    // Si no encontró otro cargo, buscamos cualquier cargo (incluyendo Operario)
    if (!$result) {
        $stmt = $conn->prepare("
            SELECT anc.CodNivelesCargos as cargo_codigo
            FROM AsignacionNivelesCargos anc
            WHERE anc.CodOperario = ? 
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            LIMIT 1
        ");
        $stmt->execute([$codOperario]);
        $result = $stmt->fetch();
    }
    
    return $result['cargo_codigo'] ?? null;
}

/**
 * Obtiene todos los cargos activos de un usuario
 */
function obtenerCargosUsuario($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT CodNivelesCargos 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND (Fin IS NULL OR Fin >= CURDATE())
        ORDER BY CodNivelesCargos ASC
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Determina la quincena basada en el día del mes (1-15: primera, 16-31: segunda)
 * @param string $fecha Fecha a evaluar (Y-m-d)
 * @return string 'primera' o 'segunda'
 */
function determinarQuincenaPorDiaMes($fecha) {
    try {
        $dia = (int)date('d', strtotime($fecha));
        return ($dia <= 15) ? 'primera' : 'segunda';
    } catch (Exception $e) {
        error_log("Error al determinar quincena: " . $e->getMessage());
        return 'primera';
    }
}

/**
 * Determina la quincena basada en el día del mes (1-15: primera, 16-31: segunda)
 * pero solo si la fecha está dentro del rango seleccionado
 * @param string $fecha Fecha a evaluar (Y-m-d)
 * @param string $fechaDesde Fecha inicio del rango (Y-m-d)
 * @param string $fechaHasta Fecha fin del rango (Y-m-d)
 * @return string 'primera', 'segunda' o 'fuera_rango'
 */
function determinarQuincenaPorDiaMesEnRango($fecha, $fechaDesde, $fechaHasta) {
    try {
        // Primero verificar si la fecha está dentro del rango
        $fechaObj = new DateTime($fecha);
        $desdeObj = new DateTime($fechaDesde);
        $hastaObj = new DateTime($fechaHasta);
        
        if ($fechaObj < $desdeObj || $fechaObj > $hastaObj) {
            return 'fuera_rango';
        }
        
        // Si está dentro del rango, determinar quincena por día del mes
        $dia = (int)date('d', strtotime($fecha));
        return ($dia <= 15) ? 'primera' : 'segunda';
        
    } catch (Exception $e) {
        error_log("Error al determinar quincena: " . $e->getMessage());
        return 'fuera_rango';
    }
}

/**
 * Obtiene todos los operarios activos del sistema con información de sucursal
 * MODIFICADA: Elimina filtros restrictivos de fecha y evita duplicados
 */
function obtenerTodosOperariosActivos11() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT
            o.CodOperario, 
            o.Nombre, 
            o.Apellido, 
            o.Apellido2,
            s.nombre as sucursal_nombre,
            s.codigo as sucursal_codigo
        FROM Operarios o
        INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        LEFT JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE o.Operativo = 1
        -- Solo validamos fechas en AsignacionNivelesCargos, no en Operarios
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        -- Agrupamos por código de operario para evitar duplicados
        GROUP BY o.CodOperario
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene operarios con cargos 2 (Operario) y 5 (Líder de Sucursal) sin restricciones
 */
function obtenerTodosOperariosParaSelector() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT
            o.CodOperario, 
            o.Nombre, 
            o.Apellido, 
            o.Apellido2
        FROM Operarios o
        INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.CodNivelesCargos IN (2, 5, 43)
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Verifica tardanza con tolerancia de 1 minuto
 */
function verificarTardanzaConTolerancia($horaActual, $horaProgramada) {
    if (empty($horaProgramada)) {
        return false;
    }
    
    $toleranciaMinutos = 1; // 1 minuto de gracia
    
    $horaActualTimestamp = strtotime($horaActual);
    $horaProgramadaTimestamp = strtotime($horaProgramada);
    $horaLimiteTimestamp = strtotime("+$toleranciaMinutos minutes", $horaProgramadaTimestamp);
    
    return $horaActualTimestamp > $horaLimiteTimestamp;
}

/**
 * Verifica si está en el minuto de gracia (no es tardanza pero sí después de la hora programada)
 */
function verificarMinutoGracia($horaActual, $horaProgramada) {
    if (empty($horaProgramada)) {
        return false;
    }
    
    $horaActualTimestamp = strtotime($horaActual);
    $horaProgramadaTimestamp = strtotime($horaProgramada);
    $horaLimiteTimestamp = strtotime("+1 minutes", $horaProgramadaTimestamp);
    
    return $horaActualTimestamp > $horaProgramadaTimestamp && 
           $horaActualTimestamp <= $horaLimiteTimestamp;
}

/**
 * Obtiene los tipos de documentos configurados por pestaña - CORREGIDA
 */
function obtenerTiposDocumentosPorPestaña($pestaña) {
    $configuracion = [
        'datos-personales' => [
            'obligatorios' => [
                'record_ley_510' => 'Récord Ley 510',
                'certificado_salud' => 'Certificado de Salud',
                'constancia_judicial' => 'Constancia Judicial',
                'soportes_estudios' => 'Soportes de Estudios',
                'historial_inss' => 'Historial de INSS'
            ],
            'opcionales' => [
                'hoja_vida' => 'Hoja de Vida',
                'cartas_recomendacion' => 'Cartas de Recomendación Personal',
                'soportes_empleos_anteriores' => 'Soportes de Empleos Anteriores',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ],
        'inss' => [
            'obligatorios' => [
                'hoja_inscripcion_inss' => 'Hoja de Inscripción INSS',
            ],
            'opcionales' => [
                'colilla_inss' => 'Colilla INSS',
                'otro' => 'Otro Documento INSS' // AGREGADO
            ]
        ],
        'contrato' => [
            'obligatorios' => [
                'contrato_firmado' => 'Contrato Firmado'
            ],
            'opcionales' => [
                'anexos_contrato' => 'Anexos del Contrato',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ],
        'contactos-emergencia' => [
            'obligatorios' => [],
            'opcionales' => [
                'formulario_contactos' => 'Formulario de Contactos de Emergencia',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ],
        'salario' => [
            'obligatorios' => [],
            'opcionales' => [
                'escalas_salariales' => 'Escalas Salariales',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ],
        'movimientos' => [
            'obligatorios' => [],
            'opcionales' => [
                'documentos_movimiento' => 'Documentos de Movimiento',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ],
        'categoria' => [
            'obligatorios' => [],
            'opcionales' => [
                'certificaciones' => 'Certificaciones',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ],
        'adendums' => [
            'obligatorios' => [],
            'opcionales' => [
                'adendums_firmados' => 'Adendums Firmados',
                'otro' => 'Otro Documento' // AGREGADO
            ]
        ]
    ];
    
    return $configuracion[$pestaña] ?? ['obligatorios' => [], 'opcionales' => []];
}

/**
 * Verifica el estado de los documentos obligatorios por pestaña
 */
function verificarEstadoDocumentosObligatorios($codOperario, $pestaña) {
    $tiposDocumentos = obtenerTiposDocumentosPorPestaña($pestaña);
    $documentosObligatorios = $tiposDocumentos['obligatorios'];
    
    if ($pestaña === 'global') {
        return verificarEstadoGlobalDocumentos($codOperario);
    }
    
    if (empty($documentosObligatorios)) {
        return 'no_aplica'; // No hay documentos obligatorios para esta pestaña
    }
    
    global $conn;
    
    $placeholders = str_repeat('?,', count($documentosObligatorios) - 1) . '?';
    $tipos = array_keys($documentosObligatorios);
    
    $stmt = $conn->prepare("
        SELECT tipo_documento, COUNT(*) as cantidad
        FROM ArchivosAdjuntos 
        WHERE cod_operario = ? 
        AND pestaña = ?
        AND tipo_documento IN ($placeholders)
        AND obligatorio = 1
        GROUP BY tipo_documento
    ");
    
    $params = array_merge([$codOperario, $pestaña], $tipos);
    $stmt->execute($params);
    $documentosSubidos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $totalObligatorios = count($documentosObligatorios);
    $subidos = 0;
    
    foreach ($documentosObligatorios as $tipo => $nombre) {
        if (isset($documentosSubidos[$tipo]) && $documentosSubidos[$tipo] > 0) {
            $subidos++;
        }
    }
    
    if ($subidos == 0) {
        return 'pendiente';
    } elseif ($subidos == $totalObligatorios) {
        return 'completo';
    } else {
        return 'parcial';
    }
}

/**
 * Obtiene el ícono según el estado de documentos
 */
function obtenerIconoEstadoDocumentos($estado) {
    $iconos = [
        'completo' => '<i class="fas fa-check-circle" style="color: #28a745; margin-left: 5px;"></i>',
        'parcial' => '<i class="fas fa-clock" style="color: #ffc107; margin-left: 5px;"></i>',
        'pendiente' => '<i class="fas fa-exclamation-circle" style="color: #dc3545; margin-left: 5px;"></i>',
        'no_aplica' => ''
    ];
    
    return $iconos[$estado] ?? '';
}

/**
 * Calcula el tiempo total trabajado desde la fecha de inicio del contrato
 * MEJORADA: Si el contrato está activo, calcula hasta hoy. Si está finalizado, calcula hasta la fecha de finalización.
 * MEJORADA: Siempre usa el último contrato del operario
 */
function calcularTiempoTrabajado($fechaInicioContrato, $fechaFinContrato = null, $fechaSalida = null, $estaActivo = true) {
    if (empty($fechaInicioContrato) || $fechaInicioContrato == '0000-00-00') {
        return 'Sin contrato';
    }
    
    try {
        $fechaInicio = new DateTime($fechaInicioContrato);
        $hoy = new DateTime();
        
        // DETERMINAR FECHA FINAL CORRECTA
        $fechaFin = $hoy; // Por defecto: fecha actual (para contratos activos)
        
        // PRIORIDAD 1: Si hay fecha de salida, usar esa fecha (contrato finalizado definitivamente)
        if (!empty($fechaSalida) && $fechaSalida != '0000-00-00') {
            $fechaSalidaObj = new DateTime($fechaSalida);
            // Solo usar fecha salida si es anterior o igual a hoy
            if ($fechaSalidaObj <= $hoy) {
                $fechaFin = $fechaSalidaObj;
            }
        }
        // PRIORIDAD 2: Si no está activo y tiene fecha fin de contrato, usar esa fecha
        elseif (!$estaActivo && !empty($fechaFinContrato) && $fechaFinContrato != '0000-00-00') {
            $fechaFinContratoObj = new DateTime($fechaFinContrato);
            // Solo usar fecha fin si es anterior o igual a hoy
            if ($fechaFinContratoObj <= $hoy) {
                $fechaFin = $fechaFinContratoObj;
            }
        }
        // PRIORIDAD 3: Si está activo pero tiene fecha fin de contrato PASADA, usar fecha fin
        elseif ($estaActivo && !empty($fechaFinContrato) && $fechaFinContrato != '0000-00-00') {
            $fechaFinContratoObj = new DateTime($fechaFinContrato);
            if ($fechaFinContratoObj < $hoy) {
                $fechaFin = $fechaFinContratoObj; // Usar fecha fin si ya pasó
            }
            // Si la fecha fin es futura, se mantiene $fechaFin = $hoy (calcula hasta hoy)
        }
        // Para contratos indefinidos activos sin fecha fin, se mantiene $fechaFin = $hoy
        
        // Asegurar que la fecha fin no sea menor que la fecha inicio
        if ($fechaFin < $fechaInicio) {
            $fechaFin = clone $fechaInicio;
        }
        
        // Asegurar que no calcule fechas futuras
        if ($fechaFin > $hoy) {
            $fechaFin = $hoy;
        }
        
        $diferencia = $fechaInicio->diff($fechaFin);
        
        $años = $diferencia->y;
        $meses = $diferencia->m;
        $dias = $diferencia->d;
        
        $resultado = [];
        if ($años > 0) {
            $resultado[] = $años . ' año' . ($años > 1 ? 's' : '');
        }
        if ($meses > 0) {
            $resultado[] = $meses . ' mes' . ($meses > 1 ? 'es' : '');
        }
        if ($dias > 0) {
            $resultado[] = $dias . ' día' . ($dias > 1 ? 's' : '');
        }
        
        return empty($resultado) ? 'Menos de 1 día' : implode(', ', $resultado);
        
    } catch (Exception $e) {
        error_log("Error calculando tiempo trabajado: " . $e->getMessage());
        return 'Error en cálculo';
    }
}

/**
 * Obtiene la última fecha laborada (última marcación) de un operario
 */
function obtenerUltimaFechaLaborada($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT MAX(fecha) as ultima_fecha 
        FROM marcaciones 
        WHERE CodOperario = ? 
        AND (hora_ingreso IS NOT NULL OR hora_salida IS NOT NULL)
        AND fecha <= CURDATE()
    ");
    $stmt->execute([$codOperario]);
    $result = $stmt->fetch();
    
    return $result['ultima_fecha'] ?? null;
}

/**
 * Determina si un contrato está finalizado basado en fecha_salida
 */
function contratoEstaFinalizado($contrato) {
    return !empty($contrato['fecha_salida']) && $contrato['fecha_salida'] != '0000-00-00';
}

/**
 * Determina si un contrato está activo
 */
function contratoEstaActivo($contrato) {
    // Si tiene fecha_salida, no está activo
    if (contratoEstaFinalizado($contrato)) {
        return false;
    }
    
    // Si no tiene fecha_salida, verificar por fin_contrato
    if (!empty($contrato['fin_contrato']) && $contrato['fin_contrato'] != '0000-00-00') {
        $fechaFin = new DateTime($contrato['fin_contrato']);
        $fechaActual = new DateTime();
        return $fechaFin >= $fechaActual;
    }
    
    // Si no tiene fecha fin o es indefinido, está activo
    return true;
}

/**
 * Calcula el tiempo restante para la finalización del contrato - MEJORADA
 * MEJORADA: Siempre usa el último contrato del operario
 */
function calcularTiempoRestanteContrato($fechaFinContrato, $estaActivo = true, $fechaSalida = null) {
    // Si hay fecha de salida en el último contrato, el contrato está finalizado
    if (!empty($fechaSalida) && $fechaSalida != '0000-00-00') {
        $fechaSalidaObj = new DateTime($fechaSalida);
        $hoy = new DateTime();
        if ($fechaSalidaObj <= $hoy) {
            return '<span class="status-inactivo">Finalizado</span>';
        }
    }
    
    if (!$estaActivo) {
        return '<span class="status-inactivo">Inactivo</span>';
    }
    
    if (empty($fechaFinContrato) || $fechaFinContrato == '0000-00-00') {
        return '<span class="status-success">Indefinido</span>';
    }
    
    try {
        $fechaFin = new DateTime($fechaFinContrato);
        $fechaActual = new DateTime();
        
        // Si la fecha fin ya pasó
        if ($fechaFin < $fechaActual) {
            return '<span class="status-inactivo">Vencido</span>';
        }
        
        $diferencia = $fechaActual->diff($fechaFin);
        
        $años = $diferencia->y;
        $meses = $diferencia->m;
        $dias = $diferencia->d;
        
        // Si queda menos de 30 días, mostrar alerta
        $diasTotales = $diferencia->days;
        
        if ($diasTotales <= 7) {
            // Menos de 1 semana - ALERTA ROJA
            if ($diasTotales == 0) {
                return '<span class="status-inactivo">Vence hoy</span>';
            } elseif ($diasTotales == 1) {
                return '<span class="status-inactivo">1 día</span>';
            } else {
                return '<span class="status-inactivo">' . $diasTotales . ' días</span>';
            }
        } elseif ($diasTotales <= 30) {
            // Menos de 1 mes - ALERTA AMARILLA
            if ($meses == 0) {
                return '<span class="status-alerta">' . $diasTotales . ' días</span>';
            } else {
                return '<span class="status-alerta">' . $meses . ' mes' . ($meses > 1 ? 'es' : '') . '</span>';
            }
        } elseif ($años == 0) {
            // Menos de 1 año - INFORMACIÓN
            if ($meses == 0) {
                return '<span class="status-info">' . $dias . ' días</span>';
            } elseif ($dias == 0) {
                return '<span class="status-info">' . $meses . ' mes' . ($meses > 1 ? 'es' : '') . '</span>';
            } else {
                return '<span class="status-info">' . $meses . ' mes' . ($meses > 1 ? 'es' : '') . ', ' . $dias . ' día' . ($dias > 1 ? 's' : '') . '</span>';
            }
        } else {
            // Más de 1 año - ÉXITO
            if ($meses == 0) {
                return '<span class="status-success">' . $años . ' año' . ($años > 1 ? 's' : '') . '</span>';
            } else {
                return '<span class="status-success">' . $años . ' año' . ($años > 1 ? 's' : '') . ', ' . $meses . ' mes' . ($meses > 1 ? 'es' : '') . '</span>';
            }
        }
        
    } catch (Exception $e) {
        error_log("Error calculando tiempo restante: " . $e->getMessage());
        return '<span class="status-inactivo">Error</span>';
    }
}

/**
 * Obtiene el salario de referencia (contrato o último adendum)
 */
function obtenerSalarioReferencia($codOperario) {
    global $conn;
    
    // Primero intentar obtener el último adendum activo
    $stmtAdendum = $conn->prepare("
        SELECT Salario 
        FROM OperariosCategorias 
        WHERE CodOperario = ? 
        AND es_activo = 1
        AND Salario IS NOT NULL
        ORDER BY FechaInicio DESC 
        LIMIT 1
    ");
    $stmtAdendum->execute([$codOperario]);
    $adendum = $stmtAdendum->fetch();
    
    if ($adendum && $adendum['Salario'] > 0) {
        return $adendum['Salario'];
    }
    
    // Si no hay adendum, obtener el salario inicial del contrato
    $stmtContrato = $conn->prepare("
        SELECT salario_inicial 
        FROM Contratos 
        WHERE cod_operario = ? 
        AND (fin_contrato IS NULL OR fin_contrato >= CURDATE())
        ORDER BY inicio_contrato DESC 
        LIMIT 1
    ");
    $stmtContrato->execute([$codOperario]);
    $contrato = $stmtContrato->fetch();
    
    return $contrato['salario_inicial'] ?? 0;
}

/**
 * Obtiene la categoría sugerida para un cargo específico
 */
function obtenerCategoriaSugeridaPorCargo($codCargo) {
    $mapeo = [
        2 => [ // Cargo: Operario
            'idCategoria' => 5,
            'nombre' => 'Operario'
        ],
        5 => [ // Cargo: Líder de Sucursal
            'idCategoria' => 1, 
            'nombre' => 'Líder'
        ]
        // Agregar más mapeos según necesites
    ];
    
    return $mapeo[$codCargo] ?? null;
}

/**
 * Obtiene información completa de categoría por ID
 */
function obtenerCategoriaCompleta($idCategoria) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM CategoriasOperarios WHERE idCategoria = ?");
    $stmt->execute([$idCategoria]);
    return $stmt->fetch();
}

/**
 * Obtiene las últimas N semanas del sistema
 */
function obtenerUltimasSemanas($cantidad = 4) {
    global $conn;
    
    $sql = "SELECT * FROM SemanasSistema 
            WHERE fecha_fin <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY numero_semana DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$cantidad]);
    $semanas = $stmt->fetchAll();
    
    // Ordenar ascendente para mostrar de más antigua a más reciente
    return array_reverse($semanas);
}

/**
 * Obtiene las sucursales permitidas para un usuario según su cargo
 */
function obtenerSucursalesPermitidas($codOperario) {
    global $conn;
    
    // Si es admin, puede ver todas las sucursales
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM sucursales WHERE activa = 1 ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Verificar si tiene cargo 14 (Mantenimiento) o 35
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tiene_cargo 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND CodNivelesCargos IN (14, 35)
        AND (Fin IS NULL OR Fin >= CURDATE())
    ");
    $stmt->execute([$codOperario]);
    $result = $stmt->fetch();
    
    // Si tiene cargo 14 o 35, puede ver todas las sucursales
    if ($result && $result['tiene_cargo'] > 0) {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM sucursales WHERE activa = 1 ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Si no tiene cargo 14 o 35, obtener solo las sucursales donde es líder (cargo 5)
    $stmt = $conn->prepare("
        SELECT DISTINCT s.codigo, s.nombre 
        FROM AsignacionNivelesCargos anc
        JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE anc.CodOperario = ? 
        AND (anc.CodNivelesCargos = 5 OR anc.CodNivelesCargos = 43)
        AND (anc.Fin IS NULL OR Fin >= CURDATE())
        AND s.activa = 1
        ORDER BY s.nombre
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetchAll();
}

/**
 * Verifica si un usuario tiene acceso a una sucursal específica
 */
function verificarAccesoSucursal($codOperario, $codSucursal) {
    global $conn;
    
    // Si es admin, tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    // Verificar si tiene cargo 14 (Mantenimiento) o 35
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tiene_cargo 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND CodNivelesCargos IN (14, 35)
        AND (Fin IS NULL OR Fin >= CURDATE())
    ");
    $stmt->execute([$codOperario]);
    $result = $stmt->fetch();
    
    // Si tiene cargo 14 o 35, puede acceder a cualquier sucursal
    if ($result && $result['tiene_cargo'] > 0) {
        return true;
    }
    
    // Si no tiene cargo 14 o 35, verificar si es líder de esta sucursal
    $stmt = $conn->prepare("
        SELECT COUNT(*) as es_lider 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND (CodNivelesCargos = 5 OR CodNivelesCargos = 43)
        AND Sucursal = ?
        AND (Fin IS NULL OR Fin >= CURDATE())
    ");
    $stmt->execute([$codOperario, $codSucursal]);
    $result = $stmt->fetch();
    
    return $result && $result['es_lider'] > 0;
}

/**
 * Obtiene las faltas pendientes de revisión (tipo "Pendiente")
 */
function obtenerFaltasPendientesRevisión($codSucursal = null, $fechaDesde = null, $fechaHasta = null) {
    global $conn;
    
    // Si no se proporcionan fechas, calcular según lógica de días del mes
    if (!$fechaDesde || !$fechaHasta) {
        $fechas = calcularPeriodoRevisionFaltas();
        $fechaDesde = $fechas['desde'];
        $fechaHasta = $fechas['hasta'];
    }
    
    $sql = "
        SELECT fm.*, 
               o.Nombre AS operario_nombre, 
               o.Nombre2 AS operario_nombre2,
               o.Apellido AS operario_apellido,
               o.Apellido2 AS operario_apellido2,
               s.nombre AS sucursal_nombre,
               r.Nombre AS registrador_nombre,
               r.Apellido AS registrador_apellido
        FROM faltas_manual fm
        JOIN Operarios o ON fm.cod_operario = o.CodOperario
        JOIN sucursales s ON fm.cod_sucursal = s.codigo
        JOIN Operarios r ON fm.registrado_por = r.CodOperario
        WHERE fm.tipo_falta = 'Pendiente'
        AND fm.fecha_falta BETWEEN ? AND ?
    ";
    
    $params = [$fechaDesde, $fechaHasta];
    
    if (!empty($codSucursal) && $codSucursal !== 'todas') {
        $sql .= " AND fm.cod_sucursal = ?";
        $params[] = $codSucursal;
    }
    
    $sql .= " ORDER BY fm.fecha_falta DESC, o.Nombre, o.Apellido";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Calcula el periodo de revisión según los días del mes
 * Días 1-2: mes anterior, Días 3+: mes actual
 */
function calcularPeriodoRevisionFaltas() {
    $hoy = new DateTime();
    $dia = (int)$hoy->format('d');
    
    if ($dia <= 2) {
        // Mes anterior
        $mesAnterior = new DateTime('first day of last month');
        $desde = $mesAnterior->format('Y-m-01');
        $hasta = $mesAnterior->format('Y-m-t');
    } else {
        // Mes actual
        $desde = $hoy->format('Y-m-01');
        $hasta = $hoy->format('Y-m-t');
    }
    
    return [
        'desde' => $desde,
        'hasta' => $hasta,
        'periodo' => $dia <= 2 ? 'mes_anterior' : 'mes_actual'
    ];
}

/**
 * Calcula días restantes para revisión de faltas
 */
function calcularDiasRestantesRevisionFaltas() {
    $hoy = new DateTime();
    $dia = (int)$hoy->format('d');
    
    if ($dia <= 2) {
        // Si estamos en días 1-2, la fecha límite es el día 2
        return 2 - $dia;
    } else {
        // Si estamos después del día 2, la fecha límite es el día 2 del próximo mes
        $proximoMes = new DateTime('first day of next month');
        $proximoMes->modify('+1 day'); // Día 2 del próximo mes
        $diferencia = $hoy->diff($proximoMes);
        return $diferencia->days;
    }
}

/**
 * Determina el color del indicador según días restantes
 */
function determinarColorIndicadorFaltas($diasRestantes) {
    if ($diasRestantes < 0) {
        return 'rojo'; // Vencido
    } elseif ($diasRestantes <= 1) {
        return 'rojo'; // 1 día o menos
    } elseif ($diasRestantes <= 2) {
        return 'amarillo'; // 2 días
    } else {
        return 'verde'; // 3+ días
    }
}

/**
 * Obtiene el total de faltas pendientes para el indicador
 */
function obtenerTotalFaltasPendientesRevisión() {
    $faltasPendientes = obtenerFaltasPendientesRevisión();
    return count($faltasPendientes);
}

/**
 * Verifica si el usuario es jefe de CDS (cargo 19) o Producción 12
 */
function esJefeCDS($codOperario = null) {
    if ($codOperario === null) {
        $codOperario = $_SESSION['usuario_id'] ?? 0;
    }
    return verificarAccesoCargo([19, 12]);
}

/**
 * Verifica si el usuario tiene acceso a los formularios de mantenimiento
 */
function verificarAccesoFormulariosMantenimiento($codOperario) {
    // Si es admin, tiene acceso completo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    // Verificar si tiene cargo 5 (Líder), 14 (Mantenimiento), 19 (Jefe CDS) o 35
    return verificarAccesoCargo([5, 43, 46, 12, 14, 19, 35]);
}

/**
 * Obtiene las sucursales permitidas para formularios de mantenimiento
 */
function obtenerSucursalesPermitidasMantenimiento($codOperario) {
    global $conn;
    
    // Si es admin, puede ver todas las sucursales
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM sucursales WHERE activa = 1 ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Verificar si tiene cargo 14 (Mantenimiento) o 35 - acceso a todas las sucursales
    if (verificarAccesoCargo([14, 35])) {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM sucursales WHERE activa = 1 ORDER BY nombre");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Verificar si tiene cargo 19 (Jefe CDS) - solo sucursal 18
    if (verificarAccesoCargo([19, 12])) {
        $stmt = $conn->prepare("SELECT codigo, nombre FROM sucursales WHERE codigo = 18 AND activa = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Para cargo 5 (Líderes) y otros, obtener solo sus sucursales asignadas
    $stmt = $conn->prepare("
        SELECT DISTINCT s.codigo, s.nombre 
        FROM AsignacionNivelesCargos anc
        JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE anc.CodOperario = ? 
        AND (anc.Fin IS NULL OR Fin >= CURDATE())
        AND s.activa = 1
        ORDER BY s.nombre
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetchAll();
}

/**
 * Verifica si un usuario tiene acceso a una sucursal específica para mantenimiento
 */
function verificarAccesoSucursalMantenimiento($codOperario, $codSucursal) {
    global $conn;
    
    // Si es admin, tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    // Verificar si tiene cargo 14 (Mantenimiento) o 35 - acceso a todas las sucursales
    if (verificarAccesoCargo([14, 35])) {
        return true;
    }
    
    // Verificar si tiene cargo 19 (Jefe CDS) - solo sucursal 18
    if (verificarAccesoCargo([19, 12])) {
        return $codSucursal == 18;
    }
    
    // Para cargo 5 (Líderes) y otros, verificar si tiene acceso a esta sucursal
    $stmt = $conn->prepare("
        SELECT COUNT(*) as tiene_acceso 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND Sucursal = ?
        AND (Fin IS NULL OR Fin >= CURDATE())
    ");
    $stmt->execute([$codOperario, $codSucursal]);
    $result = $stmt->fetch();
    
    return $result && $result['tiene_acceso'] > 0;
}

/**
 * Obtiene el nombre del departamento por su código
 */
function obtenerNombreDepartamento($codDepartamento) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT nombre FROM departamentos WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codDepartamento]);
    $result = $stmt->fetch();
    
    return $result['nombre'] ?? 'Desconocido';
}

/**
 * Obtiene todos los departamentos
 */
function obtenerTodosDepartamentos() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT codigo, nombre FROM departamentos ORDER BY nombre");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene el nombre del departamento de una sucursal
 */
function obtenerDepartamentoSucursal($codSucursal) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT d.nombre 
        FROM sucursales s 
        JOIN departamentos d ON s.cod_departamento = d.codigo 
        WHERE s.codigo = ? 
        LIMIT 1
    ");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();
    
    return $result['nombre'] ?? 'Desconocido';
}

/**
 * Obtiene las sucursales por departamento
 */
function obtenerSucursalesPorDepartamento($codDepartamento) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT codigo, nombre 
        FROM sucursales 
        WHERE cod_departamento = ? 
        AND activa = 1
        ORDER BY nombre
    ");
    $stmt->execute([$codDepartamento]);
    return $stmt->fetchAll();
}

/**
 * Obtiene el monto de viático nocturno para un departamento
 */
function obtenerViaticoNocturnoDepartamento($codDepartamento) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT viatico_nocturno FROM departamentos WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codDepartamento]);
    $result = $stmt->fetch();
    
    return $result['viatico_nocturno'] ?? null;
}

function obtenerViaticoDiurnoDepartamento($codDepartamento) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT viatico_diurno FROM departamentos WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codDepartamento]);
    $result = $stmt->fetch();
    
    return $result['viatico_diurno'] ?? null;
}

/**
 * Obtiene departamentos que aplican para viáticos nocturnos
 */
function obtenerDepartamentosConViaticos() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT codigo, nombre FROM departamentos WHERE viatico_nocturno IS NOT NULL ORDER BY nombre");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Verifica si un día aplica para viático en Masaya (jueves a domingo)
 */
function aplicaViaticoMasaya($fecha) {
    $diaSemana = date('N', strtotime($fecha)); // 1=lunes, 7=domingo
    return $diaSemana >= 4 && $diaSemana <= 7; // Jueves=4 a Domingo=7
}

/**
 * Verifica si aplica viático para un departamento y fecha específicos
 */
function aplicaViaticoDepartamento($codDepartamento, $fecha) {
    // Convertir a string para comparación segura
    $codDepartamento = (string)$codDepartamento;
    
    switch ($codDepartamento) {
        case '1': // Managua - todos los días
        case '3': // Masaya - todos los días
            return true;
        case '4': // Granada - solo jueves a domingo
            $diaSemana = date('N', strtotime($fecha)); // 1=lunes, 7=domingo
            return $diaSemana >= 4 && $diaSemana <= 7; // Jueves=4 a Domingo=7
        default:
            return false;
    }
}

/**
 * Obtiene el monto de viático para una sucursal y fecha
 */
function obtenerMontoViaticoSucursal($codSucursal, $fecha) {
    $codDepartamento = obtenerCodigoDepartamentoSucursal($codSucursal);
    if (!$codDepartamento) {
        return 0;
    }
    
    // Verificar si aplica viático para este departamento y fecha
    if (!aplicaViaticoDepartamento($codDepartamento, $fecha)) {
        return 0;
    }
    
    return obtenerViaticoNocturnoDepartamento($codDepartamento) ?? 0;
}

/**
 * Obtiene feriados por departamento y fecha
 */
function obtenerFeriadosPorDepartamento($codDepartamento, $fecha) {
    global $conn;
    
    $sql = "
        SELECT f.id, f.nombre, f.tipo, f.departamento_codigo,
               COALESCE(d.nombre, 'Nacional') as nombre_departamento
        FROM feriadosnic f
        LEFT JOIN departamentos d ON f.departamento_codigo = d.codigo
        WHERE f.fecha = ?
        AND (
            f.tipo = 'Nacional' OR 
            (f.tipo = 'Departamental' AND f.departamento_codigo = ?)
        )
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fecha, $codDepartamento]);
    return $stmt->fetchAll();
}

/**
 * Verifica si una fecha es feriado para un departamento específico
 */
function esFeriadoDepartamento($codDepartamento, $fecha) {
    $feriados = obtenerFeriadosPorDepartamento($codDepartamento, $fecha);
    return !empty($feriados);
}

/**
 * Obtiene todos los feriados en un rango de fechas
 */
function obtenerFeriadosEnRango($fechaDesde, $fechaHasta, $codDepartamento = null) {
    global $conn;
    
    $sql = "
        SELECT f.*, COALESCE(d.nombre, 'Nacional') as nombre_departamento
        FROM feriadosnic f
        LEFT JOIN departamentos d ON f.departamento_codigo = d.codigo
        WHERE f.fecha BETWEEN ? AND ?
    ";
    
    $params = [$fechaDesde, $fechaHasta];
    
    if ($codDepartamento !== null) {
        $sql .= " AND (f.tipo = 'Nacional' OR f.departamento_codigo = ?)";
        $params[] = $codDepartamento;
    }
    
    $sql .= " ORDER BY f.fecha, f.tipo";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtiene las sucursales que aplican para viáticos nocturnos
 */
function obtenerSucursalesConViaticos() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT s.codigo, s.nombre, d.nombre as departamento, d.viatico_nocturno
        FROM sucursales s
        JOIN departamentos d ON s.cod_departamento = d.codigo
        WHERE d.viatico_nocturno IS NOT NULL
        AND s.activa = 1
        ORDER BY d.nombre, s.nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene el estado detallado de las auditorías para una sucursal específica
 */
function obtenerEstadoAuditoriasSucursal($codSucursal, $numeroSemana) {
    global $conn;
    
    // Obtener la semana del sistema
    $semana = obtenerSemanaPorNumero($numeroSemana);
    if (!$semana) {
        return ['completadas' => 0, 'total' => 6, 'porcentaje' => 0, 'auditorias' => []];
    }
    
    // Definir las 6 auditorías
    $auditorias = [
        [
            'tipo' => 'limpieza',
            'nombre' => 'Limpieza',
            'tabla' => 'auditoria',
            'url' => '/modulos/supervision/auditorias_original/agregar.php'
        ],
        [
            'tipo' => 'personal',
            'nombre' => 'Personal',
            'tabla' => 'auditoria_personal',
            'url' => '/modulos/supervision/auditorias_original/agregarpersonal.php'
        ],
        [
            'tipo' => 'servicio',
            'nombre' => 'Servicio',
            'tabla' => 'auditoria_servicio',
            'url' => '/modulos/supervision/auditorias_original/agregarservicio.php'
        ],
        [
            'tipo' => 'facturacion',
            'nombre' => 'Caja Facturación',
            'tabla' => 'auditoria_facturacion',
            'url' => '/modulos/supervision/auditorias_original/auditinternas/auditoria_caja_facturacion.php'
        ],
        [
            'tipo' => 'caja_chica',
            'nombre' => 'Caja Chica',
            'tabla' => 'auditoria_caja_chica',
            'url' => '/modulos/supervision/auditorias_original/auditinternas/auditoria_caja_chica.php'
        ],
        [
            'tipo' => 'inventario',
            'nombre' => 'Inventario',
            'tabla' => 'auditoria_inventario',
            'url' => '/modulos/supervision/auditorias_original/auditinternas/auditoria_inventario.php'
        ]
    ];
    
    $completadas = 0;
    $auditoriasDetalle = [];
    
    foreach ($auditorias as $auditoria) {
        $estaCompleta = false;
        
        if (in_array($auditoria['tipo'], ['limpieza', 'personal', 'servicio'])) {
            // Auditorías de desempeño (sin ajuste de hora)
            $estaCompleta = verificarAuditoriaDesempenio($auditoria['tabla'], $codSucursal, $semana);
        } else {
            // Auditorías de efectivo (con ajuste de -6 horas)
            $columnaSucursal = ($auditoria['tipo'] == 'inventario') ? 'sucursal_id' : 'sucursal_id';
            $estaCompleta = verificarAuditoriaEfectivo($auditoria['tabla'], $columnaSucursal, $codSucursal, $semana);
        }
        
        if ($estaCompleta) {
            $completadas++;
        }
        
        $auditoriasDetalle[] = [
            'tipo' => $auditoria['tipo'],
            'nombre' => $auditoria['nombre'],
            'esta_completa' => $estaCompleta,
            'url' => $auditoria['url'] . '?sucursal=' . $codSucursal . '&semana=' . $numeroSemana
        ];
    }
    
    $porcentaje = round(($completadas / 6) * 100);
    
    return [
        'completadas' => $completadas,
        'total' => 6,
        'porcentaje' => $porcentaje,
        'auditorias' => $auditoriasDetalle
    ];
}

/**
 * Obtiene los documentos obligatorios faltantes para una pestaña específica
 */
function obtenerDocumentosFaltantesPestana($codOperario, $pestaña) {
    $tiposDocumentos = obtenerTiposDocumentosPorPestaña($pestaña);
    $obligatorios = $tiposDocumentos['obligatorios'];
    
    if (empty($obligatorios)) {
        return [];
    }
    
    global $conn;
    
    // Obtener documentos obligatorios ya subidos para esta pestaña
    $placeholders = str_repeat('?,', count($obligatorios) - 1) . '?';
    $tipos = array_keys($obligatorios);
    
    $stmt = $conn->prepare("
        SELECT tipo_documento 
        FROM ArchivosAdjuntos 
        WHERE cod_operario = ? 
        AND pestaña = ? 
        AND tipo_documento IN ($placeholders)
        AND obligatorio = 1
    ");
    
    $params = array_merge([$codOperario, $pestaña], $tipos);
    $stmt->execute($params);
    $subidos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Encontrar los faltantes
    $faltantes = array_diff(array_keys($obligatorios), $subidos);
    
    $documentosFaltantes = [];
    foreach ($faltantes as $tipo) {
        $documentosFaltantes[] = $obligatorios[$tipo];
    }
    
    return $documentosFaltantes;
}

/**
 * Obtiene el expediente completo incluyendo documentos faltantes
 */
function obtenerExpedienteCompletoConFaltantes($codOperario) {
    $expedienteCompleto = obtenerExpedienteDigitalCompleto($codOperario);
    $documentosFaltantesGlobal = obtenerTodosDocumentosFaltantes($codOperario);
    
    // Combinar documentos existentes con faltantes
    foreach ($documentosFaltantesGlobal as $pestaña => $documentosFaltantes) {
        $categoriaPrincipal = obtenerNombrePestaña($pestaña);
        
        if (!isset($expedienteCompleto[$categoriaPrincipal])) {
            $expedienteCompleto[$categoriaPrincipal] = [];
        }
        
        if (!isset($expedienteCompleto[$categoriaPrincipal]['Documentos Faltantes'])) {
            $expedienteCompleto[$categoriaPrincipal]['Documentos Faltantes'] = [];
        }
        
        foreach ($documentosFaltantes as $documento) {
            $expedienteCompleto[$categoriaPrincipal]['Documentos Faltantes'][] = [
                'tipo' => 'faltante',
                'nombre_archivo' => $documento['nombre'],
                'pestaña' => $pestaña,
                'obligatorio' => true
            ];
        }
    }
    
    return $expedienteCompleto;
}

/**
 * Obtiene todos los documentos faltantes organizados por pestaña
 */
function obtenerTodosDocumentosFaltantes($codOperario) {
    $documentosFaltantes = [];
    $pestañas = ['datos-personales', 'inss', 'contrato', 'contactos-emergencia', 
                 'salario', 'movimientos', 'categoria', 'adendums'];
    
    foreach ($pestañas as $pestaña) {
        $faltantesPestana = obtenerDocumentosFaltantesPestana($codOperario, $pestaña);
        
        if (!empty($faltantesPestana)) {
            $documentosFaltantes[$pestaña] = [];
            
            foreach ($faltantesPestana as $nombreDocumento) {
                $documentosFaltantes[$pestaña][] = [
                    'nombre' => $nombreDocumento,
                    'pestaña' => $pestaña
                ];
            }
        }
    }
    
    return $documentosFaltantes;
}

/**
 * Obtiene TODOS los operarios con horario (incluyendo adicionales históricos)
 * ACTUALIZADA: Incluye cod_contrato
 */
function obtenerTodosOperariosConHorario($codSucursal, $idSemana) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            o.CodOperario, 
            o.Nombre, 
            o.Apellido, 
            o.Apellido2,
            hs.total_horas,
            hs.cod_contrato,
            -- Verificar si el operario está asignado actualmente a la sucursal
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM AsignacionNivelesCargos anc 
                    WHERE anc.CodOperario = o.CodOperario 
                    AND anc.Sucursal = ?
                    AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                ) THEN 1
                ELSE 0
            END as esta_asignado
        FROM Operarios o
        INNER JOIN HorariosSemanales hs ON o.CodOperario = hs.cod_operario
        WHERE hs.cod_sucursal = ?
        AND hs.id_semana_sistema = ?
        AND o.Operativo = 1
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal, $codSucursal, $idSemana]);
    return $stmt->fetchAll();
}

/**
 * Obtiene la cantidad de anuncios no leídos por un usuario
 */
function obtenerCantidadAnunciosNoLeidos($userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT a.id) as no_leidos
            FROM announcements a
            LEFT JOIN announcement_views av ON a.id = av.announcement_id AND av.user_id = ?
            WHERE av.id IS NULL
            AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) -- Solo últimos 30 días
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result['no_leidos'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error obteniendo anuncios no leídos: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marcar un anuncio como leído por un usuario
 */
function marcarAnuncioComoLeido($announcementId, $userId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO announcement_views (announcement_id, user_id) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$announcementId, $userId]);
    } catch (PDOException $e) {
        error_log("Error marcando anuncio como leído: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar todos los anuncios como leídos para un usuario
 */
function marcarTodosAnunciosComoLeidos($userId) {
    global $conn;
    
    try {
        // Primero obtener todos los anuncios no leídos
        $stmt = $conn->prepare("
            SELECT a.id 
            FROM announcements a
            LEFT JOIN announcement_views av ON a.id = av.announcement_id AND av.user_id = ?
            WHERE av.id IS NULL
        ");
        $stmt->execute([$userId]);
        $anunciosNoLeidos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Marcar cada uno como leído
        $marcados = 0;
        foreach ($anunciosNoLeidos as $anuncioId) {
            if (marcarAnuncioComoLeido($anuncioId, $userId)) {
                $marcados++;
            }
        }
        
        return $marcados;
    } catch (PDOException $e) {
        error_log("Error marcando todos los anuncios como leídos: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtiene el último código de contrato de un operario
 */
function obtenerUltimoCodigoContrato($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT CodContrato 
        FROM Contratos 
        WHERE cod_operario = ? 
        ORDER BY inicio_contrato DESC, CodContrato DESC 
        LIMIT 1
    ");
    $stmt->execute([$codOperario]);
    $result = $stmt->fetch();
    
    return $result['CodContrato'] ?? null;
}

/**
 * Obtiene el contrato activo de un operario (si existe)
 */
function obtenerContratoActivo($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT CodContrato, inicio_contrato, fin_contrato, fecha_salida
        FROM Contratos 
        WHERE cod_operario = ? 
        AND (fecha_salida IS NULL OR fecha_salida = '0000-00-00')
        AND (fin_contrato IS NULL OR fin_contrato >= CURDATE())
        ORDER BY inicio_contrato DESC 
        LIMIT 1
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetch() ?: null;
}

/**
 * Obtiene el rango de fechas de la quincena actual
 */
function obtenerRangoQuincenaActual() {
    $hoy = new DateTime();
    $dia = (int)$hoy->format('d');
    $mes = (int)$hoy->format('m');
    $anio = (int)$hoy->format('Y');
    
    if ($dia <= 15) {
        // Primera quincena (1-15)
        $inicio = new DateTime("$anio-$mes-01");
        $fin = new DateTime("$anio-$mes-15");
    } else {
        // Segunda quincena (16-fin de mes)
        $inicio = new DateTime("$anio-$mes-16");
        $fin = new DateTime("$anio-$mes-01");
        $fin->modify('last day of this month');
    }
    
    return [
        'inicio' => $inicio->format('Y-m-d'),
        'fin' => $fin->format('Y-m-d'),
        'nombre' => $dia <= 15 ? 'Primera Quincena' : 'Segunda Quincena'
    ];
}

/**
 * Calcula faltas ejecutadas para un operario en la quincena actual - VERSIÓN CORREGIDA
 */
function calcularFaltasEjecutadas($codOperario, $fechaInicio, $fechaFin) {
    global $conn;
    
    // LIMITAR hasta la fecha actual (no incluir días futuros)
    $fechaHoy = date('Y-m-d');
    if ($fechaFin > $fechaHoy) {
        $fechaFin = $fechaHoy;
    }
    
    // 1. Obtener faltas automáticas (SOLO días con horario programado ACTIVO y hasta hoy)
    $sqlFaltasAutomaticas = "
        SELECT COUNT(DISTINCT h.fecha) as total
        FROM (
            SELECT DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY as fecha
            FROM 
            (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
             UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
            (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
             UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
            WHERE DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY <= ?
        ) h
        WHERE h.fecha BETWEEN ? AND ?  -- Solo hasta fecha actual
        AND h.fecha <= ?  -- Doble verificación para no incluir futuros
        AND EXISTS (
            SELECT 1 FROM HorariosSemanalesOperaciones hso
            JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
            WHERE hso.cod_operario = ?
            AND h.fecha BETWEEN ss.fecha_inicio AND ss.fecha_fin
            AND (
                -- Solo considerar días con estado 'Activo'
                (DAYOFWEEK(h.fecha) = 2 AND hso.lunes_estado = 'Activo') OR
                (DAYOFWEEK(h.fecha) = 3 AND hso.martes_estado = 'Activo') OR
                (DAYOFWEEK(h.fecha) = 4 AND hso.miercoles_estado = 'Activo') OR
                (DAYOFWEEK(h.fecha) = 5 AND hso.jueves_estado = 'Activo') OR
                (DAYOFWEEK(h.fecha) = 6 AND hso.viernes_estado = 'Activo') OR
                (DAYOFWEEK(h.fecha) = 7 AND hso.sabado_estado = 'Activo') OR
                (DAYOFWEEK(h.fecha) = 1 AND hso.domingo_estado = 'Activo')
            )
        )
        AND NOT EXISTS (
            SELECT 1 FROM marcaciones m
            WHERE m.CodOperario = ?
            AND m.fecha = h.fecha
            AND (m.hora_ingreso IS NOT NULL OR m.hora_salida IS NOT NULL)
        )";
    
    $stmtFaltas = $conn->prepare($sqlFaltasAutomaticas);
    $stmtFaltas->execute([
        $fechaInicio, $fechaInicio, $fechaFin,
        $fechaInicio, $fechaFin, $fechaHoy,  // Parámetro extra para fecha actual
        $codOperario, $codOperario
    ]);
    $faltasAutomaticas = $stmtFaltas->fetch()['total'] ?? 0;
    
    // 2. Obtener faltas justificadas (las que NO son 'Pendiente' ni 'No_Pagado')
    $sqlFaltasJustificadas = "
        SELECT COUNT(*) as total 
        FROM faltas_manual 
        WHERE cod_operario = ? 
        AND fecha_falta BETWEEN ? AND ?
        AND fecha_falta <= ?  -- Solo hasta hoy
        AND tipo_falta NOT IN ('Pendiente', 'No_Pagado')";
    
    $stmtJustificadas = $conn->prepare($sqlFaltasJustificadas);
    $stmtJustificadas->execute([$codOperario, $fechaInicio, $fechaFin, $fechaHoy]);
    $faltasJustificadas = $stmtJustificadas->fetch()['total'] ?? 0;
    
    // 3. Calcular faltas ejecutadas (automáticas - justificadas)
    $faltasEjecutadas = max(0, $faltasAutomaticas - $faltasJustificadas);
    
    return [
        'faltas_automaticas' => $faltasAutomaticas,
        'faltas_justificadas' => $faltasJustificadas,
        'faltas_ejecutadas' => $faltasEjecutadas,
        'fecha_consulta' => $fechaHoy  // Para debug
    ];
}

/**
 * Calcula tardanzas ejecutadas para un operario en la quincena actual - VERSIÓN CORREGIDA
 */
function calcularTardanzasEjecutadas($codOperario, $fechaInicio, $fechaFin) {
    global $conn;
    
    // LIMITAR hasta la fecha actual
    $fechaHoy = date('Y-m-d');
    if ($fechaFin > $fechaHoy) {
        $fechaFin = $fechaHoy;
    }
    
    // 1. Obtener total de tardanzas automáticas (SOLO hasta hoy)
    $sqlTotalTardanzas = "
        SELECT COUNT(*) as total 
        FROM marcaciones m
        JOIN HorariosSemanalesOperaciones hso ON m.CodOperario = hso.cod_operario 
            AND m.sucursal_codigo = hso.cod_sucursal
        JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
        WHERE m.CodOperario = ?
        AND m.fecha BETWEEN ? AND ?
        AND m.fecha <= ?  -- Solo hasta hoy
        AND m.fecha BETWEEN ss.fecha_inicio AND ss.fecha_fin
        AND m.hora_ingreso IS NOT NULL
        AND TIMESTAMPDIFF(MINUTE, 
            CASE DAYOFWEEK(m.fecha)
                WHEN 2 THEN hso.lunes_entrada
                WHEN 3 THEN hso.martes_entrada
                WHEN 4 THEN hso.miercoles_entrada
                WHEN 5 THEN hso.jueves_entrada
                WHEN 6 THEN hso.viernes_entrada
                WHEN 7 THEN hso.sabado_entrada
                WHEN 1 THEN hso.domingo_entrada
            END,
            m.hora_ingreso
        ) > 1
        -- Verificar que el día tenía estado 'Activo'
        AND (
            (DAYOFWEEK(m.fecha) = 2 AND hso.lunes_estado = 'Activo') OR
            (DAYOFWEEK(m.fecha) = 3 AND hso.martes_estado = 'Activo') OR
            (DAYOFWEEK(m.fecha) = 4 AND hso.miercoles_estado = 'Activo') OR
            (DAYOFWEEK(m.fecha) = 5 AND hso.jueves_estado = 'Activo') OR
            (DAYOFWEEK(m.fecha) = 6 AND hso.viernes_estado = 'Activo') OR
            (DAYOFWEEK(m.fecha) = 7 AND hso.sabado_estado = 'Activo') OR
            (DAYOFWEEK(m.fecha) = 1 AND hso.domingo_estado = 'Activo')
        )";
    
    $stmtTotal = $conn->prepare($sqlTotalTardanzas);
    $stmtTotal->execute([$codOperario, $fechaInicio, $fechaFin, $fechaHoy]);
    $totalTardanzas = $stmtTotal->fetch()['total'] ?? 0;
    
    // 2. Obtener tardanzas justificadas (SOLO hasta hoy)
    $sqlTardanzasJustificadas = "
        SELECT COUNT(*) as total 
        FROM TardanzasManuales 
        WHERE cod_operario = ? 
        AND fecha_tardanza BETWEEN ? AND ?
        AND fecha_tardanza <= ?  -- Solo hasta hoy
        AND estado = 'Justificado'";
    
    $stmtJustificadas = $conn->prepare($sqlTardanzasJustificadas);
    $stmtJustificadas->execute([$codOperario, $fechaInicio, $fechaFin, $fechaHoy]);
    $tardanzasJustificadas = $stmtJustificadas->fetch()['total'] ?? 0;
    
    // 3. Calcular tardanzas ejecutadas (totales - justificadas)
    $tardanzasEjecutadas = max(0, $totalTardanzas - $tardanzasJustificadas);
    
    return [
        'total_tardanzas' => $totalTardanzas,
        'tardanzas_justificadas' => $tardanzasJustificadas,
        'tardanzas_ejecutadas' => $tardanzasEjecutadas,
        'fecha_consulta' => $fechaHoy  // Para debug
    ];
}

/**
 * Obtiene estadísticas completas de la quincena para un operario - VERSIÓN COMPLETA
 */
function obtenerEstadisticasQuincenaOperario($codOperario) {
    $rangoQuincena = obtenerRangoQuincenaActual();
    
    $faltas = calcularFaltasEjecutadas($codOperario, $rangoQuincena['inicio'], $rangoQuincena['fin']);
    $tardanzas = calcularTardanzasEjecutadas($codOperario, $rangoQuincena['inicio'], $rangoQuincena['fin']);
    $sucursales = obtenerEstadisticasSucursalesQuincena($codOperario, $rangoQuincena['inicio'], $rangoQuincena['fin']);
    
    // Calcular turnos nocturnos (marcaciones de salida después de 8pm)
    global $conn;
    $fechaHoy = date('Y-m-d');
    $sqlTurnosNocturnos = "
        SELECT COUNT(*) as total 
        FROM marcaciones 
        WHERE CodOperario = ? 
        AND fecha BETWEEN ? AND ?
        AND fecha <= ?
        AND hora_salida IS NOT NULL
        AND TIME(hora_salida) >= '20:00:00'";
    
    $stmtNocturnos = $conn->prepare($sqlTurnosNocturnos);
    $stmtNocturnos->execute([$codOperario, $rangoQuincena['inicio'], $rangoQuincena['fin'], $fechaHoy]);
    $turnosNocturnos = $stmtNocturnos->fetch()['total'] ?? 0;
    
    return [
        'rango_quincena' => $rangoQuincena,
        'faltas' => $faltas,
        'tardanzas' => $tardanzas,
        'turnos_nocturnos' => $turnosNocturnos,
        'por_sucursal' => $sucursales
    ];
}

/**
 * Obtiene estadísticas por sucursal para un operario en la quincena actual
 */
function obtenerEstadisticasSucursalesQuincena($codOperario, $fechaInicio, $fechaFin) {
    global $conn;
    
    // LIMITAR hasta la fecha actual
    $fechaHoy = date('Y-m-d');
    if ($fechaFin > $fechaHoy) {
        $fechaFin = $fechaHoy;
    }
    
    $estadisticasSucursales = [];
    
    // Obtener todas las sucursales donde el operario ha tenido marcaciones en la quincena
    $sqlSucursales = "SELECT DISTINCT m.sucursal_codigo, s.nombre 
                      FROM marcaciones m
                      JOIN sucursales s ON m.sucursal_codigo = s.codigo
                      WHERE m.CodOperario = ? 
                      AND m.fecha BETWEEN ? AND ?
                      AND m.fecha <= ?";
    
    $stmtSucursales = $conn->prepare($sqlSucursales);
    $stmtSucursales->execute([$codOperario, $fechaInicio, $fechaFin, $fechaHoy]);
    $sucursales = $stmtSucursales->fetchAll();
    
    foreach ($sucursales as $sucursal) {
        $codSucursal = $sucursal['sucursal_codigo'];
        $nombreSucursal = $sucursal['nombre'];
        
        // Tardanzas por sucursal
        $sqlTardanzasSucursal = "
            SELECT COUNT(*) as tardanzas 
            FROM marcaciones m
            JOIN HorariosSemanalesOperaciones hso ON m.CodOperario = hso.cod_operario 
                AND m.sucursal_codigo = hso.cod_sucursal
            JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
            WHERE m.CodOperario = ? 
            AND m.sucursal_codigo = ?
            AND m.fecha BETWEEN ? AND ?
            AND m.fecha <= ?
            AND m.fecha BETWEEN ss.fecha_inicio AND ss.fecha_fin
            AND m.hora_ingreso IS NOT NULL
            AND TIMESTAMPDIFF(MINUTE, 
                CASE DAYOFWEEK(m.fecha)
                    WHEN 2 THEN hso.lunes_entrada
                    WHEN 3 THEN hso.martes_entrada
                    WHEN 4 THEN hso.miercoles_entrada
                    WHEN 5 THEN hso.jueves_entrada
                    WHEN 6 THEN hso.viernes_entrada
                    WHEN 7 THEN hso.sabado_entrada
                    WHEN 1 THEN hso.domingo_entrada
                END,
                m.hora_ingreso
            ) > 1
            AND (
                (DAYOFWEEK(m.fecha) = 2 AND hso.lunes_estado = 'Activo') OR
                (DAYOFWEEK(m.fecha) = 3 AND hso.martes_estado = 'Activo') OR
                (DAYOFWEEK(m.fecha) = 4 AND hso.miercoles_estado = 'Activo') OR
                (DAYOFWEEK(m.fecha) = 5 AND hso.jueves_estado = 'Activo') OR
                (DAYOFWEEK(m.fecha) = 6 AND hso.viernes_estado = 'Activo') OR
                (DAYOFWEEK(m.fecha) = 7 AND hso.sabado_estado = 'Activo') OR
                (DAYOFWEEK(m.fecha) = 1 AND hso.domingo_estado = 'Activo')
            )";
        
        $stmtTardanzas = $conn->prepare($sqlTardanzasSucursal);
        $stmtTardanzas->execute([$codOperario, $codSucursal, $fechaInicio, $fechaFin, $fechaHoy]);
        $tardanzasSucursal = $stmtTardanzas->fetch()['tardanzas'] ?? 0;
        
        // Tardanzas justificadas por sucursal
        $sqlTardanzasJustificadasSucursal = "
            SELECT COUNT(*) as justificadas 
            FROM TardanzasManuales 
            WHERE cod_operario = ? 
            AND cod_sucursal = ?
            AND fecha_tardanza BETWEEN ? AND ?
            AND fecha_tardanza <= ?
            AND estado = 'Justificado'";
        
        $stmtJustificadas = $conn->prepare($sqlTardanzasJustificadasSucursal);
        $stmtJustificadas->execute([$codOperario, $codSucursal, $fechaInicio, $fechaFin, $fechaHoy]);
        $tardanzasJustificadasSucursal = $stmtJustificadas->fetch()['justificadas'] ?? 0;
        
        // Tardanzas ejecutadas por sucursal
        $tardanzasEjecutadasSucursal = max(0, $tardanzasSucursal - $tardanzasJustificadasSucursal);
        
        // Turnos nocturnos por sucursal
        $sqlNocturnosSucursal = "
            SELECT COUNT(*) as turnos_nocturnos 
            FROM marcaciones 
            WHERE CodOperario = ? 
            AND sucursal_codigo = ?
            AND fecha BETWEEN ? AND ?
            AND fecha <= ?
            AND hora_salida IS NOT NULL
            AND TIME(hora_salida) >= '20:00:00'";
        
        $stmtNocturnos = $conn->prepare($sqlNocturnosSucursal);
        $stmtNocturnos->execute([$codOperario, $codSucursal, $fechaInicio, $fechaFin, $fechaHoy]);
        $nocturnosSucursal = $stmtNocturnos->fetch()['turnos_nocturnos'] ?? 0;
        
        // Omisiones por sucursal (días con horario activo pero sin marcación completa)
        $sqlOmisionesSucursal = "
            SELECT COUNT(DISTINCT h.fecha) as omisiones
            FROM (
                SELECT DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY as fecha
                FROM 
                (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                WHERE DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY <= ?
            ) h
            WHERE h.fecha BETWEEN ? AND ?
            AND h.fecha <= ?
            AND EXISTS (
                SELECT 1 FROM HorariosSemanalesOperaciones hso
                JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
                WHERE hso.cod_operario = ?
                AND hso.cod_sucursal = ?
                AND h.fecha BETWEEN ss.fecha_inicio AND ss.fecha_fin
                AND (
                    (DAYOFWEEK(h.fecha) = 2 AND hso.lunes_estado = 'Activo') OR
                    (DAYOFWEEK(h.fecha) = 3 AND hso.martes_estado = 'Activo') OR
                    (DAYOFWEEK(h.fecha) = 4 AND hso.miercoles_estado = 'Activo') OR
                    (DAYOFWEEK(h.fecha) = 5 AND hso.jueves_estado = 'Activo') OR
                    (DAYOFWEEK(h.fecha) = 6 AND hso.viernes_estado = 'Activo') OR
                    (DAYOFWEEK(h.fecha) = 7 AND hso.sabado_estado = 'Activo') OR
                    (DAYOFWEEK(h.fecha) = 1 AND hso.domingo_estado = 'Activo')
                )
            )
            AND NOT EXISTS (
                SELECT 1 FROM marcaciones m
                WHERE m.CodOperario = ?
                AND m.sucursal_codigo = ?
                AND m.fecha = h.fecha
                AND (m.hora_ingreso IS NOT NULL AND m.hora_salida IS NOT NULL)
            )";
        
        $stmtOmisiones = $conn->prepare($sqlOmisionesSucursal);
        $stmtOmisiones->execute([
            $fechaInicio, $fechaInicio, $fechaFin,
            $fechaInicio, $fechaFin, $fechaHoy,
            $codOperario, $codSucursal,
            $codOperario, $codSucursal
        ]);
        $omisionesSucursal = $stmtOmisiones->fetch()['omisiones'] ?? 0;
        
        // Días fuera de horario programado
        $sqlFueraHorarioSucursal = "
            SELECT COUNT(DISTINCT m.fecha) as dias_fuera_horario
            FROM marcaciones m
            LEFT JOIN HorariosSemanalesOperaciones hso ON m.CodOperario = hso.cod_operario 
                AND m.sucursal_codigo = hso.cod_sucursal
            LEFT JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
            WHERE m.CodOperario = ? 
            AND m.sucursal_codigo = ?
            AND m.fecha BETWEEN ? AND ?
            AND m.fecha <= ?
            AND (hso.id IS NULL OR m.fecha NOT BETWEEN ss.fecha_inicio AND ss.fecha_fin)";
        
        $stmtFueraHorario = $conn->prepare($sqlFueraHorarioSucursal);
        $stmtFueraHorario->execute([$codOperario, $codSucursal, $fechaInicio, $fechaFin, $fechaHoy]);
        $fueraHorarioSucursal = $stmtFueraHorario->fetch()['dias_fuera_horario'] ?? 0;
        
        $estadisticasSucursales[$codSucursal] = [
            'nombre' => $nombreSucursal,
            'tardanzas_ejecutadas' => $tardanzasEjecutadasSucursal,
            'turnos_nocturnos' => $nocturnosSucursal,
            'omisiones_marcacion' => $omisionesSucursal,
            'dias_fuera_horario' => $fueraHorarioSucursal
        ];
    }
    
    return $estadisticasSucursales;
}

/**
 * Verifica si el usuario actual está de cumpleaños y devuelve los datos necesarios
 */
function verificarCumpleanosUsuario($codOperario = null) {
    global $conn;
    
    if ($codOperario === null) {
        $codOperario = $_SESSION['usuario_id'] ?? null;
    }
    
    if (!$codOperario) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT Nombre, Apellido, Cumpleanos 
            FROM Operarios 
            WHERE CodOperario = ? 
            AND Cumpleanos IS NOT NULL
            AND Cumpleanos != '0000-00-00 00:00:00'
        ");
        $stmt->execute([$codOperario]);
        $operario = $stmt->fetch();
        
        if (!$operario || empty($operario['Cumpleanos'])) {
            return null;
        }
        
        // Extraer solo la parte de la fecha (ignorar la hora)
        $cumpleanos = date('m-d', strtotime($operario['Cumpleanos']));
        $hoy = date('m-d');
        
        // Verificar si hoy es el cumpleaños
        if ($cumpleanos === $hoy) {
            return [
                'nombre' => trim($operario['Nombre'] . ' ' . $operario['Apellido']),
                'fecha_completa' => $operario['Cumpleanos'],
                'edad' => calcularEdad($operario['Cumpleanos'])
            ];
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Error verificando cumpleaños: " . $e->getMessage());
        return null;
    }
}

/**
 * Calcula la edad basada en la fecha de cumpleaños
 */
function calcularEdad($fechaNacimiento) {
    if (empty($fechaNacimiento) || $fechaNacimiento == '0000-00-00 00:00:00') {
        return null;
    }
    
    $nacimiento = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento);
    return $edad->y;
}

/**
 * Obtiene el nombre de la sucursal por su código
 */
function obtenerNombreSucursalPorCodigo($codSucursal) {
    global $conn;
    
    if (empty($codSucursal)) {
        return 'Sin sucursal';
    }
    
    $stmt = $conn->prepare("SELECT nombre FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();
    
    return $result['nombre'] ?? 'Sucursal ' . $codSucursal;
}

/**
 * Obtiene operarios que han tenido horario programado en una fecha específica
 */
function obtenerOperariosConHorarioEnFecha($codSucursal, $fechaFalta) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT o.CodOperario, 
               o.Nombre, 
               o.Apellido, 
               o.Apellido2,
               hso.cod_sucursal
        FROM Operarios o
        INNER JOIN HorariosSemanalesOperaciones hso ON o.CodOperario = hso.cod_operario
        INNER JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
        WHERE hso.cod_sucursal = ?
        AND ? BETWEEN ss.fecha_inicio AND ss.fecha_fin
        AND o.Operativo = 1
        -- Verificar que el operario tenga horario programado en esa fecha específica
        AND (
            (DAYOFWEEK(?) = 2 AND hso.lunes_estado IS NOT NULL) OR
            (DAYOFWEEK(?) = 3 AND hso.martes_estado IS NOT NULL) OR
            (DAYOFWEEK(?) = 4 AND hso.miercoles_estado IS NOT NULL) OR
            (DAYOFWEEK(?) = 5 AND hso.jueves_estado IS NOT NULL) OR
            (DAYOFWEEK(?) = 6 AND hso.viernes_estado IS NOT NULL) OR
            (DAYOFWEEK(?) = 7 AND hso.sabado_estado IS NOT NULL) OR
            (DAYOFWEEK(?) = 1 AND hso.domingo_estado IS NOT NULL)
        )
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        GROUP BY o.CodOperario
        ORDER BY o.Nombre, o.Apellido
    ");
    
    $stmt->execute([
        $codSucursal, 
        $fechaFalta, 
        $fechaFalta, $fechaFalta, $fechaFalta, $fechaFalta, 
        $fechaFalta, $fechaFalta, $fechaFalta
    ]);
    
    return $stmt->fetchAll();
}

/**
 * Obtiene el horario programado y marcado de un operario para una fecha específica
 */
function obtenerHorarioProgramadoMarcado($codOperario, $codSucursal, $fecha) {
    global $conn;
    
    $resultado = [
        'programado' => null,
        'marcado' => null,
        'diferencia_entrada' => null,
        'diferencia_salida' => null
    ];
    
    // 1. Obtener horario programado
    $stmt = $conn->prepare("
        SELECT 
            hso.lunes_entrada, hso.lunes_salida,
            hso.martes_entrada, hso.martes_salida,
            hso.miercoles_entrada, hso.miercoles_salida,
            hso.jueves_entrada, hso.jueves_salida,
            hso.viernes_entrada, hso.viernes_salida,
            hso.sabado_entrada, hso.sabado_salida,
            hso.domingo_entrada, hso.domingo_salida,
            hso.lunes_estado, hso.martes_estado,
            hso.miercoles_estado, hso.jueves_estado,
            hso.viernes_estado, hso.sabado_estado,
            hso.domingo_estado
        FROM HorariosSemanalesOperaciones hso
        JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
        WHERE hso.cod_operario = ?
        AND hso.cod_sucursal = ?
        AND ? BETWEEN ss.fecha_inicio AND ss.fecha_fin
        LIMIT 1
    ");
    
    $stmt->execute([$codOperario, $codSucursal, $fecha]);
    $horario = $stmt->fetch();
    
    if ($horario) {
        // Determinar día de la semana (1=lunes, 2=martes, etc.)
        $diaSemana = date('N', strtotime($fecha));
        
        // Mapear a las columnas correctas
        $dias = [
            1 => ['entrada' => 'lunes_entrada', 'salida' => 'lunes_salida', 'estado' => 'lunes_estado'],
            2 => ['entrada' => 'martes_entrada', 'salida' => 'martes_salida', 'estado' => 'martes_estado'],
            3 => ['entrada' => 'miercoles_entrada', 'salida' => 'miercoles_salida', 'estado' => 'miercoles_estado'],
            4 => ['entrada' => 'jueves_entrada', 'salida' => 'jueves_salida', 'estado' => 'jueves_estado'],
            5 => ['entrada' => 'viernes_entrada', 'salida' => 'viernes_salida', 'estado' => 'viernes_estado'],
            6 => ['entrada' => 'sabado_entrada', 'salida' => 'sabado_salida', 'estado' => 'sabado_estado'],
            7 => ['entrada' => 'domingo_entrada', 'salida' => 'domingo_salida', 'estado' => 'domingo_estado']
        ];
        
        $dia = $dias[$diaSemana];
        
        $resultado['programado'] = [
            'entrada' => $horario[$dia['entrada']],
            'salida' => $horario[$dia['salida']],
            'estado' => $horario[$dia['estado']]
        ];
    }
    
    // 2. Obtener horario marcado
    $stmt = $conn->prepare("
        SELECT hora_ingreso, hora_salida 
        FROM marcaciones 
        WHERE CodOperario = ? 
        AND sucursal_codigo = ?
        AND fecha = ?
        LIMIT 1
    ");
    $stmt->execute([$codOperario, $codSucursal, $fecha]);
    $marcado = $stmt->fetch();
    
    if ($marcado) {
        $resultado['marcado'] = [
            'entrada' => $marcado['hora_ingreso'],
            'salida' => $marcado['hora_salida']
        ];
        
        // 3. Calcular diferencias si hay horario programado y marcado
        if ($resultado['programado'] && $resultado['programado']['entrada'] && $marcado['hora_ingreso']) {
            $horaProgramada = strtotime($resultado['programado']['entrada']);
            $horaMarcada = strtotime($marcado['hora_ingreso']);
            $resultado['diferencia_entrada'] = round(($horaMarcada - $horaProgramada) / 60); // En minutos
        }
        
        if ($resultado['programado'] && $resultado['programado']['salida'] && $marcado['hora_salida']) {
            $horaProgramada = strtotime($resultado['programado']['salida']);
            $horaMarcada = strtotime($marcado['hora_salida']);
            $resultado['diferencia_salida'] = round(($horaMarcada - $horaProgramada) / 60); // En minutos
        }
    }
    
    return $resultado;
}

/**
 * Formatea una hora a formato corto (HH:MM)
 */
function formatoHoraCorta($hora) {
    if (!$hora) return 'No';
    return date('H:i', strtotime($hora));
}

/**
 * Obtiene la cantidad de operarios activos con exclusiones específicas
 * (No considerar aquellos con cargo 27 y excluir ciertos IDs)
 */
function obtenerCantidadOperariosActivosFiltrados() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.CodOperario) as total_activos
        FROM Operarios o
        WHERE o.Operativo = 1
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        -- AND o.CodOperario NOT IN (566, 567, 568, 569, 570, 571, 572, 573, 574, 575, 576, 590)
    ");
    
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['total_activos'] ?? 0;
}

/**
 * Obtiene el código de sucursal por su nombre
 */
function obtenerCodigoSucursalPorNombre($nombreSucursal) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT codigo FROM sucursales WHERE nombre = ? LIMIT 1");
    $stmt->execute([$nombreSucursal]);
    $result = $stmt->fetch();
    
    return $result['codigo'] ?? null;
}

/**
 * Obtiene los códigos de cargo que corresponden a gerencia (ReportaA es NULL o vacío)
 */
function obtenerCodigosGerencia() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT CodNivelesCargos 
        FROM NivelesCargos 
        WHERE ReportaA IS NULL OR ReportaA = '' OR ReportaA = '0'
    ");
    $stmt->execute();
    $gerencias = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    return $gerencias;
}

/**
 * Verifica si un usuario es gerente
 */
function esGerente($codOperario = null) {
    if ($codOperario === null) {
        $codOperario = $_SESSION['usuario_id'] ?? 0;
    }
    
    // Si es admin, se considera gerente para efectos de aprobación
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }
    
    $codigosGerencia = obtenerCodigosGerencia();
    return verificarAccesoCargo($codigosGerencia);
}

/**
 * Obtiene el nombre del gerente por su ID
 */
function obtenerNombreGerente($gerenteId) {
    if (!$gerenteId) {
        return null;
    }
    
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT Nombre, Apellido 
        FROM Operarios 
        WHERE CodOperario = ?
    ");
    $stmt->execute([$gerenteId]);
    $result = $stmt->fetch();
    
    if ($result) {
        return trim($result['Nombre'] . ' ' . $result['Apellido']);
    }
    
    return null;
}
```

## /public_html/core/database/conexion.php
```php
<?php
date_default_timezone_set('America/Managua');

$servername = "localhost";
$username = "u839374897_erp";
$password = "ERpPitHay2025$";
$dbname = "u839374897_erp";

// verifica si se puede conectar  al abse de datos local, caso contrairo manda error
try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        die("Error al conectar con la base de datos. Por favor intente más tarde.");
    }
}

// Función para ejecutar consultas seguras
function ejecutarConsulta($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage() . " - SQL: " . $sql);
        return false;
    }
}
?>
```

## /public_html/core/auth/auth.php
```php
<?php
// public_html/includes/auth.php
session_start();
require_once '../../core/helpers/funciones.php';
require_once '../../core/database/conexion.php';
// Verificar autenticación, usuario_id es el codoperario de quien se loguea
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit();
    }
}

// Obtener información del usuario actual
function obtenerUsuarioActual() {
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }
    
    global $conn;
    $stmt = $conn->prepare("
        SELECT o.*, nc.Nombre as cargo_nombre, nc.CodNivelesCargos
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
        WHERE o.CodOperario = ? 
        AND (anc.Fin IS NULL OR anc.Fin >= NOW())
        ORDER BY anc.Fecha DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch();
}

// Verificar acceso a módulo
function verificarAccesoModulo($modulo) {
    verificarAutenticacion();
    
    $usuario = obtenerUsuarioActual();
    
    // Admin tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return;
    }
    
    // Definir qué cargos pueden acceder a qué módulos, aquí es para asignar uno o más cargos dentro del sistema web
    $permisosPorCargo = [
        2 => ['operario'], // Operario
        5 => ['lideres'], // Lider de Sucursal
        8 => ['contabilidad'], // Jefe de Contabilidad
        9 => ['compras'], // Jefe de Compras
        10 => ['logistica'], // Jefe de Logística
        11 => ['operaciones'], // Jefe de Operaciones
        12 => ['produccion'], // Jefe de Producción
        13 => ['rh'], // Jefe de Recursos Humanos
        14 => ['mantenimiento'], // Jefe de Mantenimiento
        15 => ['sistema'], // Jefe de Sistemas
        // ... agregar todos los demás según la tabla NivelesCargos
        16 => ['gerencia'], // Gerencia
        17 => ['almacen'], // Almacén
        19 => ['cds'], // Jefe de CDS
        20 => ['chofer'], //Chofer
        21 => ['supervision'], // Supervisor de Sucursales
        22 => ['atencioncliente'], // Atencion al Cliente
        23 => ['almacen'], // Auxiliar de Almacén
        24 => ['motorizado'], // Motorizado
        25 => ['diseno'], // Diseñador
        26 => ['marketing'], // Marketing
        27 => ['sucursales'], // Sucursales
        35 => ['infraestructura'],
        38 => ['auxiliaradministrativo'],
        39 => 'rh',
        30 => 'rh',
        37 => 'rh',
        42 => 'marketing',
        43 => 'lideres',
        44 => 'operarios',
        45 => 'operarios',
        46 => 'operarios',
        47 => 'operarios',
        36 => 'operaciones'
        // ... etc.
    ];
    
    $cargo = $_SESSION['cargo_cod'] ?? null;
    
    if (!in_array($modulo, $permisosPorCargo[$cargo] ?? [])) {
        header('Location: ../index.php');
        exit();
    }
}
```

## /public_html/core/assets/css/indexmodulos.css
```css
/* public_html/assets/indexmodulos.css */
@import 'global_tools.css';
/* ==================== CONTENEDOR PRINCIPAL ==================== */

/* Sección de título */
.section-title {
    color: #0E544C;
    font-size: 1.5rem !important;
    margin: 30px 0 20px 0;
    padding-left: 15px;
    border-left: 5px solid #51B8AC;
    font-weight: 600;
}

/* Estilos para el contenedor de indicadores */
.indicadores-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Estilos para el indicador de tardanzas pendientes */
.indicator-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.indicator-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%);
}

.indicator-container:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.indicator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.indicator-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem !important;
    background: linear-gradient(135deg, #51B8AC20 0%, #0E544C20 100%);
    color: #0E544C;
}

.indicator-count {
    font-size: 2.5rem !important;
    font-weight: bold;
    color: #0E544C;
    margin: 10px 0;
}

.indicator-info {
    text-align: center;
    margin-top: 10px;
}

.indicator-titulo {
    color: #666;
    font-size: 0.95rem !important;
    font-weight: 500;
}

.indicator-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.indicator-status {
    font-size: 0.85rem !important;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
}


.indicator-status.verde {
    background: #28a745;
    color: black;
}

.indicator-status.amarillo {
    background: #ffc107;
    color: black;
}

.indicator-status.rojo {
    background: #dc3545;
    color: black;
}
.indicator-status.naranja {
    background: #ffc107;
    color: black;  /* ← Cambié a blanco para mejor contraste */
}

 .indicator-action {
    color: #51B8AC;
    font-size: 0.85rem !important;
    font-weight: 600;
}


/* Accesos Rápidos */
.quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.quick-access-grid {
    grid-template-columns: repeat(2, 1fr);
}
    
.quick-access-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 140px;
}

.quick-access-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(81, 184, 172, 0.2);
}

.quick-access-icon {
    font-size: 2rem !important;
    color: #51B8AC;
    margin-bottom: 10px;
}

.quick-access-title {
    font-size: 0.9rem !important;
    font-weight: 600;
    color: #0E544C;
}
        
@media (max-width: 768px) {
    .indicadores-container {
        grid-template-columns: 1fr;
    }
    .indicator-count {
        font-size: 2.5rem !important;
    }
    .indicator-info {
        text-align: center;
    }
}
```

## /public_html/core/assets/css/global_tools.css
```css
/* public_html/assets/css/global_tools.css */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Calibri', sans-serif;
    font-size: clamp(12px, 2vw, 18px) 
}

body {
    background-color: #F6F6F6;
    color: #333;
    margin: 0;
    padding: 0;
}

.main-container {
    margin-left: 70px;
    transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 100vh;
}

.sub-container {
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

        
@media (max-width: 768px) {
    .main-container {
        margin-left: 0;
    }
}
```