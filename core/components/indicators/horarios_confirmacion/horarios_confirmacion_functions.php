<?php
/**
 * Core Component: Horarios por Confirmar
 * Funciones reutilizables para el indicador de horarios pendientes de confirmación.
 * Se prefijan con hc_ para evitar colisiones con funciones de supervision/index.php.
 *
 * Requiere: $conn global, funciones de core/helpers/funciones.php
 *   - obtenerSemanaActual()
 *   - obtenerSemanaPorNumero()
 *   - obtenerSucursalesFisicas()
 */

if (!function_exists('hc_verificarEdicionesPendientesLideresTodasSemanas')) {
    /**
     * Verifica si hay ediciones de líderes pendientes después de confirmación,
     * para TODAS las semanas (no solo la siguiente).
     */
    function hc_verificarEdicionesPendientesLideresTodasSemanas()
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT DISTINCT 
                hs.cod_sucursal, 
                s.nombre as sucursal_nombre,
                hs.id_semana_sistema,
                ss.numero_semana,
                ss.fecha_inicio,
                ss.fecha_fin,
                COUNT(DISTINCT hs.cod_operario) as operarios_editados,
                MAX(hs.fecha_actualizacion) as ultima_edicion_lider,
                MAX(hso.fecha_actualizacion) as ultima_confirmacion_operaciones
            FROM HorariosSemanales hs
            JOIN sucursales s ON hs.cod_sucursal = s.codigo
            JOIN SemanasSistema ss ON hs.id_semana_sistema = ss.id
            JOIN HorariosSemanalesOperaciones hso ON 
                hs.cod_operario = hso.cod_operario 
                AND hs.id_semana_sistema = hso.id_semana_sistema 
                AND hs.cod_sucursal = hso.cod_sucursal
            WHERE hso.confirmado = 1
            AND s.activa = 1
            AND s.sucursal = 1
            AND (
                (hso.fecha_actualizacion IS NULL AND hs.fecha_actualizacion > hso.fecha_creacion)
                OR
                (hso.fecha_actualizacion IS NOT NULL AND hs.fecha_actualizacion > hso.fecha_actualizacion)
            )
            GROUP BY hs.cod_sucursal, s.nombre, hs.id_semana_sistema, ss.numero_semana, ss.fecha_inicio, ss.fecha_fin
            HAVING operarios_editados > 0
            ORDER BY ss.numero_semana DESC, s.nombre
        ");

        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('hc_determinarColorConfirmacionPendiente')) {
    /**
     * Determina el color del indicador según días restantes y total de pendientes.
     */
    function hc_determinarColorConfirmacionPendiente($diasRestantes, $totalPendientes)
    {
        if ($totalPendientes == 0) {
            return 'verde';
        }
        if ($diasRestantes <= 0) {
            return 'rojo';
        } elseif ($diasRestantes <= 1) {
            return 'rojo';
        } else {
            return 'amarillo';
        }
    }
}

