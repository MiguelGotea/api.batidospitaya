<?php
/**
 * funciones_guias.php
 * Helpers globales para el Módulo de Guías Interactivas (Digital Adoption)
 * Pitaya ERP
 */

if (!function_exists('obtenerGuiaActivaPorUrl')) {
    /**
     * Busca la guía interactiva publicada para la URL actual y valida si el cargo tiene acceso
     * 
     * @param string $urlRelativa Ruta de la página (ej: /modulos/rh/ver_marcaciones_todas_nuevo.php)
     * @param int|null $codCargo CodNivelesCargos del usuario autenticado
     * @return array|null Datos de la guía o null si no existe o no tiene permiso
     */
    function obtenerGuiaActivaPorUrl($urlRelativa, $codCargo = null)
    {
        global $conn;
        if (empty($urlRelativa) || !$conn) {
            return null;
        }

        try {
            // Normalizar URL relativa (ej: /modulos/rh/ver_marcaciones_todas_nuevo.php)
            $urlParsed = parse_url($urlRelativa, PHP_URL_PATH);

            $sql = "SELECT g.id, g.titulo, g.descripcion, g.url_relativa, g.version, g.id_categoria,
                           c.nombre AS nombre_categoria, h.nombre AS nombre_herramienta
                    FROM guia g
                    JOIN categoria_guia c ON g.id_categoria = c.id
                    JOIN herramienta_guia h ON c.id_herramienta = h.id
                    WHERE g.url_relativa LIKE ? 
                      AND g.estado = 'publicada'
                      AND h.activo = 1
                      AND c.activo = 1
                    ORDER BY g.version DESC, g.id DESC
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->execute(['%' . basename($urlParsed)]);
            $guia = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$guia) {
                return null;
            }

            return $guia;
        } catch (Exception $e) {
            error_log("Error en obtenerGuiaActivaPorUrl: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('obtenerDetalleGuiaCompleta')) {
    /**
     * Retorna la estructura completa de una guía (pasos y hotspots) lista para el reproductor
     * 
     * @param int $idGuia ID de la guía
     * @return array|null
     */
    function obtenerDetalleGuiaCompleta($idGuia)
    {
        global $conn;
        if (!$conn || empty($idGuia)) {
            return null;
        }

        try {
            $stmtGuia = $conn->prepare("
                SELECT g.*, c.nombre AS categoria_nombre, h.nombre AS herramienta_nombre
                FROM guia g
                JOIN categoria_guia c ON g.id_categoria = c.id
                JOIN herramienta_guia h ON c.id_herramienta = h.id
                WHERE g.id = ? LIMIT 1
            ");
            $stmtGuia->execute([(int)$idGuia]);
            $guia = $stmtGuia->fetch(PDO::FETCH_ASSOC);

            if (!$guia) {
                return null;
            }

            // Obtener pasos
            $stmtPasos = $conn->prepare("
                SELECT * FROM guia_paso 
                WHERE id_guia = ? 
                ORDER BY orden ASC, id ASC
            ");
            $stmtPasos->execute([$guia['id']]);
            $pasos = $stmtPasos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pasos as &$paso) {
                $stmtHotspots = $conn->prepare("
                    SELECT * FROM guia_hotspot 
                    WHERE id_paso = ? 
                    ORDER BY id ASC
                ");
                $stmtHotspots->execute([$paso['id']]);
                $paso['hotspots'] = $stmtHotspots->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
            unset($paso);

            $guia['pasos'] = $pasos;
            return $guia;
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleGuiaCompleta: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('renderBotonGuiaInteractiva')) {
    /**
     * Inyecta el Botón Flotante de Guía Interactiva (FAB) y el reproductor en cualquier página del ERP
     * 
     * @param string|null $urlOverride Ruta opcional si difiere de SCRIPT_NAME
     */
    function renderBotonGuiaInteractiva($urlOverride = null)
    {
        $usuario = function_exists('obtenerUsuarioActual') ? obtenerUsuarioActual() : null;
        $codCargo = $usuario['CodNivelesCargos'] ?? null;
        $urlActual = $urlOverride ?: ($_SERVER['SCRIPT_NAME'] ?? '');

        // Verificar si el usuario tiene permiso de editor (cargos 15 y 49)
        $esEditor = false;
        if (function_exists('tienePermiso') && $codCargo !== null) {
            $esEditor = tienePermiso('guias_interactivas', 'gestion_editor', $codCargo);
        }

        // Buscar si existe una guía activa para esta URL
        $guiaActiva = obtenerGuiaActivaPorUrl($urlActual, $codCargo);
        $idGuia = $guiaActiva ? (int)$guiaActiva['id'] : 0;
        $tituloGuia = $guiaActiva ? htmlspecialchars($guiaActiva['titulo']) : 'Tutorial Interactivo';

        // Si no hay guía activa y el usuario tampoco es editor, no renderizar nada
        if (!$guiaActiva && !$esEditor) {
            return;
        }

        $randV = mt_rand(1, 10000);
        ?>
        <!-- Módulo de Guías Interactivas (Digital Adoption) -->
        <link rel="stylesheet" href="/core/assets/css/guias_interactivas.css?v=<?= $randV ?>">
        
        <div id="guias-fab-container" class="guias-fab-container" data-guia-id="<?= $idGuia ?>" data-url="<?= htmlspecialchars($urlActual) ?>">
            <?php if ($guiaActiva): ?>
                <button type="button" class="guias-fab-btn" id="btnLanzarGuia" onclick="lanzarGuiaInteractiva(<?= $idGuia ?>)" title="Ver Guía Interactiva">
                    <i class="bi bi-question-circle-fill"></i>
                    <span class="guias-fab-badge">Guía</span>
                </button>
            <?php elseif ($esEditor): ?>
                <a href="/modulos/sistemas/guias_editor.php?url=<?= urlencode($urlActual) ?>" class="guias-fab-btn guias-fab-btn-editor" title="Crear Guía Interactiva para esta pantalla">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span class="guias-fab-badge">Crear Guía</span>
                </a>
            <?php endif; ?>

            <?php if ($esEditor && $guiaActiva): ?>
                <a href="/modulos/sistemas/guias_editor.php?id=<?= $idGuia ?>" class="guias-fab-sub-btn" title="Editar esta guía">
                    <i class="bi bi-pencil-fill"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Modal Reproductor de Guía Interactiva -->
        <div id="modalReproductorGuia" class="guias-player-modal" style="display: none;">
            <div class="guias-player-backdrop" onclick="cerrarReproductorGuia()"></div>
            <div class="guias-player-wrapper">
                <div class="guias-player-header">
                    <div class="guias-player-title-box">
                        <i class="bi bi-magic me-2 text-warning"></i>
                        <span id="gp_titulo_guia"><?= $tituloGuia ?></span>
                    </div>
                    <div class="guias-player-controls-top">
                        <button type="button" id="gp_btn_autoplay" class="btn btn-sm btn-outline-warning text-white py-1 px-2 d-flex align-items-center gap-1" onclick="toggleAutoPlay()" title="Iniciar / Pausar Reproducción Automática">
                            <i class="bi bi-play-fill" id="gp_autoplay_icon"></i> <span id="gp_autoplay_label" style="font-size:0.8rem;">Auto-Play</span>
                        </button>
                        <button type="button" class="guias-player-icon-btn" id="gp_btn_fullscreen" onclick="toggleFullScreenGuia()" title="Pantalla Completa">
                            <i class="bi bi-arrows-fullscreen" id="gp_fullscreen_icon"></i>
                        </button>
                        <span id="gp_progreso_indicador" class="guias-badge-progreso">Paso 1 de 1</span>
                        <button type="button" class="guias-player-close" onclick="cerrarReproductorGuia()" title="Cerrar Guía">&times;</button>
                    </div>
                </div>

                <div class="guias-player-body">
                    <div id="gp_canvas_container" class="guias-canvas-container">
                        <img id="gp_imagen_paso" src="" alt="Captura del paso" class="guias-imagen-pantalla">
                        <div id="gp_hotspots_layer" class="guias-hotspots-layer"></div>
                    </div>
                </div>

                <div class="guias-player-footer">
                    <button type="button" id="gp_btn_anterior" class="btn btn-sm btn-outline-light" onclick="gpPasoAnterior()">
                        <i class="bi bi-chevron-left me-1"></i> Anterior
                    </button>
                    <div class="guias-progress-bar-container">
                        <div id="gp_progress_bar" class="guias-progress-bar" style="width: 0%;"></div>
                    </div>
                    <button type="button" id="gp_btn_siguiente" class="btn btn-sm btn-primary" onclick="gpPasoSiguiente()">
                        Siguiente <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <script src="/core/assets/js/guias_interactivas_player.js?v=<?= $randV ?>"></script>
        <?php
    }
}
