/**
 * guias_interactivas_player.js
 * Motor Reproductor de Guías Interactivas (Digital Adoption)
 * Pitaya ERP
 */

let gpGuiaActual = null;
let gpPasoIndex = 0;
let gpAutoPlayTimer = null;
let gpIsAutoPlaying = false;

/**
 * Lanza el reproductor para una guía por ID
 * @param {number} idGuia 
 */
function lanzarGuiaInteractiva(idGuia) {
    if (!idGuia) return;

    detenerAutoPlay();

    // Mostrar modal con indicador de carga
    const modal = document.getElementById('modalReproductorGuia');
    if (!modal) return;
    modal.style.display = 'flex';

    const tituloEl = document.getElementById('gp_titulo_guia');
    if (tituloEl) tituloEl.textContent = 'Cargando tutorial interactivo...';

    // Cargar datos vía AJAX
    fetch(`/modulos/sistemas/ajax/guias_ajax.php?action=obtener_guia&id=${idGuia}`)
        .then(response => response.json())
        .then(res => {
            if (!res.success || !res.data) {
                gpMostrarToast(res.error || 'No se pudo cargar la guía interactiva.', 'danger');
                cerrarReproductorGuia();
                return;
            }

            gpGuiaActual = res.data;
            gpPasoIndex = 0;

            if (!gpGuiaActual.pasos || gpGuiaActual.pasos.length === 0) {
                gpMostrarToast('Esta guía no contiene pasos registrados.', 'info');
                cerrarReproductorGuia();
                return;
            }

            renderizarPasoGuia(gpPasoIndex);

            // Registrar inicio en log
            registrarProgresoGuia(gpGuiaActual.id, 1, 0);
        })
        .catch(err => {
            console.error('Error cargando guía:', err);
            gpMostrarToast('Error de conexión al cargar la guía.', 'danger');
            cerrarReproductorGuia();
        });
}

/**
 * Cierra el reproductor
 */
function cerrarReproductorGuia() {
    detenerAutoPlay();
    if (document.fullscreenElement) {
        try { document.exitFullscreen(); } catch (e) {}
    }
    const modal = document.getElementById('modalReproductorGuia');
    if (modal) modal.style.display = 'none';
    gpGuiaActual = null;
    gpPasoIndex = 0;
}

/**
 * Alterna entre modo Pantalla Completa y Ventana Normal
 */
function toggleFullScreenGuia() {
    const modal = document.getElementById('modalReproductorGuia');
    if (!modal) return;

    const icon = document.getElementById('gp_fullscreen_icon');

    if (!document.fullscreenElement) {
        if (modal.requestFullscreen) {
            modal.requestFullscreen();
        } else if (modal.webkitRequestFullscreen) {
            modal.webkitRequestFullscreen();
        }
        if (icon) icon.className = 'bi bi-fullscreen-exit';
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
        if (icon) icon.className = 'bi bi-arrows-fullscreen';
    }
}

document.addEventListener('fullscreenchange', function () {
    const icon = document.getElementById('gp_fullscreen_icon');
    if (icon) {
        icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
    }
});

/**
 * Alterna entre Reproducción Automática y Pausa
 */
function toggleAutoPlay() {
    if (gpIsAutoPlaying) {
        detenerAutoPlay();
    } else {
        iniciarAutoPlay();
    }
}

/**
 * Inicia la reproducción automática (avanza cada 3.5 segundos)
 */
function iniciarAutoPlay() {
    if (!gpGuiaActual) return;
    gpIsAutoPlaying = true;

    const btn = document.getElementById('gp_btn_autoplay');
    const icon = document.getElementById('gp_autoplay_icon');
    const label = document.getElementById('gp_autoplay_label');

    if (btn) btn.className = 'btn btn-sm btn-warning text-dark py-1 px-2 d-flex align-items-center gap-1 shadow-sm';
    if (icon) icon.className = 'bi bi-pause-fill';
    if (label) label.textContent = 'Pausar';

    if (gpAutoPlayTimer) clearInterval(gpAutoPlayTimer);
    gpAutoPlayTimer = setInterval(() => {
        if (!gpGuiaActual || !gpIsAutoPlaying) {
            detenerAutoPlay();
            return;
        }

        if (gpPasoIndex < gpGuiaActual.pasos.length - 1) {
            const hsEl = document.querySelector('.guia-hotspot-item');
            gpPasoSiguiente(true, hsEl);
        } else {
            detenerAutoPlay();
        }
    }, 3800);
}

