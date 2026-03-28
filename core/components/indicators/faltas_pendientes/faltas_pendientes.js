/**
 * Lógica específica para Faltas Pendientes
 * Funciones del modal y comportamiento específico
 */

// Funciones legacy para compatibilidad
window.mostrarModalFaltas = function () {
    if (typeof showIndicatorModal === 'function') {
        showIndicatorModal('faltas_pendientes', {});
    }
};

window.cerrarModalFaltas = function () {
    if (typeof closeIndicatorModal === 'function') {
        closeIndicatorModal();
    }
};

// Funciones específicas del indicador de faltas
window.faltasPendientes = {
    /**
     * Filtrar faltas por sucursal
     */
    filtrarPorSucursal: function (sucursalCodigo) {
        console.log('Filtrar faltas por sucursal:', sucursalCodigo);
    },

    /**
     * Exportar datos de faltas
     */
    exportarDatos: function () {
        console.log('Exportar datos de faltas');
    }
};
