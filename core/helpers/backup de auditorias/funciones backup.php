<?php
// Funciones útiles para todo el sistema

/**
 * Formatea una fecha al formato ej: 31-abr-25
 */
function formatoFecha($fecha)
{
    if (empty($fecha) || $fecha === null) {
        return '';
    }

    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    try {
        $fechaObj = new DateTime($fecha);
        $mes = $meses[(int) $fechaObj->format('m') - 1];
        return $fechaObj->format('d') . '-' . $mes . '-' . $fechaObj->format('y');
    } catch (Exception $e) {
        // Si hay error al parsear la fecha, devolver string vacío
        return '';
    }
}

/**
 * Obtiene el nombre del mes en español (alternativa moderna)
 */
function obtenerMesEspanol($fecha)
{
    if (is_string($fecha)) {
        $fecha = new DateTime($fecha);
    }

    $meses = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    $mesNumero = (int) $fecha->format('n');
    return $meses[$mesNumero] ?? '';
}

function formatoMesAnio($fecha)
{
    if (empty($fecha))
        return '';

    try {
        $fechaObj = new DateTime($fecha);
        $mes = obtenerMesEspanol($fechaObj);
        $anio = $fechaObj->format('Y');

        return $mes . ' ' . $anio;
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Formatea una fecha al formato español ej: 15-oct-2025
 * Reemplaza a strftime() obsoleto
 */
function formatoFechaEspanol($fecha = null)
{
    if (empty($fecha)) {
        $fecha = time();
    }

    if (is_string($fecha)) {
        $fecha = strtotime($fecha);
    }

    $meses = [
        'Jan' => 'ene',
        'Feb' => 'feb',
        'Mar' => 'mar',
        'Apr' => 'abr',
        'May' => 'may',
        'Jun' => 'jun',
        'Jul' => 'jul',
        'Aug' => 'ago',
        'Sep' => 'sep',
        'Oct' => 'oct',
        'Nov' => 'nov',
        'Dec' => 'dic'
    ];

    $fechaIngles = date('d-M-Y', $fecha);
    $partes = explode('-', $fechaIngles);

    if (count($partes) === 3) {
        $dia = $partes[0];
        $mes = $meses[$partes[1]] ?? $partes[1];
        $anio = $partes[2];
        return "$dia-$mes-$anio";
    }

    return date('d-m-Y', $fecha);
}

/**
 * Verifica si un usuario tiene un cargo específico
 */
function tieneCargo($cargoRequerido)
{
    if (!isset($_SESSION['cargo_cod'])) {
        return false;
    }

    // Los cargos pueden tener jerarquía si es necesario
    $jerarquia = [
        'gerencia' => 16,
        'jefe' => [8, 9, 10, 11, 12, 13, 14, 15, 17, 19, 21, 22, 26],
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
function requerirCargo($cargoRequerido)
{
    if (!tieneCargo($cargoRequerido)) {
        header('Location: /index.php');
        exit();
    }
}

/**
 * Redirige a la página de inicio según el cargo
 */
function redirigirSegunCargo()
{
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

    // Mapeo de cargos a rutas, los códigos de cargos que pueden ir a una carpeta específica, aquí están todos los cargos
    $redirecciones = [
        2 => '/modulos/operarios/',      // Operario
        5 => '/modulos/lideres/',      // Líder de Sucursal
        8 => '/modulos/contabilidad/',     // Jefe de Contabilidad
        9 => '/modulos/compras/',           // Jefe de Compras
        10 => '/modulos/logistica/',        // Jefe de Logística
        11 => '/modulos/operaciones/',      // Jefe de Operaciones
        12 => '/modulos/produccion/',       // Jefe de Producción
        13 => '/modulos/rh/',              // Jefe de Recursos Humanos
        14 => '/modulos/mantenimiento/',    // Jefe de Mantenimiento
        15 => '/modulos/sistemas/',         // Jefe de Sistemas
        16 => '/modulos/gerencia/',       // Gerencia
        17 => '/modulos/almacen/',          // Jefe de Almacén
        19 => '/modulos/cds/',             // Jefe de CDS
        20 => '/modulos/chofer/',     // Chofer
        21 => '/modulos/supervision/',     // Supervisor de Sucursales
        22 => '/modulos/atencioncliente/', // Atencion al Cliente
        23 => '/modulos/almacen/',         // Auxiliar de Almacen
        24 => '/modulos/motorizado/',     // Motorizado
        25 => '/modulos/diseno/',           // Diseñador
        26 => '/modulos/marketing/',        // Jefe de Marketing
        27 => '/modulos/sucursales/'        // Sucursales
    ];

    $destino = $redirecciones[$_SESSION['cargo_cod']] ?? '/index.php';
    header("Location: $destino");
    exit();
}

/**
 * Verifica si el usuario tiene alguno de los cargos especificados
 */
function verificarAccesoCargo($cargosRequeridos)
{
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }

    // Si es admin, tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }

    global $conn;
    $cargosRequeridos = (array) $cargosRequeridos;
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
function obtenerNombreUsuario()
{
    $usuario = obtenerUsuarioActual();

    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return $usuario['nombre'];
    } else {
        return $usuario['Nombre'] . ' ' . $usuario['Apellido'];
    }
}

/**
 * Verifica si el usuario tiene un cargo específico y está asignado a una sucursal específica
 */
function verificarAccesoSucursalCargo($cargosRequeridos, $sucursalesRequeridas)
{
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }

    // Si es admin, tiene acceso a todo
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return true;
    }

    global $conn;
    $cargosRequeridos = (array) $cargosRequeridos;
    $sucursalesRequeridas = (array) $sucursalesRequeridas;

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
function obtenerSemanaActual()
{
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
function obtenerSemanaPorId($id)
{
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
function obtenerSemanasDisponibles()
{
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
 */
function obtenerSucursalesLider($codOperario)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT s.codigo, s.nombre 
        FROM AsignacionNivelesCargos anc
        JOIN sucursales s ON anc.Sucursal = s.codigo
        WHERE anc.CodOperario = ? 
        AND anc.CodNivelesCargos = 5 
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        GROUP BY s.codigo, s.nombre
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetchAll();
}

/**
 * Obtiene los operarios activos de una sucursal para un rango de fechas
 */
function obtenerOperariosSucursal($codSucursal, $fechaInicio, $fechaFin)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT o.CodOperario, o.Nombre, o.Apellido 
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.Sucursal = ?
        AND o.Operativo = 1
        AND (anc.Fin IS NULL OR anc.Fin >= ?)
        AND anc.Fecha <= ?
        AND o.CodOperario NOT IN (
            SELECT DISTINCT CodOperario 
            FROM AsignacionNivelesCargos 
            WHERE CodNivelesCargos = 27
            AND (Fin IS NULL OR Fin >= ?)
            AND Fecha <= ?
        )
        GROUP BY o.CodOperario, o.Nombre, o.Apellido
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio]);
    return $stmt->fetchAll();
}

/**
 * Obtiene los operarios Solo con horarios por parte del líder de una sucursal
 */
function obtenerOperariosSucursalConHorario($codSucursal, $idSemana)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT DISTINCT o.CodOperario, o.Nombre, o.Apellido 
        FROM Operarios o
        JOIN HorariosSemanales hs ON o.CodOperario = hs.cod_operario
        WHERE hs.cod_sucursal = ?
        AND hs.id_semana_sistema = ?
        AND o.Operativo = 1
        AND o.CodOperario NOT IN (566, 567, 568, 569, 570, 571, 572, 573, 574, 575, 576, 590)
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal, $idSemana]);
    return $stmt->fetchAll();
}