/**
 * Detiene la reproducción automática
 */
function detenerAutoPlay() {
    gpIsAutoPlaying = false;
    if (gpAutoPlayTimer) {
        clearInterval(gpAutoPlayTimer);
        gpAutoPlayTimer = null;
    }

    const btn = document.getElementById('gp_btn_autoplay');
    const icon = document.getElementById('gp_autoplay_icon');
    const label = document.getElementById('gp_autoplay_label');

    if (btn) btn.className = 'btn btn-sm btn-outline-warning text-white py-1 px-2 d-flex align-items-center gap-1';
    if (icon) icon.className = 'bi bi-play-fill';
    if (label) label.textContent = 'Auto-Play';
}

let gpIsTransitioning = false;

/**
 * Renderiza el paso actual en pantalla
 * @param {number} index 
 * @param {string} direccion ('next' o 'prev')
 */
function renderizarPasoGuia(index, direccion = 'next') {
    if (!gpGuiaActual || !gpGuiaActual.pasos[index]) return;

    const paso = gpGuiaActual.pasos[index];
    const totalPasos = gpGuiaActual.pasos.length;

    // Actualizar título e indicador
    const tituloEl = document.getElementById('gp_titulo_guia');
    if (tituloEl) tituloEl.textContent = gpGuiaActual.titulo || 'Guía Interactiva';

    const progresoEl = document.getElementById('gp_progreso_indicador');
    if (progresoEl) progresoEl.textContent = `Paso ${index + 1} de ${totalPasos}`;

    // Barra de progreso
    const progressBar = document.getElementById('gp_progress_bar');
    if (progressBar) {
        const pct = Math.round(((index + 1) / totalPasos) * 100);
        progressBar.style.width = `${pct}%`;
    }

    // Botones navegación
    const btnAnt = document.getElementById('gp_btn_anterior');
    if (btnAnt) btnAnt.disabled = (index === 0);

    const btnSig = document.getElementById('gp_btn_siguiente');
    if (btnSig) {
        if (index === totalPasos - 1) {
            btnSig.innerHTML = 'Finalizar <i class="bi bi-check-circle ms-1"></i>';
            btnSig.className = 'btn btn-sm btn-success';
        } else {
            btnSig.innerHTML = 'Siguiente <i class="bi bi-chevron-right ms-1"></i>';
            btnSig.className = 'btn btn-sm btn-primary';
        }
    }

    // Imagen y capa de hotspots
    const canvasContainer = document.getElementById('gp_canvas_container');
    const imgEl = document.getElementById('gp_imagen_paso');
    const layerEl = document.getElementById('gp_hotspots_layer');
    if (layerEl) layerEl.innerHTML = '';

    if (canvasContainer) {
        canvasContainer.className = `guias-canvas-container gp-slide-in-${direccion}`;
        canvasContainer.style.transform = 'scale(1)';
        canvasContainer.style.transformOrigin = '50% 50%';
    }

    if (imgEl) {
        imgEl.onload = function () {
            dibujarHotspotsYCallout(paso);
        };
        imgEl.src = paso.imagen_ruta;
    }
}

/**
 * Dibuja los hotspots y el callout sobre la imagen con zoom suave
 * @param {object} paso 
 */
