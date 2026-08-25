<?php
// /public_html/core/auth/auth.php

// Extender sesión a 6 horas (21600 segundos equivalente)
ini_set('session.gc_maxlifetime', 21600);
session_set_cookie_params(21600);
session_start();

// ✅ USAR RUTAS ABSOLUTAS basadas en DOCUMENT_ROOT
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/helpers/funciones.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/database/conexion.php';

// Verificar autenticación
function verificarAutenticacion()
{
    if (!isset($_SESSION['usuario_id'])) {
        // Pasar la URL actual como parámetro redirect para regresar después del login
        $redirectUrl = $_SERVER['REQUEST_URI'] ?? '';
        $loginUrl = '/login.php';
        if (!empty($redirectUrl) && $redirectUrl !== '/login.php') {
            $loginUrl = '/login.php?redirect=' . urlencode($redirectUrl);
        }
        header('Location: ' . $loginUrl);
        exit();
    }
}

// Obtener información del usuario actual
function obtenerUsuarioActual()
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    if (isset($_SESSION['datos_usuario_actual'])) {
        return $_SESSION['datos_usuario_actual'];
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
    $usuario = $stmt->fetch();

    if ($usuario) {
        $_SESSION['datos_usuario_actual'] = $usuario;
    }

    return $usuario;
}

// Verificar acceso a módulo
function verificarAccesoModulo($modulo)
{
    verificarAutenticacion();

    // Admin tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return;
    }

    // Normalizar nombres de módulos comunes (singular/plural)
    $moduloBuscado = trim(strtolower($modulo));
    if ($moduloBuscado === 'operario') $moduloBuscado = 'operarios';
    if ($moduloBuscado === 'sistema') $moduloBuscado = 'sistemas';

    if (!isset($_SESSION['modulos_permitidos'])) {
        $cargosUsuario = obtenerCargosUsuario($_SESSION['usuario_id']);
        if (empty($cargosUsuario)) {
            $_SESSION['modulos_permitidos'] = [];
        } else {
            global $conn;
            $placeholders = implode(',', array_fill(0, count($cargosUsuario), '?'));
            try {
                $stmt = $conn->prepare("
                    SELECT DISTINCT modulo_ruta 
                    FROM NivelesCargos 
                    WHERE CodNivelesCargos IN ($placeholders) 
                      AND modulo_ruta IS NOT NULL
                ");
                $stmt->execute($cargosUsuario);
                $_SESSION['modulos_permitidos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (PDOException $e) {
                error_log("Error al cargar modulos_permitidos: " . $e->getMessage());
                $_SESSION['modulos_permitidos'] = [];
            }
        }
    }

    $tieneAcceso = in_array($moduloBuscado, $_SESSION['modulos_permitidos']);

    if (!$tieneAcceso) {
        header('Location: /index.php');
        exit();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// VALIDACIÓN AUTOMÁTICA DE DISPOSITIVO PARA CARGO 27 (SUCURSALES)
// Se ejecuta en CADA carga de página para usuarios con ese cargo.
// Al estar aquí, protege automáticamente TODAS las herramientas actuales
// y futuras sin necesidad de modificar ningún archivo adicional.
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_SESSION['usuario_id']) && isset($_SESSION['cargo_cod']) && (int)$_SESSION['cargo_cod'] === 27) {

    // Obtener sucursal del caché de sesión para evitar consultas innecesarias
    $sucursalCargo27 = $_SESSION['datos_usuario_actual']['sucursal_codigo'] ?? null;

    if (!$sucursalCargo27) {
        // Consulta ligera si el caché aún no está disponible en esta petición
        global $conn;
        $stmtSuc = $conn->prepare(
            "SELECT anc.Sucursal FROM AsignacionNivelesCargos anc
             WHERE anc.CodOperario = ?
               AND anc.CodNivelesCargos = 27
               AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
             ORDER BY anc.Fecha DESC LIMIT 1"
        );
        $stmtSuc->execute([$_SESSION['usuario_id']]);
        $rowSuc = $stmtSuc->fetch();
        $sucursalCargo27 = $rowSuc['Sucursal'] ?? null;
    }

    if (!$sucursalCargo27) {
        // Sin sucursal asignada: invalidar sesión y regresar al login
        session_destroy();
        header('Location: /login.php?error=' . urlencode('Tu usuario no tiene sucursal asignada. Contacta a soporte técnico.'));
        exit();
    }

    $validacionDispositivo = verificarDispositivoAutorizado($sucursalCargo27);

    if (!$validacionDispositivo['status']) {
        // Dispositivo no autorizado: no destruimos sesión para que el usuario
        // pueda intentar desde un dispositivo autorizado sin necesidad de re-loguearse.
        header('Location: /login.php?error=' . urlencode($validacionDispositivo['msg']));
        exit();
    }
}
