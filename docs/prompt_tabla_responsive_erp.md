# Prompt: Replicar Mejoras de Tabla Responsive + Sticky Header en Módulo ERP

## Contexto del Sistema
Este ERP está construido en PHP + Bootstrap 5 + jQuery. Los módulos siguen el estándar de
`cupones.php` como referencia canónica. Este prompt replica el comportamiento final y correcto
de `ver_marcaciones_todas_nuevo.php` después de todas las correcciones aplicadas.

---

## Objetivo
Aplicar las siguientes mejoras a un módulo con tabla de datos y buscador global:

1. **Scroll horizontal solo en la tabla** (no en toda la página)
2. **Encabezado de columnas fijado** al hacer scroll vertical (compatible con overflow-x: auto)
3. **Buscador global sticky** que queda fijo arriba al hacer scroll
4. **Pills/pestañas de vista** integradas en el buscador sticky
5. **Paginación fuera del scroll horizontal**
6. **Layout responsive**: en PC → buscador a la izquierda + pestañas a la derecha; en móvil → pestañas arriba + buscador abajo
7. **Sin efecto bounce/rubber-band** al llegar al tope en móviles

---

## PARTE 1 — CSS (agregar al final del CSS del módulo)

```css
/* ========================================================================= */
/* ESTILOS ESTÁNDAR — Tabla Responsive + Sticky Header + Buscador Global     */
/* ========================================================================= */

/* 1. Asegurar contexto de scroll vertical nativo de la ventana */
html {
    overflow-y: scroll;
    min-height: 100vh;
    overscroll-behavior-y: none; /* Eliminar efecto bounce/rubber-band en móviles */
}

body {
    min-height: 100vh;
    overscroll-behavior-y: none;
}

/* 2. Contenedor de tabla: scroll horizontal SOLO aquí, no en la página */
.cupones-table-container {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* 3. Tabla principal */
.cupones-table {
    width: 100%;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-collapse: separate !important;
    border-spacing: 0 !important;
}

/* Bordes redondeados en esquinas (compatible con border-collapse: separate) */
.cupones-table thead th:first-child { border-top-left-radius: 8px; }
.cupones-table thead th:last-child  { border-top-right-radius: 8px; }
.cupones-table tbody tr:last-child td:first-child { border-bottom-left-radius: 8px; }
.cupones-table tbody tr:last-child td:last-child  { border-bottom-right-radius: 8px; }

/* 4. Encabezado: position: static porque el sticky se maneja por JS */
/* IMPORTANTE: NO usar position: sticky aquí porque el contenedor tiene overflow-x: auto */
.cupones-table thead th {
    font-weight: 600;
    padding: 14px 12px;
    border: none;
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    position: static !important;
    white-space: nowrap;
    font-size: 14px;
    background-color: #0E544C !important;
    color: white !important;
}

.cupones-table thead th:last-child { border-right: none; }

.cupones-table tbody tr:hover { background-color: #f8f9fa; }

.cupones-table tbody td {
    padding: 12px;
    vertical-align: middle;
    font-size: 14px;
    border-right: 1px solid #e9ecef;
}

.cupones-table tbody td:last-child { border-right: none; }

/* 5. Contenedor sticky del buscador global + pills */
/* CLAVE: position sticky en el contenedor del buscador, no en los th */
.sticky-search-container {
    position: sticky;
    top: 0;
    z-index: 1025;
    background-color: #F6F6F6; /* Debe coincidir con el fondo del body/contenido */
    padding-top: 10px;
    padding-bottom: 10px;
    /* Compensar padding del contenedor padre para extenderse de borde a borde */
    margin-left: -16px;
    margin-right: -16px;
    padding-left: 16px;
    padding-right: 16px;
}

#buscarGlobal:focus {
    border-color: #0E544C !important;
    box-shadow: 0 0 0 0.25rem rgba(14, 84, 76, 0.25) !important;
    outline: none;
}

/* 6. Layout en PC: buscador a la izquierda (flex-grow) y pestañas a la derecha */
.sticky-search-container .d-flex {
    flex-direction: row;
    align-items: center;
    gap: 16px;
}

.sticky-search-container .vista-switch {
    order: 1;  /* Pestañas al final */
    flex-shrink: 0;
}

.sticky-search-container .flex-grow-1 {
    order: 0;  /* Buscador primero */
    width: auto;
}

/* 7. Layout en Móvil: pestañas arriba, buscador abajo */
@media (max-width: 768px) {
    .cupones-table {
        font-size: 13px;
    }

    .cupones-table thead th,
    .cupones-table tbody td {
        padding: 10px 8px;
    }

    .sticky-search-container {
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .sticky-search-container .d-flex {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
    }

    .sticky-search-container .vista-switch {
        order: 0 !important; /* Pestañas arriba */
        width: 100%;
        display: flex;
    }

    .sticky-search-container .vista-switch .vista-pill {
        flex: 1;
        justify-content: center;
        text-align: center;
        padding: 6px 8px;
        font-size: 13px;
    }

    .sticky-search-container .flex-grow-1 {
        order: 1 !important; /* Buscador abajo */
        width: 100%;
    }

    /* Paginación en columna en móvil */
    .pagination-container-responsive {
        flex-direction: column !important;
        align-items: center !important;
        gap: 12px !important;
    }
}

/* 8. Pills de vista (pestañas: Todo, Ausencias, Tardanzas, etc.) */
.vista-switch {
    display: inline-flex;
    background: rgba(14, 84, 76, 0.08);
    border: 1px solid rgba(14, 84, 76, 0.15);
    border-radius: 10px;
    padding: 3px;
    gap: 2px;
}

.vista-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 8px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    background: transparent;
    color: #0E544C;
    transition: all 0.2s;
    white-space: nowrap;
}

.vista-pill.active {
    background: #0E544C;
    color: white;
    box-shadow: 0 2px 6px rgba(14, 84, 76, 0.25);
}

.vista-pill:not(.active):hover { background: rgba(14, 84, 76, 0.12); }
.vista-pill i { font-size: 13px; }
```

