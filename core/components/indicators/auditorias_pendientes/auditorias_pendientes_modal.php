<?php
/**
 * Partial: Modal de detalles de Auditorías Pendientes
 * 
 * Reutiliza el HTML/PHP de supervision/index.php adaptado
 * a los nombres e IDs del core y del nuevo indicador.
 *
 * Variables requeridas antes del include:
 *   $audpend_estadoAuditoriasMensual  → resultado de audpend_obtenerEstadoAuditoriasMensual()
 */
?>

<!-- Modal de Detalles de Auditorías Mensuales (Core) -->
<div id="modalAuditoriasMensuales" class="modal-pendientes">
    <div class="modal-content-pendientes" style="max-width: 95%;">
        <div class="modal-header-pendientes">
            <h3><i class="fas fa-clipboard-check"></i> Auditorías Mensuales por Sucursal</h3>
            <span class="close-modal" onclick="cerrarModalAuditoriasMensuales()">&times;</span>
        </div>
        <div class="modal-body-pendientes">

            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4>Resumen Mensual - <?= ucfirst($audpend_estadoAuditoriasMensual['mes_nombre']) ?> <?= $audpend_estadoAuditoriasMensual['ano'] ?></h4>
                <p><strong>Progreso Global:</strong> <?= $audpend_estadoAuditoriasMensual['total_completas'] ?> de <?= $audpend_estadoAuditoriasMensual['total_sucursales'] ?> sucursales (<?= $audpend_estadoAuditoriasMensual['porcentaje_global'] ?>%)</p>
                <p><strong>Requisitos:</strong>
                    • Departamento 1 (Managua): 3 visitas completas/mes<br>
                    • Otros departamentos: 2 visitas completas/mes<br>
                    <small>* Cada visita debe incluir las 6 auditorías (limpieza, personal, servicio, facturación, caja chica, inventario)</small>
                </p>
            </div>

            <?php if ($audpend_estadoAuditoriasMensual['total_sucursales'] > 0): ?>
                <div style="display: grid; gap: 15px;">
                    <?php foreach ($audpend_estadoAuditoriasMensual['sucursales'] as $sucursal): ?>
                        <div class="sucursal-auditoria-item" style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                            <div class="sucursal-header" style="background: #f8f9fa; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;"
                                onclick="toggleAuditoriasSucursal(<?= $sucursal['codigo'] ?>)">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <strong><?= $sucursal['nombre'] ?></strong>
                                    <span style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                        <?= $sucursal['departamento_nombre'] ?>
                                    </span>
                                    <?php
                                    $clasePorcentaje = 'porcentaje-badge';
                                    if ($sucursal['porcentaje'] == 100) {
                                        $clasePorcentaje .= '';
                                    } elseif ($sucursal['porcentaje'] >= 50) {
                                        $clasePorcentaje .= ' medio';
                                    } else {
                                        $clasePorcentaje .= ' bajo';
                                    }
                                    ?>
                                    <span class="<?= $clasePorcentaje ?>">
                                        <?= $sucursal['visitas_completas'] ?>/<?= $sucursal['visitas_requeridas'] ?> visitas (<?= $sucursal['porcentaje'] ?>%)
                                    </span>
                                </div>
                                <i class="fas fa-chevron-down" id="icon-<?= $sucursal['codigo'] ?>"></i>
                            </div>

                            <div id="auditorias-<?= $sucursal['codigo'] ?>" class="auditorias-contenido">
                                <div style="padding: 15px;">
                                    <div style="margin-bottom: 15px;">
                                        <strong>Detalle de Visitas:</strong>
                                        <?php if (empty($sucursal['detalle_visitas'])): ?>
                                            <p style="color: #666; font-style: italic; margin-top: 10px;">No se han realizado visitas este mes</p>
                                        <?php else: ?>
                                            <div style="display: grid; gap: 15px; margin-top: 10px;">
                                                <?php foreach ($sucursal['detalle_visitas'] as $index => $visita): ?>
                                                    <div style="border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                                                        <div style="background: <?= $visita['completa'] ? '#d4edda' : '#f8d7da' ?>; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
                                                            <span>
                                                                <i class="fas fa-calendar-day"></i>
                                                                <strong>Visita <?= $index + 1 ?>:</strong> <?= formatoFecha($visita['fecha']) ?>
                                                            </span>
                                                            <span style="<?= $visita['completa'] ? 'color: #155724;' : 'color: #721c24;' ?>">
                                                                <?php if ($visita['completa']): ?>
                                                                    <i class="fas fa-check-circle"></i> Completa (<?= $visita['total_completas'] ?>/6)
                                                                <?php else: ?>
                                                                    <i class="fas fa-exclamation-triangle"></i> Incompleta (<?= $visita['total_completas'] ?>/6)
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                        <div style="padding: 15px; background: white;">
                                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                                                <?php foreach ($visita['detalle_auditorias'] as $tipo => $auditoria): ?>
                                                                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                                                        <?php if ($auditoria['completa']): ?>
                                                                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                                                        <?php else: ?>
                                                                            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                                                                        <?php endif; ?>
                                                                        <span style="flex: 1;"><?= $auditoria['nombre'] ?></span>
                                                                        <?php if (!$auditoria['completa']): ?>
                                                                            <a href="<?= $auditoria['url'] ?>?sucursal=<?= $sucursal['codigo'] ?>&fecha=<?= $visita['fecha'] ?>"
                                                                                class="btn-auditoria btn-crear-auditoria"
                                                                                style="padding: 4px 8px; font-size: 0.7rem;"
                                                                                target="_blank">
                                                                                <i class="fas fa-plus"></i> Agregar
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="border-top: 1px solid #dee2e6; padding-top: 15px;">
                                        <strong>Agregar Nueva Auditoría:</strong>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-top: 10px;">
                                            <a href="/modulos/supervision/auditorias_original/agregar.php?sucursal=<?= $sucursal['codigo'] ?>"
                                                class="btn-auditoria btn-crear-auditoria"
                                                style="text-align: center; padding: 8px;"
                                                target="_blank">
                                                <i class="fas fa-plus"></i> Limpieza
                                            </a>
                                            <a href="/modulos/supervision/auditorias_original/agregarpersonal.php?sucursal=<?= $sucursal['codigo'] ?>"
                                                class="btn-auditoria btn-crear-auditoria"
                                                style="text-align: center; padding: 8px;"
                                                target="_blank">
                                                <i class="fas fa-plus"></i> Personal
                                            </a>
                                            <a href="/modulos/supervision/auditorias_original/agregarservicio.php?sucursal=<?= $sucursal['codigo'] ?>"
                                                class="btn-auditoria btn-crear-auditoria"
                                                style="text-align: center; padding: 8px;"
                                                target="_blank">
                                                <i class="fas fa-plus"></i> Servicio
                                            </a>
                                            <a href="/modulos/supervision/auditorias_original/auditinternas/auditoria_caja_facturacion.php?sucursal=<?= $sucursal['codigo'] ?>"
                                                class="btn-auditoria btn-crear-auditoria"
                                                style="text-align: center; padding: 8px;"
                                                target="_blank">
                                                <i class="fas fa-plus"></i> Facturación
                                            </a>
                                            <a href="/modulos/supervision/auditorias_original/auditinternas/auditoria_caja_chica.php?sucursal=<?= $sucursal['codigo'] ?>"
                                                class="btn-auditoria btn-crear-auditoria"
                                                style="text-align: center; padding: 8px;"
                                                target="_blank">
                                                <i class="fas fa-plus"></i> Caja Chica
                                            </a>
                                            <a href="/modulos/supervision/auditorias_original/auditinternas/auditoria_inventario.php?sucursal=<?= $sucursal['codigo'] ?>"
                                                class="btn-auditoria btn-crear-auditoria"
                                                style="text-align: center; padding: 8px;"
                                                target="_blank">
                                                <i class="fas fa-plus"></i> Inventario
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-info-circle" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h4>No hay sucursales para auditar</h4>
                    <p>No se encontraron sucursales activas.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>