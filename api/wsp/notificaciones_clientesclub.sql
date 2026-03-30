-- ============================================================
-- notificaciones_clientesclub.sql
-- TABLA PARA NOTIFICACIONES TRANSACCIONALES (CLIENTES CLUB)
-- ============================================================

CREATE TABLE IF NOT EXISTS `wsp_notificaciones_clientesclub_pendientes_` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `celular` VARCHAR(20) NOT NULL COMMENT 'Número de WhatsApp con código de país',
  `mensaje` TEXT NOT NULL COMMENT 'Contenido del mensaje personalizado',
  `estado` ENUM('pendiente', 'enviando', 'enviado', 'error') DEFAULT 'pendiente',
  `instancia` VARCHAR(30) DEFAULT 'wsp-clientes' COMMENT 'Para discriminar qué VPS lo procesa',
  `creado_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `enviado_at` DATETIME DEFAULT NULL,
  `error_detalle` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para optimizar el polling del VPS
CREATE INDEX idx_wsp_notif_estado_instancia ON `wsp_notificaciones_clientesclub_pendientes_` (`estado`, `instancia`);
