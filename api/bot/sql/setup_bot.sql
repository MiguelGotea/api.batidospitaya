-- ═══════════════════════════════════════════════════════════════════
--  PitayaBot — Setup BD (Etapa 1)
--  Ejecutar manualmente en la BD de Hostinger (u839374897_erp)
--
--  ⚠️  NO ejecutar como script PHP. Solo ejecución manual por el DBA.
--  Fecha de creación: 2026-03-23
-- ═══════════════════════════════════════════════════════════════════


-- ── 1. Tabla: Estado de confirmación pendiente por usuario ────────

CREATE TABLE IF NOT EXISTS bot_estado_confirmacion (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  cod_operario    INT          NOT NULL,
  celular         VARCHAR(20)  NOT NULL,
  intent          VARCHAR(60)  NOT NULL,
  payload         JSON         NOT NULL        COMMENT 'Entidades extraídas por IA',
  frase_resumen   TEXT                         COMMENT 'Texto que se mostró al usuario para confirmar',
  paso_actual     VARCHAR(50)  DEFAULT 'esperando_confirmacion',
  datos_parciales JSON                         COMMENT 'Para flujos multi-paso',
  creado_en       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  expira_en       DATETIME     NOT NULL,
  -- Índice único por celular: solo puede haber un estado pendiente activo por usuario
  UNIQUE KEY uk_celular (celular),
  INDEX idx_operario (cod_operario),
  INDEX idx_expira   (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Estado de confirmación pendiente de PitayaBot por usuario';


-- ── 2. Tabla: Log de operaciones del bot ─────────────────────────

CREATE TABLE IF NOT EXISTS bot_operaciones_log (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  cod_operario    INT,
  celular         VARCHAR(20),
  intent          VARCHAR(60),
  mensaje_entrada TEXT,
  respuesta_bot   TEXT,
  exitoso         TINYINT(1)   DEFAULT 1,
  error_detalle   TEXT,
  duracion_ms     INT,
  creado_en       DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_operario  (cod_operario),
  INDEX idx_intent    (intent),
  INDEX idx_fecha     (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log de todas las interacciones con PitayaBot';


-- ── 3. Columnas nuevas en Operarios (configuración del bot) ───────
--  Ejecutar cada ALTER individualmente si alguno ya existe.

ALTER TABLE Operarios
  ADD COLUMN IF NOT EXISTS bot_github_token      VARCHAR(500)  DEFAULT NULL
    COMMENT 'Token GitHub cifrado AES para vault Obsidian',
  ADD COLUMN IF NOT EXISTS bot_github_repo        VARCHAR(200)  DEFAULT NULL
    COMMENT 'Repositorio Obsidian: owner/repo',
  ADD COLUMN IF NOT EXISTS bot_github_branch      VARCHAR(50)   DEFAULT 'main'
    COMMENT 'Rama del vault de Obsidian',
  ADD COLUMN IF NOT EXISTS bot_github_vault_folder VARCHAR(100) DEFAULT ''
    COMMENT 'Carpeta raíz dentro del vault (vacío = raíz)',
  ADD COLUMN IF NOT EXISTS bot_imap_host          VARCHAR(100)  DEFAULT NULL
    COMMENT 'Host IMAP para búsqueda de correos del operario',
  ADD COLUMN IF NOT EXISTS bot_imap_port          INT           DEFAULT 993
    COMMENT 'Puerto IMAP (993 SSL)',
  ADD COLUMN IF NOT EXISTS bot_activo             TINYINT(1)    DEFAULT 0
    COMMENT '1 = tiene permiso PitayaBot activo';


-- ── 4. Columna ics_uid en tabla de reuniones ──────────────────────

ALTER TABLE gestion_tareas_reuniones_items
  ADD COLUMN IF NOT EXISTS ics_uid VARCHAR(100) DEFAULT NULL
    COMMENT 'UID único del evento ICS para modificar/cancelar desde calendario';


-- ── 5. Para activar el bot a un operario específico ───────────────
--  Ejecutar manualmente por cada usuario que debe tener acceso:
--
--  UPDATE Operarios SET bot_activo = 1 WHERE CodOperario = <id>;
--
--  Para desactivar:
--  UPDATE Operarios SET bot_activo = 0 WHERE CodOperario = <id>;


-- ── Fin del script ────────────────────────────────────────────────
