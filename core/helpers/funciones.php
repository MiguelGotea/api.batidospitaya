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
 * MODIFICADA: Filtra por fecha de liquidación
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
        LEFT JOIN (
            SELECT cod_operario, MAX(fecha_liquidacion) as fecha_liquidacion
            FROM Contratos
            GROUP BY cod_operario
        ) c ON o.CodOperario = c.cod_operario
        WHERE anc.Sucursal = ?
        -- Solo validamos fechas en AsignacionNivelesCargos
        AND (anc.Fin IS NULL OR anc.Fin >= ?)
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= ?)
        )
        -- FILTRO NUEVO: Solo operarios activos según fecha de liquidación
        AND (
            c.fecha_liquidacion IS NULL 
            OR c.fecha_liquidacion = '0000-00-00'
            OR c.fecha_liquidacion > CURDATE()
        )
        GROUP BY o.CodOperario, o.Nombre, o.Apellido
        ORDER BY o.Nombre, o.Apellido
    ");
    $stmt->execute([$codSucursal, $fechaFin, $fechaFin]);
    return $stmt->fetchAll();
}

/**
 * Obtiene TODOS los operarios que tienen horario guardado para una semana/sucursal
 * MODIFICADA: Filtra por fecha de liquidación
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
        LEFT JOIN (
            SELECT c1.cod_operario, c1.fecha_liquidacion
            FROM Contratos c1
            INNER JOIN (
                SELECT cod_operario, MAX(CodContrato) as max_contrato
                FROM Contratos
                GROUP BY cod_operario
            ) c2 ON c1.cod_operario = c2.cod_operario AND c1.CodContrato = c2.max_contrato
        ) c ON o.CodOperario = c.cod_operario
        WHERE hs.cod_sucursal = ?
        AND hs.id_semana_sistema = ?
        AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        -- FILTRO NUEVO: Solo operarios activos según fecha de liquidación
        AND (
            c.fecha_liquidacion IS NULL 
            OR c.fecha_liquidacion = '0000-00-00'
            OR c.fecha_liquidacion > CURDATE()
        )
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
                'historial_inss' => 'Historial de INSS',
                'cedula' => 'Cédula'
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
 * MODIFICADA: Incluye filtro por fecha de liquidación
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
            c.fecha_liquidacion,
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
        LEFT JOIN (
            SELECT cod_operario, MAX(fecha_liquidacion) as fecha_liquidacion
            FROM Contratos
            GROUP BY cod_operario
        ) c ON o.CodOperario = c.cod_operario
        WHERE hs.cod_sucursal = ?
        AND hs.id_semana_sistema = ?
        -- FILTRO NUEVO: Solo operarios activos según fecha de liquidación
        AND (
            c.fecha_liquidacion IS NULL 
            OR c.fecha_liquidacion = '0000-00-00'
            OR c.fecha_liquidacion > CURDATE()
        )
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
 * MODIFICADA: Filtra por fecha de liquidación
 */
function obtenerCantidadOperariosActivosFiltrados() {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.CodOperario) as total_activos
        FROM Operarios o
        LEFT JOIN (
            SELECT c1.cod_operario, c1.fecha_liquidacion
            FROM Contratos c1
            INNER JOIN (
                SELECT cod_operario, MAX(CodContrato) as max_contrato
                FROM Contratos
                GROUP BY cod_operario
            ) c2 ON c1.cod_operario = c2.cod_operario AND c1.CodContrato = c2.max_contrato
        ) c ON o.CodOperario = c.cod_operario
        WHERE o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND (anc2.Fin IS NULL OR anc2.Fin >= CURDATE())
        )
        -- FILTRO NUEVO: Solo operarios activos según fecha de liquidación
        AND (
            c.fecha_liquidacion IS NULL 
            OR c.fecha_liquidacion = '0000-00-00'
            OR c.fecha_liquidacion > CURDATE()
        )
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

/**
 * Verifica si un operario está activo según su fecha de liquidación
 * Un operario está INACTIVO si tiene fecha_liquidacion NO NULA y es <= fecha actual
 */
