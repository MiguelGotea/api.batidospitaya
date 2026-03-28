<?php
/**
 * Template: Tarjeta de Indicador - Estilo Original
 * Variables disponibles: $ind (datos del indicador)
 */

// Determinar si tiene modal o es enlace directo
$tieneModal = isset($ind['config']['modal']['enabled']) && $ind['config']['modal']['enabled'];
$modalId = $ind['config']['modal']['modal_id'] ?? '';
$url = $ind['url'] ?? '#';
?>

<!-- Indicador: <?= htmlspecialchars($ind['nombre'] ?? 'Sin nombre') ?> -->
<?php if ($tieneModal): ?>
    <!-- Indicador con modal -->
    <div class="indicator-container" onclick="mostrarModal<?= ucfirst(str_replace('modal', '', $modalId)) ?>()"
        style="cursor: pointer;">
    <?php else: ?>
        <!-- Indicador con enlace directo -->
        <a href="<?= htmlspecialchars($url) ?>" style="text-decoration: none; display: block;">
            <div class="indicator-container" style="cursor: pointer;">
            <?php endif; ?>

            <div class="indicator-header">
                <div class="indicator-icon">
                    <i class="fas <?= htmlspecialchars($ind['icono'] ?? 'fa-chart-line') ?>"></i>
                </div>
            </div>

            <div class="indicator-count">
                <?= htmlspecialchars($ind['valor'] ?? '0') ?>
            </div>

            <div class="indicator-info">
                <div class="indicator-titulo">
                    <?= htmlspecialchars($ind['nombre'] ?? 'Indicador') ?>
                </div>

                <div class="indicator-meta">
                    <span
                        class="indicator-status <?= htmlspecialchars($ind['codigo'] ?? '') ?>-indicador <?= htmlspecialchars($ind['color'] ?? 'gris') ?>">
                        <?php
                        // Mostrar estado según días restantes
                        if (isset($ind['dias_restantes'])) {
                            $diasRestantes = $ind['dias_restantes'];
                            $total = $ind['valor'] ?? 0;

                            if ($total == 0) {
                                echo 'Al día';
                            } elseif ($diasRestantes < 0) {
                                echo 'Vencido hace ' . abs($diasRestantes) . ' días';
                            } elseif ($diasRestantes === 0) {
                                echo 'Vence hoy';
                            } else {
                                echo $diasRestantes . ' días restantes';
                            }
                        } else {
                            echo htmlspecialchars($ind['fecha_limite'] ?? 'Sin fecha');
                        }
                        ?>
                    </span>
                    <span class="indicator-action">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </div>

            <?php if ($tieneModal): ?>
            </div>
        <?php else: ?>
    </div>
    </a>
<?php endif; ?>