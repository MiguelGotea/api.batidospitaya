-- 001_add_tipo_reunion.sql
-- Ejecutar MANUALMENTE en la base de datos de producción

ALTER TABLE `resumen_reuniones_ia`
  ADD COLUMN `tipo_reunion`
    ENUM('general','iterativa','informativa','decision','brainstorming','nivel10')
    NOT NULL DEFAULT 'general'
    AFTER `descripcion`;

ALTER TABLE `resumen_reuniones_ia`
  ADD KEY `idx_tipo_reunion` (`tipo_reunion`);
