<?php
/**
 * POS Authentication Framework - auth_pos.php
 * Batidos Pitaya | Punto de Venta
 *
 * Maneja la autenticación en dos etapas:
 *   Etapa 1: Usuario de Sucursal (CodNivelesCargos = 27) desbloquea la terminal
 *   Etapa 2: Colaborador ingresa su clave para operar, validando marcación activa
 *
 * Seguridad adicional: Solo dispositivos autorizados mediante erp_device_token
 */

// Sesiones de larga duración (2 horas mínimo)
$pos_session_lifetime = 7200;
ini_set('session.gc_maxlifetime', $pos_session_lifetime);
session_set_cookie_params([
    'lifetime' => $pos_session_lifetime,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/database/conexion.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/helpers/funciones.php';

// ---------------------------------------------------------------------------
// HELPERS DE VERIFICACIÓN DE ESTADO
// ---------------------------------------------------------------------------

/**
 * Verifica si el dispositivo está autorizado para el POS.
 * Reutiliza la lógica del ERP: cookie erp_device_token vs sucursales.cookie_token.
 * 
 * Retorna: ['status' => bool, 'msg' => string, 'sucursal_codigo' => int|null]
 */
function posVerificarDispositivo()
{
    global $conn;

    // 1. Verificar Navegador (Chrome/Edge)
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $esChromium = (strpos($ua, 'Chrome') !== false || strpos($ua, 'Edg') !== false);
    if (!$esChromium) {
        return [
            'status'  => false,
            'msg'     => 'Navegador no permitido. Usa Google Chrome o Microsoft Edge.',
            'sucursal_codigo' => null,
        ];
    }

    // 2. Verificar cookie exclusiva del POS (pos_device_token)
    //    Completamente independiente de erp_device_token del ERP
    $tokenCookie = $_COOKIE['pos_device_token'] ?? null;
    if (empty($tokenCookie)) {
        return [
            'status'  => false,
            'msg'     => 'Este dispositivo no cuenta con una autorización activa. Por favor, contacta al área de TI para configurar este dispositivo.',
            'sucursal_codigo' => null,
        ];
    }

    // 3. Buscar el token en la columna pos_cookie_token (exclusiva del POS)
    try {
        $stmt = $conn->prepare("SELECT codigo FROM sucursales WHERE pos_cookie_token = ? AND activa = 1 LIMIT 1");
        $stmt->execute([$tokenCookie]);
        $sucursal = $stmt->fetch();

        if (!$sucursal) {
            return [
                'status'  => false,
                'msg'     => 'La autorización de este dispositivo ya no es válida o la sucursal ha sido desactivada. Por favor, contacta al área de TI.',
                'sucursal_codigo' => null,
            ];
        }

        // Guardar en sesión para uso posterior
        $_SESSION['pos_device_sucursal'] = $sucursal['codigo'];

        return [
            'status'  => true,
            'msg'     => '',
            'sucursal_codigo' => $sucursal['codigo'],
        ];

    } catch (Exception $e) {
        error_log("POS - Error validando dispositivo: " . $e->getMessage());
        return [
            'status'  => false,
            'msg'     => 'Error de sistema al validar el dispositivo POS.',
            'sucursal_codigo' => null,
        ];
    }
}

/**
 * Retorna true si hay una sesión de tienda activa (Etapa 1 completada).
 */
function posTiendaAutenticada()
{
    return isset($_SESSION['pos_store_id']) && isset($_SESSION['pos_store_sucursal']);
}

/**
 * Retorna true si hay un colaborador activo en sesión (Etapa 2 completada).
 */
function posColaboradorAutenticado()
{
    return isset($_SESSION['pos_colaborador_id']);
}

/**
 * Middleware: redirige si la tienda no está autenticada.
 */
function posRequiereAutenticacionTienda()
{
    if (!posTiendaAutenticada()) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Middleware: redirige si el colaborador no está autenticado.
 */
function posRequiereColaborador()
{
    posRequiereAutenticacionTienda();
    if (!posColaboradorAutenticado()) {
        header('Location: /index.php');
        exit();
    }
}

/**
 * Middleware para AJAX: retorna error JSON si no hay colaborador autenticado.
 */
function posRequiereColaboradorAjax()
{
    if (!posTiendaAutenticada() || !posColaboradorAutenticado()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'status' => 'error',
            'mensaje' => 'Sesión expirada o no autorizada. Por favor, recarga la página.'
        ]);
        exit();
    }
}

/**
 * Obtiene información del colaborador actual (compatible con módulos ERP).
 * Reemplaza la función original de auth.php
 */
function obtenerUsuarioActual()
{
    $id = $_SESSION['pos_colaborador_id'] ?? null;
    if (!$id) return null;

    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT o.*, nc.Nombre as cargo_nombre, nc.CodNivelesCargos, anc.Sucursal as sucursal_codigo
            FROM Operarios o
            JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
            JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
            WHERE o.CodOperario = ? 
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            ORDER BY anc.Fecha DESC
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("POS - Error en obtenerUsuarioActual: " . $e->getMessage());
        return null;
    }
}

// ---------------------------------------------------------------------------
// ETAPA 1: INICIO DE SESIÓN DE TIENDA (Usuario CodNivelesCargos = 27)
// ---------------------------------------------------------------------------

/**
 * Valida las credenciales del usuario de sucursal.
 *
 * @param string $usuario
 * @param string $clave   Contraseña en texto plano
 * @return array ['status' => bool, 'msg' => string]
 */
