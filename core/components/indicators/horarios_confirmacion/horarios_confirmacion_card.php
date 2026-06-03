<?php
/**
 * Partial: Card del indicador "Horarios por Confirmar"
 * 
 * Usa las clases estándar de indexmodulos.css (indicator-container, etc.)
 * para mantener consistencia visual con los demás indicadores de rh/index.php.
 *
 * Variables requeridas antes del include:
 *   $hc_estadoConfirmacion  → resultado de hc_obtenerEstadoHorariosPendientesConfirmacion()
 */

$_hc_totalMostrar = ($hc_estadoConfirmacion['total_pendientes'] ?? 0)
                  + count($hc_estadoConfirmacion['ediciones_pendientes'] ?? []);
$_hc_color        = $hc_estadoConfirmacion['color'] ?? 'verde';
$_hc_estado       = $hc_estadoConfirmacion['estado'] ?? 'completo';
?>

<!-- Indicador: Horarios por Confirmar (core) -->
<div class="indicator-container" onclick="mostrarModalHorariosConfirmacion()" style="cursor: pointer;">
    <div class="indicator-header">
        <div class="indicator-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
    </div>

    <div class="indicator-count">
        <?php if ($_hc_estado === 'completo'): ?>
            <i class="fas fa-check" style="color: #28a745; font-size: 2rem !important;"></i>
        <?php else: ?>
            <?= $_hc_totalMostrar ?>
        <?php endif; ?>
    </div>

    <div class="indicator-info">
        <div class="indicator-titulo">
            Horarios por Confirmar
        </div>
        <div class="indicator-meta">
            <span>
                <span class="indicator-status <?= htmlspecialchars($_hc_color) ?>">
                    <?php if ($_hc_estado === 'completo'): ?>
                        Al día
                    <?php elseif ($_hc_estado === 'pendiente' && ($hc_estadoConfirmacion['periodo_activo'] ?? false)): ?>
                        <?= (int)($hc_estadoConfirmacion['dias_restantes'] ?? 0) ?> días restantes
                    <?php elseif ($_hc_estado === 'fuera_periodo'): ?>
                        Fuera de período
                    <?php else: ?>
                        Pendiente
                    <?php endif; ?>
                </span>
            </span>
            <span class="indicator-action">
                <i class="fas fa-arrow-right"></i>
            </span>
        </div>
    </div>
</div>
