/* Estilos para el modal y las tarjetas de sucursal de Horarios por Confirmar */

/* Efecto específico para tarjetas pendientes (rojo) */
.modal-body-pendientes .sucursal-card-pendiente {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.modal-body-pendientes .sucursal-card-pendiente:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2);
    border-color: #dc3545;
    background: #fff5f5;
}

/* Efecto específico para tarjetas de ediciones (amarillo) */
.modal-body-pendientes .sucursal-card-edicion {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.modal-body-pendientes .sucursal-card-edicion:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);
    border-color: #ffc107;
    background: #fffbf0;
}

/* Efecto específico para tarjetas informativas (sin horario - sin cursor pointer) */
.modal-body-pendientes .sucursal-card-informativo {
    background: #fff3cd;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
    transition: all 0.3s ease;
}

.modal-body-pendientes .sucursal-card-informativo:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.15);
    background: #fffbf0;
}