if (!function_exists('hc_obtenerEstadoHorariosPendientesConfirmacion')) {
    /**
     * Obtiene el estado completo de horarios pendientes de confirmación por operaciones.
     * Detecta: horarios sin confirmar, sucursales sin horario de líder y ediciones pendientes.
     */
    function hc_obtenerEstadoHorariosPendientesConfirmacion($codUsuario)
    {
        global $conn;

        $semanaActual    = obtenerSemanaActual();
        $semanaSiguiente = obtenerSemanaPorNumero($semanaActual['numero_semana'] + 1);

        if (!$semanaSiguiente) {
            return [
                'estado'              => 'no_disponible',
                'texto'               => 'Semana siguiente no disponible',
                'color'               => 'gris',
                'url'                 => '#',
                'semana_siguiente'    => null,
                'periodo_activo'      => false,
                'total_pendientes'    => 0,
                'total_sin_horario'   => 0,
                'sucursales_pendientes' => [],
                'sin_horario_lider'   => [],
                'ediciones_pendientes'=> [],
                'dias_restantes'      => 0,
            ];
        }

        $sucursales = obtenerSucursalesFisicas();

        // Determinar si estamos en período activo (sábado 00:00 a domingo 23:59)
        $hoy                  = new DateTime('now', new DateTimeZone('America/Managua'));
        $domingoSemanaActual  = new DateTime($semanaActual['fecha_fin'], new DateTimeZone('America/Managua'));
        $sabadoSemanaActual   = clone $domingoSemanaActual;
        $sabadoSemanaActual->modify('-1 day');
        $sabadoSemanaActual->setTime(0, 0, 0);
        $domingoSemanaActual->setTime(23, 59, 59);

        // TEMPORAL: período siempre activo (misma lógica que supervision)
        $periodoActivo = true;

        $sucursalesPendientes = [];
        $sucursalesSinHorario = [];
        $totalPendientes      = 0;
        $totalSinHorario      = 0;

        foreach ($sucursales as $sucursal) {
            // 1. Operarios activos en la sucursal para la semana siguiente
            $stmtOperarios = $conn->prepare("
                SELECT COUNT(DISTINCT o.CodOperario) as total_operarios
                FROM Operarios o
                JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
                WHERE anc.Sucursal = ?
                AND o.Operativo = 1
                AND (anc.Fin IS NULL OR anc.Fin >= ?)
                AND anc.Fecha <= ?
                AND o.CodOperario NOT IN (
                    SELECT DISTINCT anc2.CodOperario 
                    FROM AsignacionNivelesCargos anc2
                    WHERE anc2.CodNivelesCargos = 27
                    AND (anc2.Fin IS NULL OR anc2.Fin >= ?)
                )
            ");
            $stmtOperarios->execute([
                $sucursal['codigo'],
                $semanaSiguiente['fecha_fin'],
                $semanaSiguiente['fecha_inicio'],
                $semanaSiguiente['fecha_fin'],
            ]);
            $totalOperariosSucursal = $stmtOperarios->fetch()['total_operarios'] ?? 0;

            if ($totalOperariosSucursal == 0) {
                continue;
            }

            // 2. Operarios con horario del líder
            $stmtHorarioLider = $conn->prepare("
                SELECT COUNT(DISTINCT cod_operario) as con_horario_lider
                FROM HorariosSemanales
                WHERE id_semana_sistema = ? 
                AND cod_sucursal = ?
            ");
            $stmtHorarioLider->execute([$semanaSiguiente['id'], $sucursal['codigo']]);
            $conHorarioLider = $stmtHorarioLider->fetch()['con_horario_lider'] ?? 0;

            // 3. Operarios confirmados por operaciones
            $stmtConfirmados = $conn->prepare("
                SELECT COUNT(DISTINCT cod_operario) as confirmados
                FROM HorariosSemanalesOperaciones
                WHERE id_semana_sistema = ? 
                AND cod_sucursal = ?
                AND confirmado = 1
            ");
            $stmtConfirmados->execute([$semanaSiguiente['id'], $sucursal['codigo']]);
            $confirmados = $stmtConfirmados->fetch()['confirmados'] ?? 0;

            // Clasificar sucursal
            if ($conHorarioLider == 0) {
                $sucursalesSinHorario[] = [
                    'sucursal'         => $sucursal,
                    'total_operarios'  => $totalOperariosSucursal,
                    'con_horario_lider'=> 0,
                    'confirmados'      => 0,
                    'pendientes_confirmar' => 0,
                ];
                $totalSinHorario++;
            } elseif ($confirmados < $conHorarioLider) {
                $sucursalesPendientes[] = [
                    'sucursal'            => $sucursal,
                    'total_operarios'     => $totalOperariosSucursal,
                    'con_horario_lider'   => $conHorarioLider,
                    'confirmados'         => $confirmados,
                    'pendientes_confirmar'=> $conHorarioLider - $confirmados,
                ];
                $totalPendientes++;
            }
        }

        // Ediciones pendientes de reconfirmación en cualquier semana
        $edicionesPendientes = hc_verificarEdicionesPendientesLideresTodasSemanas();

        if (!$periodoActivo) {
            return [
                'estado'              => 'fuera_periodo',
                'texto'               => 'Fuera del período de confirmación',
                'color'               => 'gris',
                'url'                 => 'programar_horarios_operaciones.php?semana=' . $semanaSiguiente['numero_semana'],
                'semana_siguiente'    => $semanaSiguiente,
                'periodo_activo'      => false,
                'total_pendientes'    => $totalPendientes,
                'total_sin_horario'   => $totalSinHorario,
                'sucursales_pendientes' => $sucursalesPendientes,
                'sin_horario_lider'   => $sucursalesSinHorario,
                'ediciones_pendientes'=> $edicionesPendientes,
                'dias_restantes'      => 0,
            ];
        }

        $totalGeneral = $totalPendientes + $totalSinHorario + count($edicionesPendientes);

        if ($totalGeneral == 0) {
            return [
                'estado'              => 'completo',
                'texto'               => 'Todos los horarios de la semana ' . $semanaSiguiente['numero_semana'] . ' confirmados',
                'color'               => 'verde',
                'url'                 => 'programar_horarios_operaciones.php?semana=' . $semanaSiguiente['numero_semana'],
                'semana_siguiente'    => $semanaSiguiente,
                'periodo_activo'      => true,
                'total_pendientes'    => 0,
                'total_sin_horario'   => 0,
                'sucursales_pendientes' => [],
                'sin_horario_lider'   => [],
                'ediciones_pendientes'=> [],
                'dias_restantes'      => 0,
            ];
        }

        // Calcular días restantes
        $diasRestantes = 0;
        if ($periodoActivo) {
            $diferencia    = $hoy->diff($domingoSemanaActual);
            $diasRestantes = $diferencia->days;
        }

        $color = hc_determinarColorConfirmacionPendiente($diasRestantes, $totalGeneral);

        $texto = "{$totalPendientes} por confirmar";
        if ($totalSinHorario > 0) {
            $texto .= " + {$totalSinHorario} sin horario líder";
        }
        if (!empty($edicionesPendientes)) {
            $texto .= " + " . count($edicionesPendientes) . " reconfirmar";
        }

        return [
            'estado'              => 'pendiente',
            'texto'               => $texto,
            'color'               => $color,
            'url'                 => 'programar_horarios_operaciones.php?semana=' . $semanaSiguiente['numero_semana'],
            'semana_siguiente'    => $semanaSiguiente,
            'periodo_activo'      => true,
            'total_pendientes'    => $totalPendientes,
            'total_sin_horario'   => $totalSinHorario,
            'sucursales_pendientes' => $sucursalesPendientes,
            'sin_horario_lider'   => $sucursalesSinHorario,
            'ediciones_pendientes'=> $edicionesPendientes,
            'dias_restantes'      => $diasRestantes,
        ];
    }
}
