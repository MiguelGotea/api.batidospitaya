-- 002_drop_token_expira.sql
-- Ejecutar en la base de datos de producción para limpiar la columna que ya no se utilizará
-- Base de datos donde residen la tabla resumen_reuniones_ia.

ALTER TABLE resumen_reuniones_ia DROP COLUMN token_expira;
