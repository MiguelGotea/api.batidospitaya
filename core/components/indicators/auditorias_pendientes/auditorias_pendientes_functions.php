<?php
/**
 * Core Component: Auditorías Pendientes
 * Funciones reutilizables para el indicador de auditorías mensuales pendientes.
 * Se prefijan con audpend_ para evitar colisiones con funciones de supervision/index.php.
 *
 * Requiere: $conn global, funciones de core/helpers/funciones.php
 */

if (!function_exists('audpend_obtenerVisitasRealizadasMes')) {
    /**
     * Obtiene las fechas de visita realizadas en un mes para una sucursal
     */
    function audpend_obtenerVisitasRealizadasMes($codSucursal, $mes, $ano)
    {
        global $conn;

        $fechaInicio = "$ano-$mes-01";
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));

        // Buscar fechas distintas donde se realizó al menos una auditoría
        $sql = "
            SELECT DISTINCT DATE(fecha_hora) as fecha_visita
            FROM (
                -- Auditorías de desempeño
                SELECT fecha_hora FROM auditoria WHERE cod_sucursal = ? AND DATE(fecha_hora) BETWEEN ? AND ?
                UNION
                SELECT fecha_hora FROM auditoria_personal WHERE cod_sucursal = ? AND DATE(fecha_hora) BETWEEN ? AND ?
                UNION
                SELECT fecha_hora FROM auditoria_servicio WHERE cod_sucursal = ? AND DATE(fecha_hora) BETWEEN ? AND ?
                UNION
                -- Auditorías de efectivo (ajustar por zona horaria)
                SELECT DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) as fecha_hora FROM auditoria_facturacion WHERE sucursal_id = ? AND DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) BETWEEN ? AND ?
                UNION
                SELECT DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) as fecha_hora FROM auditoria_caja_chica WHERE sucursal_id = ? AND DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) BETWEEN ? AND ?
                UNION
                SELECT DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) as fecha_hora FROM auditoria_inventario WHERE sucursal_id = ? AND DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) BETWEEN ? AND ?
            ) as auditorias
            ORDER BY fecha_visita
        ";

        $stmt = $conn->prepare($sql);

        // Ejecutar con parámetros repetidos para cada UNION
        $params = [];
        for ($i = 0; $i < 3; $i++) { // 3 auditorías de desempeño
            $params[] = $codSucursal;
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        for ($i = 0; $i < 3; $i++) { // 3 auditorías de efectivo
            $params[] = $codSucursal;
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (!function_exists('audpend_verificarAuditoriaDesempenioFecha')) {
    /**
     * Verifica auditoría de desempeño en fecha específica
     */
    function audpend_verificarAuditoriaDesempenioFecha($tabla, $codSucursal, $fecha)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM $tabla 
            WHERE cod_sucursal = ? 
            AND DATE(fecha_hora) = ?
            LIMIT 1
        ");

        $stmt->execute([$codSucursal, $fecha]);
        $result = $stmt->fetch();
        return $result && $result['total'] > 0;
    }
}

if (!function_exists('audpend_verificarAuditoriaEfectivoFecha')) {
    /**
     * Verifica auditoría de efectivo en fecha específica
     */
    function audpend_verificarAuditoriaEfectivoFecha($tabla, $columnaSucursal, $codSucursal, $fecha)
    {
        global $conn;

        $sql = "SELECT COUNT(*) as total 
                FROM $tabla 
                WHERE $columnaSucursal = ? 
                AND DATE(DATE_SUB(fecha_hora_regsys, INTERVAL 6 HOUR)) = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$codSucursal, $fecha]);
        $result = $stmt->fetch();
        return $result && $result['total'] > 0;
    }
}

if (!function_exists('audpend_obtenerDetalleAuditoriasVisita')) {
    /**
     * Obtiene el detalle completo de auditorías para una visita específica
     */
    function audpend_obtenerDetalleAuditoriasVisita($codSucursal, $fechaVisita)
    {
        // Auditorías de desempeño
        $auditoriasDesempenio = [
            'limpieza' => [
                'nombre' => 'Limpieza',
                'completa' => audpend_verificarAuditoriaDesempenioFecha('auditoria', $codSucursal, $fechaVisita),
                'url' => '/modulos/supervision/auditorias_original/agregar.php'
            ],
            'personal' => [
                'nombre' => 'Personal',
                'completa' => audpend_verificarAuditoriaDesempenioFecha('auditoria_personal', $codSucursal, $fechaVisita),
                'url' => '/modulos/supervision/auditorias_original/agregarpersonal.php'
            ],
            'servicio' => [
                'nombre' => 'Servicio',
                'completa' => audpend_verificarAuditoriaDesempenioFecha('auditoria_servicio', $codSucursal, $fechaVisita),
                'url' => '/modulos/supervision/auditorias_original/agregarservicio.php'
            ]
        ];

        // Auditorías de efectivo
        $auditoriasEfectivo = [
            'facturacion' => [
                'nombre' => 'Caja Facturación',
                'completa' => audpend_verificarAuditoriaEfectivoFecha('auditoria_facturacion', 'sucursal_id', $codSucursal, $fechaVisita),
                'url' => '/modulos/supervision/auditorias_original/auditinternas/auditoria_caja_facturacion.php'
            ],
            'caja_chica' => [
                'nombre' => 'Caja Chica',
                'completa' => audpend_verificarAuditoriaEfectivoFecha('auditoria_caja_chica', 'sucursal_id', $codSucursal, $fechaVisita),
                'url' => '/modulos/supervision/auditorias_original/auditinternas/auditoria_caja_chica.php'
            ],
            'inventario' => [
                'nombre' => 'Inventario',
                'completa' => audpend_verificarAuditoriaEfectivoFecha('auditoria_inventario', 'sucursal_id', $codSucursal, $fechaVisita),
                'url' => '/modulos/supervision/auditorias_original/auditinternas/auditoria_inventario.php'
            ]
        ];

        $todasAuditorias = array_merge($auditoriasDesempenio, $auditoriasEfectivo);
        $completas = 0;
        $total = count($todasAuditorias);

        foreach ($todasAuditorias as $auditoria) {
            if ($auditoria['completa']) {
                $completas++;
            }
        }

        return [
            'auditorias' => $todasAuditorias,
            'completas' => $completas,
            'total' => $total,
            'completa' => ($completas == $total)
        ];
    }
}

