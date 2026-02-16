<?php
/**
 * Template: Modal de Sucursales Auditadas
 * Variables disponibles: $modalData (datos del indicador)
 */

// Extraer datos
$detalles = $modalData['detalles'] ?? [];
$total = $modalData['valor'] ?? 0;

// Función auxiliar para formato de fecha
if (!function_exists('formatoFechaSucursalesAuditadas')) {
    function formatoFechaSucursalesAuditadas($fecha)
    {
        return date('d/m/Y', strtotime($fecha));
    }
}

// Función auxiliar para calificación
if (!function_exists('formatoCalificacion')) {
    function formatoCalificacion($calificacion)
    {
        if ($calificacion >= 90) {
            return ['texto' => 'Excelente', 'color' => '#28a745'];
        } elseif ($calificacion >= 75) {
            return ['texto' => 'Bueno', 'color' => '#17a2b8'];
        } elseif ($calificacion >= 60) {
            return ['texto' => 'Regular', 'color' => '#ffc107'];
        } else {
            return ['texto' => 'Deficiente', 'color' => '#dc3545'];
        }
    }
}
?>

<!-- Modal de Detalles de Sucursales Auditadas -->
<div id="modalSucursalesAuditadas" class="modal-pendientes" style="display: block;">
    <div class="modal-content-pendientes" style="max-width: 90%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-check-square"></i> Sucursales Auditadas (Últimos 30 días)</h3>
            <span class="close-modal" onclick="cerrarModalSucursalesAuditadas()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong>Total:</strong>
                    <?= $total ?> sucursales auditadas
                </div>
            </div>

            <?php if (empty($detalles)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-info-circle" style="font-size: 3rem; color: #17a2b8; margin-bottom: 15px;"></i>
                    <h4>No hay auditorías completadas</h4>
                    <p>No se han realizado auditorías en los últimos 30 días.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; max-height: 60vh;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%); color: white;">
                                <th style="padding: 12px; text-align: left;">Sucursal</th>
                                <th style="padding: 12px; text-align: center;">Tipo Auditoría</th>
                                <th style="padding: 12px; text-align: center;">Fecha Realizada</th>
                                <th style="padding: 12px; text-align: left;">Auditor</th>
                                <th style="padding: 12px; text-align: center;">Calificación</th>
                                <th style="padding: 12px; text-align: left;">Observaciones</th>
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
                                        <?= formatoFechaSucursalesAuditadas($auditoria['fecha_realizada']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <?= htmlspecialchars($auditoria['nombre_auditor'] ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?php
                                        $calif = formatoCalificacion($auditoria['calificacion']);
                                        ?>
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                            <span style="font-size: 1.2rem; font-weight: bold; color: <?= $calif['color'] ?>;">
                                                <?= $auditoria['calificacion'] ?>%
                                            </span>
                                            <span
                                                style="padding: 2px 6px; border-radius: 4px; background: <?= $calif['color'] ?>; color: white; font-size: 0.75rem;">
                                                <?= $calif['texto'] ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <?= htmlspecialchars($auditoria['observaciones'] ?? 'Sin observaciones') ?>
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