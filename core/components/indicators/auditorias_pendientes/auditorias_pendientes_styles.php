/* Estilos para el modal y las tarjetas de sucursal de Auditorías Pendientes */

.sucursal-auditoria-item {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 10px;
}

.sucursal-header {
    background: #f8f9fa;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 1px solid transparent;
}

.sucursal-header:hover {
    background: #e9ecef !important;
}

.sucursal-header.active {
    background: #e3f2fd !important;
    border-bottom: 1px solid #2196f3;
}

.auditorias-contenido {
    max-height: 0;
    overflow: hidden;
    transition: all 0.4s ease-in-out;
    background: white;
}

.auditorias-contenido.open {
    max-height: 500px;
    padding: 15px;
    animation: slideDown 0.4s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        max-height: 500px;
        transform: translateY(0);
    }
}

.fa-chevron-down,
.fa-chevron-up {
    transition: transform 0.3s ease;
}

.fa-chevron-down.rotated {
    transform: rotate(180deg);
}

.auditoria-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
}

.auditoria-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.auditoria-item:last-child {
    margin-bottom: 0;
}

.btn-auditoria {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.btn-crear-auditoria {
    background: #28a745;
    color: white;
}

.btn-crear-auditoria:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.porcentaje-badge {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.porcentaje-badge.medio {
    background: #ffc107;
    color: #212529;
}

.porcentaje-badge.bajo {
    background: #dc3545;
    color: white;
}

.auditorias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sucursal-auditoria-item {
    animation: fadeIn 0.5s ease-out;
}

.sucursal-auditoria-item:nth-child(even) {
    animation-delay: 0.1s;
}

.sucursal-auditoria-item:nth-child(odd) {
    animation-delay: 0.2s;
}

.auditoria-item-detalle {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.auditoria-item-detalle:hover {
    background: #e9ecef;
}

.auditoria-completa {
    color: #28a745;
}

.auditoria-incompleta {
    color: #dc3545;
}

.btn-agregar-auditoria {
    padding: 4px 8px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.7rem;
    transition: all 0.3s ease;
}

.btn-agregar-auditoria:hover {
    background: #218838;
    transform: translateY(-1px);
}
