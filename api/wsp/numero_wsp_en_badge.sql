-- ============================================================
-- numero_wsp_en_badge.sql
-- Agrega la columna numero_telefono a wsp_sesion_vps_
-- Ejecutar UNA SOLA VEZ en la BD del ERP / API
-- ============================================================

ALTER TABLE wsp_sesion_vps_
    ADD COLUMN IF NOT EXISTS numero_telefono VARCHAR(20) DEFAULT NULL
    COMMENT 'Número WhatsApp vinculado actualmente (ej: 50588888888)';

-- Verificar:
-- SELECT id, estado, numero_telefono, ultimo_ping FROM wsp_sesion_vps_ WHERE id = 1;
