<?php
/**
 * Template: Modal de Faltas Pendientes de Revisión
 * Variables disponibles: $modalData (datos del indicador)
 */

// Extraer datos
$detalles = $modalData['detalles'] ?? [];
$total = $modalData['valor'] ?? 0;

// Función auxiliar para formato de fecha
if (!function_exists('formatoFechaFaltasRevision')) {
    function formatoFechaFaltasRevision($fecha)
    {
        return date('d/m/Y', strtotime($fecha));
    }
}

// Función auxiliar para tipo de falta
if (!function_exists('formatoTipoFalta')) {
    function formatoTipoFalta($tipo)
    {
        $tipos = [
            'injustificada' => 'Injustificada',
            'justificada' => 'Justificada',
            'medica' => 'Médica',
            'personal' => 'Personal'
        ];
        return $tipos[$tipo] ?? ucfirst($tipo);
    }
}
?>

<!-- Modal de Detalles de Faltas Pendientes de Revisión -->
<div id="modalFaltasRevision" class="modal-pendientes" style="display: block;">
    <div class="modal-content-pendientes" style="max-width: 90%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-clipboard-check"></i> Faltas Pendientes de Revisión</h3>
            <span class="close-modal" onclick="cerrarModalFaltasRevision()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong>Total:</strong>
                    <?= $total ?> faltas pendientes de revisión
                </div>
            </div>

            <?php if (empty($detalles)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h4>No hay faltas pendientes de revisión</h4>
                    <p>Todas las faltas han sido revisadas.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; max-height: 60vh;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%); color: white;">
                                <th style="padding: 12px; text-align: left;">Colaborador</th>
                                <th style="padding: 12px; text-align: center;">Sucursal</th>
                                <th style="padding: 12px; text-align: center;">Fecha Falta</th>
                                <th style="padding: 12px; text-align: center;">Tipo</th>
                                <th style="padding: 12px; text-align: left;">Observaciones</th>
                                <th style="padding: 12px; text-align: center;">Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $index => $falta): ?>
                                <tr style="background: <?= $index % 2 === 0 ? '#f8f9fa' : 'white' ?>;">
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <strong>
                                            <?= htmlspecialchars($falta['nombre_completo']) ?>
                                        </strong>
                                        <br><small>Código:
                                            <?= $falta['cod_operario'] ?>
                                        </small>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= htmlspecialchars($falta['sucursal_nombre'] ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= formatoFechaFaltasRevision($falta['fecha_falta']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <span
                                            style="padding: 4px 8px; border-radius: 4px; background: #ffc107; color: #000; font-size: 0.85rem;">
                                            <?= formatoTipoFalta($falta['tipo_falta']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <?= htmlspecialchars($falta['observaciones'] ?? 'Sin observaciones') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= formatoFechaFaltasRevision($falta['fecha_registro']) ?>
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