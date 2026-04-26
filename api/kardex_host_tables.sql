-- =====================================================
-- Tablas unificadas del Kardex de Productos en Host
-- Nomenclatura: msaccess_masivo_[NombreOriginalTabla]
-- Creado: 2026-04-26
--
-- PK interna: id_host (AUTO_INCREMENT)
-- Unicidad:   UNIQUE KEY (Sucursal, CodPK_local)
-- Columnas extra vs Access: id_host, Sucursal, FechaUltimoSync
-- =====================================================


-- ─────────────────────────────────────────────────────
-- 1. Inventario Cotizacion
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_InventarioCotizacion` (
    `id_host`         INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`        INT          NOT NULL,
    `CodICotizacion`  INT          NOT NULL,
    `CodCotizacion`   INT              DEFAULT NULL,
    `Cantidad`        DOUBLE           DEFAULT NULL,
    `Fecha`           DATE             DEFAULT NULL,
    `lista`           INT              DEFAULT NULL,
    `CodOperario`     INT              DEFAULT NULL,
    `primerenvio`     DOUBLE           DEFAULT NULL,
    `segundoenvio`    DOUBLE           DEFAULT NULL,
    `cantidadunidad`  DOUBLE           DEFAULT NULL,
    `cantidadpaquete` DOUBLE           DEFAULT NULL,
    `FechaUltimoSync` DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod`  (`Sucursal`, `CodICotizacion`),
    INDEX `idx_fecha`        (`Fecha`),
    INDEX `idx_sucursal`     (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────
-- 2. AjustesInventario
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_AjustesInventario` (
    `id_host`              INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`             INT          NOT NULL,
    `CodAjustesInventario` INT          NOT NULL,
    `CodCotizacion`        INT              DEFAULT NULL,
    `Cantidad`             DOUBLE           DEFAULT NULL,
    `Fecha`                DATE             DEFAULT NULL,
    `Observacion`          VARCHAR(255)     DEFAULT NULL,
    `FechaUltimoSync`      DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod` (`Sucursal`, `CodAjustesInventario`),
    INDEX `idx_fecha`       (`Fecha`),
    INDEX `idx_sucursal`    (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────
-- 3. Compras
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_Compras` (
    `id_host`           INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`          INT          NOT NULL,
    `CodIngresoAlmacen` INT          NOT NULL,
    `CodCotizacion`     INT              DEFAULT NULL,
    `Cantidad`          DOUBLE           DEFAULT NULL,
    `Fecha`             DATE             DEFAULT NULL,
    `CostoTotal`        DOUBLE           DEFAULT NULL,
    `Observaciones`     VARCHAR(255)     DEFAULT NULL,
    `CodProveedor`      INT              DEFAULT NULL,
    `Destino`           VARCHAR(255)     DEFAULT NULL,
    `Tipo`              VARCHAR(255)     DEFAULT NULL,
    `Pagado`            DATE             DEFAULT NULL,
    `NumeroFactura`     VARCHAR(255)     DEFAULT NULL,
    `CodOperario`       INT              DEFAULT NULL,
    `Ingresado`         TINYINT          DEFAULT NULL,
    `Lote`              DOUBLE           DEFAULT NULL,
    `Peso`              DOUBLE           DEFAULT NULL,
    `FechaUltimoSync`   DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod` (`Sucursal`, `CodIngresoAlmacen`),
    INDEX `idx_fecha`       (`Fecha`),
    INDEX `idx_sucursal`    (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────
-- 4. Merma Cotizacion
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_MermaCotizacion` (
    `id_host`         INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`        INT          NOT NULL,
    `CodMermaUnidad`  INT          NOT NULL,
    `CodCotizacion`   INT              DEFAULT NULL,
    `Cantidad`        DOUBLE           DEFAULT NULL,
    `Fecha`           DATE             DEFAULT NULL,
    `Observacion`     VARCHAR(255)     DEFAULT NULL,
    `CodIncidencia`   INT              DEFAULT NULL,
    `Operario`        INT              DEFAULT NULL,
    `FechaUltimoSync` DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod` (`Sucursal`, `CodMermaUnidad`),
    INDEX `idx_fecha`       (`Fecha`),
    INDEX `idx_sucursal`    (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────
-- 5. PreIngresoPitaya   (solo sistema central, codigoLocal()=0)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_PreIngresoPitaya` (
    `id_host`             INT          NOT NULL AUTO_INCREMENT,
    `Sucursal`            INT          NOT NULL,
    `CodPreIngresoPitaya` INT          NOT NULL,
    `Fecha`               DATE             DEFAULT NULL,
    `Hora`                TIME             DEFAULT NULL,
    `Destino`             VARCHAR(255)     DEFAULT NULL,
    `Validado`            TINYINT          DEFAULT NULL,
    `Impreso`             TINYINT          DEFAULT NULL,
    `FechaUltimoSync`     DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod` (`Sucursal`, `CodPreIngresoPitaya`),
    INDEX `idx_fecha`       (`Fecha`),
    INDEX `idx_sucursal`    (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────
-- 6. SubPreIngresosPitaya   (solo sistema central, codigoLocal()=0)
--    Filtro de 30 días: JOIN con msaccess_masivo_PreIngresoPitaya
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `msaccess_masivo_SubPreIngresosPitaya` (
    `id_host`                INT     NOT NULL AUTO_INCREMENT,
    `Sucursal`               INT     NOT NULL,
    `CodSubPreIngresoPitaya` INT     NOT NULL,
    `CodCotizacion`          INT         DEFAULT NULL,
    `Cantidad`               DOUBLE      DEFAULT NULL,
    `CodPreIngresoPitaya`    INT         DEFAULT NULL,
    `alerta`                 TINYINT     DEFAULT NULL,
    `FechaUltimoSync`        DATETIME    DEFAULT NULL,
    PRIMARY KEY (`id_host`),
    UNIQUE KEY `uk_suc_cod`  (`Sucursal`, `CodSubPreIngresoPitaya`),
    INDEX `idx_cod_pre`      (`CodPreIngresoPitaya`),
    INDEX `idx_sucursal`     (`Sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