function operarioEstaActivo($codOperario) {
    $contrato = obtenerUltimoContratoOperario($codOperario);
    
    if (!$contrato) {
        return false; // Si no tiene contrato, no está activo
    }
    
    // Si no tiene fecha de liquidación, está activo
    if (empty($contrato['fecha_liquidacion']) || $contrato['fecha_liquidacion'] == '0000-00-00') {
        return true;
    }
    
    // Si tiene fecha de liquidación, verificar si es futura o pasada
    $fechaLiquidacion = new DateTime($contrato['fecha_liquidacion']);
    $hoy = new DateTime();
    
    // Si la fecha de liquidación es FUTURA, sigue activo
    // Si es PASADA o es HOY, está inactivo
    return $fechaLiquidacion > $hoy;
}

/**
 * Obtiene el último contrato de un operario (por CodContrato más alto)
 * REEMPLAZA la función existente si ya existe
 */
function obtenerUltimoContratoOperario($codOperario) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT CodContrato, inicio_contrato, fin_contrato, fecha_salida, fecha_liquidacion
        FROM Contratos 
        WHERE cod_operario = ? 
        ORDER BY CodContrato DESC 
        LIMIT 1
    ");
    $stmt->execute([$codOperario]);
    return $stmt->fetch() ?: null;
}

/**
 * Verifica si un operario está activo según su fecha de liquidación
 */
function operarioEstaActivoPorLiquidacion($codOperario, $fechaReferencia = null) {
    if ($fechaReferencia === null) {
        $fechaReferencia = date('Y-m-d');
    }
    
    $contrato = obtenerUltimoContratoOperario($codOperario);
    
    if (!$contrato) {
        return false; // No tiene contrato registrado
    }
    
    // Si no tiene fecha de liquidación, está activo
    if (empty($contrato['fecha_liquidacion']) || 
        $contrato['fecha_liquidacion'] == '0000-00-00' || 
        $contrato['fecha_liquidacion'] === null) {
        return true;
    }
    
    // Si tiene fecha de liquidación, verificar si la fecha de referencia es ANTES o igual
    // Recordar: si liquidación = 2025-01-15, el operario está activo hasta el 15 inclusive
    $fechaLiquidacion = new DateTime($contrato['fecha_liquidacion']);
    $fechaRef = new DateTime($fechaReferencia);
    
    // Está activo si la fecha de referencia es MENOR O IGUAL a fecha de liquidación
    return $fechaRef <= $fechaLiquidacion;
}

/**
 * Verifica si una fecha es posterior a la fecha de liquidación del operario
 * NUEVA FUNCIÓN - Para validar al registrar faltas
 */
function fechaPosteriorLiquidacion($codOperario, $fecha) {
    $contrato = obtenerUltimoContratoOperario($codOperario);
    
    if (!$contrato || 
        empty($contrato['fecha_liquidacion']) || 
        $contrato['fecha_liquidacion'] == '0000-00-00' || 
        $contrato['fecha_liquidacion'] === null) {
        return false; // No tiene fecha de liquidación, no hay restricción
    }
    
    $fechaLiquidacion = new DateTime($contrato['fecha_liquidacion']);
    $fechaConsulta = new DateTime($fecha);
    
    // La fecha es posterior si es MAYOR a la fecha de liquidación
    // Ejemplo: liquidación = 2025-01-15, fecha = 2025-01-16 → TRUE (es posterior)
    return $fechaConsulta > $fechaLiquidacion;
}

/**
 * Obtiene operarios de una sucursal que estaban activos en una fecha específica
 * NUEVA FUNCIÓN - Considera AsignacionNivelesCargos Y fecha_liquidacion
 */
