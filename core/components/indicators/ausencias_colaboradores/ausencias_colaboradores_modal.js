/**
 * Sistema de Modal para Ausencias de Colaboradores
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de ausencias
    let modalAusenciasContainer = null;

    /**
     * Mostrar modal de ausencias
     */
    window.mostrarModalAusencias = function () {
        if (!modalAusenciasContainer) {
            modalAusenciasContainer = document.createElement('div');
            modalAusenciasContainer.id = 'modal-ausencias-container';
            document.body.appendChild(modalAusenciasContainer);
        }

        // Mostrar loading
        modalAusenciasContainer.innerHTML = `
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
        const url = '../../core/components/indicators/ausencias_colaboradores/get_modal_ausencias.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalAusenciasContainer.innerHTML = html;
                setupAusenciasCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de ausencias:', error);
                modalAusenciasContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalAusencias()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupAusenciasCloseEvents();
            });
    };

    /**
     * Cerrar modal de ausencias
     */
    window.cerrarModalAusencias = function () {
        if (modalAusenciasContainer) {
            modalAusenciasContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de ausencias
     */
    function setupAusenciasCloseEvents() {
        const modals = modalAusenciasContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalAusencias();
                }
            });
        });

        const closeButtons = modalAusenciasContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalAusencias);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalAusencias();
            }
        });
    }

})();
