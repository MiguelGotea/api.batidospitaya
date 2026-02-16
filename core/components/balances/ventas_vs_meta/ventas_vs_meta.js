/**
 * Balance Component - Ventas vs Meta
 * Maneja la visualización y scroll de datos de ventas
 */

// Almacenar instancias de balances
window.pitayaBalances = window.pitayaBalances || {};

/**
 * Inicializar un balance
 */
function initializeBalance(containerId, ajaxUrl) {
    window.pitayaBalances[containerId] = {
        data: null,
        offset: 0,
        containerId: containerId,
        ajaxUrl: ajaxUrl
    };

    // Cargar datos
    loadBalanceData(containerId);

    // Event listeners para botones de scroll
    const scrollLeft = document.getElementById(`scrollLeft_${containerId}`);
    const scrollRight = document.getElementById(`scrollRight_${containerId}`);

    if (scrollLeft) {
        scrollLeft.addEventListener('click', function () {
            scrollBalance(containerId, -1);
        });
    }

    if (scrollRight) {
        scrollRight.addEventListener('click', function () {
            scrollBalance(containerId, 1);
        });
    }
}

/**
 * Cargar datos del balance vía AJAX
 */
function loadBalanceData(containerId) {
    const balance = window.pitayaBalances[containerId];

    fetch(balance.ajaxUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                balance.data = data;
                renderBalanceTable(containerId);
            } else {
                console.error('Error:', data.message);
                showBalanceError(containerId, data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showBalanceError(containerId, 'Error al cargar datos de ventas');
        });
}

/**
 * Renderizar tabla de balance
 */
function renderBalanceTable(containerId) {
    const balance = window.pitayaBalances[containerId];
    const data = balance.data;

    if (!data || !data.datos) return;

    // Obtener los 7 días a mostrar según el offset
    const datosVisibles = data.datos.slice(balance.offset, balance.offset + 7);

    // Generar encabezados
    const header = document.getElementById(`${containerId}_header`);
    if (!header) return;

    let headerHTML = '<th>Meta: ' + data.meta_mensual.toFixed(1) + '</th>';
    headerHTML += '<th>' + data.mes_actual + '</th>';

    datosVisibles.forEach(dato => {
        headerHTML += '<th>' + dato.dia + '</th>';
    });
    header.innerHTML = headerHTML;

    // Fila de ventas reales
    const ventasRow = document.getElementById(`${containerId}_reales`);
    if (ventasRow) {
        let ventasHTML = '<td>Real</td>';
        ventasHTML += '<td>' + data.promedio_mes.ventas_reales + '</td>';
        datosVisibles.forEach(dato => {
            ventasHTML += '<td>' + dato.ventas_reales + '</td>';
        });
        ventasRow.innerHTML = ventasHTML;
    }

    // Fila de cumplimiento
    const cumplimientoRow = document.getElementById(`${containerId}_cumplimiento`);
    if (cumplimientoRow) {
        let cumplimientoHTML = '<td>Cumplimiento</td>';
        cumplimientoHTML += '<td><span class="semaforo ' + data.promedio_mes.color + '"></span>' + data.promedio_mes.cumplimiento + '%</td>';
        datosVisibles.forEach(dato => {
            cumplimientoHTML += '<td><span class="semaforo ' + dato.color + '"></span>' + dato.cumplimiento + '%</td>';
        });
        cumplimientoRow.innerHTML = cumplimientoHTML;
    }

    // Actualizar visibilidad de botones de scroll
    updateScrollButtons(containerId);
}

/**
 * Scroll del balance
 */
function scrollBalance(containerId, direction) {
    const balance = window.pitayaBalances[containerId];
    const newOffset = balance.offset + direction;

    // Validar límites
    if (newOffset < 0 || newOffset + 7 > balance.data.datos.length) {
        return;
    }

    balance.offset = newOffset;
    renderBalanceTable(containerId);
}

/**
 * Actualizar visibilidad de botones de scroll
 */
function updateScrollButtons(containerId) {
    const balance = window.pitayaBalances[containerId];
    const scrollLeft = document.getElementById(`scrollLeft_${containerId}`);
    const scrollRight = document.getElementById(`scrollRight_${containerId}`);

    if (!balance.data || !scrollLeft || !scrollRight) return;

    // Mostrar botón izquierdo si hay días anteriores
    if (balance.offset > 0) {
        scrollLeft.classList.add('visible');
    } else {
        scrollLeft.classList.remove('visible');
    }

    // Mostrar botón derecho si hay más días
    if (balance.offset + 7 < balance.data.datos.length) {
        scrollRight.classList.add('visible');
    } else {
        scrollRight.classList.remove('visible');
    }
}

/**
 * Mostrar error en el balance
 */
function showBalanceError(containerId, mensaje) {
    const wrapper = document.getElementById(`${containerId}_wrapper`);
    if (wrapper) {
        wrapper.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">' +
            '<i class="fas fa-exclamation-triangle"></i> ' + mensaje +
            '</div>';
    }
}
