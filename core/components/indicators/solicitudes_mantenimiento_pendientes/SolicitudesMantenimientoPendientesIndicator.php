<?php

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

/**
 * Indicador: Solicitudes de Mantenimiento Pendientes
 * Muestra las solicitudes de mantenimiento pendientes por agendar
 * Filtrado por sucursal: tiendas físicas ven solo sus solicitudes, otros ven todas
 */
class SolicitudesMantenimientoPendientesIndicator extends BaseIndicator
{
    protected $codigo = 'solicitudes_mantenimiento_pendientes';
    protected $nombre = 'Mantenimientos Pendientes';
    protected $icono = 'fa-tools';
    protected $categoria = 'mantenimiento';

    /**
     * Método principal requerido por BaseIndicator
     * La lógica solo depende de la sucursal, no del cargo
     */
    public function getData($userId)
    {
        return $this->getDatosOperaciones($userId);
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
            'url' => '#'
        ];
    }

    /**
     * Obtener datos filtrados por sucursal
     * Si es tienda física (sucursal=1): solo solicitudes de esa tienda
     * Si NO es tienda (sucursal=0): solicitudes de todas las sucursales
     */
    protected function getDatosOperaciones($userId = null)
    {
        try {
            // Determinar si el usuario está en una tienda física o no
            $whereClause = "";

            if ($userId !== null) {
                // Obtener la sucursal del usuario desde AsignacionNivelesCargos
                $stmt = $this->conn->prepare("
                    SELECT 
                        s.codigo as cod_sucursal,
                        s.sucursal as es_tienda
                    FROM AsignacionNivelesCargos anc
                    INNER JOIN sucursales s ON anc.sucursal = s.codigo
                    WHERE anc.CodOperario = ?
                    AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                $asignacion = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($asignacion) {
                    // Si es tienda física (sucursal = 1), filtrar solo por esa sucursal
                    if ($asignacion['es_tienda'] == 1) {
                        $whereClause = " AND t.cod_sucursal = '" . $asignacion['cod_sucursal'] . "'";
                    }
                    // Si NO es tienda (sucursal = 0), mostrar todas las solicitudes (sin filtro adicional)
                }
            }

            // Consultar solicitudes pendientes por agendar
            // Tabla: mtto_tickets
            // Status: 'solicitado' = recién creado, 'agendado' = programado pero no finalizado
            // Solo tipo_formulario = 'mantenimiento_general'

            $query = "
                SELECT COUNT(*) as total
                FROM mtto_tickets t
                WHERE t.status IN ('solicitado', 'agendado')
                AND t.tipo_formulario = 'mantenimiento_general'

                {$whereClause}
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $total = $result['total'] ?? 0;

            // Determinar color según cantidad
            $color = $this->determinarColor($total);

            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => $total,
                'color' => $color,
                'url' => '../mantenimiento/historial_solicitudes.php'
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo solicitudes mantenimiento: " . $e->getMessage());
            return $this->getErrorResponse();
        }
    }

    /**
     * Determinar color según cantidad de solicitudes
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
