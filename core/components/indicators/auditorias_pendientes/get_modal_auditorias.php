<?php
/**
 * Endpoint AJAX para Modal de Auditorías Pendientes
 * Obtiene los datos del indicador y renderiza el modal
 */

// Iniciar sesión y cargar dependencias
session_start();
require_once '../../../database/conexion.php';

// Cargar el autoloader de ComponentRegistry
require_once '../../ComponentRegistry.php';

// Cargar funciones auxiliares globales
require_once '../../../helpers/funciones.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo '<div style="text-align: center; padding: 40px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
        <p>Sesión no válida</p>
    </div>';
    exit;
}

$userId = $_SESSION['usuario_id'];

try {
    // Crear instancia del indicador usando el namespace completo
    $indicator = new \Core\Components\Indicators\List\AuditoriasPendientesIndicator($conn, '{}');

    // Obtener datos del modal
    $modalData = $indicator->getModalData($userId);

    // Renderizar el template del modal
    include 'auditorias_pendientes_modal.php';

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en modal de auditorías: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo '<div style="text-align: center; padding: 40px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
        <p>Error al cargar los datos del modal</p>
        <small style="color: #666; display: block; margin-top: 10px;">' . htmlspecialchars($e->getMessage()) . '</small>
    </div>';
}
