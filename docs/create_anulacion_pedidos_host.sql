-- ============================================================
-- Tabla: AnulacionPedidosHost
-- Base de datos: u839374897_erp (MySQL host)
-- Propósito: Centralizar solicitudes de anulación de todas
--            las sucursales para gestión web y retorno a Access.
-- ============================================================

CREATE TABLE IF NOT EXISTS `AnulacionPedidosHost` (
  `CodAnulacionHost`     INT UNSIGNED     NOT NULL AUTO_INCREMENT,

  -- Identificación del pedido y tienda
  `CodPedido`            INT UNSIGNED     NOT NULL,
  `Sucursal`             TINYINT UNSIGNED NOT NULL,

  -- Datos de la solicitud (vienen de Access)
  `HoraSolicitada`       DATETIME         DEFAULT NULL,
  `HoraAnulada`          DATETIME         DEFAULT NULL,
  `Modalidad`            TINYINT          DEFAULT 1          COMMENT '1=anulado en tienda, 2=por web/telegram',
  `CodPedidoCambio`      INT UNSIGNED     DEFAULT 0,
  `Motivo`               VARCHAR(500)     DEFAULT NULL,
  `CodMotivoAnulacion`   INT UNSIGNED     DEFAULT NULL,

  -- Gestión de aprobación (desde la web ERP)
  `Status`               TINYINT          NOT NULL DEFAULT 0  COMMENT '0=pendiente, 1=aprobado, 2=rechazado',
  `ComentarioAprobacion` VARCHAR(500)     DEFAULT NULL,
  `AprobadoPor`          VARCHAR(100)     DEFAULT NULL,
  `FechaAprobacion`      DATETIME         DEFAULT NULL,

  -- Confirmación de ejecución en tienda (Access confirma tras ejecutar)
  `EjecutadoEnTienda`    TINYINT(1)       NOT NULL DEFAULT 0,
  `HoraEjecutadaTienda`  DATETIME         DEFAULT NULL,

  -- Control de sync
  `FechaUltimoSync`      DATETIME         DEFAULT NULL       COMMENT 'Última vez que Access hizo sync',
  `FechaCreacion`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`CodAnulacionHost`),
  UNIQUE KEY `uq_pedido_sucursal` (`CodPedido`, `Sucursal`),
  INDEX `idx_status`    (`Status`),
  INDEX `idx_sucursal`  (`Sucursal`),
  INDEX `idx_codpedido` (`CodPedido`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Solicitudes de anulación centralizadas desde todas las sucursales';

-- ============================================================
-- Tabla de log de sync en Access (opcional, si la quieren local)
-- ============================================================
-- En Access creen una tabla: AnulacionSyncLog
-- Campos: FechaHora(Fecha/Hora), Origen(Texto 100), Mensaje(Texto 500)
-- El módulo VBA la usa si existe, si no la ignora silenciosamente.
-- ============================================================
