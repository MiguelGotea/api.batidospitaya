<?php

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

/**
 * Indicador: Ausencias de Colaboradores
 * Muestra las ausencias del día actual
 */
class AusenciasColaboradoresIndicator extends BaseIndicator
{
    protected $codigo = 'ausencias_colaboradores';
    protected $nombre = 'Ausencias Hoy';
    protected $icono = 'fa-user-times';
    protected $categoria = 'rh';

    /**
     * Método principal requerido por BaseIndicator
     */
    public function getData($userId)
    {
        $contexto = $this->detectarContexto($userId);

        switch ($contexto) {
            case 'rh':
                return $this->getDatosRH();
            default:
                return $this->getErrorResponse();
        }
    }

    /**
     * Detectar el contexto del usuario según su cargo
     */
    protected function detectarContexto($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT nc.CodNivelesCargos as cargo_codigo
            FROM Operarios o
            INNER JOIN AsignacionNivelesCargos anc ON o.CodOperario = anc.CodOperario
            INNER JOIN NivelesCargos nc ON anc.CodNivelesCargos = nc.CodNivelesCargos
            WHERE o.CodOperario = ?
            AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $cargo = $stmt->fetch();

        if (!$cargo)
            return 'default';

        // Códigos de cargo: 13=RH, 39, 30, 37, 28
        if (in_array($cargo['cargo_codigo'], [13, 39, 30, 37, 28]))
            return 'rh';

        return 'default';
    }

    /**
     * Respuesta de error estándar
     */
    protected function getErrorResponse()
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'icono' => $this->icono,
            'valor' => 0,
            'color' => 'gris',
            'detalles' => []
        ];
    }

    /**
     * Obtener datos para contexto de Líderes
     */
    protected function getDatosLider($userId)
    {
        // Este indicador es solo para RH
        return [];
    }

    /**
     * Obtener datos para contexto de Operaciones
     */
    protected function getDatosOperaciones()
    {
        // Este indicador es solo para RH
        return [];
    }

    /**
     * Obtener datos para contexto de RH
     */
    protected function getDatosRH()
    {
        try {
            // Obtener ausencias del día actual
            $query = "
                SELECT 
                    a.id,
                    a.cod_operario,
                    CONCAT(o.Nombre, ' ', o.Apellido) as nombre_completo,
                    a.fecha_ausencia,
                    a.tipo_ausencia,
                    a.motivo,
                    a.estado,
                    s.nombre as sucursal_nombre,
                    c.nombre as cargo_nombre
                FROM ausencias a
                INNER JOIN Operarios o ON a.cod_operario = o.CodOperario
                LEFT JOIN sucursales s ON o.CodSucursal = s.codigo
                LEFT JOIN cargos c ON o.CodCargo = c.codigo
                WHERE DATE(a.fecha_ausencia) = CURDATE()
                ORDER BY s.nombre, o.Nombre
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $detalles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $total = count($detalles);
            $color = $this->determinarColor($total);

            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => $total,
                'color' => $color,
                'detalles' => $detalles,
                'modo' => 'rh'
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo ausencias de colaboradores: " . $e->getMessage());
            return $this->getErrorResponse();
        }
    }

    /**
     * Obtener datos del modal
     */
    public function getModalData($userId, $params = [])
    {
        $contexto = $this->detectarContexto($userId);

        if ($contexto === 'rh') {
            $data = $this->getDatosRH();
        } else {
            return [];
        }

        return $data;
    }

    /**
     * Determinar color según cantidad
     */
    private function determinarColor($total)
    {
        if ($total == 0) {
            return 'verde';
        } elseif ($total <= 3) {
            return 'amarillo';
        } else {
            return 'rojo';
        }
    }
}