function obtenerOperariosSucursalPorFecha($codSucursal, $fechaReferencia) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            o.CodOperario, 
            o.Nombre, 
            o.Nombre2,
            o.Apellido, 
            o.Apellido2,
            c.fecha_liquidacion,
            c.CodContrato
        FROM Operarios o
        INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
        LEFT JOIN (
            -- Subquery para obtener el último contrato de cada operario
            SELECT c1.cod_operario, c1.CodContrato, c1.fecha_liquidacion
            FROM Contratos c1
            INNER JOIN (
                SELECT cod_operario, MAX(CodContrato) as max_contrato
                FROM Contratos
                GROUP BY cod_operario
            ) c2 ON c1.cod_operario = c2.cod_operario AND c1.CodContrato = c2.max_contrato
        ) c ON o.CodOperario = c.cod_operario
        WHERE anc.Sucursal = ?
        -- Verificar que estaba asignado a la sucursal en esa fecha
        AND anc.Fecha <= ?
        AND (anc.Fin IS NULL OR anc.Fin >= ?)
        -- Excluir cargo 27
        AND o.CodOperario NOT IN (
            SELECT DISTINCT anc2.CodOperario 
            FROM AsignacionNivelesCargos anc2
            WHERE anc2.CodNivelesCargos = 27
            AND anc2.Fecha <= ?
            AND (anc2.Fin IS NULL OR anc2.Fin >= ?)
        )
        -- FILTRO NUEVO: Solo operarios activos según fecha de liquidación
        AND (
            c.fecha_liquidacion IS NULL 
            OR c.fecha_liquidacion = '0000-00-00'
            OR c.fecha_liquidacion >= ?
        )
        GROUP BY o.CodOperario
        ORDER BY o.Nombre, o.Apellido
    ");
    
    $stmt->execute([
        $codSucursal, 
        $fechaReferencia, $fechaReferencia, // Para AsignacionNivelesCargos
        $fechaReferencia, $fechaReferencia, // Para cargo 27
        $fechaReferencia  // Para fecha_liquidacion
    ]);
    
    return $stmt->fetchAll();
}

/**
 * Verifica si un operario tiene contrato registrado
 */
function operarioTieneContrato($codOperario) {
    $contrato = obtenerUltimoContratoOperario($codOperario);
    return $contrato !== null;
}

/**
 * Obtiene mensaje de estado del contrato para mostrar al usuario
 */
function obtenerMensajeEstadoContrato($codOperario) {
    $contrato = obtenerUltimoContratoOperario($codOperario);
    
    if (!$contrato) {
        return [
            'tipo' => 'sin_contrato',
            'mensaje' => 'Este colaborador no tiene registro de contrato. Por favor contactar con el área de RH.',
            'clase' => 'alert-warning'
        ];
    }
    
    if (empty($contrato['fecha_liquidacion']) || 
        $contrato['fecha_liquidacion'] == '0000-00-00' || 
        $contrato['fecha_liquidacion'] === null) {
        return [
            'tipo' => 'activo',
            'mensaje' => 'Colaborador activo',
            'clase' => 'alert-success'
        ];
    }
    
    $fechaLiquidacion = new DateTime($contrato['fecha_liquidacion']);
    $hoy = new DateTime();
    
    if ($fechaLiquidacion < $hoy) {
        return [
            'tipo' => 'liquidado',
            'mensaje' => 'Colaborador liquidado el ' . $fechaLiquidacion->format('d-m-Y'),
            'clase' => 'alert-danger'
        ];
    } else {
        return [
            'tipo' => 'liquidacion_futura',
            'mensaje' => 'Colaborador con liquidación programada para ' . $fechaLiquidacion->format('d-m-Y'),
            'clase' => 'alert-info'
        ];
    }
}

/**
 * Verifica si el usuario que tiene asignado actulemnte un cargo , agarra el orimero que encuentra asi que aplica a administrativos mas que operarios
 */
function obtenerOperariosPorCargoVigente($codNivelCargo) {
    if (!isset($_SESSION['usuario_id'])) {
        return []; // Devuelve array vacío si no hay sesión
    }
    
    global $conn;
    $codNivelCargo = (int)$codNivelCargo; // Aseguramos que sea un número entero
    
    $stmt = $conn->prepare("
        SELECT CodOperario
        FROM AsignacionNivelesCargos 
        WHERE CodNivelesCargos = ?
        AND Fecha <= CURDATE()
        AND (Fin IS NULL OR Fin > CURDATE())
        ORDER BY CodOperario
    ");
    
    $stmt->execute([$codNivelCargo]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extraer solo los CodOperario en un array simple
    $operarios = [];
    foreach ($resultados as $row) {
        $operarios[] = $row['CodOperario'];
    }
    
    return $operarios;
}