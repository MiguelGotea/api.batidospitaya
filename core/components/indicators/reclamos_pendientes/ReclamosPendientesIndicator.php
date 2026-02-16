<?php

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

/**
 * Indicador: Reclamos Pendientes
 * Muestra los reclamos pendientes de atención
 */
class ReclamosPendientesIndicator extends BaseIndicator
{
    protected $codigo = 'reclamos_pendientes';
    protected $nombre = 'Reclamos Pendientes';
    protected $icono = 'fa-exclamation-triangle';
    protected $categoria = 'general';

    /**
     * Método principal requerido por BaseIndicator
     */
    public function getData($userId)
    {
        // Este indicador está disponible para todos
        return $this->getDatosOperaciones();
    }

    /**
     * Detectar el contexto del usuario según su cargo
     */
    protected function detectarContexto($userId)
    {
        // Este indicador no necesita detección de contexto
        return 'general';
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
     * Obtener datos para contexto de Líderes
     */
    protected function getDatosLider($userId)
    {
        return $this->getDatosOperaciones();
    }

    /**
     * Obtener datos para contexto de Operaciones
     */
    protected function getDatosOperaciones()
    {
        try {
            // Obtener reclamos pendientes (sin reporte de investigación)
            $sql = "
                SELECT 
                    r.id,
                    r.fecha_evento,
                    r.sucursal,
                    r.descripcion,
                    r.tipo_reclamo,
                    r.medio_compra,
                    DATEDIFF(CURDATE(), r.fecha_evento) as dias_pendiente,
                    s.nombre as sucursal_nombre
                FROM reclamos r
                LEFT JOIN reportes_investigacion ri ON r.id = ri.reclamo_id 
                LEFT JOIN sucursales s ON r.sucursal = s.codigo
                WHERE ri.id IS NULL  -- Sin reporte de investigación
                AND r.fecha_evento IS NOT NULL
                ORDER BY r.fecha_evento ASC, dias_pendiente DESC
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $reclamosPendientes = $stmt->fetchAll();

            $total = count($reclamosPendientes);

            // Determinar color según cantidad y días pendientes
            $color = $this->determinarColor($reclamosPendientes);

            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => $total,
                'color' => $color,
                'url' => '../supervision/auditorias_original/reclamospend.php',
                'modo' => 'operaciones'
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo reclamos pendientes: " . $e->getMessage());
            return $this->getErrorResponse();
        }
    }

    /**
     * Obtener datos para contexto de RH
     */
    protected function getDatosRH()
    {
        return $this->getDatosOperaciones();
    }

    /**
     * Determinar color según cantidad de reclamos y días pendientes
     * Rojo: Si hay reclamos con más de 7 días
     * Amarillo: Si hay reclamos pero todos dentro de 7 días
     * Verde: Sin reclamos pendientes
     */
    private function determinarColor($reclamosPendientes)
    {
        if (empty($reclamosPendientes)) {
            return 'verde';
        }

        // Verificar si hay reclamos con más de 7 días
        foreach ($reclamosPendientes as $reclamo) {
            if ($reclamo['dias_pendiente'] > 7) {
                return 'rojo';
            }
        }

        // Si hay reclamos pero todos están dentro de los 7 días
        return 'amarillo';
    }
}
