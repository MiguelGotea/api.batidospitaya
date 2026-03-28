<?php
/**
 * Template: Balance Card
 * Variables disponibles: $balance (datos del balance)
 */

$containerId = $balance['container_id'] ?? 'balanceContainer';
$ajaxUrl = $balance['ajax_url'] ?? '';
$nombre = $balance['nombre'] ?? 'Balance';
$icono = $balance['icono'] ?? 'fa-chart-bar';
?>

<!-- Balance Card -->
<div class="balance-card">
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