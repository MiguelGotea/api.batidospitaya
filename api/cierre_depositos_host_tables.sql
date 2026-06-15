-- =====================================================
-- Tablas de Cierre Diario y Depósitos en Host
-- Nomenclatura: msaccess_masivo_[NombreOriginalTabla]
-- Creado: 2026-06-15
--
-- PK interna: id_host (AUTO_INCREMENT)
-- Unicidad:   UNIQUE KEY (Sucursal, CodPK_local)
-- Columnas extra vs Access: id_host, Sucursal, FechaUltimoSync
-- =====================================================


-- ─────────────────────────────────────────────────────
-- 1. CierreDiario
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_CierreDiario` (
    `id_host`            INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`           INT          NOT NULL,
    `CodigoCierre`       INT          NOT NULL,
    `HoraInicial`        DATETIME         DEFAULT NULL,
    `HoraFinal`          DATETIME         DEFAULT NULL,
    `Fecha`              DATETIME         DEFAULT NULL,
    `CodOperario`        INT              DEFAULT NULL,
    `MFCor`              DOUBLE           DEFAULT NULL,
    `MFDol`              DOUBLE           DEFAULT NULL,
    `Faltante`           INT              DEFAULT NULL,
    `TotalHugo`          DOUBLE           DEFAULT NULL,
    `TotalPedidosYa`     DOUBLE           DEFAULT NULL,
    `TotalTransferencia` DOUBLE           DEFAULT NULL,
    `TotalPOS`           DOUBLE           DEFAULT NULL,
    `Observaciones`      VARCHAR(255)     DEFAULT NULL,
    `FechaUltimoSync`    DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod` (`Sucursal`, `CodigoCierre`),
    INDEX `idx_fecha`       (`Fecha`),
    INDEX `idx_sucursal`    (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────
-- 2. Depositos
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_Depositos` (
    `id_host`         INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`        INT          NOT NULL,
    `CodDeposito`     INT          NOT NULL,
    `Monto`           INT              DEFAULT NULL,
    `Denominacion`    VARCHAR(255)     DEFAULT NULL,
    `Tipo`            VARCHAR(255)     DEFAULT NULL,
    `Fecha`           DATE             DEFAULT NULL,
    `Observacion`     VARCHAR(255)     DEFAULT NULL,
    `DuranteTurno`    TINYINT          DEFAULT NULL,
    `FechaUltimoSync` DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod` (`Sucursal`, `CodDeposito`),
    INDEX `idx_fecha`       (`Fecha`),
    INDEX `idx_sucursal`    (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