function dibujarHotspotsYCallout(paso) {
    const layerEl = document.getElementById('gp_hotspots_layer');
    const canvasContainer = document.getElementById('gp_canvas_container');
    if (!layerEl || !canvasContainer) return;
    layerEl.innerHTML = '';

    const hotspots = paso.hotspots || [];

    // Si no hay hotspots definidos, dibujamos un callout informativo central y vista completa
    if (hotspots.length === 0) {
        canvasContainer.style.transformOrigin = '50% 50%';
        canvasContainer.style.transform = 'scale(1)';

        const callout = document.createElement('div');
        callout.className = 'guia-callout-card';
        callout.style.left = '50%';
        callout.style.top = '50%';
        callout.style.transform = 'translate(-50%, -50%)';
        callout.innerHTML = `
            <h6><i class="bi bi-info-circle-fill"></i> ${escHtml(paso.titulo_paso || 'Información')}</h6>
            <div class="guia-callout-text">${paso.texto_ayuda || 'Observa la pantalla y presiona Siguiente para continuar.'}</div>
            <div class="guia-callout-action">
                <button type="button" class="guia-callout-btn-next" onclick="gpPasoSiguiente(false, null)">Entendido / Siguiente</button>
            </div>
        `;
        layerEl.appendChild(callout);
        return;
    }

    hotspots.forEach(hs => {
        const posX = parseFloat(hs.pos_x) || 50;
        const posY = parseFloat(hs.pos_y) || 50;

        // 1. Hotspot elemento
        const hsEl = document.createElement('div');
        hsEl.className = 'guia-hotspot-item';
        hsEl.style.left = `${posX}%`;
        hsEl.style.top = `${posY}%`;

        if (hs.forma === 'rectangulo') {
            const ancho = parseFloat(hs.ancho) || 10;
            const alto = parseFloat(hs.alto) || 5;
            hsEl.innerHTML = `
                <div class="guia-hotspot-rect" style="width: ${ancho}vw; height: ${alto}vh; transform: translate(-50%, -50%);"></div>
            `;
        } else {
            hsEl.innerHTML = `
                <div class="guia-hotspot-beacon">
                    <div class="guia-hotspot-ripple"></div>
                    <div class="guia-hotspot-core"></div>
                </div>
            `;
        }

        hsEl.addEventListener('click', function (e) {
            e.stopPropagation();
            gpPasoSiguiente(false, hsEl);
        });

        layerEl.appendChild(hsEl);

        // 2. Callout / Tooltip asociado
        const callout = document.createElement('div');
        callout.className = 'guia-callout-card';

        // Posicionamiento según la ubicación definida en el editor
        const posPref = hs.tooltip_posicion || 'abajo';

        if (posPref.startsWith('libre:')) {
            const parts = posPref.split(':');
            const customX = parseFloat(parts[1]) || 50;
            const customY = parseFloat(parts[2]) || 50;
            callout.style.left = `${Math.max(12, Math.min(88, customX))}%`;
            callout.style.top = `${Math.max(10, Math.min(90, customY))}%`;
            callout.style.transform = 'translate(-50%, -50%)';
        } else if (posPref === 'centro') {
            callout.style.left = '50%';
            callout.style.top = '50%';
            callout.style.transform = 'translate(-50%, -50%)';
        } else if (posPref === 'arriba') {
            callout.style.left = `${Math.max(16, Math.min(84, posX))}%`;
            callout.style.bottom = `${Math.max(6, 100 - posY + 4)}%`;
            callout.style.transform = 'translateX(-50%)';
        } else if (posPref === 'derecha') {
            callout.style.left = `${Math.min(74, posX + 4)}%`;
            callout.style.top = `${Math.max(16, Math.min(84, posY))}%`;
            callout.style.transform = 'translateY(-50%)';
        } else if (posPref === 'izquierda') {
            callout.style.right = `${Math.max(6, 100 - posX + 4)}%`;
            callout.style.top = `${Math.max(16, Math.min(84, posY))}%`;
            callout.style.transform = 'translateY(-50%)';
        } else {
            // 'abajo' (por defecto)
            callout.style.left = `${Math.max(16, Math.min(84, posX))}%`;
            callout.style.top = `${Math.min(74, posY + 4)}%`;
            callout.style.transform = 'translateX(-50%)';
        }

        const textoTooltip = hs.tooltip_texto || paso.texto_ayuda || 'Haz clic en este elemento para continuar.';
        const tituloTooltip = paso.titulo_paso || 'Paso ' + (gpPasoIndex + 1);

        callout.innerHTML = `
            <h6><i class="bi bi-cursor-fill"></i> ${escHtml(tituloTooltip)}</h6>
            <div class="guia-callout-text">${textoTooltip}</div>
            <div class="guia-callout-action">
                <button type="button" class="guia-callout-btn-next" onclick="gpPasoSiguiente(false, null)">Continuar</button>
            </div>
        `;

        layerEl.appendChild(callout);
    });
}

