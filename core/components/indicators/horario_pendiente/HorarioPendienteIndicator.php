<?php
/**
 * Indicador: Horario Pendiente
 * Compartido por: Líderes, Operaciones
 */

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

class HorarioPendienteIndicator extends BaseIndicator
{

    protected $codigo = 'horario_pendiente';
    protected $nombre = 'Horario Pendiente';
    protected $icono = 'fa-calendar';

    public function getData($userId)
    {
        // La lógica solo depende de la sucursal, no del cargo
        // getDatosLider ya filtra por tipo de sucursal (tienda física vs no tienda)
        return $this->getDatosLider($userId);
    }


    private function getDatosLider($userId)
    {
        global $conn;

        // Obtener semana actual y siguiente
        $semanaActual = $this->obtenerSemanaActual();
        $semanaSiguiente = $this->obtenerSemanaPorNumero($semanaActual['numero_semana'] + 1);

        if (!$semanaSiguiente) {
            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => 0,
                'color' => 'gris',
                'fecha_limite' => 'Semana no disponible',
                'dias_restantes' => 0,
                'detalles' => []
            ];
        }

        // Obtener sucursales del líder
        $sucursalesLider = $this->obtenerSucursalesLider($userId);
        if (empty($sucursalesLider)) {
            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => 0,
                'color' => 'gris',
                'fecha_limite' => 'Sin sucursales',
                'dias_restantes' => 0,
                'detalles' => []
            ];
        }

        // Determinar si estamos en período activo (lunes 00:00 a viernes 23:59 de la semana ACTUAL)
        $hoy = new \DateTime('now', new \DateTimeZone('America/Managua'));
        $lunesSemanaActual = new \DateTime($semanaActual['fecha_inicio'], new \DateTimeZone('America/Managua'));
        $viernesSemanaActual = clone $lunesSemanaActual;
        $viernesSemanaActual->modify('+4 days');

        $lunesSemanaActual->setTime(0, 0, 0);
        $viernesSemanaActual->setTime(23, 59, 59);

        $periodoActivo = ($hoy >= $lunesSemanaActual && $hoy <= $viernesSemanaActual);

        // Verificar si ya se subió el horario para alguna sucursal
        $horarioSubido = false;
        $sucursalesSinHorario = [];
        $sucursalesConHorario = [];

        foreach ($sucursalesLider as $sucursal) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total 
                FROM HorariosSemanales 
                WHERE id_semana_sistema = ? 
                AND cod_sucursal = ?
            ");
            $stmt->execute([$semanaSiguiente['id'], $sucursal['codigo']]);
            $result = $stmt->fetch();

            if ($result['total'] > 0) {
                $horarioSubido = true;
                $sucursalesConHorario[] = $sucursal;
            } else {
                $sucursalesSinHorario[] = $sucursal;
            }
        }

        // Calcular días restantes
        $diasRestantes = 0;
        if ($periodoActivo) {
            $diferencia = $hoy->diff($viernesSemanaActual);
            $diasRestantes = $diferencia->days;

            if ($diferencia->days == 0) {
                if ($hoy->format('H:i') <= '23:59') {
                    $diasRestantes = 0;
                } else {
                    $diasRestantes = -1;
                }
            }
        }

        // Determinar estado y color
        if (!$periodoActivo) {
            $texto = 'Fuera del período de programación';
            $color = 'gris';
            $valor = 0;
        } elseif ($horarioSubido && empty($sucursalesSinHorario)) {
            $texto = 'Horario completo';
            $color = 'verde';
            $valor = 0;
        } elseif ($horarioSubido && !empty($sucursalesSinHorario)) {
            $texto = 'Horario parcial';
            $color = $this->determinarColorHorarioPendiente($diasRestantes);
            $valor = count($sucursalesSinHorario);
        } else {
            $texto = 'Horario pendiente';
            $color = $this->determinarColorHorarioPendiente($diasRestantes);
            $valor = count($sucursalesSinHorario);
        }

        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'icono' => $this->icono,
            'valor' => $valor,
            'color' => $color,
            'fecha_limite' => $diasRestantes . ' días',
            'dias_restantes' => $diasRestantes,
            'semana' => $semanaSiguiente['numero_semana'] ?? 0,
            'url' => 'programar_horarios_lider2.php?semana=' . ($semanaSiguiente['numero_semana'] ?? 0),
            'detalles' => [
                'estado' => $periodoActivo ? 'activo' : 'fuera_periodo',
                'semana_siguiente' => $semanaSiguiente,
                'sucursales_sin_horario' => $sucursalesSinHorario,
                'sucursales_con_horario' => $sucursalesConHorario
            ]
        ];
    }

    private function getDatosOperaciones()
    {
        // TODO: Implementar lógica para operaciones (todas las sucursales)
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
        $data = $this->getDatosLider($userId);
        return $data['detalles'];
    }

    /**
     * Determina el color según días restantes para subir horario
     */
    private function determinarColorHorarioPendiente($diasRestantes)
    {
        if ($diasRestantes < 0) {
            return 'rojo';
        } elseif ($diasRestantes === 0) {
            return 'amarillo';
        } elseif ($diasRestantes <= 2) {
            return 'amarillo';
        } else {
            return 'verde';
        }
    }

    /**
     * Obtener la semana actual del sistema
     */
    private function obtenerSemanaActual()
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
     * Obtener una semana por su número de semana
     */
    private function obtenerSemanaPorNumero($numeroSemana)
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
