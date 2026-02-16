/**
 * Lógica específica para Tardanzas Pendientes
 * Funciones del modal y comportamiento específico
 */

// Funciones legacy para compatibilidad
window.mostrarModalTardanzas = function () {
    if (typeof showIndicatorModal === 'function') {
        showIndicatorModal('tardanzas_pendientes', {});
    }
};

window.cerrarModalTardanzas = function () {
    if (typeof closeIndicatorModal === 'function') {
        closeIndicatorModal();
    }
};

// Funciones específicas del indicador de tardanzas
window.tardanzasPendientes = {
    /**
     * Filtrar tardanzas por sucursal
     */
    filtrarPorSucursal: function (sucursalCodigo) {
        // Implementar filtrado si es necesario
        console.log('Filtrar tardanzas por sucursal:', sucursalCodigo);
    },

    /**
     * Exportar datos de tardanzas
     */
    exportarDatos: function () {
        // Implementar exportación si es necesario
        console.log('Exportar datos de tardanzas');
    }
};
