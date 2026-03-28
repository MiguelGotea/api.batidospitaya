/**
 * Sistema de Modal para Tardanzas Pendientes
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de tardanzas
    let modalTardanzasContainer = null;

    /**
     * Mostrar modal de tardanzas
     */
    window.mostrarModalTardanzas = function () {
        if (!modalTardanzasContainer) {
            modalTardanzasContainer = document.createElement('div');
            modalTardanzasContainer.id = 'modal-tardanzas-container';
            document.body.appendChild(modalTardanzasContainer);
        }

        // Mostrar loading
        modalTardanzasContainer.innerHTML = `
            <div class="modal-pendientes" style="display: block;">
                <div class="modal-content-pendientes" style="max-width: 600px;">
                    <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #51B8AC;"></i>
                        <p style="margin-top: 15px;">Cargando datos...</p>
                    </div>
                </div>
            </div>
        `;

        // Cargar contenido del modal vía AJAX
        const url = '../../core/components/indicators/tardanzas_pendientes/get_modal_tardanzas.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalTardanzasContainer.innerHTML = html;
                setupTardanzasCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de tardanzas:', error);
                modalTardanzasContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalTardanzas()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupTardanzasCloseEvents();
            });
    };

    /**
     * Cerrar modal de tardanzas
     */
    window.cerrarModalTardanzas = function () {
        if (modalTardanzasContainer) {
            modalTardanzasContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de tardanzas
     */
    function setupTardanzasCloseEvents() {
        const modals = modalTardanzasContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalTardanzas();
                }
            });
        });

        const closeButtons = modalTardanzasContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalTardanzas);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalTardanzas();
            }
        });
    }

})();