---

## PARTE 2 — HTML del módulo (estructura obligatoria)

```html
<!-- ZONA PRINCIPAL DEL MÓDULO (dentro del contenedor padre con padding: 16px) -->

<!-- A. Buscador Global Sticky + Pills de Vista (SIEMPRE fuera del cupones-table-container) -->
<div class="sticky-search-container">
    <div class="d-flex align-items-center gap-3">

        <!-- Pills de vista (pestañas) -->
        <div class="vista-switch flex-shrink-0" id="vistaSwitchHD">
            <button class="vista-pill active" id="btnVistaTodos"
                onclick="setFiltroIncidencias('todos')">Todo</button>
            <button class="vista-pill" id="btnVistaAusencias"
                onclick="setFiltroIncidencias('faltas')">Ausencias</button>
            <!-- Agregar más pills según el módulo -->
        </div>

        <!-- Buscador global -->
        <div class="position-relative flex-grow-1">
            <i class="bi bi-search position-absolute"
               style="left: 16px; top: 50%; transform: translateY(-50%); z-index: 10; color: #6c757d; font-size: 16px;"></i>
            <input type="text" id="buscarGlobal" class="form-control w-100"
                placeholder="Buscar en todos los campos..."
                style="padding-left: 44px; padding-right: 44px; height: 46px !important;
                       font-size: 14px !important; border-radius: 12px !important;">
            <div id="buscarGlobalSpinner" class="position-absolute"
                 style="right: 16px; top: 50%; transform: translateY(-50%); z-index: 10; display: none; color: #6c757d;">
                <i class="fas fa-spinner fa-spin" style="font-size: 16px;"></i>
            </div>
        </div>

    </div>
</div>

<!-- B. Contenedor de tabla con scroll horizontal -->
<div class="cupones-table-container" style="margin-top: 0;">

    <!-- Tabla de datos -->
    <table id="tabla-principal" class="table table-hover cupones-table w-100">
        <thead>
            <tr>
                <th>Columna 1</th>
                <th>Columna 2</th>
                <!-- ... más columnas ... -->
            </tr>
        </thead>
        <tbody id="tablaCuerpo">
            <!-- Datos cargados vía AJAX -->
        </tbody>
    </table>

</div>
<!-- CIERRE del cupones-table-container -->

<!-- C. Controles de paginación: FUERA del cupones-table-container para evitar scroll horizontal -->
<div class="d-flex justify-content-between align-items-center mt-3 pagination-container-responsive">
    <div class="d-flex align-items-center gap-2">
        <label class="mb-0">Mostrar:</label>
        <select class="form-select form-select-sm" id="registrosPorPagina"
                style="width: auto; padding-right: 24px;" onchange="cambiarRegistrosPorPagina()">
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
        <span class="mb-0">registros</span>
    </div>
    <div id="paginacion"></div>
</div>
```

