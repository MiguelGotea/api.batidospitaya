-- =============================================================
-- Script: Crear tablas msaccess_masivo_Procesamiento
--                       msaccess_masivo_Porcionamiento
--                       msaccess_masivo_SubPorcionamiento
-- Fecha:  2026-08-21
-- Origen: Access -> MySQL (sync kardex)
-- =============================================================

-- -------------------------------------------------------------
-- Tabla: msaccess_masivo_Procesamiento
-- Campos Access: CodProcesamiento (PK), CodCotizacion, Cantidad,
--                MedidaInicial, MedidaFinal, Fecha, Observaciones, Operario
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `msaccess_masivo_Procesamiento` (
    `Sucursal`         INT            NOT NULL,
    `CodProcesamiento` INT            NOT NULL,
    `CodCotizacion`    INT            DEFAULT NULL,
    `Cantidad`         DECIMAL(15,4)  DEFAULT NULL,
    `MedidaInicial`    DECIMAL(15,4)  DEFAULT NULL,
    `MedidaFinal`      DECIMAL(15,4)  DEFAULT NULL,
    `Fecha`            DATE           DEFAULT NULL,
    `Observaciones`    VARCHAR(255)   DEFAULT NULL,
    `Operario`         INT            DEFAULT NULL,
    `FechaUltimoSync`  DATETIME       DEFAULT NULL,
    PRIMARY KEY (`Sucursal`, `CodProcesamiento`),
    INDEX `idx_proc_fecha`    (`Fecha`),
    INDEX `idx_proc_sucursal` (`Sucursal`),
    INDEX `idx_proc_codcot`   (`CodCotizacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sync Access->MySQL tabla Procesamiento';

-- -------------------------------------------------------------
-- Tabla: msaccess_masivo_Porcionamiento
-- Campos Access: CodPorcionamiento (PK), CodCotizacion, CodProcesamiento,
--                Cantidad, Observaciones, Fecha, CodOperario,
--                Procedencia, CodSubPorcionamiento, HInicial, HFinal
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `msaccess_masivo_Porcionamiento` (
    `Sucursal`             INT            NOT NULL,
    `CodPorcionamiento`    INT            NOT NULL,
    `CodCotizacion`        INT            DEFAULT NULL,
    `CodProcesamiento`     INT            DEFAULT NULL,
    `Cantidad`             DECIMAL(15,4)  DEFAULT NULL,
    `Observaciones`        VARCHAR(255)   DEFAULT NULL,
    `Fecha`                DATE           DEFAULT NULL,
    `CodOperario`          INT            DEFAULT NULL,
    `Procedencia`          INT            DEFAULT NULL,
    `CodSubPorcionamiento` INT            DEFAULT NULL,
    `HInicial`             DATETIME       DEFAULT NULL,
    `HFinal`               DATETIME       DEFAULT NULL,
    `FechaUltimoSync`      DATETIME       DEFAULT NULL,
    PRIMARY KEY (`Sucursal`, `CodPorcionamiento`),
    INDEX `idx_po_fecha`        (`Fecha`),
    INDEX `idx_po_sucursal`     (`Sucursal`),
    INDEX `idx_po_codcot`       (`CodCotizacion`),
    INDEX `idx_po_codproc`      (`CodProcesamiento`),
    INDEX `idx_po_codsub`       (`CodSubPorcionamiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sync Access->MySQL tabla Porcionamiento';


-- -------------------------------------------------------------
-- Tabla: msaccess_masivo_SubPorcionamiento
-- Campos Access: CodSubPorcionamiento (PK), Procedencia, CodProcesamiento,
--                Cantidad, Fecha, HInicial, HFinal, CodOperario
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `msaccess_masivo_SubPorcionamiento` (
    `Sucursal`             INT            NOT NULL,
    `CodSubPorcionamiento` INT            NOT NULL,
    `Procedencia`          INT            DEFAULT NULL,
    `CodProcesamiento`     INT            DEFAULT NULL,
    `Cantidad`             DECIMAL(15,4)  DEFAULT NULL,
    `Fecha`                DATE           DEFAULT NULL,
    `HInicial`             DATETIME       DEFAULT NULL,
    `HFinal`               DATETIME       DEFAULT NULL,
    `CodOperario`          INT            DEFAULT NULL,
    `FechaUltimoSync`      DATETIME       DEFAULT NULL,
    PRIMARY KEY (`Sucursal`, `CodSubPorcionamiento`),
    INDEX `idx_spo_fecha`       (`Fecha`),
    INDEX `idx_spo_sucursal`    (`Sucursal`),
    INDEX `idx_spo_codproc`     (`CodProcesamiento`),
    INDEX `idx_spo_procedencia` (`Procedencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sync Access->MySQL tabla SubPorcionamiento';
