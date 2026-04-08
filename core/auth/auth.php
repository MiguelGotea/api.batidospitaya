// ✅ INTEGRACIÓN POS (Doble Autenticación)
// Este archivo actúa como puente para que los módulos copiados del ERP
// cumplan con la seguridad del POS: Dispositivo + Tienda + Colaborador.
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/auth/auth_pos.php';

// Exigir que tanto la tienda como el colaborador estén autenticados
// Esto protege automáticamente todos los archivos que incluyen auth.php
posRequiereColaborador();

// ✅ Mapeo de sesión para compatibilidad con funciones de este archivo
// Los módulos originales usan $_SESSION['usuario_id'], lo sincronizamos con el colaborador del POS.
if (isset($_SESSION['pos_colaborador_id'])) {
    $_SESSION['usuario_id'] = $_SESSION['pos_colaborador_id'];
    
    // Si no tenemos el código de cargo en sesión, lo buscamos una vez para compatibilidad
    if (!isset($_SESSION['cargo_cod'])) {
        global $conn;
        $stmtC = $conn->prepare("SELECT CodNivelesCargos FROM AsignacionNivelesCargos WHERE CodOperario = ? AND (Fin IS NULL OR Fin >= CURDATE()) ORDER BY Fecha DESC LIMIT 1");
        $stmtC->execute([$_SESSION['pos_colaborador_id']]);
        $rowC = $stmtC->fetch();
        if ($rowC) {
            $_SESSION['cargo_cod'] = $rowC['CodNivelesCargos'];
        }
    }
}

// ✅ USAR RUTAS ABSOLUTAS basadas en DOCUMENT_ROOT
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/helpers/funciones.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/database/conexion.php';

// Verificar autenticación
function verificarAutenticacion()
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit();
    }
}

// Obtener información del usuario actual
function obtenerUsuarioActual()
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    global $conn;
    $stmt = $conn->prepare("
        SELECT o.*, nc.Nombre as cargo_nombre, nc.CodNivelesCargos, anc.Sucursal as sucursal_codigo
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
        WHERE o.CodOperario = ? 
        AND (anc.Fin IS NULL OR anc.Fin > NOW())
        ORDER BY anc.Fecha DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    return $stmt->fetch();
}

// Verificar acceso a módulo
function verificarAccesoModulo($modulo)
{
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