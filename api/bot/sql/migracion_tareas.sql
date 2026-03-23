-- ============================================================
-- Migración para soporte de prioridad en gestion_tareas_reuniones_items
-- Ejecutar SOLO si la columna no existe todavía
-- ============================================================

ALTER TABLE gestion_tareas_reuniones_items
    ADD COLUMN IF NOT EXISTS prioridad ENUM('alta', 'media', 'baja') NOT NULL DEFAULT 'media';

-- Índice para mejorar búsquedas por operario+estado
CREATE INDEX IF NOT EXISTS idx_bot_tareas
    ON gestion_tareas_reuniones_items (cod_operario_creador, tipo, estado, fecha_meta);
