-- =============================================================================
-- resumen_reuniones_ia.sql
-- Herramienta: Resumen de Reuniones IA
-- Ejecutar en la base de datos del ERP (Hostinger)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Tabla principal
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resumen_reuniones_ia` (
  `id`                INT          NOT NULL AUTO_INCREMENT,
  `titulo`            VARCHAR(255) NOT NULL,
  `descripcion`       TEXT,
  `colaboradores`     JSON,                                  -- Array de CodOperario, ej: [101, 205]
  `creado_por`        INT          NOT NULL,                 -- CodOperario del creador
  `token`             VARCHAR(48)  NOT NULL,                 -- bin2hex(random_bytes(24)) = 48 chars
  `token_expira`      DATETIME     NOT NULL,                 -- creacion + 6 horas
  `estado`            ENUM(
                        'creada',
                        'grabando',
                        'pausada',
                        'finalizada',
                        'procesando',
                        'completada',
                        'cerrada'
                      )            NOT NULL DEFAULT 'creada',
  `ruta_audio`        VARCHAR(500)          DEFAULT NULL,    -- Ruta física en el VPS (se limpia al aprobar)
  `resultado_final`   LONGTEXT              DEFAULT NULL,    -- Markdown generado por Gemini
  `fecha_creacion`    DATETIME     NOT NULL DEFAULT NOW(),
  `fecha_finalizada`  DATETIME              DEFAULT NULL,    -- Cuando se presiona Finalizar
  `fecha_completada`  DATETIME              DEFAULT NULL,    -- Cuando Gemini termina
  `fecha_aprobada`    DATETIME              DEFAULT NULL,    -- Cuando se aprueba en el ERP
  `audio_borrado`     TINYINT(1)   NOT NULL DEFAULT 0,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  INDEX `idx_creado_por` (`creado_por`),
  INDEX `idx_estado`     (`estado`),
  INDEX `idx_token`      (`token`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registros de reuniones grabadas y resumidas con IA';

-- -----------------------------------------------------------------------------
-- 2. Registro en el sistema de permisos del ERP
-- -----------------------------------------------------------------------------

-- Registrar la herramienta
INSERT INTO `tools_erp` (
  `nombre`,
  `titulo`,
  `tipo_componente`,
  `class_name`,
  `config_json`,
  `grupo`,
  `descripcion`,
  `url_real`,
  `url_alias`,
  `icono`,
  `orden`,
  `activo`
)
VALUES (
  'resumen_reuniones_ia',
  'Resumen de Reuniones IA',
  'herramienta',
  NULL,
  NULL,
  'sistemas',
  'Grabar, transcribir y resumir reuniones corporativas usando Inteligencia Artificial.',
  '/modulos/sistemas/resumen_reuniones.php',
  'resumen-reuniones-ia',
  'bi bi-mic-fill',
  0,
  1
)
ON DUPLICATE KEY UPDATE
  `titulo` = VALUES(`titulo`),
  `tipo_componente` = VALUES(`tipo_componente`),
  `class_name` = VALUES(`class_name`),
  `config_json` = VALUES(`config_json`),
  `grupo` = VALUES(`grupo`),
  `descripcion` = VALUES(`descripcion`),
  `url_real` = VALUES(`url_real`),
  `url_alias` = VALUES(`url_alias`),
  `icono` = VALUES(`icono`),
  `orden` = VALUES(`orden`),
  `activo` = VALUES(`activo`);

-- Registrar la acción 'vista' para esta herramienta
INSERT INTO `acciones_tools_erp` (`tool_erp_id`, `nombre_accion`)
SELECT t.`id`, 'vista'
FROM   `tools_erp` t
WHERE  t.`nombre` = 'resumen_reuniones_ia'
  AND  NOT EXISTS (
    SELECT 1
    FROM   `acciones_tools_erp` a
    WHERE  a.`tool_erp_id` = t.`id`
      AND  a.`nombre_accion` = 'vista'
  );
