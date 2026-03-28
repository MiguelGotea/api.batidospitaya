/**
 * Sistema de Modal para Faltas Pendientes
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de faltas
    let modalFaltasContainer = null;

    /**
     * Mostrar modal de faltas
     */
    window.mostrarModalFaltas = function () {
        if (!modalFaltasContainer) {
            modalFaltasContainer = document.createElement('div');
            modalFaltasContainer.id = 'modal-faltas-container';
            document.body.appendChild(modalFaltasContainer);
        }

        // Mostrar loading
        modalFaltasContainer.innerHTML = `
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
        const url = '../../core/components/indicators/faltas_pendientes/get_modal_faltas.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalFaltasContainer.innerHTML = html;
                setupFaltasCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de faltas:', error);
                modalFaltasContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalFaltas()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupFaltasCloseEvents();
            });
    };

    /**
     * Cerrar modal de faltas
     */
    window.cerrarModalFaltas = function () {
        if (modalFaltasContainer) {
            modalFaltasContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de faltas
     */
    function setupFaltasCloseEvents() {
        const modals = modalFaltasContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalFaltas();
                }
            });
        });

        const closeButtons = modalFaltasContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalFaltas);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalFaltas();
            }
        });
    }

})();
