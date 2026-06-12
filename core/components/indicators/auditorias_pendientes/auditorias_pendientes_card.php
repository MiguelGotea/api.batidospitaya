<?php
/**
 * Partial: Card del indicador "Auditorías Pendientes"
 * 
 * Usa las clases estándar de indexmodulos.css (indicator-container, etc.)
 * para mantener consistencia visual con los demás indicadores del ERP.
 *
 * Variables requeridas antes del include:
 *   $audpend_estadoAuditoriasMensual  → resultado de audpend_obtenerEstadoAuditoriasMensual()
 */

$_ap_totalMostrar = ($audpend_estadoAuditoriasMensual['total_pendientes'] ?? 0);
$_ap_color        = $audpend_estadoAuditoriasMensual['color_global'] ?? 'verde';
$_ap_estado       = $audpend_estadoAuditoriasMensual['estado_global'] ?? 'completo';
$_ap_mesNombre    = $audpend_estadoAuditoriasMensual['mes_nombre'] ?? '';
?>

<!-- Indicador: Auditorías Pendientes (core) -->
<div class="indicator-container" onclick="mostrarModalAuditoriasMensuales()" style="cursor: pointer;">
    <div class="indicator-header">
        <div class="indicator-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>
    </div>

    <div class="indicator-count">
        <?php if ($_ap_estado === 'completo'): ?>
            <i class="fas fa-check" style="color: #28a745; font-size: 2rem !important;"></i>
        <?php else: ?>
            <?= $_ap_totalMostrar ?>
        <?php endif; ?>
    </div>

    <div class="indicator-info">
        <div class="indicator-titulo">
            Auditorías Pendientes
        </div>
        <div class="indicator-meta">
            <span>
                <span class="indicator-status <?= htmlspecialchars($_ap_color) ?>">
                    <?php if ($_ap_estado === 'completo'): ?>
                        Al día
                    <?php else: ?>
                        <?= ucfirst($_ap_mesNombre) ?>
                    <?php endif; ?>
                </span>
            </span>
            <span class="indicator-action">
                <i class="fas fa-arrow-right"></i>
            </span>
        </div>
    </div>
</div>
