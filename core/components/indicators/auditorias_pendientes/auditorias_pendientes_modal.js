/**
 * Sistema de Modal para Auditorías Pendientes
 * Completamente independiente
 */

(function () {
    'use strict';

    // Contenedor específico para modal de auditorías
    let modalAuditoriasContainer = null;

    /**
     * Mostrar modal de auditorías
     */
    window.mostrarModalAuditorias = function () {
        if (!modalAuditoriasContainer) {
            modalAuditoriasContainer = document.createElement('div');
            modalAuditoriasContainer.id = 'modal-auditorias-container';
            document.body.appendChild(modalAuditoriasContainer);
        }

        // Mostrar loading
        modalAuditoriasContainer.innerHTML = `
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
        const url = '../../core/components/indicators/auditorias_pendientes/get_modal_auditorias.php';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                modalAuditoriasContainer.innerHTML = html;
                setupAuditoriasCloseEvents();
            })
            .catch(error => {
                console.error('Error cargando modal de auditorías:', error);
                modalAuditoriasContainer.innerHTML = `
                    <div class="modal-pendientes" style="display: block;">
                        <div class="modal-content-pendientes" style="max-width: 600px;">
                            <div class="modal-body-pendientes" style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                                <p style="margin-top: 15px;">Error al cargar el modal</p>
                                <button onclick="cerrarModalAuditorias()" class="btn-ver-detalles" style="margin-top: 20px;">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                setupAuditoriasCloseEvents();
            });
    };

    /**
     * Cerrar modal de auditorías
     */
    window.cerrarModalAuditorias = function () {
        if (modalAuditoriasContainer) {
            modalAuditoriasContainer.innerHTML = '';
        }
    };

    /**
     * Configurar eventos para cerrar modal de auditorías
     */
    function setupAuditoriasCloseEvents() {
        const modals = modalAuditoriasContainer.querySelectorAll('.modal-pendientes');
        modals.forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    cerrarModalAuditorias();
                }
            });
        });

        const closeButtons = modalAuditoriasContainer.querySelectorAll('.close-modal');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', cerrarModalAuditorias);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                cerrarModalAuditorias();
            }
        });
    }

})();
