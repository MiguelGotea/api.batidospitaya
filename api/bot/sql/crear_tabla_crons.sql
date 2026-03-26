-- Tabla de configuración de crons del bot
-- Ejecutar en Hostinger via phpMyAdmin

CREATE TABLE IF NOT EXISTS bot_crons_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(50) NOT NULL UNIQUE COMMENT 'briefing_diario, recordatorio_reunion...',
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  horario VARCHAR(50) NOT NULL COMMENT 'Expresión cron: 0 7 * * 1-5',
  activo TINYINT(1) DEFAULT 1,
  ultima_ejecucion DATETIME DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO bot_crons_config (clave, nombre, descripcion, horario, activo) VALUES
('briefing_diario',     'Briefing Matutino',    'Resumen del día para cada usuario a las 7 AM (Lun-Vie)',     '0 7 * * 1-5',  1),
('recordatorio_reunion','Recordatorio Reunión', 'Aviso 1h antes de cada reunión (verifica cada 15 min)',       '*/15 * * * *', 1),
('resumen_fin_dia',     'Resumen Fin de Día',   'Tareas completadas vs pendientes a las 6 PM (Lun-Vie)',       '0 18 * * 1-5', 1),
('revision_semanal',    'Revisión Semanal',     'Resumen semanal generado por Gemini (viernes 5 PM)',          '0 17 * * 5',   1),
('cumpleanios',         'Cumpleaños',           'Notifica cumpleaños de compañeros a las 8 AM diariamente',   '0 8 * * *',    1);
