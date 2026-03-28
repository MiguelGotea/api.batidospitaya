<?php

namespace Core\Components\Indicators\List;

use Core\Components\Indicators\BaseIndicator;

/**
 * Indicador: Documentos Incompletos
 * Muestra operarios con documentación incompleta
 */
class DocumentosIncompletosIndicator extends BaseIndicator
{
    protected $codigo = 'documentos_incompletos';
    protected $nombre = 'Documentos Incompletos';
    protected $icono = 'fa-file-alt';
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
            'url' => '#'
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
            // TODO: Implementar función obtenerCantidadOperariosIncompletos()
            $query = "
                SELECT COUNT(*) as total
                FROM Operarios
                WHERE Estado = 'Activo'
                AND (
                    FotoOperario IS NULL OR FotoOperario = '' OR
                    DPI IS NULL OR DPI = '' OR
                    NIT IS NULL OR NIT = ''
                )
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            $total = $result['total'] ?? 0;

            $color = $this->determinarColor($total);

            return [
                'codigo' => $this->codigo,
                'nombre' => $this->nombre,
                'icono' => $this->icono,
                'valor' => $total,
                'color' => $color,
                'url' => '../talento_humano/operarios.php',
                'modo' => 'rh'
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo documentos incompletos: " . $e->getMessage());
            return $this->getErrorResponse();
        }
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
