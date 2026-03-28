/**
 * Sistema de Modal para Contratos por Vencer
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de contratos
    let modalContratosContainer = null;

    /**
     * Mostrar modal de contratos
     */
    window.mostrarModalContratos = function () {
        if (!modalContratosContainer) {
            modalContratosContainer = document.createElement('div');
            modalContratosContainer.id = 'modal-contratos-container';
            document.body.appendChild(modalContratosContainer);
        }

        // Mostrar loading
        modalContratosContainer.innerHTML = `
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
        const url = '../../core/components/indicators/contratos_vencer/get_modal_contratos.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalContratosContainer.innerHTML = html;
                setupContratosCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de contratos:', error);
                modalContratosContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalContratos()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupContratosCloseEvents();
            });
    };

    /**
     * Cerrar modal de contratos
     */
    window.cerrarModalContratos = function () {
        if (modalContratosContainer) {
            modalContratosContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de contratos
     */
    function setupContratosCloseEvents() {
        const modals = modalContratosContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalContratos();
                }
            });
        });

        const closeButtons = modalContratosContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalContratos);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalContratos();
            }
        });
    }

})();
