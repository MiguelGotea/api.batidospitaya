<?php
/**
 * Template: Balance Card
 * Variables disponibles: $balance (datos del balance)
 */

$containerId = $balance['container_id'] ?? 'balanceContainer';
$ajaxUrl = $balance['ajax_url'] ?? '';
$nombre = $balance['nombre'] ?? 'Balance';
$icono = $balance['icono'] ?? 'fa-chart-bar';
$badge = $balance['badge'] ?? '';
?>

<!-- Balance Card -->
<div class="balance-card" style="margin-bottom: 25px; border: 1px solid rgba(0,0,0,0.08);">
    <div class="balance-card-header" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background-color: #fcfcfc; border-bottom: 1px solid rgba(0,0,0,0.06);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas <?= htmlspecialchars($icono) ?>" style="color: #0E544C; font-size: 1.2rem;"></i>
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #212529;"><?= htmlspecialchars($nombre) ?></h3>
        </div>
        <?php if (!empty($badge)): ?>
            <span class="badge" style="background-color: #0E544C; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; display: inline-flex; align-items: center;">
                <i class="fas fa-store" style="margin-right: 5px; font-size: 0.8rem;"></i><?= htmlspecialchars($badge) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="balance-card-body">
        <div class="ventas-scroll-container">
            <button class="scroll-btn scroll-btn-left" id="scrollLeft_<?= $containerId ?>">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="ventas-table-wrapper" id="<?= $containerId ?>_wrapper">
                <table class="ventas-meta-table" id="<?= $containerId ?>_table">
                    <thead>
                        <tr id="<?= $containerId ?>_header">
                            <!-- Generado dinámicamente por JS -->
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="<?= $containerId ?>_reales">
                            <!-- Generado dinámicamente por JS -->
                        </tr>
                        <tr id="<?= $containerId ?>_cumplimiento">
                            <!-- Generado dinámicamente por JS -->
                        </tr>
                    </tbody>
                </table>
            </div>
            <button class="scroll-btn scroll-btn-right" id="scrollRight_<?= $containerId ?>">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
// Inicializar balance cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initializeBalance === 'function') {
        initializeBalance('<?= $containerId ?>', '<?= $ajaxUrl ?>');
    }
});
</script>