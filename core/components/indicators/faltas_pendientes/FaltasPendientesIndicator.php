<?php
/**
 * Indicador: Faltas Pendientes
 * Compartido por: Líderes, Operaciones, RH
 */

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

class FaltasPendientesIndicator extends BaseIndicator
{

    protected $codigo = 'faltas_pendientes';
    protected $nombre = 'Faltas Pendientes';
    protected $icono = 'fa-user-times';

    public function getData($userId)
    {
        // La lógica solo depende de la sucursal, no del cargo
        // getDatosLider ya filtra por tipo de sucursal (tienda física vs no tienda)
        return $this->getDatosLider($userId);
    }


    private function getDatosLider($userId)
    {
        global $conn;

        // Determinar el periodo a revisar según el día del mes
        $hoy = new \DateTime();
        $diaMes = (int) $hoy->format('d');
        $diasRestantes = $this->calcularDiasRestantesReporteFaltas();

        if ($diaMes <= 1) {
            // Día 1: revisar mes anterior
            $mesRevisar = new \DateTime('first day of last month');
            $fechaDesde = $mesRevisar->format('Y-m-01');
            $fechaHasta = $mesRevisar->format('Y-m-t');
            $periodo = 'mes_anterior';
            $mesNombre = $this->obtenerMesEspanol($mesRevisar) . ' ' . $mesRevisar->format('Y');
        } else {
            // Días 2+: revisar mes actual (hasta ayer para evitar futuros)
            $fechaDesde = $hoy->format('Y-m-01');
            $fechaHasta = date('Y-m-d', strtotime('-1 day')); // Solo hasta ayer
            $periodo = 'mes_actual';
            $mesNombre = $this->obtenerMesEspanol($hoy) . ' ' . $hoy->format('Y');
        }

        // Obtener sucursales del líder
        $sucursalesLider = $this->obtenerSucursalesLider($userId);
        if (empty($sucursalesLider)) {
            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => 0,
                'color' => 'verde',
                'fecha_limite' => 'Sin faltas pendientes',
                'dias_restantes' => $diasRestantes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'url' => 'faltas_manual.php',
                'detalles' => []
            ];
        }

        $sucursalesCodigos = array_column($sucursalesLider, 'codigo');
        $placeholders = implode(',', array_fill(0, count($sucursalesCodigos), '?'));

