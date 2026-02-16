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