-- ================================================================
-- Limpieza de Columnas Redundantes (Etapa 3 Refinada)
-- Ejecutar manualmente en Hostinger (u839374897_erp)
-- ================================================================

-- Eliminamos hora_inicio ya que ahora usamos fecha_reunion (DATETIME)
ALTER TABLE gestion_tareas_reuniones_items
  DROP COLUMN IF EXISTS hora_inicio;

-- Opcional: Si quieres mover fechas existentes de fecha_meta a fecha_reunion
-- UPDATE gestion_tareas_reuniones_items
-- SET fecha_reunion = CONCAT(fecha_meta, ' 00:00:00')
-- WHERE tipo = 'reunion' AND fecha_reunion IS NULL AND fecha_meta IS NOT NULL;
