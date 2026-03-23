-- ================================================================
-- Etapa 3 PitayaBot — Columnas nuevas para soporte de reuniones
-- Ejecutar manualmente en Hostinger (u839374897_erp)
-- ================================================================

ALTER TABLE gestion_tareas_reuniones_items
  ADD COLUMN IF NOT EXISTS hora_inicio   TIME         DEFAULT NULL   COMMENT 'Hora de inicio de la reunion',
  ADD COLUMN IF NOT EXISTS duracion_min  INT          DEFAULT 60     COMMENT 'Duracion en minutos',
  ADD COLUMN IF NOT EXISTS ics_sequence  INT          DEFAULT 0      COMMENT 'SEQUENCE ICS para modificaciones',
  ADD COLUMN IF NOT EXISTS lugar         VARCHAR(200) DEFAULT NULL   COMMENT 'Lugar o enlace de la reunion';

-- Indice para consultas de reuniones por operario y fecha
CREATE INDEX IF NOT EXISTS idx_bot_reuniones
  ON gestion_tareas_reuniones_items (cod_operario_creador, tipo, estado, fecha_meta, hora_inicio);
