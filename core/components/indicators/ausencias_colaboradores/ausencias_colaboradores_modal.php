<?php
/**
 * Template: Modal de Ausencias de Colaboradores
 * Variables disponibles: $modalData (datos del indicador)
 */

// Extraer datos
$detalles = $modalData['detalles'] ?? [];
$total = $modalData['valor'] ?? 0;

// Función auxiliar para formato de fecha
if (!function_exists('formatoFechaAusencias')) {
    function formatoFechaAusencias($fecha)
    {
        return date('d/m/Y', strtotime($fecha));
    }
}

// Función auxiliar para tipo de ausencia
if (!function_exists('formatoTipoAusencia')) {
    function formatoTipoAusencia($tipo)
    {
        $tipos = [
            'permiso' => 'Permiso',
            'vacaciones' => 'Vacaciones',
            'licencia' => 'Licencia',
            'medica' => 'Médica',
            'personal' => 'Personal'
        ];
        return $tipos[$tipo] ?? ucfirst($tipo);
    }
}
?>

<!-- Modal de Detalles de Ausencias de Colaboradores -->
<div id="modalAusencias" class="modal-pendientes" style="display: block;">
    <div class="modal-content-pendientes" style="max-width: 90%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-user-times"></i> Ausencias de Hoy</h3>
            <span class="close-modal" onclick="cerrarModalAusencias()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong>Total:</strong>
                    <?= $total ?> colaboradores ausentes hoy
                </div>
            </div>

            <?php if (empty($detalles)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h4>No hay ausencias registradas hoy</h4>
                    <p>Todos los colaboradores están presentes.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; max-height: 60vh;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(90deg, #51B8AC 0%, #0E544C 100%); color: white;">
                                <th style="padding: 12px; text-align: left;">Colaborador</th>
                                <th style="padding: 12px; text-align: center;">Sucursal</th>
                                <th style="padding: 12px; text-align: center;">Cargo</th>
                                <th style="padding: 12px; text-align: center;">Tipo Ausencia</th>
                                <th style="padding: 12px; text-align: left;">Motivo</th>
                                <th style="padding: 12px; text-align: center;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $index => $ausencia): ?>
                                <tr style="background: <?= $index % 2 === 0 ? '#f8f9fa' : 'white' ?>;">
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <strong>
                                            <?= htmlspecialchars($ausencia['nombre_completo']) ?>
                                        </strong>
                                        <br><small>Código:
                                            <?= $ausencia['cod_operario'] ?>
                                        </small>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= htmlspecialchars($ausencia['sucursal_nombre'] ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?= htmlspecialchars($ausencia['cargo_nombre'] ?? 'N/A') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?php
                                        $tipo = $ausencia['tipo_ausencia'];
                                        $colorTipo = match ($tipo) {
                                            'vacaciones' => '#28a745',
                                            'permiso' => '#ffc107',
                                            'licencia' => '#17a2b8',
                                            'medica' => '#dc3545',
                                            default => '#6c757d'
                                        };
                                        ?>
                                        <span
                                            style="padding: 4px 8px; border-radius: 4px; background: <?= $colorTipo ?>; color: white; font-size: 0.85rem;">
                                            <?= formatoTipoAusencia($tipo) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                                        <?= htmlspecialchars($ausencia['motivo'] ?? 'Sin especificar') ?>
                                    </td>
                                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                        <?php
                                        $estado = $ausencia['estado'];
                                        $colorEstado = match ($estado) {
                                            'aprobada' => '#28a745',
                                            'pendiente' => '#ffc107',
                                            'rechazada' => '#dc3545',
                                            default => '#6c757d'
                                        };
                                        ?>
                                        <span style="color: <?= $colorEstado ?>; font-weight: bold;">
                                            <?= ucfirst($estado) ?>
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