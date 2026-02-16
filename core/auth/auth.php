<?php
// /public_html/core/auth/auth.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ✅ USAR RUTAS ABSOLUTAS basadas en DOCUMENT_ROOT
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/helpers/funciones.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/database/conexion.php';

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
    
    // Definir qué cargos pueden acceder a qué módulos
    $permisosPorCargo = [
        2 => ['operario'],
        5 => ['lideres'],
        8 => ['contabilidad'],
        9 => ['compras'],
        10 => ['logistica'],
        11 => ['operaciones'],
        12 => ['produccion'],
        13 => ['rh'],
        14 => ['mantenimiento'],
        15 => ['sistema'],
        16 => ['gerencia'],
        17 => ['almacen'],
        19 => ['cds'],
        20 => ['chofer'],
        21 => ['supervision'],
        22 => ['atencioncliente'],
        23 => ['almacen'],
        24 => ['motorizado'],
        25 => ['diseno'],
        26 => ['marketing'],
        27 => ['sucursales'],
        35 => ['infraestructura'],
        38 => ['auxiliaradministrativo'],
        39 => ['rh'],
        30 => ['rh'],
        37 => ['rh'],
        42 => ['marketing'],
        43 => ['lideres'],
        44 => ['operarios'],
        45 => ['operarios'],
        46 => ['operarios'],
        47 => ['operarios'],
        49 => ['gerencia'],
        36 => ['operaciones']
    ];
    
    $cargo = $_SESSION['cargo_cod'] ?? null;
    
    if (!in_array($modulo, $permisosPorCargo[$cargo] ?? [])) {
        header('Location: /index.php');
        exit();
    }
}