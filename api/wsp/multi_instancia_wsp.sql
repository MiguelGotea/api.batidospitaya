-- ============================================================
-- multi_instancia_wsp.sql
-- Migración para soportar múltiples instancias WhatsApp en el VPS
-- Ejecutar UNA SOLA VEZ en la BD del ERP / API (base pitaya_erp)
--
-- CONTEXTO:
--   Antes: wsp_sesion_vps_ tenía una sola fila (id=1) para un único VPS.
--   Ahora: cada instancia PM2 tiene su propia fila identificada por 'instancia'.
--   Esto permite correr wsp-clientes y wsp-rrhh en paralelo sin conflicto.
-- ============================================================


-- 1. Agregar columna 'instancia' (nombre PM2 del proceso)
ALTER TABLE wsp_sesion_vps_
    ADD COLUMN IF NOT EXISTS instancia VARCHAR(30) NOT NULL DEFAULT 'wsp-clientes'
        COMMENT 'Nombre PM2: wsp-clientes, wsp-rrhh, wsp-proveedores...'
        AFTER id;

-- 2. Agregar columna 'numero_telefono' (número WA vinculado)
ALTER TABLE wsp_sesion_vps_
    ADD COLUMN IF NOT EXISTS numero_telefono VARCHAR(20) DEFAULT NULL
        COMMENT 'Número WhatsApp vinculado actualmente (ej: 50588888888)'
        AFTER qr_base64;

-- 3. La fila existente (id=1) ya es la instancia 'wsp-clientes' → actualizar para dejar consistente
UPDATE wsp_sesion_vps_ SET instancia = 'wsp-clientes' WHERE id = 1;

-- 4. Agregar UNIQUE KEY en instancia (permite INSERT ON DUPLICATE KEY UPDATE por nombre)
ALTER TABLE wsp_sesion_vps_
    ADD UNIQUE KEY IF NOT EXISTS uq_instancia (instancia);

-- 5. Verificar resultado
SELECT id, instancia, estado, numero_telefono, ultimo_ping, ip_vps FROM wsp_sesion_vps_;

-- ============================================================
-- CUANDO SE ACTIVE wsp-rrhh: insertar su fila
-- ============================================================
-- INSERT INTO wsp_sesion_vps_ (instancia, estado)
-- VALUES ('wsp-rrhh', 'desconectado')
-- ON DUPLICATE KEY UPDATE estado = estado;
