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
            $paramsQuery = [];

            if ($userId !== null) {
                // Obtener todas las sucursales asignadas al usuario desde AsignacionNivelesCargos
                $stmt = $this->conn->prepare("
                    SELECT DISTINCT
                        s.codigo as cod_sucursal,
                        s.sucursal as es_tienda
                    FROM AsignacionNivelesCargos anc
                    INNER JOIN sucursales s ON anc.sucursal = s.codigo
                    WHERE anc.CodOperario = ?
                    AND (anc.Fin IS NULL OR anc.Fin >= CURDATE())
                    AND s.activa = 1
                ");
                $stmt->execute([$userId]);
                $asignaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($asignaciones)) {
                    // Verificar si tiene alguna sucursal que NO sea tienda física (es_tienda = 0)
                    $tieneNoTienda = false;
                    $codigosTiendas = [];
                    foreach ($asignaciones as $asig) {
                        if ($asig['es_tienda'] == 0) {
                            $tieneNoTienda = true;
                            break;
                        } else {
                            $codigosTiendas[] = $asig['cod_sucursal'];
                        }
                    }

                    // Si es tienda física (es_tienda = 1) en todas sus asignaciones, filtrar por ellas
                    if (!$tieneNoTienda && !empty($codigosTiendas)) {
                        $placeholders = implode(',', array_fill(0, count($codigosTiendas), '?'));
                        $whereClause = " AND t.cod_sucursal IN ($placeholders)";
                        $paramsQuery = $codigosTiendas;
                    }
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
            $stmt->execute($paramsQuery);
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
