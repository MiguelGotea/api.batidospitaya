/**
 * Sistema de Modal para Sucursales Auditadas
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de sucursales auditadas
    let modalSucursalesAuditadasContainer = null;

    /**
     * Mostrar modal de sucursales auditadas
     */
    window.mostrarModalSucursalesAuditadas = function () {
        if (!modalSucursalesAuditadasContainer) {
            modalSucursalesAuditadasContainer = document.createElement('div');
            modalSucursalesAuditadasContainer.id = 'modal-sucursales-auditadas-container';
            document.body.appendChild(modalSucursalesAuditadasContainer);
        }

        // Mostrar loading
        modalSucursalesAuditadasContainer.innerHTML = `
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
        const url = '../../core/components/indicators/sucursales_auditadas/get_modal_sucursales_auditadas.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalSucursalesAuditadasContainer.innerHTML = html;
                setupSucursalesAuditadasCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de sucursales auditadas:', error);
                modalSucursalesAuditadasContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalSucursalesAuditadas()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupSucursalesAuditadasCloseEvents();
            });
    };

    /**
     * Cerrar modal de sucursales auditadas
     */
    window.cerrarModalSucursalesAuditadas = function () {
        if (modalSucursalesAuditadasContainer) {
            modalSucursalesAuditadasContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de sucursales auditadas
     */
    function setupSucursalesAuditadasCloseEvents() {
        const modals = modalSucursalesAuditadasContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalSucursalesAuditadas();
                }
            });
        });

        const closeButtons = modalSucursalesAuditadasContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalSucursalesAuditadas);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalSucursalesAuditadas();
            }
        });
    }

})();
