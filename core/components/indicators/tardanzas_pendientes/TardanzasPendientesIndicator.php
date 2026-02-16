<?php
/**
 * Indicador: Tardanzas Pendientes
 * Compartido por: Líderes, Operaciones, RH
 */

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

class TardanzasPendientesIndicator extends BaseIndicator
{

    protected $codigo = 'tardanzas_pendientes';
    protected $nombre = 'Tardanzas Pendientes';
    protected $icono = 'fa-clock';

    public function getData($userId)
    {
        // La lógica solo depende de la sucursal, no del cargo
        // getDatosLider ya filtra por tipo de sucursal (tienda física vs no tienda)
        return $this->getDatosLider($userId);
    }


    /**
     * Obtener datos para Líder - IMPLEMENTACIÓN COMPLETA
     */
    private function getDatosLider($userId)
    {
        // Determinar el periodo a revisar según el día del mes
        $hoy = new \DateTime();
        $diaMes = (int) $hoy->format('d');
        $diasRestantes = $this->calcularDiasRestantesReporte();

        if ($diaMes <= 2) {
            // Días 1-2: revisar mes anterior
            $mesRevisar = new \DateTime('first day of last month');
            $fechaDesde = $mesRevisar->format('Y-m-01');
            $fechaHasta = $mesRevisar->format('Y-m-t');
            $periodo = 'mes_anterior';
            $mesNombre = $this->obtenerMesEspanol($mesRevisar) . ' ' . $mesRevisar->format('Y');
        } else {
            // Días 3+: revisar mes actual
            $fechaDesde = $hoy->format('Y-m-01');
            $fechaHasta = $hoy->format('Y-m-t');
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
                'fecha_limite' => $diasRestantes . ' días restantes',
                'dias_restantes' => $diasRestantes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'url' => '../operaciones/tardanzas_manual.php',
                'detalles' => []
            ];
        }

        $sucursalesCodigos = array_column($sucursalesLider, 'codigo');
        $placeholders = implode(',', array_fill(0, count($sucursalesCodigos), '?'));

        // Consulta para obtener tardanzas reales no reportadas CON DETALLES
        $sql = "SELECT 
            m.CodOperario,
            m.fecha,
            m.sucursal_codigo,
            s.nombre as sucursal_nombre,
            CONCAT(
                IFNULL(o.Nombre, ''), ' ', 
                IFNULL(o.Nombre2, ''), ' ', 
                IFNULL(o.Apellido, ''), ' ', 
                IFNULL(o.Apellido2, '')
            ) AS nombre_completo,
            m.hora_ingreso,
            CASE DAYOFWEEK(m.fecha)
                WHEN 2 THEN hso.lunes_entrada
                WHEN 3 THEN hso.martes_entrada
                WHEN 4 THEN hso.miercoles_entrada
                WHEN 5 THEN hso.jueves_entrada
                WHEN 6 THEN hso.viernes_entrada
                WHEN 7 THEN hso.sabado_entrada
                WHEN 1 THEN hso.domingo_entrada
            END as hora_programada,
            TIMESTAMPDIFF(
                MINUTE, 
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
            ) as minutos_tardanza
        FROM marcaciones m
        INNER JOIN HorariosSemanalesOperaciones hso ON m.CodOperario = hso.cod_operario 
            AND m.sucursal_codigo = hso.cod_sucursal
        INNER JOIN SemanasSistema ss ON hso.id_semana_sistema = ss.id
        INNER JOIN sucursales s ON m.sucursal_codigo = s.codigo
        INNER JOIN Operarios o ON m.CodOperario = o.CodOperario
        WHERE m.sucursal_codigo IN ($placeholders)
            AND m.fecha BETWEEN ? AND ?
            AND m.fecha BETWEEN ss.fecha_inicio AND ss.fecha_fin
            AND m.hora_ingreso IS NOT NULL
            AND TIMESTAMPDIFF(
                MINUTE, 
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
            AND NOT EXISTS (
                SELECT 1 FROM TardanzasManuales tm
                WHERE tm.cod_operario = m.CodOperario
                    AND tm.fecha_tardanza = m.fecha
                    AND tm.cod_sucursal = m.sucursal_codigo
            )
            AND m.id = (
                SELECT MIN(m2.id)
                FROM marcaciones m2
                WHERE m2.CodOperario = m.CodOperario
                    AND m2.sucursal_codigo = m.sucursal_codigo
                    AND m2.fecha = m.fecha
                    AND m2.hora_ingreso IS NOT NULL
            )
        ORDER BY m.fecha DESC, m.sucursal_codigo, nombre_completo
        ";

        $params = array_merge($sucursalesCodigos, [$fechaDesde, $fechaHasta]);

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $detalles = $stmt->fetchAll();

            $totalTardanzas = count($detalles);
            $color = $this->determinarColorTardanzas($totalTardanzas, $diasRestantes);

            // Construir URL con parámetros
            $urlTardanzas = "../operaciones/tardanzas_manual.php?" . http_build_query([
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
                'valor' => $totalTardanzas,
                'total' => $totalTardanzas,  // Para compatibilidad con el modal

                'color' => $color,
                'fecha_limite' => $diasRestantes . ' días restantes',
                'dias_restantes' => $diasRestantes,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'sucursales' => $sucursalesCodigos,
                'modo' => 'lider',
                'url' => $urlTardanzas,
                'detalles' => $detalles
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo tardanzas pendientes: " . $e->getMessage());

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
                'url' => '../operaciones/tardanzas_manual.php',
                'detalles' => []
            ];
        }
    }

