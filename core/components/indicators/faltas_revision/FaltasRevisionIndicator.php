<?php

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

/**
 * Indicador: Faltas Pendientes de Revisión
 * Muestra las faltas que requieren revisión por parte de RH
 */
class FaltasRevisionIndicator extends BaseIndicator
{
    protected $codigo = 'faltas_revision';
    protected $nombre = 'Faltas Pendientes Revisión';
    protected $icono = 'fa-clipboard-check';
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
            // Obtener faltas pendientes de revisión
            $query = "
                SELECT 
                    f.id,
                    f.cod_operario,
                    CONCAT(o.Nombre, ' ', o.Apellido) as nombre_completo,
                    f.fecha_falta,
                    f.tipo_falta,
                    f.observaciones,
                    f.fecha_registro,
                    s.nombre as sucursal_nombre
                FROM faltas f
                INNER JOIN Operarios o ON f.cod_operario = o.CodOperario
                LEFT JOIN sucursales s ON o.CodSucursal = s.codigo
                WHERE f.estado_revision = 'pendiente'
                ORDER BY f.fecha_registro DESC
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
            error_log("Error obteniendo faltas pendientes de revisión: " . $e->getMessage());
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
        } elseif ($total <= 5) {
            return 'amarillo';
        } else {
            return 'rojo';
        }
    }
}