---

## PARTE 3 — JavaScript (agregar antes del `</html>` del módulo)

Este script crea un encabezado sticky clonado que funciona correctamente aunque el
contenedor tenga `overflow-x: auto`.

> **Por qué JS y no CSS:** `position: sticky` en `thead th` NO funciona cuando
> un elemento padre tiene `overflow-x: auto`. Son incompatibles en todos los navegadores.
> La solución estándar (usada por DataTables, AG Grid, etc.) es clonar el encabezado
> por JavaScript.

```html
<script>
/* ============================================================
   Sticky Header Clonado — compatible con overflow-x: auto
   Arquitectura:
   - wrapper div (position:fixed + overflow:hidden) = ventana de recorte
   - tabla clon dentro del wrapper = se desplaza horizontalmente con transform
   ============================================================ */
(function () {
    'use strict';

    var stickyWrapper  = null;   // div fixed que recorta
    var stickyTable    = null;   // tabla clon dentro del wrapper
    var tableContainer = null;
    var origTable      = null;

    // Guardar referencias a listeners para poder removerlos al reiniciar
    var scrollHandler  = null;
    var resizeHandler  = null;
    var hScrollHandler = null;

    function initStickyHeader() {
        // ⚠️ Ajustar estos selectores según el módulo:
        tableContainer = document.querySelector('.cupones-table-container');
        origTable      = document.getElementById('tabla-principal'); // ← ID de la tabla

        if (!tableContainer || !origTable) return;

        // Remover wrapper previo (si se reinicia por carga AJAX)
        var prev = document.getElementById('sticky-header-wrapper');
        if (prev) prev.parentNode.removeChild(prev);

        // Remover listeners anteriores
        if (scrollHandler)  { window.removeEventListener('scroll', scrollHandler); }
        if (resizeHandler)  { window.removeEventListener('resize', resizeHandler); }
        if (hScrollHandler && tableContainer) {
            tableContainer.removeEventListener('scroll', hScrollHandler);
        }

        var origThead = origTable.querySelector('thead');
        if (!origThead) return;

        // Wrapper: ventana fija de recorte (tiene el tamaño exacto del contenedor)
        stickyWrapper = document.createElement('div');
        stickyWrapper.id = 'sticky-header-wrapper';
        stickyWrapper.style.cssText = [
            'position:fixed',
            'overflow:hidden',
            'z-index:1010',
            'visibility:hidden',
            'pointer-events:none',
            'box-sizing:border-box'
        ].join(';');

        // Tabla clon: se mueve horizontalmente dentro del wrapper
        stickyTable = document.createElement('table');
        stickyTable.className = origTable.className;
        stickyTable.style.cssText = [
            'position:relative',
            'border-collapse:separate',
            'border-spacing:0',
            'table-layout:fixed',
            'margin:0',
            'padding:0',
            'box-shadow:none',
            'border-radius:0',
            'white-space:nowrap'
        ].join(';');

        var theadClone = origThead.cloneNode(true);
        // Quitar onclick del clon para evitar acciones duplicadas
        theadClone.querySelectorAll('[onclick]').forEach(function (el) {
            el.removeAttribute('onclick');
        });

        stickyTable.appendChild(theadClone);
        stickyWrapper.appendChild(stickyTable);
        document.body.appendChild(stickyWrapper);

        syncAll();

        scrollHandler  = function () { syncAll(); };
        resizeHandler  = function () { syncAll(); };
        hScrollHandler = function () { syncHScroll(); };

        window.addEventListener('scroll', scrollHandler, { passive: true });
        window.addEventListener('resize', resizeHandler, { passive: true });
        tableContainer.addEventListener('scroll', hScrollHandler, { passive: true });
    }

    function syncAll() {
        syncPosition();
        syncWidths();
        syncHScroll();
    }

    function syncPosition() {
        if (!stickyWrapper || !tableContainer || !origTable) return;

        var containerRect   = tableContainer.getBoundingClientRect();
        var searchContainer = document.querySelector('.sticky-search-container');
        var searchHeight    = searchContainer ? searchContainer.offsetHeight : 0;

        var origThead    = origTable.querySelector('thead');
        var theadRect    = origThead ? origThead.getBoundingClientRect() : null;
        var theadBottom  = theadRect ? theadRect.bottom : 0;
        var containerEnd = containerRect.bottom;

        // Visible solo cuando: tabla en viewport Y thead original ya salió de pantalla
        var tableVisible  = containerRect.top < window.innerHeight && containerEnd > searchHeight;
        var theadScrolled = theadBottom <= searchHeight + 2;

        if (tableVisible && theadScrolled) {
            stickyWrapper.style.top        = searchHeight + 'px';
            stickyWrapper.style.left       = containerRect.left + 'px';
            stickyWrapper.style.width      = containerRect.width + 'px';
            stickyWrapper.style.visibility = 'visible';
        } else {
            stickyWrapper.style.visibility = 'hidden';
        }
    }

    function syncWidths() {
        if (!stickyTable || !origTable) return;
        var origThs   = origTable.querySelectorAll('thead th');
        var clonedThs = stickyTable.querySelectorAll('thead th');

        // Copiar ancho exacto de cada columna real al clon
        origThs.forEach(function (th, i) {
            if (clonedThs[i]) {
                var w = th.getBoundingClientRect().width;
                clonedThs[i].style.minWidth = w + 'px';
                clonedThs[i].style.maxWidth = w + 'px';
                clonedThs[i].style.width    = w + 'px';
            }
        });
    }

    function syncHScroll() {
        // Mover la tabla clon con el mismo desplazamiento horizontal del contenedor
        if (stickyTable && tableContainer) {
            stickyTable.style.transform = 'translateX(-' + tableContainer.scrollLeft + 'px)';
        }
    }

    // Llamar esto después de cada carga AJAX que actualice la tabla
    window.initStickyTableHeader = function () {
        setTimeout(initStickyHeader, 80);
    };

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(initStickyHeader, 300);
    });
})();
</script>
```