    /**
     * Calcular días restantes para reportar tardanzas
     */
    private function calcularDiasRestantesReporte()
    {
        $hoy = new \DateTime();
        $diaMes = (int) $hoy->format('d');

        if ($diaMes <= 2) {
            // Días 1-2: fecha límite es el día 2
            return max(0, 2 - $diaMes);
        } else {
            // Días 3+: fecha límite es día 2 del próximo mes
            $proximoMes = new \DateTime('first day of next month');
            $proximoMes->modify('+1 day'); // Día 2 del próximo mes
            $diferencia = $hoy->diff($proximoMes);
            return $diferencia->days;
        }
    }

    /**
     * Determinar el color del indicador según total de tardanzas y días restantes
     */
    private function determinarColorTardanzas($totalTardanzas, $diasRestantes)
    {
        if ($totalTardanzas == 0) {
            return 'verde';
        }

        if ($diasRestantes <= 0) {
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
     * Obtener datos para Operaciones
     */
    private function getDatosOperaciones()
    {
        // TODO: Implementar lógica para operaciones (todas las sucursales)
        // Por ahora retorna datos vacíos
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'icono' => $this->icono,
            'valor' => 0,
            'color' => 'gris',
            'fecha_limite' => 'Pendiente de implementar',
            'dias_restantes' => 0,
            'fecha_desde' => date('Y-m-01'),
            'fecha_hasta' => date('Y-m-t'),
            'url' => '../operaciones/tardanzas_manual.php',
            'detalles' => []
        ];
    }

    /**
     * Obtener datos para RH
     */
    private function getDatosRH()
    {
        // TODO: Implementar lógica para RH (todas las sucursales)
        // Por ahora retorna datos vacíos
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'icono' => $this->icono,
            'valor' => 0,
            'color' => 'gris',
            'fecha_limite' => 'Pendiente de implementar',
            'dias_restantes' => 0,
            'fecha_desde' => date('Y-m-01'),
            'fecha_hasta' => date('Y-m-t'),
            'url' => '../operaciones/tardanzas_manual.php',
            'detalles' => []
        ];
    }

    /**
     * Datos por defecto (sin acceso)
     */
    private function getDatosDefault()
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'icono' => $this->icono,
            'valor' => 0,
            'color' => 'gris',
            'fecha_limite' => 'Sin acceso',
            'dias_restantes' => 0,
            'detalles' => []
        ];
    }

    /**
     * Obtener datos del modal (detalles de tardanzas)
     * La lógica solo depende de la sucursal, no del cargo
     */
    public function getModalData($userId, $params = [])
    {
        // getDatosLider ya filtra por tipo de sucursal (tienda física vs no tienda)
        return $this->getDatosLider($userId);
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
