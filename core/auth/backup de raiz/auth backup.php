<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

// Verificar autenticación
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

    $esAdmin = isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
    
    if ($esAdmin) {
        return [
            'nombre' => $_SESSION['usuario_nombre'] ?? '',
            'rol' => 'admin'
        ];
    }

    // Para operarios, obtener información completa
    $sql = "SELECT o.CodOperario, o.Nombre, o.Apellido, o.usuario,
                   nc.Nombre as cargo_nombre, s.nombre as sucursal_nombre, s.codigo as sucursal_codigo
            FROM Operarios o
            LEFT JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario 
                AND (anc.Fin IS NULL OR anc.Fin > CURDATE())
                AND anc.Fecha <= CURDATE()
            LEFT JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
            LEFT JOIN sucursales s ON anc.Sucursal = s.codigo
            WHERE o.CodOperario = ?
            LIMIT 1";
    
    $stmt = ejecutarConsulta($sql, [$_SESSION['usuario_id']]);
    
    if ($stmt && $usuario = $stmt->fetch()) {
        return $usuario;
    }

    return null;
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
        2 => ['operario', 'publico'], // Operario
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
        // ... etc.
    ];
    
    $cargo = $_SESSION['cargo_cod'] ?? null;
    
    if (!in_array($modulo, $permisosPorCargo[$cargo] ?? [])) {
        header('Location: /index.php');
        exit();
    }
}