<?php
session_start();
require_once '../../../core/database/conexion.php'; // Cambiado: anteriormente llamaba al conexion de auditor�as, ahora llama al del core;
require_once '../../../core/helpers/funciones.php'; // Antes llamaba a funciones.php de auditor�a

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
        // ... etc.
    ];
    
    $cargo = $_SESSION['cargo_cod'] ?? null;
    
    if (!in_array($modulo, $permisosPorCargo[$cargo] ?? [])) {
        header('Location: ../index.php');
        exit();
    }
}