/**
 * Avanza al siguiente paso con transición cinematográfica de diapositiva
 * @param {boolean} isAuto
 * @param {HTMLElement|null} clickedEl
 */
function gpPasoSiguiente(isAuto = false, clickedEl = null) {
    if (!gpGuiaActual || gpIsTransitioning) return;
    if (!isAuto && gpIsAutoPlaying) {
        detenerAutoPlay();
    }

    const canvasContainer = document.getElementById('gp_canvas_container');

    // Efecto de pulso en el hotspot clickeado
    if (clickedEl) {
        clickedEl.classList.add('clicked');
    }

    gpIsTransitioning = true;
    const delayAnimacion = clickedEl ? 180 : 40;

    setTimeout(() => {
        if (gpPasoIndex < gpGuiaActual.pasos.length - 1) {
            // Deslizamiento suave hacia la izquierda
            if (canvasContainer) {
                canvasContainer.className = 'guias-canvas-container gp-slide-out-next';
            }

            setTimeout(() => {
                gpPasoIndex++;
                renderizarPasoGuia(gpPasoIndex, 'next');
                registrarProgresoGuia(gpGuiaActual.id, gpPasoIndex + 1, 0);
                gpIsTransitioning = false;
            }, 180);
        } else {
            // Fin de la guía
            detenerAutoPlay();
            registrarProgresoGuia(gpGuiaActual.id, gpGuiaActual.pasos.length, 1);
            gpMostrarToast('🎉 ¡Felicitaciones! Has completado esta guía interactiva.', 'success');
            cerrarReproductorGuia();
            gpIsTransitioning = false;
        }
    }, delayAnimacion);
}

/**
 * Retrocede al paso anterior con transición suave de diapositiva inversa
 */
function gpPasoAnterior() {
    if (!gpGuiaActual || gpPasoIndex <= 0 || gpIsTransitioning) return;
    if (gpIsAutoPlaying) detenerAutoPlay();

    const canvasContainer = document.getElementById('gp_canvas_container');
    gpIsTransitioning = true;

    if (canvasContainer) {
        canvasContainer.className = 'guias-canvas-container gp-slide-out-prev';
    }

    setTimeout(() => {
        gpPasoIndex--;
        renderizarPasoGuia(gpPasoIndex, 'prev');
        gpIsTransitioning = false;
    }, 180);
}

/**
 * Registra progreso en log
 */
function registrarProgresoGuia(idGuia, pasoMax, completada) {
    if (!idGuia) return;
    const formData = new FormData();
    formData.append('id_guia', idGuia);
    formData.append('paso', pasoMax);
    formData.append('completada', completada);

    fetch('/modulos/sistemas/ajax/guias_ajax.php?action=guardar_progreso', {
        method: 'POST',
        body: formData
    }).catch(() => {});
}

/**
 * Escapa HTML
 */
function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Toast notification para el player
 */
function gpMostrarToast(mensaje, tipo) {
    let container = document.getElementById('toast-container-guias');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container-guias';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:999999;min-width:300px;';
        document.body.appendChild(container);
    }

    const iconos = { success: 'bi-check-circle-fill', danger: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
    const icono = iconos[tipo] || 'bi-bell-fill';
    const toast = document.createElement('div');
    toast.className = `toast-notif toast-${tipo || 'success'}`;
    toast.innerHTML = `
        <i class="bi ${icono}"></i>
        <span>${mensaje}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// Atajos de teclado para el Reproductor
document.addEventListener('keydown', function (e) {
    const modal = document.getElementById('modalReproductorGuia');
    if (!modal || modal.style.display === 'none') return;

    // Evitar interceptar teclas si el foco está en un campo de texto
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;

    if (e.key === 'Escape') {
        if (document.fullscreenElement) {
            try { document.exitFullscreen(); } catch (err) {}
        } else {
            cerrarReproductorGuia();
        }
    } else if (e.key === 'ArrowRight' || e.key === 'PageDown') {
        e.preventDefault();
        gpPasoSiguiente();
    } else if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
        e.preventDefault();
        gpPasoAnterior();
    } else if (e.key === ' ' || e.code === 'Space') {
        e.preventDefault();
        toggleAutoPlay();
    } else if (e.key === 'f' || e.key === 'F') {
        e.preventDefault();
        toggleFullScreenGuia();
    }
});
