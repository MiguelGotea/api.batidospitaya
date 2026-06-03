<?php
/**
 * Partial: Modal de detalles de Horarios por Confirmar
 * 
 * Reutiliza el HTML/PHP de supervision/index.php adaptado
 * a los nombres e IDs del core y rh.
 *
 * Variables requeridas antes del include:
 *   $hc_estadoConfirmacion  → resultado de hc_obtenerEstadoHorariosPendientesConfirmacion()
 */
?>

<!-- Modal de Detalles de Confirmación Pendiente (Core) -->
<div id="modalHorariosConfirmacion" class="modal-pendientes">
    <div class="modal-content-pendientes" style="max-width: 95%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-clipboard-check"></i> Detalles de Horarios Pendientes de Confirmación</h3>
            <span class="close-modal" onclick="cerrarModalHorariosConfirmacion()">&times;</span>
        </div>
        <div class="modal-body-pendientes">
            <div class="filtros-modal" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; display:none;">
                <div>
                    <strong>Semana:</strong>
                    <?php if ($hc_estadoConfirmacion['semana_siguiente']): ?>
                        <?= $hc_estadoConfirmacion['semana_siguiente']['numero_semana'] ?>
                        (<?= formatoFecha($hc_estadoConfirmacion['semana_siguiente']['fecha_inicio']) ?> -
                        <?= formatoFecha($hc_estadoConfirmacion['semana_siguiente']['fecha_fin']) ?>)
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                    |
                    <strong>Por confirmar:</strong> <?= $hc_estadoConfirmacion['total_pendientes'] ?>
                    <?php if ($hc_estadoConfirmacion['total_sin_horario'] > 0): ?>
                        | <strong>Sin horario líder:</strong> <?= $hc_estadoConfirmacion['total_sin_horario'] ?>
                    <?php endif; ?>
                    <?php if (!empty($hc_estadoConfirmacion['ediciones_pendientes'])): ?>
                        | <strong>Ediciones Líder:</strong> <?= count($hc_estadoConfirmacion['ediciones_pendientes']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($hc_estadoConfirmacion['periodo_activo'] && ($hc_estadoConfirmacion['total_pendientes'] > 0 || !empty($hc_estadoConfirmacion['sucursales_pendientes']))): ?>
                    <a href="<?= $hc_estadoConfirmacion['url'] ?>" class="btn-ver-detalles" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Confirmar Horarios
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($hc_estadoConfirmacion['sucursales_pendientes']) && empty($hc_estadoConfirmacion['ediciones_pendientes'])): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                    <h4>Confirmación completa</h4>
                    <p>Todos los horarios de la semana <?= $hc_estadoConfirmacion['semana_siguiente']['numero_semana'] ?? 'siguiente' ?> han sido confirmados.</p>
                </div>
            <?php else: ?>

                <!-- Información del Período -->
                <?php if ($hc_estadoConfirmacion['periodo_activo']): ?>
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; margin-bottom: 15px; display:none;">
                        <p style="margin: 0;">
                            <i class="fas fa-clock"></i>
                            <strong>Período activo:</strong> Tienes <strong><?= $hc_estadoConfirmacion['dias_restantes'] ?> días</strong> para confirmar los horarios
                            de la semana <?= $hc_estadoConfirmacion['semana_siguiente']['numero_semana'] ?>.
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Sección de Sucursales con Horarios Pendientes de Confirmar -->
                <?php if (!empty($hc_estadoConfirmacion['sucursales_pendientes'])): ?>
                    <div style="margin-bottom: 30px;">
                        <h4 style="display:none;"><i class="fas fa-clock"></i> Sucursales con Horarios Pendientes de Confirmar (<?= count($hc_estadoConfirmacion['sucursales_pendientes']) ?>)</h4>
                        <p style="color: #666; margin-bottom: 15px; display:none;"><i class="fas fa-clock"></i>
                            Estas sucursales tienen horarios programados por líderes que necesitan confirmación.
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 15px;">
                            <?php foreach ($hc_estadoConfirmacion['sucursales_pendientes'] as $sucursalPendiente): ?>
                                <div class="sucursal-card-pendiente"
                                    onclick="window.open('programar_horarios_operaciones.php?semana=<?= $hc_estadoConfirmacion['semana_siguiente']['numero_semana'] ?? '' ?>&sucursal=<?= $sucursalPendiente['sucursal']['codigo'] ?>', '_blank')">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <strong style="flex: 1;"><?= htmlspecialchars($sucursalPendiente['sucursal']['nombre']) ?></strong>
                                        <span style="background: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; display:none;">
                                            <?= $sucursalPendiente['pendientes_confirmar'] ?> pendientes
                                        </span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #666;">
                                        <?php if ($hc_estadoConfirmacion['semana_siguiente']): ?>
                                            <?= formatoFecha($hc_estadoConfirmacion['semana_siguiente']['fecha_inicio']) ?> - <?= formatoFecha($hc_estadoConfirmacion['semana_siguiente']['fecha_fin']) ?>
                                        <?php endif; ?>
                                        (<?= $hc_estadoConfirmacion['semana_siguiente']['numero_semana'] ?? 'N/A' ?>)
                                    </div>
                                    <div style="margin-top: 8px; text-align: right;">
                                        <small style="color: #007bff; font-weight: bold;">
                                            <i class="fas fa-external-link-alt"></i> Click para confirmar
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Sección de Ediciones Pendientes de reconfirmar edición de Líderes -->
                <?php if (!empty($hc_estadoConfirmacion['ediciones_pendientes'])): ?>
                    <div style="margin-bottom: 30px;">
                        <h4 style="display:none;"><i class="fas fa-edit"></i> Ediciones Pendientes de Reconfirmar (<?= count($hc_estadoConfirmacion['ediciones_pendientes']) ?>)</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($hc_estadoConfirmacion['ediciones_pendientes'] as $edicion): ?>
                                <div class="sucursal-card-edicion"
                                    onclick="window.open('programar_horarios_operaciones.php?semana=<?= $edicion['numero_semana'] ?>&sucursal=<?= $edicion['cod_sucursal'] ?>', '_blank')">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <strong style="flex: 1;"><?= htmlspecialchars($edicion['sucursal_nombre']) ?></strong>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #856404;">
                                        <?= formatoFecha($edicion['fecha_inicio']) ?> al <?= formatoFecha($edicion['fecha_fin']) ?> (<?= $edicion['numero_semana'] ?>)
                                        <br><strong>Estado: Confirmado con cambios</strong>
                                    </div>
                                    <div style="margin-top: 8px; text-align: right;">
                                        <small style="color: #007bff; font-weight: bold;">
                                            <i class="fas fa-external-link-alt"></i> Click para reconfirmar
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Sección de Sucursales Sin Horario del Líder -->
                <?php if (!empty($hc_estadoConfirmacion['sin_horario_lider'])): ?>
                    <div style="margin-bottom: 30px;">
                        <h4 style="display:none;"><i class="fas fa-exclamation-triangle"></i> Sucursales Sin Horario del Líder (<?= count($hc_estadoConfirmacion['sin_horario_lider']) ?>)</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($hc_estadoConfirmacion['sin_horario_lider'] as $sucursalSinHorario): ?>
                                <div class="sucursal-card-informativo">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <strong style="flex: 1;"><?= htmlspecialchars($sucursalSinHorario['sucursal']['nombre']) ?></strong>
                                        <span style="background: #ffc107; color: #856404; padding: 3px 8px; border-radius: 12px; font-size: 0.8rem; display:none;">
                                            <?= $sucursalSinHorario['total_operarios'] ?> operarios
                                        </span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #856404;">
                                        <?php if ($hc_estadoConfirmacion['semana_siguiente']): ?>
                                            <?= formatoFecha($hc_estadoConfirmacion['semana_siguiente']['fecha_inicio']) ?> - <?= formatoFecha($hc_estadoConfirmacion['semana_siguiente']['fecha_fin']) ?>
                                        <?php endif; ?>
                                        (<?= $hc_estadoConfirmacion['semana_siguiente']['numero_semana'] ?? 'N/A' ?>)
                                        <br><strong>Sin horario programado</strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
