<?php

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

/**
 * Indicador: Auditorías Pendientes
 * Muestra las auditorías pendientes de realizar
 */
class AuditoriasPendientesIndicator extends BaseIndicator
{
    protected $codigo = 'auditorias_pendientes';
    protected $nombre = 'Auditorías Pendientes';
    protected $icono = 'fa-clipboard-list';
    protected $categoria = 'operaciones';

    /**
     * Método principal requerido por BaseIndicator
     */
    public function getData($userId)
    {
        $contexto = $this->detectarContexto($userId);

        switch ($contexto) {
            case 'operaciones':
                return $this->getDatosOperaciones();
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

        // Códigos de cargo: 11=Operaciones, 16, 21, 36
        if (in_array($cargo['cargo_codigo'], [11, 16, 21, 36]))
            return 'operaciones';

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
        // Este indicador es solo para Operaciones
        return [];
    }

    /**
     * Obtener datos para contexto de Operaciones
     */
    protected function getDatosOperaciones()
    {
        try {
            // Obtener auditorías pendientes
            $query = "
                SELECT 
                    a.id,
                    a.cod_sucursal,
                    s.nombre as sucursal_nombre,
                    a.tipo_auditoria,
                    a.fecha_programada,
                    a.auditor_asignado,
                    CONCAT(o.Nombre, ' ', o.Apellido) as nombre_auditor,
                    a.prioridad,
                    DATEDIFF(a.fecha_programada, CURDATE()) as dias_restantes
                FROM auditorias a
                INNER JOIN sucursales s ON a.cod_sucursal = s.codigo
                LEFT JOIN Operarios o ON a.auditor_asignado = o.CodOperario
                WHERE a.estado = 'pendiente'
                AND a.fecha_programada >= CURDATE()
                ORDER BY a.fecha_programada ASC
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
                'modo' => 'operaciones'
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo auditorías pendientes: " . $e->getMessage());
            return $this->getErrorResponse();
        }
    }

    /**
     * Obtener datos para contexto de RH
     */
    protected function getDatosRH()
    {
        // Este indicador es solo para Operaciones
        return [];
    }

    /**
     * Obtener datos del modal
     */
    public function getModalData($userId, $params = [])
    {
        $contexto = $this->detectarContexto($userId);

        if ($contexto === 'operaciones') {
            $data = $this->getDatosOperaciones();
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
