/**
 * Scripts JS para el modal de Auditorías Pendientes.
 */
function mostrarModalAuditoriasMensuales() {
    const modal = document.getElementById('modalAuditoriasMensuales');
    if (modal) {
        modal.style.display = 'block';
    }
}

function cerrarModalAuditoriasMensuales() {
    const modal = document.getElementById('modalAuditoriasMensuales');
    if (modal) {
        modal.style.display = 'none';
    }
}

function toggleAuditoriasSucursal(codSucursal) {
    const contenido = document.getElementById('auditorias-' + codSucursal);
    const icono = document.getElementById('icon-' + codSucursal);
    const header = contenido.previousElementSibling;

    if (contenido.classList.contains('open')) {
        contenido.classList.remove('open');
        icono.className = 'fas fa-chevron-down';
        header.classList.remove('active');
    } else {
        // Cerrar todos los demás que estén abiertos en este modal
        const abiertos = document.querySelectorAll('.auditorias-contenido.open');
        abiertos.forEach(function(item) {
            item.classList.remove('open');
            const itemCod = item.id.replace('auditorias-', '');
            const itemIcon = document.getElementById('icon-' + itemCod);
            if (itemIcon) itemIcon.className = 'fas fa-chevron-down';
            const itemHeader = item.previousElementSibling;
            if (itemHeader) itemHeader.classList.remove('active');
        });

        contenido.classList.add('open');
        icono.className = 'fas fa-chevron-down rotated';
        header.classList.add('active');
    }
}

// Cerrar modal al hacer clic afuera (usando EventListener para no pisar window.onclick)
window.addEventListener('click', function (event) {
    const modal = document.getElementById('modalAuditoriasMensuales');
    if (modal && event.target === modal) {
        cerrarModalAuditoriasMensuales();
    }
});

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarModalAuditoriasMensuales();
    }
});