function posValidarLoginTienda($usuario, $clave)
{
    if (empty($usuario) || empty($clave)) {
        return ['status' => false, 'msg' => 'Completa usuario y contraseña.'];
    }

    $stmt = ejecutarConsulta("
        SELECT
            o.CodOperario,
            o.usuario,
            o.clave,
            o.clave_hash,
            o.Operativo,
            anc.Sucursal,
            s.nombre as sucursal_nombre
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE o.usuario = ?
          AND anc.CodNivelesCargos = 27
          AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        LIMIT 1
    ", [$usuario]);

    if (!$stmt) {
        return ['status' => false, 'msg' => 'Error al consultar base de datos.'];
    }

    $user = $stmt->fetch();

    if (!$user) {
        return ['status' => false, 'msg' => 'Usuario no encontrado o sin permisos de sucursal.'];
    }

    if (!$user['Operativo']) {
        return ['status' => false, 'msg' => 'Cuenta desactivada. Contacta a Recursos Humanos.'];
    }

    // Verificar que la sucursal del usuario coincida con la del dispositivo
    $dispositivoSucursal = $_SESSION['pos_device_sucursal'] ?? null;
    if ($dispositivoSucursal && $user['Sucursal'] != $dispositivoSucursal) {
        return [
            'status' => false,
            'msg'    => 'Tu usuario no corresponde a la sucursal de este dispositivo.',
        ];
    }

    // Validación de contraseña: prioridad bcrypt, respaldo texto plano
    $claveValida = false;
    if (!empty($user['clave_hash']) && password_verify($clave, $user['clave_hash'])) {
        $claveValida = true;
    } elseif ($clave === $user['clave']) {
        $claveValida = true;
    }

    if (!$claveValida) {
        return ['status' => false, 'msg' => 'Contraseña incorrecta.'];
    }

    // Guardar sesión de tienda
    $_SESSION['pos_store_id']       = $user['CodOperario'];
    $_SESSION['pos_store_usuario']  = $user['usuario'];
    $_SESSION['pos_store_sucursal'] = $user['Sucursal'];
    $_SESSION['pos_store_sucursal_nombre'] = $user['sucursal_nombre'];

    return ['status' => true];
}

// ---------------------------------------------------------------------------
// ETAPA 2: ACCESO DE COLABORADOR (solo clave + validación de marcación)
// ---------------------------------------------------------------------------

/**
 * Valida el PIN/clave de un colaborador y verifica su marcación activa en la sucursal.
 *
 * @param string $clave  Contraseña/PIN del colaborador
 * @return array ['status' => bool, 'msg' => string]
 */
function posValidarColaborador($clave)
{
    if (!posTiendaAutenticada()) {
        return ['status' => false, 'msg' => 'Sesión de tienda no activa.'];
    }

    if (empty($clave)) {
        return ['status' => false, 'msg' => 'Ingresa tu clave.'];
    }

    $sucursal = $_SESSION['pos_store_sucursal'];
    $hoy      = date('Y-m-d');

    // Buscar colaboradores activos (excluir cargo 27 = encargados de sucursal)
    $stmt = ejecutarConsulta("
        SELECT
            o.CodOperario,
            o.Nombre,
            o.Apellido,
            o.clave,
            o.clave_hash
        FROM Operarios o
        WHERE o.Operativo = 1
          AND o.CodOperario NOT IN (
              SELECT DISTINCT anc.CodOperario
              FROM AsignacionNivelesCargos anc
              WHERE anc.CodNivelesCargos = 27
                AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
          )
    ", []);

    if (!$stmt) {
        return ['status' => false, 'msg' => 'Error al consultar base de datos.'];
    }

    $operarios = $stmt->fetchAll();
    $encontrado = null;

    foreach ($operarios as $op) {
        // Prioridad: clave_hash (bcrypt); respaldo: clave (texto plano)
        if (!empty($op['clave_hash']) && password_verify($clave, $op['clave_hash'])) {
            $encontrado = $op;
            break;
        } elseif ($clave === $op['clave']) {
            $encontrado = $op;
            break;
        }
    }

    if (!$encontrado) {
        return ['status' => false, 'msg' => 'Clave incorrecta.'];
    }

    // Verificar marcación activa HOY en esta sucursal
    $stmtMarc = ejecutarConsulta("
        SELECT id
        FROM marcaciones
        WHERE CodOperario    = ?
          AND fecha           = ?
          AND sucursal_codigo = ?
          AND hora_ingreso    IS NOT NULL
          AND (hora_salida IS NULL OR hora_salida = '')
        LIMIT 1
    ", [$encontrado['CodOperario'], $hoy, $sucursal]);

    if (!$stmtMarc || !$stmtMarc->fetch()) {
        return [
            'status' => false,
            'msg'    => 'Acceso denegado: No tienes una marcación de entrada activa hoy en esta sucursal.',
        ];
    }

    // Sesión de colaborador
    $_SESSION['pos_colaborador_id']     = $encontrado['CodOperario'];
    $_SESSION['pos_colaborador_nombre'] = trim($encontrado['Nombre'] . ' ' . $encontrado['Apellido']);

    return ['status' => true];
}

// ---------------------------------------------------------------------------
// CIERRE DE SESIÓN
// ---------------------------------------------------------------------------

/**
 * Cierra solo la sesión del colaborador actual.
 */
function posCerrarSesionColaborador()
{
    unset(
        $_SESSION['pos_colaborador_id'],
        $_SESSION['pos_colaborador_nombre']
    );
}

/**
 * Cierra la sesión completa (colaborador + tienda).
 */
function posCerrarSesionCompleta()
{
    session_unset();
    session_destroy();
}
?>
