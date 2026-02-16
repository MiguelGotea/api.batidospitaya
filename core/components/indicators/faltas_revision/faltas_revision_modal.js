/**
 * Sistema de Modal para Faltas Pendientes de Revisión
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de faltas revisión
    let modalFaltasRevisionContainer = null;

    /**
     * Mostrar modal de faltas revisión
     */
    window.mostrarModalFaltasRevision = function () {
        if (!modalFaltasRevisionContainer) {
            modalFaltasRevisionContainer = document.createElement('div');
            modalFaltasRevisionContainer.id = 'modal-faltas-revision-container';
            document.body.appendChild(modalFaltasRevisionContainer);
        }

        // Mostrar loading
        modalFaltasRevisionContainer.innerHTML = `
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
        const url = '../../core/components/indicators/faltas_revision/get_modal_faltas_revision.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalFaltasRevisionContainer.innerHTML = html;
                setupFaltasRevisionCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de faltas revisión:', error);
                modalFaltasRevisionContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalFaltasRevision()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupFaltasRevisionCloseEvents();
            });
    };

    /**
     * Cerrar modal de faltas revisión
     */
    window.cerrarModalFaltasRevision = function () {
        if (modalFaltasRevisionContainer) {
            modalFaltasRevisionContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de faltas revisión
     */
    function setupFaltasRevisionCloseEvents() {
        const modals = modalFaltasRevisionContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalFaltasRevision();
                }
            });
        });

        const closeButtons = modalFaltasRevisionContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalFaltasRevision);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalFaltasRevision();
            }
        });
    }

})();
