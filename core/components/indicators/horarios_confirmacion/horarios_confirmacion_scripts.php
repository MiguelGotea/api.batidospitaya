/**
 * Scripts JS para el modal de Horarios por Confirmar.
 */
function mostrarModalHorariosConfirmacion() {
    const modal = document.getElementById('modalHorariosConfirmacion');
    if (modal) {
        modal.style.display = 'block';
    }
}

function cerrarModalHorariosConfirmacion() {
    const modal = document.getElementById('modalHorariosConfirmacion');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Cerrar modal al hacer clic afuera (usando EventListener para no pisar window.onclick)
window.addEventListener('click', function (event) {
    const modal = document.getElementById('modalHorariosConfirmacion');
    if (modal && event.target === modal) {
        cerrarModalHorariosConfirmacion();
    }
});

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        cerrarModalHorariosConfirmacion();
    }
});