        // Consulta CORREGIDA para obtener ausencias reales no reportadas
        $sql = "
            SELECT 
                hso.cod_operario,
                hso.cod_sucursal,
                s.nombre as sucursal_nombre,
                CONCAT(
                    IFNULL(o.Nombre, ''), ' ', 
                    IFNULL(o.Nombre2, ''), ' ', 
                    IFNULL(o.Apellido, ''), ' ', 
                    IFNULL(o.Apellido2, '')
                ) AS nombre_completo,
                h.fecha,
                CASE DAYOFWEEK(h.fecha)
                    WHEN 2 THEN hso.lunes_entrada
                    WHEN 3 THEN hso.martes_entrada
                    WHEN 4 THEN hso.miercoles_entrada
                    WHEN 5 THEN hso.jueves_entrada
                    WHEN 6 THEN hso.viernes_entrada
                    WHEN 7 THEN hso.sabado_entrada
                    WHEN 1 THEN hso.domingo_entrada
                END as hora_entrada_programada,
                CASE DAYOFWEEK(h.fecha)
                    WHEN 2 THEN hso.lunes_salida
                    WHEN 3 THEN hso.martes_salida
                    WHEN 4 THEN hso.miercoles_salida
                    WHEN 5 THEN hso.jueves_salida
                    WHEN 6 THEN hso.viernes_salida
                    WHEN 7 THEN hso.sabado_salida
                    WHEN 1 THEN hso.domingo_salida
                END as hora_salida_programada,
                CASE DAYOFWEEK(h.fecha)
                    WHEN 2 THEN hso.lunes_estado
                    WHEN 3 THEN hso.martes_estado
                    WHEN 4 THEN hso.miercoles_estado
                    WHEN 5 THEN hso.jueves_estado
                    WHEN 6 THEN hso.viernes_estado
                    WHEN 7 THEN hso.sabado_estado
                    WHEN 1 THEN hso.domingo_estado
                END as estado_dia
            FROM (
                SELECT DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY as fecha
                FROM 
                (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                (SELECT 0 a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
                 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                WHERE DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY <= ?
            ) h
            INNER JOIN HorariosSemanalesOperaciones hso ON hso.cod_sucursal IN ($placeholders)
            INNER JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
            INNER JOIN sucursales s ON hso.cod_sucursal = s.codigo
            INNER JOIN Operarios o ON hso.cod_operario = o.CodOperario
            WHERE h.fecha BETWEEN ? AND ?
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
            AND NOT EXISTS (
                SELECT 1 FROM marcaciones m
                WHERE m.CodOperario = hso.cod_operario
                AND m.sucursal_codigo = hso.cod_sucursal
                AND m.fecha = h.fecha
                AND (m.hora_ingreso IS NOT NULL OR m.hora_salida IS NOT NULL)
            )
            AND NOT EXISTS (
                SELECT 1 FROM faltas_manual fm
                WHERE fm.cod_operario = hso.cod_operario
                AND fm.fecha_falta = h.fecha
                AND fm.cod_sucursal = hso.cod_sucursal
            )
            AND o.Operativo = 1
            AND EXISTS (
                SELECT 1 FROM AsignacionNivelesCargos anc
                WHERE anc.CodOperario = o.CodOperario
                AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            )
            ORDER BY h.fecha DESC, hso.cod_sucursal, nombre_completo
        ";

        $params = array_merge(
            [$fechaDesde, $fechaDesde, $fechaHasta],
            $sucursalesCodigos,
            [$fechaDesde, $fechaHasta]
        );

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $detalles = $stmt->fetchAll();

            $totalFaltas = count($detalles);
            $color = $this->determinarColorFaltas($totalFaltas, $diasRestantes);

            $urlFaltas = "faltas_manual.php?" . http_build_query([
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'sucursales' => implode(',', $sucursalesCodigos),
                'modo' => 'lider',
                'periodo' => $periodo
            ]);

            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => $totalFaltas,
                'total' => $totalFaltas,  // Para compatibilidad con el modal

                'color' => $color,
                'fecha_limite' => $diasRestantes . ' días restantes',
                'dias_restantes' => $diasRestantes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'sucursales' => $sucursalesCodigos,
                'modo' => 'lider',
                'url' => '../lideres/faltas_manual.php',
                'detalles' => $detalles
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo faltas pendientes: " . $e->getMessage());

            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => 0,
                'color' => 'verde',
                'fecha_limite' => 'Error en cálculo',
                'dias_restantes' => $diasRestantes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'url' => 'faltas_manual.php',
                'detalles' => []
            ];
        }
    }

    private function getDatosOperaciones()
    {
        // TODO: Implementar lógica para operaciones
        return $this->getDatosDefault();
    }

    private function getDatosRH()
    {
        // TODO: Implementar lógica para RH
        return $this->getDatosDefault();
    }

    private function getDatosDefault()
    {
        return [
            'nombre' => $this->nombre,
            'icono' => $this->icono,
            'valor' => 0,
            'color' => 'gris',
            'fecha_limite' => 'Sin acceso',
            'detalles' => []
        ];
    }

    public function getModalData($userId, $params = [])
    {
        // getDatosLider ya filtra por tipo de sucursal (tienda física vs no tienda)
        return $this->getDatosLider($userId);
    }

    /**
     * Calcula días restantes para reportar faltas
     */
    private function calcularDiasRestantesReporteFaltas()
    {
        $hoy = new \DateTime();
        $diaMes = (int) $hoy->format('d');

        if ($diaMes <= 1) {
            return 0;
        } else {
            $proximoMes = new \DateTime('first day of next month');
            $diferencia = $hoy->diff($proximoMes);
            return $diferencia->days;
        }
    }

    /**
     * Determinar el color del indicador según total de faltas y días restantes
     */
    private function determinarColorFaltas($totalFaltas, $diasRestantes)
    {
        if ($totalFaltas == 0) {
            return 'verde';
        }

        if ($diasRestantes <= 0) {
            return 'rojo';
        } elseif ($diasRestantes <= 1) {
            return 'rojo';
        } elseif ($diasRestantes <= 3) {
            return 'amarillo';
        } else {
            return 'verde';
        }
    }

    /**
     * Obtener el nombre del mes en español
     */
    private function obtenerMesEspanol($fecha)
    {
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

    /**
     * Obtener sucursales según el tipo de sucursal del usuario
     * Si está en tienda física (sucursal=1): solo esa sucursal
     * Si NO está en tienda (sucursal=0): todas las sucursales activas
     */
    private function obtenerSucursalesLider($codOperario)
    {
        global $conn;

        // Obtener la sucursal del usuario y verificar si es tienda física
        $stmt = $conn->prepare("
            SELECT 
                s.codigo,
                s.nombre,
                s.sucursal as es_tienda
            FROM AsignacionNivelesCargos anc
            INNER JOIN sucursales s ON anc.Sucursal = s.codigo
            WHERE anc.CodOperario = ?
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            AND s.activa = 1
            ORDER BY anc.Fecha DESC
            LIMIT 1
        ");
        $stmt->execute([$codOperario]);
        $asignacion = $stmt->fetch();

        if (!$asignacion) {
            return [];
        }

        // Si es tienda física (sucursal = 1), retornar solo esa sucursal
        if ($asignacion['es_tienda'] == 1) {
            return [
                [
                    'codigo' => $asignacion['codigo'],
                    'nombre' => $asignacion['nombre']
                ]
            ];
        }

        // Si NO es tienda (sucursal = 0), retornar todas las sucursales activas
        $stmt = $conn->query("
            SELECT codigo, nombre
            FROM sucursales
            WHERE activa = 1
            ORDER BY nombre
        ");
        return $stmt->fetchAll();
    }
}
