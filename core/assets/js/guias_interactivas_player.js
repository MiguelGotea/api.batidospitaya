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
                alert(res.error || 'No se pudo cargar la guía interactiva.');
                cerrarReproductorGuia();
                return;
            }

            gpGuiaActual = res.data;
            gpPasoIndex = 0;

            if (!gpGuiaActual.pasos || gpGuiaActual.pasos.length === 0) {
                alert('Esta guía no contiene pasos registrados.');
                cerrarReproductorGuia();
                return;
            }

            renderizarPasoGuia(gpPasoIndex);

            // Registrar inicio en log
            registrarProgresoGuia(gpGuiaActual.id, 1, 0);
        })
        .catch(err => {
            console.error('Error cargando guía:', err);
            alert('Error de conexión al cargar la guía.');
            cerrarReproductorGuia();
        });
}

/**
 * Cierra el reproductor
 */
function cerrarReproductorGuia() {
    detenerAutoPlay();
    const modal = document.getElementById('modalReproductorGuia');
    if (modal) modal.style.display = 'none';
    gpGuiaActual = null;
    gpPasoIndex = 0;
}

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
            gpPasoSiguiente(true);
        } else {
            detenerAutoPlay();
        }
    }, 3500);
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

/**
 * Renderiza el paso actual en pantalla
 * @param {number} index 
 */
function renderizarPasoGuia(index) {
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
    const imgEl = document.getElementById('gp_imagen_paso');
    const layerEl = document.getElementById('gp_hotspots_layer');
    if (layerEl) layerEl.innerHTML = '';

    if (imgEl) {
        imgEl.onload = function () {
            dibujarHotspotsYCallout(paso);
        };
        imgEl.src = paso.imagen_ruta;
    }
}

/**
 * Dibuja los hotspots y el callout sobre la imagen
 * @param {object} paso 
 */
function dibujarHotspotsYCallout(paso) {
    const layerEl = document.getElementById('gp_hotspots_layer');
    if (!layerEl) return;
    layerEl.innerHTML = '';

    const hotspots = paso.hotspots || [];

    // Si no hay hotspots definidos, dibujamos un callout informativo central
    if (hotspots.length === 0) {
        const callout = document.createElement('div');
        callout.className = 'guia-callout-card';
        callout.style.left = '50%';
        callout.style.top = '50%';
        callout.style.transform = 'translate(-50%, -50%)';
        callout.innerHTML = `
            <h6><i class="bi bi-info-circle-fill"></i> ${escHtml(paso.titulo_paso || 'Información')}</h6>
            <div class="guia-callout-text">${paso.texto_ayuda || 'Observa la pantalla y presiona Siguiente para continuar.'}</div>
            <div class="guia-callout-action">
                <button type="button" class="guia-callout-btn-next" onclick="gpPasoSiguiente()">Entendido / Siguiente</button>
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
            gpPasoSiguiente();
        });

        layerEl.appendChild(hsEl);

        // 2. Callout / Tooltip asociado
        const callout = document.createElement('div');
        callout.className = 'guia-callout-card';

        // Posicionamiento inteligente del callout según el cuadrante
        let calloutLeft = posX;
        let calloutTop = posY + 5;

        if (posX > 65) {
            callout.style.right = `${100 - posX + 4}%`;
        } else {
            callout.style.left = `${posX + 4}%`;
        }

        if (posY > 65) {
            callout.style.bottom = `${100 - posY + 4}%`;
        } else {
            callout.style.top = `${posY + 4}%`;
        }

        const textoTooltip = hs.tooltip_texto || paso.texto_ayuda || 'Haz clic en este elemento para continuar.';
        const tituloTooltip = paso.titulo_paso || 'Paso ' + (gpPasoIndex + 1);

        callout.innerHTML = `
            <h6><i class="bi bi-cursor-fill"></i> ${escHtml(tituloTooltip)}</h6>
            <div class="guia-callout-text">${textoTooltip}</div>
            <div class="guia-callout-action">
                <button type="button" class="guia-callout-btn-next" onclick="gpPasoSiguiente()">Continuar</button>
            </div>
        `;

        layerEl.appendChild(callout);
    });
}

/**
 * Avanza al siguiente paso
 * @param {boolean} isAuto
 */
function gpPasoSiguiente(isAuto = false) {
    if (!gpGuiaActual) return;
    if (!isAuto && gpIsAutoPlaying) {
        detenerAutoPlay();
    }

    if (gpPasoIndex < gpGuiaActual.pasos.length - 1) {
        gpPasoIndex++;
        renderizarPasoGuia(gpPasoIndex);
        registrarProgresoGuia(gpGuiaActual.id, gpPasoIndex + 1, 0);
    } else {
        // Fin de la guía
        detenerAutoPlay();
        registrarProgresoGuia(gpGuiaActual.id, gpGuiaActual.pasos.length, 1);
        alert('🎉 ¡Felicitaciones! Has completado esta guía interactiva.');
        cerrarReproductorGuia();
    }
}

/**
 * Retrocede al paso anterior
 */
function gpPasoAnterior() {
    if (gpIsAutoPlaying) detenerAutoPlay();
    if (!gpGuiaActual || gpPasoIndex <= 0) return;
    gpPasoIndex--;
    renderizarPasoGuia(gpPasoIndex);
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

// Atajos de teclado
document.addEventListener('keydown', function (e) {
    const modal = document.getElementById('modalReproductorGuia');
    if (!modal || modal.style.display === 'none') return;

    if (e.key === 'Escape') {
        cerrarReproductorGuia();
    } else if (e.key === 'ArrowRight') {
        gpPasoSiguiente();
    } else if (e.key === 'ArrowLeft') {
        gpPasoAnterior();
    }
});
