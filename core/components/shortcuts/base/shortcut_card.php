<?php
/**
 * Template: Tarjeta de Acceso Directo (Shortcut)
 * Variables disponibles: $shortcut (datos del acceso directo)
 */
?>

<!-- Shortcut: <?= htmlspecialchars($shortcut['nombre'] ?? 'Herramienta') ?> -->
<a href="<?= htmlspecialchars($shortcut['url'] ?? '#') ?>" style="text-decoration: none; display: block;">
    <div class="indicator-container shortcut-card" style="cursor: pointer;">
        <div class="indicator-header">
            <div class="indicator-icon">
                <i class="fas <?= htmlspecialchars($shortcut['icono'] ?? 'fa-link') ?>"></i>
            </div>
        </div>

        <div class="indicator-info">
            <div class="indicator-titulo">
                <?= htmlspecialchars($shortcut['nombre'] ?? 'Herramienta') ?>
            </div>

            <?php if (!empty($shortcut['descripcion'])): ?>
                <div class="shortcut-description">
                    <?= htmlspecialchars($shortcut['descripcion']) ?>
                </div>
            <?php endif; ?>

            <div class="indicator-meta">
                <span class="indicator-action">
                    <i class="fas fa-arrow-right"></i>
                </span>
            </div>
        </div>
    </div>
</a>