---

## PARTE 4 — Llamada después de cada carga AJAX

En el callback de éxito de tu función `cargarDatos()` (o equivalente), agregar:

```javascript
// Dentro del success de $.ajax o fetch, después de renderizar la tabla:
renderizarTabla(response.datos);
renderizarPaginacion(response.total_registros);

// ← Agregar esta llamada:
if (typeof window.initStickyTableHeader === 'function') {
    window.initStickyTableHeader();
}
```

---

## Lista de verificación al aplicar a un módulo nuevo

- [ ] **CSS**: Agregar el bloque CSS completo de la Parte 1 al archivo `.css` del módulo
- [ ] **HTML**: El `sticky-search-container` está **fuera** del `cupones-table-container`
- [ ] **HTML**: La paginación (`#paginacion` + `Mostrar: X`) está **fuera** del `cupones-table-container`
- [ ] **HTML**: La tabla tiene un `id` único (ej. `id="tabla-principal"`)
- [ ] **JS**: El script de la Parte 3 está antes del `</html>` del módulo
- [ ] **JS**: Ajustar los selectores en el script:
  - `document.querySelector('.cupones-table-container')` → igual si usas esa clase
  - `document.getElementById('tabla-principal')` → cambiar al ID real de tu tabla
- [ ] **JS**: Agregar la llamada `initStickyTableHeader()` en el callback AJAX

---

## Notas técnicas importantes

| Problema | Causa | Solución |
|---|---|---|
| `sticky` en `thead th` no funciona | `overflow-x: auto` en padre rompe el sticky | Clonar encabezado por JS |
| Clon sale del borde de la tabla | Tabla clonada más ancha que viewport | Wrapper div con `overflow: hidden` |
| Scroll horizontal no sincroniza | `margin-left` no recorta | `transform: translateX()` dentro del wrapper |
| Efecto bounce al llegar al top en móvil | `overflow-y: scroll` sin `overscroll-behavior` | `overscroll-behavior-y: none` en `html` y `body` |
| Paginación se desplaza horizontalmente | Paginación dentro del `overflow-x: auto` | Mover paginación fuera del contenedor |
| Buscador queda mal en pantalla pequeña | Falta media query | Pills arriba + buscador abajo con `flex-direction: column` |
