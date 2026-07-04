/**
 * FAB Draggable — Estándar Pitaya ERP
 * Ubicación: /core/assets/js/fab_button.js
 *
 * Permite arrastrar el botón flotante libremente dentro del viewport.
 * La posición es solo de sesión: al recargar la página vuelve a la esquina.
 * Funciona con mouse (desktop) y touch (móvil/tablet).
 *
 * Uso: Incluir después del DOM cargado, en páginas que usen .fab-container
 *   <script src="/core/assets/js/fab_button.js"></script>
 */

(function () {
    'use strict';

    // Margen mínimo desde los bordes del viewport (px)
    const MARGIN = 10;

    /**
     * Clamp un valor entre min y max
     */
    function clamp(val, min, max) {
        return Math.min(Math.max(val, min), max);
    }

    /**
     * Aplica position:fixed directamente como estilo inline para
     * garantizar que ningún padre (con overflow/transform) lo rompa.
     */
    function forceFixed(el) {
        el.style.position = 'fixed';
        el.style.left     = 'auto';
        el.style.top      = 'auto';
        el.style.margin   = '0';
        el.style.zIndex   = '9999';
    }

    /**
     * Inicializa el drag sobre el elemento fab-container dado
     */
    function initDraggable(fab) {
        // ── Mover al <body> para escapar de contenedores con overflow/transform
        if (fab.parentElement !== document.body) {
            document.body.appendChild(fab);
        }

        // ── Forzar position:fixed via inline style (máxima prioridad)
        forceFixed(fab);
        fab.style.bottom = '20px';
        fab.style.right  = '20px';

        let dragging    = false;
        let didDrag     = false;
        let startX      = 0;
        let startY      = 0;
        let startRight  = 0;
        let startBottom = 0;

        const handle = fab.querySelector('.btn-floating-pitaya') || fab;
        handle.style.cursor = 'grab';

        /* ── Dimensiones seguras del trigger (no del contenedor expandido) ── */
        function getTriggerSize() {
            const t = fab.querySelector('.btn-floating-pitaya') || fab;
            return {
                w: t.offsetWidth  || 65,
                h: t.offsetHeight || 65
            };
        }

        /* ── Aplicar posición con seguridad de límites ── */
        function safePosition(right, bottom) {
            const { w, h } = getTriggerSize();
            const r = clamp(right,  MARGIN, Math.max(MARGIN, window.innerWidth  - w - MARGIN));
            const b = clamp(bottom, MARGIN, Math.max(MARGIN, window.innerHeight - h - MARGIN));
            fab.style.right  = r + 'px';
            fab.style.bottom = b + 'px';
        }

        /* ── Coordenadas normalizadas mouse/touch ── */
        function getCoords(e) {
            const t = e.touches ? e.touches[0] : e;
            return { x: t.clientX, y: t.clientY };
        }

        /* ── INICIO DEL DRAG ── */
        function onPointerDown(e) {
            if (e.button !== undefined && e.button !== 0) return;

            dragging = true;
            didDrag  = false;

            const coords = getCoords(e);
            startX = coords.x;
            startY = coords.y;

            // Leer posición actual desde los estilos inline (no getBoundingClientRect
            // para evitar que el scroll afecte la lectura en móvil)
            startRight  = parseFloat(fab.style.right)  || 20;
            startBottom = parseFloat(fab.style.bottom) || 20;

            handle.style.cursor = 'grabbing';
            fab.classList.add('fab-dragging');

            document.addEventListener('mousemove', onPointerMove, { passive: false });
            document.addEventListener('mouseup',   onPointerUp);
            document.addEventListener('touchmove', onPointerMove, { passive: false });
            document.addEventListener('touchend',  onPointerUp);
        }

        /* ── MOVIMIENTO ── */
        function onPointerMove(e) {
            if (!dragging) return;

            const coords = getCoords(e);
            const dx = coords.x - startX;  // + → derecha
            const dy = coords.y - startY;  // + → abajo

            if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
                didDrag = true;
                e.preventDefault(); // Bloquea scroll nativo durante el drag
            }

            if (!didDrag) return;

            // right decrece al mover derecha (dx+), bottom decrece al mover abajo (dy+)
            safePosition(startRight - dx, startBottom - dy);
        }

        /* ── FIN DEL DRAG ── */
        function onPointerUp() {
            if (!dragging) return;
            dragging = false;
            handle.style.cursor = 'grab';
            fab.classList.remove('fab-dragging');

            document.removeEventListener('mousemove', onPointerMove);
            document.removeEventListener('mouseup',   onPointerUp);
            document.removeEventListener('touchmove', onPointerMove);
            document.removeEventListener('touchend',  onPointerUp);

            if (didDrag) {
                // Bloquear el click que dispararía el menú al soltar
                fab.classList.add('fab-just-dragged');
                setTimeout(() => fab.classList.remove('fab-just-dragged'), 250);
            }
        }

        handle.addEventListener('mousedown',  onPointerDown);
        handle.addEventListener('touchstart', onPointerDown, { passive: true });

        handle.addEventListener('click', function (e) {
            if (fab.classList.contains('fab-just-dragged')) {
                return;
            }
            fab.classList.toggle('active');
            if (fab.classList.contains('active')) {
                ajustarAlturaOpciones(fab);
            }
            e.stopPropagation();
        });

        // Close menu when clicking outside of the fab container
        document.addEventListener('click', function (e) {
            if (!fab.contains(e.target)) {
                fab.classList.remove('active');
            }
        });

        // Close menu when clicking an option (event delegation)
        fab.addEventListener('click', function (e) {
            if (e.target.closest('.fab-option')) {
                fab.classList.remove('active');
            }
        });

        // Interceptar click post-drag para no abrir el menú
        fab.addEventListener('click', function (e) {
            if (fab.classList.contains('fab-just-dragged')) {
                e.stopPropagation();
                e.preventDefault();
            }
        }, true);

        // ── Seguro extra para navegadores móviles con scroll problemático:
        //    re-afirmar position:fixed si el scroll mueve el elemento.
        //    Throttle con rAF para no disparar decenas de veces por segundo.
        let scrollRafId = null;
        window.addEventListener('scroll', function () {
            if (!dragging && !scrollRafId) {
                scrollRafId = requestAnimationFrame(function () {
                    scrollRafId = null;
                    forceFixed(fab);
                });
            }
        }, { passive: true });

        window.addEventListener('resize', function () {
            // BUG FIX: usar safePosition que basa los límites en el trigger,
            // NO en fab.offsetHeight (que incluye opciones expandidas y produce
            // bottom negativo → FAB desaparece de pantalla).
            safePosition(
                parseFloat(fab.style.right)  || 20,
                parseFloat(fab.style.bottom) || 20
            );
            // Recalcular altura de opciones si el menú está abierto
            if (fab.classList.contains('active')) {
                ajustarAlturaOpciones(fab);
            }
        }, { passive: true });
    }

    /**
     * Ajusta el max-height de .fab-options según el espacio disponible
     * hacia arriba del trigger en el viewport, evitando desbordamiento
     * y que las opciones tapen el botón principal.
     */
    function ajustarAlturaOpciones(fab) {
        const options = fab.querySelector('.fab-options');
        if (!options) return;

        const trigger = fab.querySelector('.btn-floating-pitaya');
        const GAP = 15; // gap del flex-container entre options y trigger
        const PADDING_SAFE = 12; // espacio extra desde el borde superior del viewport

        // Posición del botón trigger relativa al viewport
        let triggerBottom;
        if (trigger) {
            const rect = trigger.getBoundingClientRect();
            // La parte superior del trigger dentro del viewport
            triggerBottom = rect.top;
        } else {
            // Fallback: usar el bottom del fab
            const fabBottom = parseFloat(fab.style.bottom) || 20;
            triggerBottom = window.innerHeight - fabBottom - 65;
        }

        // Espacio disponible hacia arriba: desde el tope del viewport hasta justo
        // encima del trigger, menos el gap y el padding de seguridad
        const espacioDisponible = triggerBottom - GAP - PADDING_SAFE;

        if (espacioDisponible > 60) {
            options.style.maxHeight = espacioDisponible + 'px';
        } else {
            // Muy poco espacio: colapsar a mínimo con scroll
            options.style.maxHeight = '60px';
        }
    }

    /* ── Esperar a que el DOM esté listo ── */
    function init() {
        document.querySelectorAll('.fab-container').forEach(initDraggable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