if (!function_exists('audpend_obtenerEstadoAuditoriasMensual')) {
    /**
     * Obtiene el estado mensual de auditorías por sucursal - MEJORADA
     */
    function audpend_obtenerEstadoAuditoriasMensual($codSucursal = null)
    {
        global $conn;

        $mesActual = date('n');
        $anoActual = date('Y');
        $mesNombre = date('F', mktime(0, 0, 0, $mesActual, 1));

        // Obtener todas las sucursales o una específica
        if ($codSucursal) {
            $sucursales = obtenerSucursalesFisicas();
            $sucursales = array_filter($sucursales, function ($s) use ($codSucursal) {
                return $s['codigo'] == $codSucursal;
            });
        } else {
            $sucursales = obtenerSucursalesFisicas();
        }

        $resultados = [];
        $totalCompletas = 0;
        $totalSucursales = count($sucursales);
        $totalPendientes = 0;

        foreach ($sucursales as $sucursal) {
            $codDepartamento = $sucursal['cod_departamento'];

            // Determinar visitas requeridas según departamento
            $visitasRequeridas = ($codDepartamento == 1) ? 3 : 2;

            // Obtener las visitas realizadas este mes
            $visitasRealizadas = audpend_obtenerVisitasRealizadasMes($sucursal['codigo'], $mesActual, $anoActual);

            // Verificar visitas completas (con las 6 auditorías)
            $visitasCompletas = 0;
            $detalleVisitas = [];

            foreach ($visitasRealizadas as $fechaVisita) {
                $detalleVisita = audpend_obtenerDetalleAuditoriasVisita($sucursal['codigo'], $fechaVisita);
                $completa = $detalleVisita['completa'];

                if ($completa) {
                    $visitasCompletas++;
                }

                $detalleVisitas[] = [
                    'fecha' => $fechaVisita,
                    'completa' => $completa,
                    'detalle_auditorias' => $detalleVisita['auditorias'],
                    'total_completas' => $detalleVisita['completas'],
                    'total_auditorias' => $detalleVisita['total']
                ];
            }

            $porcentaje = $visitasRequeridas > 0 ? round(($visitasCompletas / $visitasRequeridas) * 100) : 100;

            // Determinar estado individual de la sucursal
            if ($visitasCompletas >= $visitasRequeridas) {
                $estadoSucursal = 'completo';
                $totalCompletas++;
            } elseif ($visitasCompletas > 0) {
                $estadoSucursal = 'parcial';
                $totalPendientes++;
            } else {
                $estadoSucursal = 'pendiente';
                $totalPendientes++;
            }

            $resultados[] = [
                'codigo' => $sucursal['codigo'],
                'nombre' => $sucursal['nombre'],
                'departamento' => $codDepartamento,
                'departamento_nombre' => obtenerNombreDepartamento($codDepartamento),
                'visitas_requeridas' => $visitasRequeridas,
                'visitas_completas' => $visitasCompletas,
                'visitas_realizadas' => count($visitasRealizadas),
                'porcentaje' => $porcentaje,
                'detalle_visitas' => $detalleVisitas,
                'estado' => $estadoSucursal,
                'color' => $estadoSucursal == 'completo' ? 'verde' : ($estadoSucursal == 'parcial' ? 'amarillo' : 'rojo')
            ];
        }

        $porcentajeGlobal = $totalSucursales > 0 ? round(($totalCompletas / $totalSucursales) * 100) : 100;

        // Determinar color global para el indicador
        if ($porcentajeGlobal == 100) {
            $colorGlobal = 'verde';
            $estadoGlobal = 'completo';
        } elseif ($porcentajeGlobal >= 70) {
            $colorGlobal = 'amarillo';
            $estadoGlobal = 'avanzado';
        } else {
            $colorGlobal = 'rojo';
            $estadoGlobal = 'pendiente';
        }

        return [
            'mes' => $mesActual,
            'ano' => $anoActual,
            'mes_nombre' => $mesNombre,
            'sucursales' => $resultados,
            'total_sucursales' => $totalSucursales,
            'total_completas' => $totalCompletas,
            'total_pendientes' => $totalPendientes,
            'porcentaje_global' => $porcentajeGlobal,
            'estado_global' => $estadoGlobal,
            'color_global' => $colorGlobal
        ];
    }
}

if (!function_exists('audpend_visitaTieneTodasAuditorias')) {
    /**
     * Verifica si una visita (fecha) tiene las 6 auditorías completas
     */
    function audpend_visitaTieneTodasAuditorias($codSucursal, $fechaVisita)
    {
        $detalle = audpend_obtenerDetalleAuditoriasVisita($codSucursal, $fechaVisita);
        return $detalle['completa'];
    }
}
