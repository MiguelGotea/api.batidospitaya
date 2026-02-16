<?php

namespace Core\Components\Balances\List;

use Core\Components\Balances\BaseBalance;

/**
 * Ventas vs Meta Balance Component
 */
class VentasVsMetaBalance extends BaseBalance
{
    /**
     * Render balance configuration
     * @param int $userId
     * @return array
     */
    public function render($userId)
    {
        return [
            'nombre' => $this->config['nombre'] ?? 'Ventas vs Meta',
            'icono' => $this->config['icono'] ?? 'fa-chart-line',
            'container_id' => 'ventasBalanceContainer',
            'ajax_url' => $this->config['ajax_url'] ?? 'ajax/get_ventas_balance.php',
            'config' => $this->config
        ];
    }

    /**
     * Fetch balance data for AJAX requests
     * @param int $userId
     * @param array $params
     * @return array
     */
    public function fetchData($userId, $params = [])
    {
        // Esta lógica es la que estaba en get_ventas_balance.php
        // TODO: En un sistema más maduro, esta lógica usaría modelos del core

        $codOperario = $userId;

        // Obtener sucursales del líder usando la función estándar
        $sucursales = obtenerSucursalesLider($codOperario);

        if (empty($sucursales)) {
            return ['success' => false, 'message' => 'Sin sucursales asignadas'];
        }

        $sucursalCodigo = $sucursales[0]['codigo'];
        $fechaReferencia = date('Y-m-d', strtotime('-1 day'));
        $primerDiaMes = date('Y-m-01', strtotime($fechaReferencia));
        $diaActual = (int) date('d', strtotime($fechaReferencia));

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $mesActual = $meses[(int) date('m', strtotime($fechaReferencia)) - 1];

        try {
            $stmtMeta = $this->conn->prepare("
                SELECT meta FROM ventas_meta 
                WHERE cod_sucursal = ? AND fecha >= ? AND fecha <= ?
                LIMIT 1
            ");
            $stmtMeta->execute([$sucursalCodigo, $primerDiaMes, $fechaReferencia]);
            $metaData = $stmtMeta->fetch();
            $metaMensual = $metaData ? round($metaData['meta'] / 1000, 1) : 0;

            $datos = [];
            $totalVentas = 0;
            $totalMeta = 0;
            $diasConDatos = 0;

            $yearMonth = date('Y-m-', strtotime($fechaReferencia));
            for ($dia = $diaActual; $dia >= 1; $dia--) {
                $fecha = $yearMonth . str_pad($dia, 2, '0', STR_PAD_LEFT);

                $stmtVentas = $this->conn->prepare("
                    SELECT COALESCE(SUM(Precio), 0) AS Total_Ventas
                    FROM VentasGlobalesAccessCSV
                    WHERE local = ? AND Anulado = 0 AND Fecha = ?
                ");
                $stmtVentas->execute([$sucursalCodigo, $fecha]);
                $ventasData = $stmtVentas->fetch();
                $ventasReales = $ventasData ? ($ventasData['Total_Ventas'] / 1000) : 0;

                $stmtMetaDia = $this->conn->prepare("
                    SELECT meta FROM ventas_meta 
                    WHERE cod_sucursal = ? AND fecha = ?
                ");
                $stmtMetaDia->execute([$sucursalCodigo, $fecha]);
                $metaDiaData = $stmtMetaDia->fetch();
                $metaDia = $metaDiaData ? ($metaDiaData['meta'] / 1000) : 0;

                $cumplimiento = $metaDia > 0 ? ($ventasReales / $metaDia) * 100 : 0;

                if ($cumplimiento < 85) {
                    $color = 'rojo';
                } elseif ($cumplimiento >= 100) {
                    $color = 'verde';
                } else {
                    $color = 'amarillo';
                }

                $datos[] = [
                    'dia' => $dia,
                    'fecha' => $fecha,
                    'ventas_reales' => round($ventasReales, 1),
                    'meta' => round($metaDia, 1),
                    'cumplimiento' => round($cumplimiento, 0),
                    'color' => $color
                ];

                $totalVentas += $ventasReales;
                $totalMeta += $metaDia;
                $diasConDatos++;
            }

            $promedioVentas = $diasConDatos > 0 ? $totalVentas / $diasConDatos : 0;
            $promedioMeta = $diasConDatos > 0 ? $totalMeta / $diasConDatos : 0;
            $cumplimientoMes = $promedioMeta > 0 ? ($promedioVentas / $promedioMeta) * 100 : 0;

            if ($cumplimientoMes < 85) {
                $colorMes = 'rojo';
            } elseif ($cumplimientoMes >= 100) {
                $colorMes = 'verde';
            } else {
                $colorMes = 'amarillo';
            }

            return [
                'success' => true,
                'meta_mensual' => $metaMensual,
                'mes_actual' => $mesActual,
                'datos' => $datos,
                'promedio_mes' => [
                    'ventas_reales' => round($promedioVentas, 1),
                    'cumplimiento' => round($cumplimientoMes, 0),
                    'color' => $colorMes
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
