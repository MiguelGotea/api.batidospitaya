<?php
/**
 * Template: Modal de Contratos por Vencer
 * Variables disponibles: $modalData (datos del indicador)
 */

// Extraer datos
$detalles = $modalData['detalles'] ?? [];
$total = $modalData['valor'] ?? 0;

// Función auxiliar para formato de fecha
if (!function_exists('formatoFechaContratos')) {
    function formatoFechaContratos($fecha)
    {
        return date('d/m/Y', strtotime($fecha));
    }
}
?>

<!-- Modal de Detalles de Contratos por Vencer -->
<div id="modalContratos" class="modal-pendientes" style="display: block;">
    <div class="modal-content-pendientes" style="max-width: 90%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-file-contract"></i> Contratos por Vencer</h3>
            <span class="close-modal" onclick="cerrarModalContratos()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong>Total:</strong>
                    <?= $total ?> contratos próximos a vencer (30 días)
                </div>
            </div>

            <?php if (empty($detalles)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h4>No hay contratos próximos a vencer</h4>
                    <p>Todos los contratos están vigentes.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; max-height: 60vh;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%); color: white;">
                                <th style="padding: 12px; text-align: left;">Colaborador</th>
                                <th style="padding: 12px; text-align: center;">Sucursal</th>
                                <th style="padding: 12px; text-align: center;">Fecha Inicio</th>
                                <th style="padding: 12px; text-align: center;">Fecha Fin</th>
                                <th style="padding: 12px; text-align: center;">Días Restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $index => $contrato): ?>
                                <tr style="background: <?= $index % 2 === 0 ? '#f8f9fa' : 'white' ?>;">
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <strong>
                                            <?= htmlspecialchars($contrato['nombre_completo']) ?>
                                        </strong>
                                        <br><small>Código:
                                            <?= $contrato['CodOperario'] ?>
                                        </small>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= htmlspecialchars($contrato['sucursal_nombre'] ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= formatoFechaContratos($contrato['fecha_inicio']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= formatoFechaContratos($contrato['fecha_fin']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?php
                                        $dias = $contrato['dias_restantes'];
                                        $color = $dias <= 7 ? '#dc3545' : ($dias <= 15 ? '#ffc107' : '#28a745');
                                        ?>
                                        <span style="color: <?= $color ?>; font-weight: bold;">
                                            <?= $dias ?> días
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>