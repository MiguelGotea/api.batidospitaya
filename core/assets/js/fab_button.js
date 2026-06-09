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

            const fabW = fab.offsetWidth;
            const fabH = fab.offsetHeight;

            // right decrece al mover derecha (dx+), bottom decrece al mover abajo (dy+)
            let newRight  = startRight  - dx;
            let newBottom = startBottom - dy;

            newRight  = clamp(newRight,  MARGIN, window.innerWidth  - fabW - MARGIN);
            newBottom = clamp(newBottom, MARGIN, window.innerHeight - fabH - MARGIN);

            fab.style.right  = newRight  + 'px';
            fab.style.bottom = newBottom + 'px';
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

        // Interceptar click post-drag para no abrir el menú
        fab.addEventListener('click', function (e) {
            if (fab.classList.contains('fab-just-dragged')) {
                e.stopPropagation();
                e.preventDefault();
            }
        }, true);

        // ── Seguro extra para navegadores móviles con scroll problemático:
        //    re-afirmar position:fixed si el scroll mueve el elemento
        window.addEventListener('scroll', function () {
            if (!dragging) {
                forceFixed(fab);
            }
        }, { passive: true });

        window.addEventListener('resize', function () {
            // Al rotar pantalla, re-confinar dentro del nuevo viewport
            const fabW = fab.offsetWidth;
            const fabH = fab.offsetHeight;
            const r = clamp(parseFloat(fab.style.right)  || 20, MARGIN, window.innerWidth  - fabW - MARGIN);
            const b = clamp(parseFloat(fab.style.bottom) || 20, MARGIN, window.innerHeight - fabH - MARGIN);
            fab.style.right  = r + 'px';
            fab.style.bottom = b + 'px';
        }, { passive: true });
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
