/**
 * FAB Draggable — Estándar Pitaya ERP
 * Ubicación: /core/assets/js/fab_button.js
 *
 * Permite arrastrar el botón flotante libremente dentro del viewport.
 * La posición se guarda en localStorage por página para no perderla al recargar.
 * Funciona con mouse (desktop) y touch (móvil/tablet).
 *
 * Uso: Incluir después del DOM cargado, en páginas que usen .fab-container
 *   <script src="/core/assets/js/fab_button.js"></script>
 */

(function () {
    'use strict';

    // Clave única por URL para guardar posición independiente por página
    const STORAGE_KEY = 'fab_pos_' + window.location.pathname;

    // Margen mínimo desde los bordes del viewport (px)
    const MARGIN = 10;

    /**
     * Clamp un valor entre min y max
     */
    function clamp(val, min, max) {
        return Math.min(Math.max(val, min), max);
    }

    /**
     * Aplica la posición guardada al FAB container
     */
    function applyStoredPosition(el) {
        try {
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
            if (saved && typeof saved.right === 'number' && typeof saved.bottom === 'number') {
                el.style.right  = saved.right  + 'px';
                el.style.bottom = saved.bottom + 'px';
                el.style.left   = 'auto';
                el.style.top    = 'auto';
            }
        } catch (e) {
            // Sin posición guardada → mantener la del CSS
        }
    }

    /**
     * Guarda la posición actual (right, bottom) en localStorage
     */
    function savePosition(right, bottom) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ right, bottom }));
        } catch (e) { /* silencioso */ }
    }

    /**
     * Inicializa el drag sobre el elemento fab-container dado
     */
    function initDraggable(fab) {
        // Restaurar posición guardada
        applyStoredPosition(fab);

        let dragging  = false;
        let didDrag   = false;   // Distinguir drag de click/tap
        let startX    = 0;
        let startY    = 0;
        let startRight  = 0;
        let startBottom = 0;

        // El handle de drag es solo el botón principal, no las opciones
        const handle = fab.querySelector('.btn-floating-pitaya') || fab;

        handle.style.cursor = 'grab';

        /* ── Obtener coordenadas normalizadas (mouse o touch) ── */
        function getCoords(e) {
            const t = e.touches ? e.touches[0] : e;
            return { x: t.clientX, y: t.clientY };
        }

        /* ── INICIO DEL DRAG ── */
        function onPointerDown(e) {
            // Solo botón izquierdo en mouse
            if (e.button !== undefined && e.button !== 0) return;

            dragging  = true;
            didDrag   = false;

            const coords = getCoords(e);
            startX = coords.x;
            startY = coords.y;

            // Posición actual del FAB (right/bottom relativos al viewport)
            const rect = fab.getBoundingClientRect();
            startRight  = window.innerWidth  - rect.right;
            startBottom = window.innerHeight - rect.bottom;

            handle.style.cursor = 'grabbing';
            fab.classList.add('fab-dragging');

            // Capturar eventos globales
            document.addEventListener('mousemove', onPointerMove, { passive: false });
            document.addEventListener('mouseup',   onPointerUp);
            document.addEventListener('touchmove', onPointerMove, { passive: false });
            document.addEventListener('touchend',  onPointerUp);
        }

        /* ── MOVIMIENTO ── */
        function onPointerMove(e) {
            if (!dragging) return;

            const coords = getCoords(e);
            const dx = coords.x - startX;
            const dy = coords.y - startY;

            // Umbral mínimo para considerar que es drag (evita bloquear clicks)
            if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
                didDrag = true;
                e.preventDefault(); // Evitar scroll mientras drags
            }

            if (!didDrag) return;

            // Calcular nueva posición (right/bottom basado en viewport)
            const fabW = fab.offsetWidth;
            const fabH = fab.offsetHeight;

            let newRight  = startRight  - dx;
            let newBottom = startBottom + dy;

            // Confinarlo dentro del viewport con margen
            newRight  = clamp(newRight,  MARGIN, window.innerWidth  - fabW - MARGIN);
            newBottom = clamp(newBottom, MARGIN, window.innerHeight - fabH - MARGIN);

            fab.style.right  = newRight  + 'px';
            fab.style.bottom = newBottom + 'px';
            fab.style.left   = 'auto';
            fab.style.top    = 'auto';
        }

        /* ── FIN DEL DRAG ── */
        function onPointerUp(e) {
            if (!dragging) return;
            dragging = false;
            handle.style.cursor = 'grab';
            fab.classList.remove('fab-dragging');

            document.removeEventListener('mousemove', onPointerMove);
            document.removeEventListener('mouseup',   onPointerUp);
            document.removeEventListener('touchmove', onPointerMove);
            document.removeEventListener('touchend',  onPointerUp);

            if (didDrag) {
                // Guardar posición final
                const right  = parseFloat(fab.style.right)  || 20;
                const bottom = parseFloat(fab.style.bottom) || 20;
                savePosition(right, bottom);

                // Evitar que el click post-drag dispare el menú del FAB
                fab.classList.add('fab-just-dragged');
                setTimeout(() => fab.classList.remove('fab-just-dragged'), 200);
            }
        }

        handle.addEventListener('mousedown',  onPointerDown);
        handle.addEventListener('touchstart', onPointerDown, { passive: true });

        // Evitar que un drag active el menú al soltar
        fab.addEventListener('click', function (e) {
            if (fab.classList.contains('fab-just-dragged')) {
                e.stopPropagation();
                e.preventDefault();
            }
        }, true);
    }

    /* ── Esperar a que el DOM esté listo ── */
    function init() {
        const fabs = document.querySelectorAll('.fab-container');
        fabs.forEach(initDraggable);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