/**
 * Obtiene el horario de un operario para una semana y sucursal específica
 */
function obtenerHorarioOperario($codOperario, $numeroSemana, $codSucursal)
{
    global $conn;

    // Primero obtener el ID de la semana
    $semana = obtenerSemanaPorNumero($numeroSemana);
    if (!$semana)
        return null;

    $stmt = $conn->prepare("
        SELECT * FROM HorariosSemanales
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
function obtenerSemanaPorNumero($numeroSemana)
{
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
 * Función para traducir meses al español en formatos tipo dd-Mmm-yy
 */
function traducirMes($fechaFormateada)
{
    $mesesIngles = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dec'];
    $mesesEspanol = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    return str_replace($mesesIngles, $mesesEspanol, $fechaFormateada);
}

/**
 * Formatea una fecha al formato ej: 31-abr-25 (día-mes-año)
 */
function formatoFechaCorta($fecha)
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fechaObj = new DateTime($fecha);
    $mes = $meses[(int) $fechaObj->format('m') - 1];
    return $fechaObj->format('d') . '-' . $mes . '-' . $fechaObj->format('y');
}

/**
 * Formatea una hora al formato 12 horas con AM/PM
 */
function formatoHoraAmPm($hora)
{
    if (empty($hora) || $hora == '00:00:00') {
        return '-';
    }
    return date('h:i A', strtotime($hora));
}

/**
 * Obtiene la semana del sistema para una fecha específica
 */
function obtenerSemanaPorFecha($fecha)
{
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
function obtenerHorarioOperacionesPorDia($codOperario, $idSemana, $codSucursal, $fecha)
{
    global $conn;

    // Primero obtener el día de la semana (0=domingo, 1=lunes, etc.)
    $stmt = $conn->prepare("SELECT DAYOFWEEK(?) as dia_semana");
    $stmt->execute([$fecha]);
    $diaSemana = $stmt->fetch()['dia_semana'];

    // Ajustar a nuestro sistema donde 1=lunes, 7=domingo
    $diaSemana = $diaSemana - 1;
    if ($diaSemana == 0)
        $diaSemana = 7;

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
 * Obtiene la IP permitida para la sucursal del usuario actual
 */
function obtenerIpPermitidaSucursal($codSucursal)
{
    global $conn;

    $stmt = $conn->prepare("SELECT ip_direccion FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();

    return $result['ip_direccion'] ?? null;
}

/**
 * Verifica si la IP actual coincide con la IP permitida para la sucursal o cualquier sucursal del mismo departamento
 */
function verificarIpSucursal($codSucursal)
{
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
function obtenerIpCliente()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Obtiene las sucursales asignadas a un usuario (no necesariamente líder)
 */
function obtenerSucursalesUsuario($codOperario)
{
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
function obtenerNombreSucursal($codSucursal)
{
    global $conn;

    $stmt = $conn->prepare("SELECT nombre FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();

    return $result['nombre'] ?? 'Desconocida';
}

/**
 * Verifica si el operario tuvo una omisión de marcación el día anterior
 */
function verificarOmisionDiaAnterior($codOperario, $sucursalCodigo)
{
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
 * Obtiene el código de departamento de una sucursal
 */
function obtenerCodigoDepartamentoSucursal($codSucursal)
{
    global $conn;

    $stmt = $conn->prepare("SELECT cod_departamento FROM sucursales WHERE codigo = ? LIMIT 1");
    $stmt->execute([$codSucursal]);
    $result = $stmt->fetch();

    return $result['cod_departamento'] ?? null;
}

/**
 * Obtiene los operarios activos de una sucursal para un líder específico
 */
function obtenerOperariosSucursalLider($codSucursal, $codLider)
{
    global $conn;

    // Verificar que el líder tenga asignada esta sucursal
    $stmt = $conn->prepare("
        SELECT COUNT(*) as es_lider 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND CodNivelesCargos = 5 
        AND Sucursal = ?
        AND (Fin IS NULL OR Fin >= CURDATE())
    ");
    $stmt->execute([$codLider, $codSucursal]);
    $result = $stmt->fetch();

    if (!$result || $result['es_lider'] == 0) {
        return [];
    }

    // Obtener operarios de la sucursal
    $stmt = $conn->prepare("
        SELECT o.CodOperario, o.Nombre, o.Apellido 
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.Sucursal = ?
        AND o.Operativo = 1
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        GROUP BY o.CodOperario, o.Nombre, o.Apellido
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal]);
    return $stmt->fetchAll();
}

/**
 * Obtiene todas las sucursales activas del sistema
 */
function obtenerTodasSucursales()
{
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
 * Obtiene solo las sucursales físicas (donde sucursal = 1)
 */
function obtenerSucursalesFisicas()
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT id, codigo, nombre 
        FROM sucursales 
        WHERE activa = 1 AND sucursal = 1
        ORDER BY nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene los operarios activos de una sucursal (para líder o RH)
 */
function obtenerOperariosSucursalParaFaltas($codSucursal, $codUsuario = null)
{
    global $conn;

    // Si se proporciona un código de usuario, verificar si es líder de esa sucursal
    if ($codUsuario) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as es_lider 
            FROM AsignacionNivelesCargos 
            WHERE CodOperario = ? 
            AND CodNivelesCargos = 5 
            AND Sucursal = ?
            AND (Fin IS NULL OR Fin >= CURDATE())
        ");
        $stmt->execute([$codUsuario, $codSucursal]);
        $result = $stmt->fetch();

        // Si no es líder y no es RH, devolver array vacío
        if ((!$result || $result['es_lider'] == 0) && !verificarAccesoCargo([13])) {
            return [];
        }
    }

    // Obtener operarios de la sucursal excluyendo los que tienen asignación a sucursal 27 (CDS)
    $stmt = $conn->prepare("
        SELECT o.CodOperario, o.Nombre, o.Apellido 
        FROM Operarios o
        JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        WHERE anc.Sucursal = ?
        AND o.Operativo = 1
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND o.CodOperario NOT IN (
            SELECT DISTINCT CodOperario 
            FROM AsignacionNivelesCargos 
            WHERE Sucursal = 27
            AND (Fin IS NULL OR Fin >= CURDATE())
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
function obtenerHorarioProgramadoFaltaManual($codOperario, $codSucursal, $fecha)
{
    global $conn;

    // Primero obtener el día de la semana (1=lunes, 2=martes, ..., 7=domingo)
    $stmt = $conn->prepare("SELECT DAYOFWEEK(?) as dia_semana");
    $stmt->execute([$fecha]);
    $diaSemana = $stmt->fetch()['dia_semana'];

    // Ajustar a nuestro sistema donde 1=lunes, 7=domingo
    $diaSemana = $diaSemana - 1;
    if ($diaSemana == 0)
        $diaSemana = 7;

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
function obtenerMarcaciones($codOperario, $codSucursal, $fecha)
{
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
function obtenerDatosCompletosOperario($codOperario)
{
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
 * Obtiene el nombre completo formateado de un operario - MEJORADA para manejar NULLs
 */
function obtenerNombreCompletoOperario($operario)
{
    // Asegurarnos de que tenemos un array y manejar valores NULL
    if (!is_array($operario)) {
        return 'Nombre no disponible';
    }

    $nombre = isset($operario['Nombre']) ? trim($operario['Nombre']) : '';
    $nombre2 = isset($operario['Nombre2']) ? trim($operario['Nombre2']) : '';
    $apellido = isset($operario['Apellido']) ? trim($operario['Apellido']) : '';
    $apellido2 = isset($operario['Apellido2']) ? trim($operario['Apellido2']) : '';

    $partes = [];

    if (!empty($nombre)) {
        $partes[] = $nombre;
    }
    if (!empty($nombre2)) {
        $partes[] = $nombre2;
    }
    if (!empty($apellido)) {
        $partes[] = $apellido;
    }
    if (!empty($apellido2)) {
        $partes[] = $apellido2;
    }

    return empty($partes) ? 'Nombre no disponible' : implode(' ', $partes);
}

/**
 * Obtiene el cargo principal de un usuario (priorizando códigos diferentes a 2)
 */
function obtenerCargoPrincipalUsuario($codOperario)
{
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
 * Obtiene la sucursal asignada al usuario actual
 */
function obtenerSucursalUsuarioActual()
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    // Si es admin, puede ver todos los avisos
    if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
        return null;
    }

    global $conn;

    // Obtener la sucursal activa más reciente del usuario
    $stmt = $conn->prepare("
        SELECT Sucursal 
        FROM AsignacionNivelesCargos 
        WHERE CodOperario = ? 
        AND (Fin IS NULL OR Fin >= CURDATE())
        ORDER BY Fecha DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch();

    return $result ? $result['Sucursal'] : null;
}

/**
 * Obtiene el código del cargo principal de un usuario (priorizando códigos diferentes a 2)
 */
function obtenerCargoCodigoPrincipalUsuario($codOperario)
{
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
 * Obtiene la cantidad de anuncios no leídos por un usuario
 */
function obtenerCantidadAnunciosNoLeidos($userId)
{
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
function marcarAnuncioComoLeido($announcementId, $userId)
{
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
function marcarTodosAnunciosComoLeidos($userId)
{
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
function obtenerUltimoCodigoContrato($codOperario)
{
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
