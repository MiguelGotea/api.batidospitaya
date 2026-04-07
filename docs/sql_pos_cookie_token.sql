-- ============================================================
-- POS: Agregar columna pos_cookie_token a tabla sucursales
-- Independiente de cookie_token que usa el ERP
-- Ejecutar UNA SOLA VEZ en la base de datos u839374897_erp
-- ============================================================

ALTER TABLE `sucursales`
    ADD COLUMN `pos_cookie_token` varchar(64) DEFAULT NULL
    COMMENT 'Token de autorizacion exclusivo del dominio pos.batidospitaya.com'
    AFTER `cookie_token`;