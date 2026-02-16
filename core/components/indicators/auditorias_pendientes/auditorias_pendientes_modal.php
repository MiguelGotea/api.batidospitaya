<?php
/**
 * Template: Modal de Auditorías Pendientes
 * Variables disponibles: $modalData (datos del indicador)
 */

// Extraer datos
$detalles = $modalData['detalles'] ?? [];
$total = $modalData['valor'] ?? 0;

// Función auxiliar para formato de fecha
if (!function_exists('formatoFechaAuditorias')) {
    function formatoFechaAuditorias($fecha)
    {
        return date('d/m/Y', strtotime($fecha));
    }
}

// Función auxiliar para prioridad
if (!function_exists('formatoPrioridad')) {
    function formatoPrioridad($prioridad)
    {
        $prioridades = [
            'alta' => ['texto' => 'Alta', 'color' => '#dc3545'],
            'media' => ['texto' => 'Media', 'color' => '#ffc107'],
            'baja' => ['texto' => 'Baja', 'color' => '#28a745']
        ];
        return $prioridades[$prioridad] ?? ['texto' => ucfirst($prioridad), 'color' => '#6c757d'];
    }
}
?>

<!-- Modal de Detalles de Auditorías Pendientes -->
<div id="modalAuditorias" class="modal-pendientes" style="display: block;">
    <div class="modal-content-pendientes" style="max-width: 90%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-clipboard-list"></i> Auditorías Pendientes</h3>
            <span class="close-modal" onclick="cerrarModalAuditorias()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong>Total:</strong>
                    <?= $total ?> auditorías programadas
                </div>
            </div>

            <?php if (empty($detalles)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h4>No hay auditorías pendientes</h4>
                    <p>Todas las auditorías están al día.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; max-height: 60vh;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%); color: white;">
                                <th style="padding: 12px; text-align: left;">Sucursal</th>
                                <th style="padding: 12px; text-align: center;">Tipo Auditoría</th>
                                <th style="padding: 12px; text-align: center;">Fecha Programada</th>
                                <th style="padding: 12px; text-align: left;">Auditor</th>
                                <th style="padding: 12px; text-align: center;">Prioridad</th>
                                <th style="padding: 12px; text-align: center;">Días Restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $index => $auditoria): ?>
                                <tr style="background: <?= $index % 2 === 0 ? '#f8f9fa' : 'white' ?>;">
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <strong>
                                            <?= htmlspecialchars($auditoria['sucursal_nombre']) ?>
                                        </strong>
                                        <br><small>Código:
                                            <?= $auditoria['cod_sucursal'] ?>
                                        </small>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= htmlspecialchars(ucfirst($auditoria['tipo_auditoria'])) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= formatoFechaAuditorias($auditoria['fecha_programada']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <?= htmlspecialchars($auditoria['nombre_auditor'] ?? 'Sin asignar') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?php
                                        $prioridad = formatoPrioridad($auditoria['prioridad']);
                                        ?>
                                        <span
                                            style="padding: 4px 8px; border-radius: 4px; background: <?= $prioridad['color'] ?>; color: white; font-size: 0.85rem;">
                                            <?= $prioridad['texto'] ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?php
                                        $dias = $auditoria['dias_restantes'];
                                        $color = $dias <= 3 ? '#dc3545' : ($dias <= 7 ? '#ffc107' : '#28a745');
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