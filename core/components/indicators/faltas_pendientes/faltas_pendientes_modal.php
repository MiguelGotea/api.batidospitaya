<?php
/**
 * Template: Modal de Faltas Pendientes
 * Variables disponibles: $modalData (datos del indicador)
 */

// Extraer datos
$detalles = $modalData['detalles'] ?? [];
$total = $modalData['total'] ?? 0;
$fechaDesde = $modalData['fecha_desde'] ?? '';
$fechaHasta = $modalData['fecha_hasta'] ?? '';
$diasRestantes = $modalData['dias_restantes'] ?? 0;
$urlFaltas = $modalData['url'] ?? 'faltas_manual.php';

// Función auxiliar para formato de fecha
if (!function_exists('formatoFecha')) {
    function formatoFecha($fecha)
    {
        return date('d/m/Y', strtotime($fecha));
    }
}

// Función auxiliar para formato de hora
if (!function_exists('formatoHoraAmPm')) {
    function formatoHoraAmPm($hora)
    {
        if (!$hora)
            return 'N/A';
        return date('g:i A', strtotime($hora));
    }
}
?>

<!-- Modal de Detalles de Faltas Pendientes -->
<div id="modalFaltas" class="modal-pendientes" style="display: block;">
    <div class="modal-content-pendientes" style="max-width: 90%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-list"></i> Detalles de Faltas Pendientes de Reportar</h3>
            <span class="close-modal" onclick="closeIndicatorModal()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong>Periodo:</strong>
                    <?= formatoFecha($fechaDesde) ?> -
                    <?= formatoFecha($fechaHasta) ?>
                    | <strong>Total:</strong>
                    <?= $total ?> faltas
                    <?php
                    if ($diasRestantes < 0) {
                        echo "<span style='color: #dc3545;'> (Vencido hace " . abs($diasRestantes) . " días)</span>";
                    } elseif ($diasRestantes === 0) {
                        echo "<span style='color: #dc3545;'> (Vence hoy)</span>";
                    } else {
                        echo " (" . $diasRestantes . " días restantes)";
                    }
                    ?>
                </div>
                <a href="<?= htmlspecialchars($urlFaltas) ?>" class="btn-ver-detalles" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Ir a Reportar Faltas
                </a>
            </div>

            <?php if (empty($detalles)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h4>No hay faltas pendientes de reportar</h4>
                    <p>Todas las ausencias han sido reportadas correctamente.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; max-height: 60vh;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%); color: white;">
                                <th style="padding: 12px; text-align: left;">Colaborador</th>
                                <th style="padding: 12px; text-align: center;">Sucursal</th>
                                <th style="padding: 12px; text-align: center;">Fecha</th>
                                <th style="padding: 12px; text-align: center;">Horario Programado</th>
                                <th style="padding: 12px; text-align: center;">Estado Día</th>
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
                                        <?= htmlspecialchars($falta['sucursal_nombre']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= formatoFecha($falta['fecha']) ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= $falta['hora_entrada_programada'] ? formatoHoraAmPm($falta['hora_entrada_programada']) : 'N/A' ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <span style="color: #dc3545; font-weight: bold;">
                                            Falta
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