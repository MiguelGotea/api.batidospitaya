-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 12, 2026 at 05:36 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u839374897_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `acciones_tools_erp`
--

CREATE TABLE `acciones_tools_erp` (
  `id` int(11) NOT NULL,
  `tool_erp_id` int(11) NOT NULL,
  `nombre_accion` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Adelantos`
--

CREATE TABLE `Adelantos` (
  `CodAdelanto` int(11) NOT NULL,
  `monto_total` decimal(10,2) DEFAULT NULL,
  `cod_operario` int(11) DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Adelantos prueba';

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `branches` text NOT NULL,
  `branch` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `modified_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Referencia a Operarios.CodOperario',
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_branches`
--

CREATE TABLE `announcement_branches` (
  `announcement_id` int(11) NOT NULL,
  `branch_id` varchar(10) NOT NULL COMMENT 'Contendrá el código de sucursal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_views`
--

CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'CodOperario del usuario',
  `viewed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ArchivosAdjuntos`
--

CREATE TABLE `ArchivosAdjuntos` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `cod_contrato_asociado` int(11) DEFAULT NULL,
  `cod_adendum_asociado` int(11) DEFAULT NULL,
  `id_tipo_documento` int(11) DEFAULT NULL,
  `obligatorio` tinyint(1) DEFAULT 0,
  `categoria` varchar(50) DEFAULT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tamaño` int(11) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `cod_usuario_subio` int(11) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp(),
  `pestaña` varchar(50) DEFAULT NULL,
  `tipo_documento` varchar(100) DEFAULT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL COMMENT 'Fecha de vencimiento de documento si es que aplica'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archivos_presentacion_producto`
--

CREATE TABLE `archivos_presentacion_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre descriptivo del archivo',
  `descripcion` varchar(500) DEFAULT NULL,
  `id_presentacion_producto` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `ruta` varchar(500) NOT NULL COMMENT 'Ruta del archivo en servidor',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos adjuntos a productos (PDFs, Excel, etc)';

-- --------------------------------------------------------

--
-- Table structure for table `AsignacionNivelesCargos`
--

CREATE TABLE `AsignacionNivelesCargos` (
  `CodAsignacionNivelesCargos` int(11) NOT NULL,
  `CodOperario` int(11) DEFAULT NULL,
  `CodNivelesCargos` int(11) DEFAULT NULL,
  `Fecha` date DEFAULT NULL COMMENT 'Fecha de inicio',
  `Fin` date DEFAULT NULL COMMENT 'Fecha final de operario',
  `Sucursal` int(11) DEFAULT NULL,
  `CodTipoContrato` int(11) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de registro',
  `fecha_ultima_modificacion` datetime DEFAULT NULL,
  `usuario_ultima_modificacion` int(11) DEFAULT NULL,
  `cod_usuario_creador` int(11) DEFAULT NULL,
  `codigo_contrato_asociado` varchar(50) DEFAULT NULL,
  `Observaciones` varchar(255) DEFAULT NULL,
  `CodContrato` int(11) DEFAULT NULL,
  `TipoAdendum` enum('inicial','cargo','salario','ambos','movimiento') DEFAULT NULL,
  `Salario` decimal(10,2) DEFAULT NULL,
  `es_activo` tinyint(1) DEFAULT NULL,
  `FotoExamen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `sucursal` varchar(255) NOT NULL,
  `cod_sucursal` int(11) DEFAULT NULL,
  `persona` varchar(255) NOT NULL,
  `operario_id` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_1` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_2` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_3` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_4` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_5` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_6` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_7` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_8` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_9` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_10` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_11` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_12` int(11) DEFAULT NULL,
  `limpieza_exterior_1_1_13` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_1` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_2` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_3` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_4` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_5` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_6` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_7` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_8` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_9` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_10` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_11` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_12` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_13` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_14` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_15` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_16` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_17` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_18` int(11) DEFAULT NULL,
  `limpieza_interior_1_2_19` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_1` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_2` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_3` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_4` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_5` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_6` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_7` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_8` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_9` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_10` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_11` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_12` int(11) DEFAULT NULL,
  `limpieza_equipo_1_3_13` int(11) DEFAULT NULL,
  `limpieza_insumos_1_4_1` int(11) DEFAULT NULL,
  `limpieza_insumos_1_4_2` int(11) DEFAULT NULL,
  `limpieza_insumos_1_4_3` int(11) DEFAULT NULL,
  `limpieza_insumos_1_4_4` int(11) DEFAULT NULL,
  `limpieza_insumos_1_4_5` int(11) DEFAULT NULL,
  `limpieza_insumos_1_4_6` int(11) DEFAULT NULL,
  `promedio_exterior` decimal(5,2) DEFAULT 0.00,
  `promedio_interior` decimal(5,2) DEFAULT 0.00,
  `promedio_equipo` decimal(5,2) DEFAULT 0.00,
  `promedio_insumos` decimal(5,2) DEFAULT 0.00,
  `promedio_general` decimal(5,2) DEFAULT 0.00,
  `tipo_auditoria` varchar(50) NOT NULL DEFAULT 'limpieza',
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `comentarios` text DEFAULT NULL,
  `sucursal_codigo` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_caja_chica`
--

CREATE TABLE `auditoria_caja_chica` (
  `id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `sucursal_id` varchar(10) DEFAULT NULL,
  `sucursal` varchar(50) NOT NULL,
  `lider_tienda` varchar(100) NOT NULL,
  `lider_tienda_codigo` int(11) DEFAULT NULL,
  `monto_designado` decimal(10,2) NOT NULL,
  `total_conteo` decimal(10,2) NOT NULL,
  `faltante_sobrante` decimal(10,2) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_deduccion` timestamp NULL DEFAULT NULL COMMENT 'Fecha a deducir',
  `cod_contrato` int(11) DEFAULT NULL,
  `cobrado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_caja_chica_detalle`
--

CREATE TABLE `auditoria_caja_chica_detalle` (
  `id` int(11) NOT NULL,
  `auditoria_id` int(11) NOT NULL,
  `denominacion` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha_hora_regsys` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_facturacion`
--

CREATE TABLE `auditoria_facturacion` (
  `id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `sucursal_id` varchar(10) NOT NULL,
  `sucursal` varchar(50) NOT NULL,
  `cajero` int(11) DEFAULT NULL,
  `cajero_nombre` varchar(100) DEFAULT NULL,
  `monto_designado` decimal(10,2) NOT NULL,
  `total_conteo` decimal(10,2) NOT NULL,
  `faltante_sobrante` decimal(10,2) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto_path` varchar(255) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_deduccion` timestamp NULL DEFAULT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `cobrado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_fotos`
--

CREATE TABLE `auditoria_fotos` (
  `id` int(11) NOT NULL,
  `auditoria_id` int(11) NOT NULL,
  `ruta_foto` varchar(255) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Registro en sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_inventario`
--

CREATE TABLE `auditoria_inventario` (
  `id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `sucursal_id` varchar(10) NOT NULL,
  `sucursal` varchar(50) NOT NULL,
  `auditor` varchar(100) DEFAULT NULL,
  `participantes` text DEFAULT NULL,
  `total_faltante` decimal(10,2) DEFAULT 0.00,
  `comentarios` text DEFAULT NULL,
  `auditor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto_path` varchar(255) DEFAULT NULL,
  `foto_path_2` varchar(255) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_inventario_detalle`
--

CREATE TABLE `auditoria_inventario_detalle` (
  `id` int(11) NOT NULL,
  `auditoria_id` int(11) NOT NULL,
  `producto` varchar(255) NOT NULL,
  `inventario_fisico` int(11) NOT NULL,
  `inventario_sistema` int(11) NOT NULL,
  `diferencia` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_inventario_operarios`
--

CREATE TABLE `auditoria_inventario_operarios` (
  `id` int(11) NOT NULL,
  `auditoria_id` int(11) NOT NULL,
  `operario_id` int(11) NOT NULL,
  `operario_nombre` varchar(100) NOT NULL,
  `operario_cargo` varchar(100) NOT NULL,
  `fecha_deduccion` timestamp NULL DEFAULT NULL,
  `operario_categoria` varchar(255) DEFAULT NULL,
  `monto` double(10,2) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NULL DEFAULT current_timestamp(),
  `cod_contrato` int(11) DEFAULT NULL,
  `cobrado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_personal`
--

CREATE TABLE `auditoria_personal` (
  `id` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `sucursal` varchar(255) DEFAULT NULL,
  `cod_sucursal` int(11) DEFAULT NULL,
  `persona` varchar(255) DEFAULT NULL,
  `operario_id` int(11) DEFAULT NULL,
  `presentacion_personal_2_1` int(11) DEFAULT NULL,
  `presentacion_personal_2_2` int(11) DEFAULT NULL,
  `presentacion_personal_2_3` int(11) DEFAULT NULL,
  `presentacion_personal_2_4` int(11) DEFAULT NULL,
  `presentacion_personal_2_5` int(11) DEFAULT NULL,
  `presentacion_personal_2_6` int(11) DEFAULT NULL,
  `presentacion_personal_2_7` int(11) DEFAULT NULL,
  `presentacion_personal_2_8` varchar(10) DEFAULT NULL,
  `promedio_personal` decimal(5,2) DEFAULT 0.00,
  `tipo_auditoria` varchar(50) NOT NULL DEFAULT 'personal',
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `comentarios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_personal_fotos`
--

CREATE TABLE `auditoria_personal_fotos` (
  `id` int(11) NOT NULL,
  `auditoria_id` int(11) NOT NULL,
  `ruta_foto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_procesos`
--

CREATE TABLE `auditoria_procesos` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `sucursal_nombre` varchar(255) NOT NULL,
  `operario_id` int(11) NOT NULL,
  `operario_nombre` varchar(255) NOT NULL,
  `item_1` tinyint(4) DEFAULT 0,
  `item_2` tinyint(4) DEFAULT 0,
  `item_3` tinyint(4) DEFAULT 0,
  `item_4` tinyint(4) DEFAULT 0,
  `item_5` tinyint(4) DEFAULT 0,
  `item_6` tinyint(4) DEFAULT 0,
  `item_7` tinyint(4) DEFAULT 0,
  `item_8` tinyint(4) DEFAULT 0,
  `item_9` tinyint(4) DEFAULT 0,
  `item_10` tinyint(4) DEFAULT 0,
  `item_11` tinyint(4) DEFAULT 0,
  `item_12` tinyint(4) DEFAULT 0,
  `porcentaje_cumplimiento` int(11) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_promociones`
--

CREATE TABLE `auditoria_promociones` (
  `id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `sucursal_nombre` varchar(255) NOT NULL,
  `operario_id` int(11) NOT NULL,
  `operario_nombre` varchar(255) NOT NULL,
  `respuesta_1` text DEFAULT NULL,
  `respuesta_2` text DEFAULT NULL,
  `respuesta_3` text DEFAULT NULL,
  `respuesta_4` text DEFAULT NULL,
  `respuesta_5` text DEFAULT NULL,
  `porcentaje_cumplimiento` int(11) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_servicio`
--

CREATE TABLE `auditoria_servicio` (
  `id` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `sucursal` varchar(255) DEFAULT NULL,
  `cod_sucursal` int(11) DEFAULT NULL,
  `persona` varchar(255) DEFAULT NULL,
  `operario_id` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_1` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_2` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_3` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_4` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_5` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_6` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_7` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_8` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_9` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_10` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_11` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_12` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_13` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_14` int(11) DEFAULT NULL,
  `evaluacion_servicio_4_15` int(11) DEFAULT NULL,
  `promedio_calificacion` decimal(5,2) DEFAULT 0.00,
  `tipo_auditoria` varchar(50) NOT NULL DEFAULT 'servicio',
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `comentarios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auditoria_servicio_fotos`
--

CREATE TABLE `auditoria_servicio_fotos` (
  `id` int(11) NOT NULL,
  `auditoria_id` int(11) DEFAULT NULL,
  `ruta_foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `AutorizacionesEdicion`
--

CREATE TABLE `AutorizacionesEdicion` (
  `id` int(11) NOT NULL,
  `id_semana` int(11) NOT NULL,
  `cod_sucursal` int(11) NOT NULL,
  `autorizado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BitacoraColaborador`
--

CREATE TABLE `BitacoraColaborador` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `anotacion` text NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `cod_usuario_registro` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BoletaAguinaldo`
--

CREATE TABLE `BoletaAguinaldo` (
  `id_boleta_aguinaldo` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `cod_contrato` int(11) NOT NULL,
  `empleado_nombre` varchar(255) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_inicio_periodo` date NOT NULL,
  `fecha_final_periodo` date NOT NULL,
  `periodo_laborado_meses` decimal(5,2) NOT NULL,
  `salario_basico` decimal(10,2) NOT NULL,
  `total_aguinaldo` decimal(10,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `registrado_por` int(11) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BoletaPago`
--

CREATE TABLE `BoletaPago` (
  `id_boleta` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `empleado_nombre` varchar(150) NOT NULL,
  `salario_basico` decimal(10,2) NOT NULL,
  `fecha_planilla` date NOT NULL,
  `salario_basico_quincenal_dias` decimal(5,2) DEFAULT NULL,
  `salario_basico_quincenal_monto` decimal(10,2) DEFAULT NULL,
  `feriados_laborados_horas` decimal(5,2) DEFAULT NULL,
  `feriados_laborados_monto` decimal(10,2) DEFAULT NULL,
  `horas_extras_horas` decimal(5,2) DEFAULT NULL,
  `horas_extras_monto` decimal(10,2) DEFAULT NULL,
  `faltas_septimo_dia_dias` decimal(5,2) DEFAULT NULL,
  `faltas_septimo_dia_monto` decimal(10,2) DEFAULT NULL,
  `inss_empleado_porcentaje` decimal(5,2) DEFAULT NULL,
  `inss_empleado_monto` decimal(10,2) DEFAULT NULL,
  `vacaciones_dias` decimal(5,2) DEFAULT 0.00,
  `Deducciones` decimal(10,2) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `wsp_programacion_id` int(11) DEFAULT NULL COMMENT 'FK a wsp_planilla_programaciones_',
  `wsp_enviado` tinyint(1) NOT NULL DEFAULT 0,
  `wsp_error` varchar(500) DEFAULT NULL,
  `wsp_fecha_envio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_crons_config`
--

CREATE TABLE `bot_crons_config` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL COMMENT 'briefing_diario, recordatorio_reunion...',
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `horario` varchar(50) NOT NULL COMMENT 'Expresión cron: 0 7 * * 1-5',
  `activo` tinyint(1) DEFAULT 1,
  `ultima_ejecucion` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_estado_confirmacion`
--

CREATE TABLE `bot_estado_confirmacion` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `celular` varchar(20) NOT NULL,
  `intent` varchar(60) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Entidades extraídas por IA' CHECK (json_valid(`payload`)),
  `frase_resumen` text DEFAULT NULL COMMENT 'Texto que se mostró al usuario para confirmar',
  `paso_actual` varchar(50) DEFAULT 'esperando_confirmacion',
  `datos_parciales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Para flujos multi-paso' CHECK (json_valid(`datos_parciales`)),
  `creado_en` datetime DEFAULT current_timestamp(),
  `expira_en` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estado de confirmación pendiente de PitayaBot por usuario';

-- --------------------------------------------------------

--
-- Table structure for table `bot_intents`
--

CREATE TABLE `bot_intents` (
  `id` int(11) NOT NULL,
  `intent_name` varchar(100) NOT NULL COMMENT 'Ej: saludo, precio, horario, queja',
  `keywords` text DEFAULT NULL COMMENT 'Palabras clave separadas por coma',
  `response_templates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array de strings: variantes de respuesta' CHECK (json_valid(`response_templates`)),
  `priority` int(11) NOT NULL DEFAULT 1 COMMENT 'Mayor = más prioridad',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `instancia` varchar(30) DEFAULT NULL COMMENT 'NULL = aplica a todas las instancias',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `media_url` varchar(255) DEFAULT NULL COMMENT 'URL pública de la imagen o documento a enviar (ej: https://erp.batidospitaya.com/menu.pdf)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_operaciones_log`
--

CREATE TABLE `bot_operaciones_log` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `intent` varchar(60) DEFAULT NULL,
  `mensaje_entrada` text DEFAULT NULL,
  `respuesta_bot` text DEFAULT NULL,
  `exitoso` tinyint(1) DEFAULT 1,
  `error_detalle` text DEFAULT NULL,
  `duracion_ms` int(11) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de todas las interacciones con PitayaBot';

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `codigo` int(11) DEFAULT NULL COMMENT 'Código de la sucursal en la empresa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `caja_chica_sucursales`
--

CREATE TABLE `caja_chica_sucursales` (
  `id` int(11) NOT NULL,
  `sucursal_id` varchar(10) DEFAULT NULL,
  `monto_designado` decimal(10,2) NOT NULL,
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `CategoriasOperarios`
--

CREATE TABLE `CategoriasOperarios` (
  `idCategoria` int(11) NOT NULL,
  `NombreCategoria` varchar(50) NOT NULL,
  `Peso` decimal(3,1) NOT NULL,
  `color` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categoria_producto_maestro`
--

CREATE TABLE `categoria_producto_maestro` (
  `id` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `CentroCostos`
--

CREATE TABLE `CentroCostos` (
  `CodCentroCostos` int(11) NOT NULL,
  `Codigo` int(11) DEFAULT NULL,
  `CodigoTexto` varchar(255) DEFAULT NULL,
  `Nombre` varchar(255) DEFAULT NULL,
  `Clase` varchar(255) DEFAULT NULL,
  `Grupo` int(11) DEFAULT NULL,
  `Activo` tinyint(4) DEFAULT NULL,
  `Sector` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_registro` date DEFAULT curdate(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clientesclub`
--

CREATE TABLE `clientesclub` (
  `id_clienteclub` int(11) NOT NULL,
  `sucursal` int(11) NOT NULL,
  `membresia` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `cedula` varchar(20) DEFAULT NULL,
  `celular` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `puntos_iniciales` int(11) DEFAULT NULL,
  `nombre_sucursal` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `componentes_receta_producto`
--

CREATE TABLE `componentes_receta_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre descriptivo del componente',
  `id_receta_producto_global` int(11) NOT NULL COMMENT 'FK a receta_producto_global',
  `id_presentacion_producto` int(11) NOT NULL COMMENT 'FK a producto_presentacion (ingrediente)',
  `cantidad` decimal(10,4) NOT NULL COMMENT 'Cantidad del ingrediente',
  `orden` int(11) NOT NULL DEFAULT 0 COMMENT 'Orden de aparición en la lista',
  `indice` varchar(50) DEFAULT NULL COMMENT 'Índice o código del paso',
  `notas` varchar(500) DEFAULT NULL COMMENT 'Instrucciones adicionales',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ingredientes/componentes de cada receta - NO permite recursión (validado en PHP)';

-- --------------------------------------------------------

--
-- Table structure for table `compra_local_configuracion_despacho`
--

CREATE TABLE `compra_local_configuracion_despacho` (
  `id` int(11) NOT NULL,
  `id_producto_presentacion` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `id_perfil` int(11) DEFAULT NULL,
  `codigo_sucursal` varchar(10) NOT NULL COMMENT 'FK a sucursales',
  `dia_entrega` tinyint(4) NOT NULL COMMENT '1=Lun, 2=Mar, 3=Mié, 4=Jue, 5=Vie, 6=Sáb, 7=Dom',
  `is_delivery` tinyint(1) DEFAULT 0,
  `status` enum('activo','inactivo') DEFAULT 'activo' COMMENT 'Estado de la configuración',
  `base_consumption` decimal(10,2) DEFAULT 0.00,
  `lead_time_days` int(11) DEFAULT 0,
  `shelf_life_days` int(11) DEFAULT 7,
  `event_factor` decimal(10,2) DEFAULT 1.00,
  `pedido_minimo` int(11) DEFAULT 1 COMMENT 'Cantidad mínima de pedido para este producto y sucursal',
  `usuario_creacion` int(11) DEFAULT NULL COMMENT 'Usuario que creó el registro',
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'Usuario que modificó el registro',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración de días de entrega habilitados por producto y sucursal';

-- --------------------------------------------------------

--
-- Table structure for table `compra_local_pedidos_historico`
--

CREATE TABLE `compra_local_pedidos_historico` (
  `id` int(11) NOT NULL,
  `id_producto_presentacion` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `codigo_sucursal` varchar(10) NOT NULL COMMENT 'FK a sucursales',
  `fecha_entrega` date NOT NULL COMMENT 'Fecha específica de entrega del pedido',
  `cantidad_pedido` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Cantidad solicitada',
  `usuario_registro` int(11) DEFAULT NULL COMMENT 'Usuario que registró el pedido',
  `fecha_hora_reportada` timestamp NULL DEFAULT NULL COMMENT 'Última modificación del pedido',
  `notas` text DEFAULT NULL COMMENT 'Notas adicionales sobre el pedido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial completo de pedidos con fechas específicas de entrega';

-- --------------------------------------------------------

--
-- Table structure for table `compra_local_perfiles_despacho`
--

CREATE TABLE `compra_local_perfiles_despacho` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `frecuencia_semanas` int(11) NOT NULL DEFAULT 1 COMMENT '1=Semanal, 2=Quincenal, 4=Mensual',
  `lunes` tinyint(1) DEFAULT 0,
  `martes` tinyint(1) DEFAULT 0,
  `miercoles` tinyint(1) DEFAULT 0,
  `jueves` tinyint(1) DEFAULT 0,
  `viernes` tinyint(1) DEFAULT 0,
  `sabado` tinyint(1) DEFAULT 0,
  `domingo` tinyint(1) DEFAULT 0,
  `semana_referencia` int(11) DEFAULT NULL COMMENT 'Número de semana (SemanasSistema) de inicio de ciclo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ContactosEmergencia`
--

CREATE TABLE `ContactosEmergencia` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `nombre_contacto` varchar(100) NOT NULL,
  `parentesco` varchar(50) NOT NULL,
  `telefono_movil` varchar(20) NOT NULL,
  `telefono_casa` varchar(20) DEFAULT NULL,
  `telefono_trabajo` varchar(20) DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contacto_proveedores`
--

CREATE TABLE `contacto_proveedores` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Contratos`
--

CREATE TABLE `Contratos` (
  `CodContrato` int(11) NOT NULL,
  `cod_tipo_contrato` int(11) DEFAULT NULL,
  `codigo_manual_contrato` varchar(50) DEFAULT NULL,
  `cod_operario` int(11) DEFAULT NULL,
  `inicio_contrato` date DEFAULT NULL,
  `fin_contrato` date DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `CodSalario` int(11) DEFAULT NULL,
  `monto_contrato` decimal(10,2) DEFAULT NULL,
  `fecha_salida` date DEFAULT NULL,
  `fecha_liquidacion` date DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `cod_tipo_salida` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `cod_sucursal_contrato` varchar(10) DEFAULT NULL,
  `salario_inicial` decimal(10,2) DEFAULT NULL,
  `frecuencia_pago` varchar(20) DEFAULT 'quincenal',
  `cod_usuario_creador` int(11) DEFAULT NULL,
  `CodAsignacionNivelesCargos` int(11) DEFAULT NULL,
  `foto_solicitud_renuncia` varchar(255) DEFAULT NULL,
  `devolucion_herramientas_trabajo` bit(1) DEFAULT NULL,
  `persona_recibe_herramientas_trabajo` varchar(255) DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp(),
  `dias_trabajados` int(11) DEFAULT NULL,
  `monto_indemnizacion` decimal(10,2) DEFAULT NULL,
  `numero_planilla` int(11) DEFAULT NULL,
  `hospital_inss` varchar(255) DEFAULT NULL,
  `fecha_ultima_modificacion` datetime DEFAULT NULL,
  `usuario_ultima_modificacion` int(11) DEFAULT NULL,
  `numero_nomina` int(11) DEFAULT 1 COMMENT 'Numero de nomina de una planilla',
  `Finalizado` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contratos_tiposDocumentos`
--

CREATE TABLE `contratos_tiposDocumentos` (
  `id` int(11) NOT NULL,
  `pestaña` varchar(50) NOT NULL COMMENT 'Pestaña/Categoría donde se muestra (e.g., contrato, inss)',
  `nombre_clave` varchar(100) NOT NULL COMMENT 'Identificador interno (e.g., cedula, contrato_firmado)',
  `nombre_descriptivo` varchar(255) NOT NULL COMMENT 'Nombre legible para el usuario',
  `es_obligatorio` tinyint(1) DEFAULT 0 COMMENT '1 si es requerido para completar el expediente',
  `activo` tinyint(1) DEFAULT 1 COMMENT '1 si el tipo de documento está vigente',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `inicial_postulacion` tinyint(1) DEFAULT 0 COMMENT '1 si es requerido como documento de solicitud de empleo',
  `tiene_vencimiento` tinyint(1) DEFAULT 0 COMMENT '1 si documento tiene fecha vencimiento',
  `tipo` varchar(100) NOT NULL COMMENT 'tipo documento o dato'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `instancia` varchar(30) NOT NULL COMMENT 'wsp-clientes, wsp-crmbot...',
  `numero_cliente` varchar(20) NOT NULL COMMENT 'Número del cliente que escribe',
  `numero_remitente` varchar(20) NOT NULL COMMENT 'Número actual en wsp_sesion_vps_ al momento',
  `status` enum('bot','humano') NOT NULL DEFAULT 'bot',
  `last_intent` varchar(100) DEFAULT NULL COMMENT 'Última intención detectada para contexto',
  `last_interaction_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversion_unidad_producto`
--

CREATE TABLE `conversion_unidad_producto` (
  `id` int(11) NOT NULL,
  `id_unidad_producto_inicio` int(11) NOT NULL COMMENT 'FK - Unidad de origen',
  `id_unidad_producto_final` int(11) NOT NULL COMMENT 'FK - Unidad de destino',
  `cantidad` decimal(10,4) NOT NULL COMMENT 'Factor de conversión (ej: 1 litro = 1000 ml)',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `Cotizaciones`
--

CREATE TABLE `Cotizaciones` (
  `CodCotizacion` int(11) DEFAULT NULL,
  `CodIngrediente` varchar(100) DEFAULT NULL,
  `Marca` varchar(100) DEFAULT NULL,
  `Linea` varchar(100) DEFAULT NULL,
  `Capacidad` varchar(100) DEFAULT NULL,
  `Conversion` double DEFAULT NULL,
  `Unidad` varchar(100) DEFAULT NULL,
  `Prioridad` tinyint(4) DEFAULT NULL COMMENT 'Presentaciones que salen en la lista de compras despacho (SOLO APLICA A VARIABLES)',
  `Subproducto` tinyint(4) DEFAULT NULL COMMENT 'Si es una medida de porcionado(si)',
  `PaquetePorciones` int(11) DEFAULT NULL COMMENT 'presentación para despacho',
  `TiempoOperativo` double DEFAULT NULL COMMENT 'meta de velocidad en producción',
  `CodAlmacenamiento` int(11) DEFAULT NULL COMMENT 'código de donde se almacena para calcular carga de despacho',
  `MezclaPorcion` tinyint(4) DEFAULT NULL COMMENT 'Compuesto en una sola porción con otro ingrediente',
  `ConversionEstandar` double DEFAULT NULL COMMENT 'Aplica conversion=0, dato de referencia, rendimiento meta',
  `PresentacionCompra` double DEFAULT NULL COMMENT 'Presentacion para compras',
  `Descontinuado` tinyint(4) DEFAULT NULL COMMENT 'Productos que ya estan descontinuados',
  `CompraDirectaSucursal` tinyint(1) DEFAULT NULL COMMENT 'Productos habilitados que se compran directo en una sucursal',
  `OrdenInventario` int(11) DEFAULT NULL,
  `Especificaciones` varchar(100) DEFAULT NULL,
  `CodigoAlmacenDespacho` varchar(100) DEFAULT NULL COMMENT 'Codigo de estante y orden'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de cotización de ingredientes para producción y despacho';

-- --------------------------------------------------------

--
-- Table structure for table `CuentaBancaria`
--

CREATE TABLE `CuentaBancaria` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) DEFAULT NULL,
  `numero_cuenta` varchar(100) DEFAULT NULL,
  `titular` varchar(255) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `moneda` varchar(20) DEFAULT NULL,
  `desde` date DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cuenta_proveedor`
--

CREATE TABLE `cuenta_proveedor` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `numero_cuenta` varchar(100) NOT NULL,
  `titular` varchar(255) NOT NULL,
  `moneda` enum('Córdoba','Dólar') NOT NULL DEFAULT 'Córdoba',
  `banco` varchar(100) NOT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cupones_sucursales`
--

CREATE TABLE `cupones_sucursales` (
  `id` int(11) NOT NULL,
  `numero_cupon` varchar(255) NOT NULL,
  `fecha_caducidad` date NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `monto` int(11) NOT NULL,
  `aplicado` tinyint(1) DEFAULT 0,
  `cod_sucursal` int(11) DEFAULT NULL,
  `cod_pedido` int(11) DEFAULT NULL,
  `hora_activacion` datetime DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `DBBatidos`
--

CREATE TABLE `DBBatidos` (
  `CodBatido` varchar(100) DEFAULT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `CodGrupo` int(11) DEFAULT NULL,
  `Medida` varchar(100) DEFAULT NULL,
  `Precio` int(11) DEFAULT NULL,
  `Vigencia` int(11) DEFAULT NULL,
  `CodigoBarras` varchar(100) DEFAULT NULL,
  `Marca` varchar(100) DEFAULT NULL,
  `CompraVenta` int(11) DEFAULT NULL,
  `CodSubGrupo` int(11) DEFAULT NULL,
  `Endulzante` int(11) DEFAULT NULL,
  `Desde` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `DBIngredientes`
--

CREATE TABLE `DBIngredientes` (
  `CodIngrediente` varchar(100) DEFAULT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `NombreSinProcesar` varchar(100) DEFAULT NULL,
  `NombreProcesado` varchar(100) DEFAULT NULL,
  `NombreReceta` varchar(100) DEFAULT NULL,
  `Unidad` varchar(100) DEFAULT NULL,
  `OrdenListaControles` int(11) DEFAULT NULL,
  `ListaImportante` tinyint(4) DEFAULT NULL,
  `Inventario` tinyint(4) DEFAULT NULL,
  `Tipo` varchar(100) DEFAULT NULL,
  `TIPO1` varchar(100) DEFAULT NULL,
  `TIPO2` varchar(100) DEFAULT NULL,
  `compralocal` tinyint(4) DEFAULT NULL,
  `Consumible` tinyint(4) DEFAULT NULL,
  `Cocina` tinyint(4) DEFAULT NULL,
  `ComprasSemana` double DEFAULT NULL,
  `Vigente` tinyint(4) DEFAULT NULL,
  `ProovedorPrincipal` int(11) DEFAULT NULL,
  `ProovedorSecundario` int(11) DEFAULT NULL,
  `ConversionGramos` double DEFAULT NULL,
  `CUEstandar` double DEFAULT NULL,
  `CodCeCoSubCuentas` int(11) DEFAULT NULL,
  `CodControlAlmacenes` int(11) DEFAULT NULL,
  `presentacionpreparacion` varchar(100) DEFAULT NULL,
  `conversionpreparacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deducciones_operaciones`
--

CREATE TABLE `deducciones_operaciones` (
  `id` int(11) NOT NULL,
  `tipo_deduccion` enum('facturacion','caja_chica','inventario','faltante_inventario','faltante_danos') NOT NULL,
  `id_referencia` int(11) NOT NULL COMMENT 'ID en la tabla original',
  `operario_id` int(11) NOT NULL,
  `sucursal_id` varchar(10) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_evento` datetime NOT NULL,
  `fecha_deduccion` date DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_registro` int(11) DEFAULT NULL COMMENT 'Usuario que registró la deducción'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departamentos`
--

CREATE TABLE `departamentos` (
  `codigo` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `tiene_sucursal` tinyint(1) DEFAULT 0 COMMENT '¿Este departamento tiene sucursal?',
  `viatico_nocturno` decimal(10,2) DEFAULT NULL COMMENT 'Monto de viático nocturno. NULL = no aplica',
  `horario_nocturno_viatico` time DEFAULT NULL,
  `viatico_diurno` decimal(10,2) DEFAULT NULL,
  `horario_mananero_viatico` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diccionario_productos_legado`
--

CREATE TABLE `diccionario_productos_legado` (
  `id` int(11) NOT NULL,
  `CodIngrediente` varchar(100) NOT NULL COMMENT 'Código del ingrediente en el sistema antiguo (DBIngredientes)',
  `CodCotizacion` int(11) NOT NULL COMMENT 'ID de la presentación en el sistema antiguo (Cotizaciones)',
  `id_producto_presentacion` int(11) NOT NULL COMMENT 'FK a producto_presentacion.id en el nuevo ERP',
  `id_variedad_producto` int(11) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL COMMENT 'Observaciones opcionales del mapeo',
  `fecha_mapeo` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en que se realizó el mapeo',
  `usuario_mapeo` int(11) NOT NULL COMMENT 'FK a Operarios - quién realizó el mapeo',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'Última modificación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Diccionario de traducción: presentación antigua (Cotizaciones) → nuevo ERP (producto_presentacion)';

-- --------------------------------------------------------

--
-- Table structure for table `DVR_Sucursales`
--

CREATE TABLE `DVR_Sucursales` (
  `cod_sucursal` int(11) NOT NULL,
  `nombre_sucursal` varchar(255) DEFAULT NULL,
  `modelo` varchar(255) DEFAULT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `clave_dispositivo` varchar(255) DEFAULT NULL,
  `portal_ip_local` varchar(255) DEFAULT NULL,
  `portal_usuario` varchar(255) DEFAULT NULL,
  `portal_clave` varchar(255) DEFAULT NULL,
  `url_imagen` varchar(255) DEFAULT NULL,
  `capacidad` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `endulzantes`
--

CREATE TABLE `endulzantes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT NULL COMMENT 'Orden a mostrar en el dropdown de Endulzantes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `endulzantes_asignaciones`
--

CREATE TABLE `endulzantes_asignaciones` (
  `id` int(11) NOT NULL,
  `endulzante_id` int(11) NOT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `subgrupo_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `entrevistas_candidatos`
--

CREATE TABLE `entrevistas_candidatos` (
  `id` int(11) NOT NULL,
  `id_postulacion` int(11) NOT NULL COMMENT 'FK a postulacion_plaza',
  `fecha_entrevista` date NOT NULL COMMENT 'Fecha programada de entrevista',
  `hora_entrevista` time NOT NULL COMMENT 'Hora de la entrevista',
  `notas_entrevista` text DEFAULT NULL,
  `reclutador_entrevista` int(11) NOT NULL COMMENT 'CodOperario del entrevistador de RRHH',
  `modalidad_entrevista` varchar(100) NOT NULL COMMENT 'Virtual (Google Meet)/Presencial/Telefónica',
  `notas_adicionales` text DEFAULT NULL COMMENT 'Notas para el entrevistador o candidato',
  `resultado_entrevista` enum('pendiente','aprobado','rechazado','no_asistio') NOT NULL DEFAULT 'pendiente' COMMENT 'Resultado de la entrevista',
  `comentarios_resultado` text DEFAULT NULL COMMENT 'Comentarios del entrevistador sobre el resultado',
  `calificacion` tinyint(1) DEFAULT NULL COMMENT 'Calificación del 1-5',
  `usuario_registra` int(11) NOT NULL COMMENT 'CodOperario que programó la entrevista',
  `usuario_modifica` int(11) DEFAULT NULL COMMENT 'CodOperario que modifica',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Programación y seguimiento de entrevistas a candidatos';

-- --------------------------------------------------------

--
-- Table structure for table `extras`
--

CREATE TABLE `extras` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltante_caja`
--

CREATE TABLE `faltante_caja` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `sucursal` varchar(100) NOT NULL,
  `operario_id` int(11) NOT NULL,
  `operario_nombre` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_deduccion` date DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `registrador_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Restar 6 horas',
  `cod_contrato` int(11) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `cobrado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltante_danos`
--

CREATE TABLE `faltante_danos` (
  `id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `sucursal_codigo` int(11) NOT NULL,
  `sucursal_nombre` varchar(50) NOT NULL,
  `producto_danado` varchar(255) NOT NULL,
  `valor_faltante` decimal(10,2) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `registrador_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltante_danos_operarios`
--

CREATE TABLE `faltante_danos_operarios` (
  `id` int(11) NOT NULL,
  `faltante_id` int(11) NOT NULL,
  `operario_id` int(11) NOT NULL,
  `operario_nombre` varchar(100) NOT NULL,
  `operario_cargo` varchar(100) NOT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_deduccion` timestamp NULL DEFAULT NULL,
  `operario_categoria` varchar(255) DEFAULT NULL,
  `monto` double(10,2) DEFAULT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `cobrado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltante_inventario`
--

CREATE TABLE `faltante_inventario` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `sucursal_id` varchar(10) NOT NULL,
  `sucursal` varchar(50) NOT NULL,
  `auditor` varchar(100) NOT NULL,
  `total_faltante` decimal(10,2) DEFAULT 0.00,
  `comentarios` text DEFAULT NULL,
  `registrador_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltante_inventario_detalle`
--

CREATE TABLE `faltante_inventario_detalle` (
  `id` int(11) NOT NULL,
  `faltante_id` int(11) NOT NULL,
  `producto` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha_hora_regsys` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltante_inventario_operarios`
--

CREATE TABLE `faltante_inventario_operarios` (
  `id` int(11) NOT NULL,
  `faltante_id` int(11) NOT NULL,
  `operario_id` int(11) NOT NULL,
  `operario_nombre` varchar(100) NOT NULL,
  `operario_cargo` varchar(100) NOT NULL,
  `fecha_deduccion` timestamp NULL DEFAULT NULL,
  `operario_categoria` varchar(255) DEFAULT NULL,
  `monto` double(10,2) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `cod_contrato` int(11) DEFAULT NULL,
  `cobrado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Faltas`
--

CREATE TABLE `Faltas` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cod_sucursal` varchar(10) NOT NULL,
  `id_horario_programado` int(11) DEFAULT NULL,
  `hora_entrada_programada` time DEFAULT NULL,
  `hora_salida_programada` time DEFAULT NULL,
  `estado` enum('Vacaciones','Subsidio','Dias_mas_septimo','No_Pagado') NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faltas_manual`
--

CREATE TABLE `faltas_manual` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha_falta` date NOT NULL,
  `cod_sucursal` varchar(10) NOT NULL,
  `tipo_falta` enum('Pendiente','No_Pagado','Vacaciones','Subsidio_3dias','Subsidio_INSS','Subsidio_maternidad','Reposo_hasta_3dias','Compensacion_feria','Compensacion_dia_trabajado','Cuido_materno','Dia_mas_septimo','Omision_marcacion','Atencion_medica','Cita_medica_programada','Ajuste_horario') NOT NULL,
  `porcentaje_pago` decimal(5,2) DEFAULT NULL,
  `observaciones` varchar(500) NOT NULL,
  `observaciones_rrhh` text DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) NOT NULL COMMENT 'CodOperario del usuario que registra',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL COMMENT 'CodOperario del usuario que actualizó',
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'Restar 6 horas',
  `cod_contrato` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FechasSistema`
--

CREATE TABLE `FechasSistema` (
  `numero_semana` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feriadosnic`
--

CREATE TABLE `feriadosnic` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('Nacional','Departamental') NOT NULL,
  `departamento_codigo` int(11) DEFAULT NULL COMMENT 'NULL=Nacional, referencia a tabla departamentos',
  `recurrente` tinyint(1) DEFAULT 1 COMMENT '¿Misma fecha para cada año?',
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FeriadosStatus`
--

CREATE TABLE `FeriadosStatus` (
  `id` int(11) NOT NULL,
  `id_marcacion` int(11) DEFAULT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha_feriado` date DEFAULT NULL,
  `horas_trabajadas` decimal(5,2) DEFAULT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `estado` enum('Pagado','Descansado') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fichatecnica_presentacion_producto`
--

CREATE TABLE `fichatecnica_presentacion_producto` (
  `id` int(11) NOT NULL,
  `campo` varchar(100) NOT NULL COMMENT 'Nombre del atributo (Peso, Volumen, etc)',
  `descripcion` varchar(500) NOT NULL COMMENT 'Valor del atributo',
  `id_presentacion_producto` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Especificaciones técnicas de productos (campos dinámicos)';

-- --------------------------------------------------------

--
-- Table structure for table `formato_salida`
--

CREATE TABLE `formato_salida` (
  `id` int(11) NOT NULL,
  `cod_contrato` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `induccion_p1` text DEFAULT NULL,
  `induccion_p2` text DEFAULT NULL,
  `laboral_p1` text DEFAULT NULL,
  `laboral_p2` text DEFAULT NULL,
  `laboral_p3` text DEFAULT NULL,
  `laboral_p4` text DEFAULT NULL,
  `laboral_p5` text DEFAULT NULL,
  `laboral_p6` text DEFAULT NULL,
  `sat_salario` tinyint(4) DEFAULT NULL,
  `sat_ambiente` tinyint(4) DEFAULT NULL,
  `sat_relacion_companeros` tinyint(4) DEFAULT NULL,
  `sat_relacion_jefe` tinyint(4) DEFAULT NULL,
  `sat_relacion_superiores` tinyint(4) DEFAULT NULL,
  `sat_horario` tinyint(4) DEFAULT NULL,
  `sat_trabajo_equipo` tinyint(4) DEFAULT NULL,
  `sat_recomendaria` tinyint(4) DEFAULT NULL,
  `opinion_clima` text DEFAULT NULL,
  `firma_entrevistado` varchar(255) DEFAULT NULL,
  `firma_entrevistador` varchar(255) DEFAULT NULL,
  `usuario_creador` int(11) DEFAULT NULL COMMENT 'CodOperario del creador',
  `usuario_ultima_modificacion` int(11) DEFAULT NULL COMMENT 'CodOperario de la última modificación',
  `fecha_ultima_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_registro` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fotos_presentacion_producto`
--

CREATE TABLE `fotos_presentacion_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre descriptivo de la foto',
  `descripcion` varchar(500) DEFAULT NULL,
  `id_presentacion_producto` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `ruta` varchar(500) NOT NULL COMMENT 'Ruta del archivo en servidor',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Galería de fotos de productos';

-- --------------------------------------------------------

--
-- Table structure for table `gestion_proyectos_proyectos`
--

CREATE TABLE `gestion_proyectos_proyectos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre del proyecto visible en barra',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción detallada - solo visible en tooltip al hacer hover',
  `CodNivelesCargos` int(11) NOT NULL COMMENT 'Cargo del equipo de liderazgo (EquipoLiderazgo=1)',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio del proyecto',
  `fecha_fin` date NOT NULL COMMENT 'Fecha de finalización del proyecto',
  `orden_visual` int(11) DEFAULT 0 COMMENT 'Orden vertical dentro del cargo para proyectos traslapados',
  `es_subproyecto` tinyint(1) DEFAULT 0 COMMENT '0=Proyecto padre, 1=Subproyecto',
  `proyecto_padre_id` int(11) DEFAULT NULL COMMENT 'NULL si es proyecto padre, ID del padre si es subproyecto',
  `esta_expandido` tinyint(1) DEFAULT 0 COMMENT 'Estado visual de expansión de subproyectos (1=expandido, 0=contraído)',
  `color` varchar(7) DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `creado_por` int(11) NOT NULL COMMENT 'CodOperario del usuario que creó el proyecto',
  `fecha_modificacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `modificado_por` int(11) DEFAULT NULL COMMENT 'CodOperario del último usuario que modificó'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestión de proyectos tipo Gantt para equipo de liderazgo - Batidos Pitaya ERP';

-- --------------------------------------------------------

--
-- Table structure for table `gestion_tareas_reuniones_archivos`
--

CREATE TABLE `gestion_tareas_reuniones_archivos` (
  `id` int(11) NOT NULL,
  `id_item` int(11) DEFAULT NULL COMMENT 'ID de tarea/reunión',
  `id_comentario` int(11) DEFAULT NULL COMMENT 'ID de comentario',
  `tipo_vinculo` enum('item','comentario','finalizacion','resumen') NOT NULL DEFAULT 'item',
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_archivo` varchar(50) DEFAULT NULL,
  `tamano_bytes` int(11) DEFAULT NULL,
  `cod_operario_subio` int(11) NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Archivos adjuntos de tareas, reuniones y comentarios';

-- --------------------------------------------------------

--
-- Table structure for table `gestion_tareas_reuniones_comentarios`
--

CREATE TABLE `gestion_tareas_reuniones_comentarios` (
  `id` int(11) NOT NULL,
  `id_item` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comentarios en tareas y reuniones';

-- --------------------------------------------------------

--
-- Table structure for table `gestion_tareas_reuniones_items`
--

CREATE TABLE `gestion_tareas_reuniones_items` (
  `id` int(11) NOT NULL,
  `tipo` enum('tarea','subtarea','reunion') NOT NULL COMMENT 'Tipo de item',
  `id_padre` int(11) DEFAULT NULL COMMENT 'ID de tarea padre (solo para subtareas)',
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cod_cargo_asignado` int(11) DEFAULT NULL COMMENT 'Cargo asignado (tareas/subtareas)',
  `cod_cargo_creador` int(11) NOT NULL COMMENT 'Cargo que creó el item',
  `cod_operario_creador` int(11) NOT NULL COMMENT 'Operario que creó el item',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_meta` date DEFAULT NULL COMMENT 'Fecha límite (tareas/subtareas)',
  `fecha_reunion` datetime DEFAULT NULL COMMENT 'Fecha de reunión',
  `estado` enum('solicitado','en_progreso','finalizado','cancelado') NOT NULL DEFAULT 'en_progreso',
  `fecha_finalizacion` datetime DEFAULT NULL,
  `detalles_finalizacion` text DEFAULT NULL,
  `resumen_reunion` longtext DEFAULT NULL COMMENT 'HTML del resumen de reunión',
  `progreso` decimal(5,2) DEFAULT 0.00 COMMENT 'Porcentaje de progreso (0-100)',
  `fecha_ultima_modificacion` datetime DEFAULT NULL,
  `cod_operario_ultima_modificacion` int(11) DEFAULT NULL,
  `ics_uid` varchar(100) DEFAULT NULL COMMENT 'UID único del evento ICS para modificar/cancelar desde calendario',
  `prioridad` enum('alta','media','baja') NOT NULL DEFAULT 'media',
  `duracion_min` int(11) DEFAULT 60,
  `ics_sequence` int(11) DEFAULT 0,
  `lugar` varchar(200) DEFAULT NULL,
  `hora_tarea` time DEFAULT NULL COMMENT 'Hora estimada de inicio de la tarea'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tareas, subtareas y reuniones del equipo de liderazgo';

-- --------------------------------------------------------

--
-- Table structure for table `gestion_tareas_reuniones_participantes`
--

CREATE TABLE `gestion_tareas_reuniones_participantes` (
  `id` int(11) NOT NULL,
  `id_item` int(11) NOT NULL COMMENT 'ID de la reunión',
  `cod_cargo` int(11) NOT NULL COMMENT 'Cargo invitado',
  `confirmacion` enum('pendiente','asistire','no_asistire') DEFAULT 'pendiente',
  `fecha_confirmacion` datetime DEFAULT NULL,
  `cod_operario_confirmo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Participantes invitados a reuniones';

-- --------------------------------------------------------

--
-- Table structure for table `gestores_reclamos`
--

CREATE TABLE `gestores_reclamos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `GrupoProductosVenta`
--

CREATE TABLE `GrupoProductosVenta` (
  `CodGrupo` int(11) DEFAULT NULL,
  `NombreGrupo` varchar(20) DEFAULT NULL,
  `Tipo` varchar(20) DEFAULT NULL,
  `prioridad` int(11) DEFAULT NULL,
  `SumaPuntos` varchar(9) DEFAULT NULL,
  `Imprimible` varchar(9) DEFAULT NULL,
  `Editable` varchar(9) DEFAULT NULL,
  `Tamanos` varchar(9) DEFAULT NULL,
  `Vidrio` varchar(9) DEFAULT NULL,
  `EstacionTrabajo` int(11) DEFAULT NULL,
  `Endulzante` varchar(9) DEFAULT NULL,
  `Preparacion` varchar(9) DEFAULT NULL,
  `control` varchar(9) DEFAULT NULL,
  `alias` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grupos_productos`
--

CREATE TABLE `grupos_productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0 COMMENT 'Orden para mostrar el grupo en el sistema web',
  `activo` tinyint(1) DEFAULT 1,
  `no_endulzantes` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grupo_presentacion_producto`
--

CREATE TABLE `grupo_presentacion_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos principales de productos';

-- --------------------------------------------------------

--
-- Table structure for table `historial_estados_personal`
--

CREATE TABLE `historial_estados_personal` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL COMMENT '1=Activo, 0=Inactivo',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL significa que es el estado actual',
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial_proveedores`
--

CREATE TABLE `historial_proveedores` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `tipo_cambio` varchar(50) NOT NULL COMMENT 'datos_basicos, contacto, cuenta, tipo_pago, vigencia',
  `descripcion` text NOT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `fecha_cambio` datetime DEFAULT current_timestamp(),
  `usuario_cambio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `HorariosSemanales`
--

CREATE TABLE `HorariosSemanales` (
  `id` int(11) NOT NULL,
  `id_semana_sistema` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `cod_sucursal` int(11) NOT NULL,
  `lunes_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `lunes_entrada` time DEFAULT NULL,
  `lunes_salida` time DEFAULT NULL,
  `lunes_sucursal_externa` int(11) DEFAULT NULL,
  `lunes_comentario` varchar(255) DEFAULT NULL,
  `lunes_horas` decimal(5,2) DEFAULT 0.00,
  `martes_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `martes_entrada` time DEFAULT NULL,
  `martes_salida` time DEFAULT NULL,
  `martes_sucursal_externa` int(11) DEFAULT NULL,
  `martes_comentario` varchar(255) DEFAULT NULL,
  `martes_horas` decimal(5,2) DEFAULT 0.00,
  `miercoles_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `miercoles_entrada` time DEFAULT NULL,
  `miercoles_salida` time DEFAULT NULL,
  `miercoles_sucursal_externa` int(11) DEFAULT NULL,
  `miercoles_comentario` varchar(255) DEFAULT NULL,
  `miercoles_horas` decimal(5,2) DEFAULT 0.00,
  `jueves_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `jueves_entrada` time DEFAULT NULL,
  `jueves_salida` time DEFAULT NULL,
  `jueves_sucursal_externa` int(11) DEFAULT NULL,
  `jueves_comentario` varchar(255) DEFAULT NULL,
  `jueves_horas` decimal(5,2) DEFAULT 0.00,
  `viernes_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `viernes_entrada` time DEFAULT NULL,
  `viernes_salida` time DEFAULT NULL,
  `viernes_sucursal_externa` int(11) DEFAULT NULL,
  `viernes_comentario` varchar(255) DEFAULT NULL,
  `viernes_horas` decimal(5,2) DEFAULT 0.00,
  `sabado_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `sabado_entrada` time DEFAULT NULL,
  `sabado_salida` time DEFAULT NULL,
  `sabado_sucursal_externa` int(11) DEFAULT NULL,
  `sabado_comentario` varchar(255) DEFAULT NULL,
  `sabado_horas` decimal(5,2) DEFAULT 0.00,
  `domingo_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `domingo_entrada` time DEFAULT NULL,
  `domingo_salida` time DEFAULT NULL,
  `domingo_sucursal_externa` int(11) DEFAULT NULL,
  `domingo_comentario` varchar(255) DEFAULT NULL,
  `domingo_horas` decimal(5,2) DEFAULT 0.00,
  `total_horas` decimal(10,2) DEFAULT 0.00,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `HorariosSemanalesOperaciones`
--

CREATE TABLE `HorariosSemanalesOperaciones` (
  `id` int(11) NOT NULL,
  `id_semana_sistema` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `cod_sucursal` varchar(10) NOT NULL,
  `lunes_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `lunes_entrada` time DEFAULT NULL,
  `lunes_salida` time DEFAULT NULL,
  `lunes_sucursal_externa` int(11) DEFAULT NULL,
  `lunes_comentario` varchar(255) DEFAULT NULL,
  `lunes_horas` decimal(5,2) DEFAULT 0.00,
  `martes_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `martes_entrada` time DEFAULT NULL,
  `martes_salida` time DEFAULT NULL,
  `martes_sucursal_externa` int(11) DEFAULT NULL,
  `martes_comentario` varchar(255) DEFAULT NULL,
  `martes_horas` decimal(5,2) DEFAULT 0.00,
  `miercoles_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `miercoles_entrada` time DEFAULT NULL,
  `miercoles_salida` time DEFAULT NULL,
  `miercoles_sucursal_externa` int(11) DEFAULT NULL,
  `miercoles_comentario` varchar(255) DEFAULT NULL,
  `miercoles_horas` decimal(5,2) DEFAULT 0.00,
  `jueves_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `jueves_entrada` time DEFAULT NULL,
  `jueves_salida` time DEFAULT NULL,
  `jueves_sucursal_externa` int(11) DEFAULT NULL,
  `jueves_comentario` varchar(255) DEFAULT NULL,
  `jueves_horas` decimal(5,2) DEFAULT 0.00,
  `viernes_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `viernes_entrada` time DEFAULT NULL,
  `viernes_salida` time DEFAULT NULL,
  `viernes_sucursal_externa` int(11) DEFAULT NULL,
  `viernes_comentario` varchar(255) DEFAULT NULL,
  `viernes_horas` decimal(5,2) DEFAULT 0.00,
  `sabado_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `sabado_entrada` time DEFAULT NULL,
  `sabado_salida` time DEFAULT NULL,
  `sabado_sucursal_externa` int(11) DEFAULT NULL,
  `sabado_comentario` varchar(255) DEFAULT NULL,
  `sabado_horas` decimal(5,2) DEFAULT 0.00,
  `domingo_estado` enum('Activo','Vacaciones','Subsidio','Libre','Feriado','Comp.Feriado','Otra.Tienda','Finalizado') NOT NULL DEFAULT 'Activo',
  `domingo_entrada` time DEFAULT NULL,
  `domingo_salida` time DEFAULT NULL,
  `domingo_sucursal_externa` int(11) DEFAULT NULL,
  `domingo_comentario` varchar(255) DEFAULT NULL,
  `domingo_horas` decimal(5,2) DEFAULT 0.00,
  `total_horas` decimal(10,2) DEFAULT 0.00,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `confirmado` tinyint(1) DEFAULT 1,
  `fecha_confirmacion` datetime DEFAULT NULL,
  `autorizado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Permitido editar por operaciones, 0=No permitido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `HorasExtraStatus`
--

CREATE TABLE `HorasExtraStatus` (
  `id` int(11) NOT NULL,
  `id_marcacion` int(11) NOT NULL,
  `estado` enum('Pendiente','Aprobado','Denegado') NOT NULL DEFAULT 'Pendiente',
  `observaciones` text DEFAULT NULL,
  `horas_extras` decimal(5,2) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cod_operario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `horas_extras_manual`
--

CREATE TABLE `horas_extras_manual` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `horas_extras` decimal(5,2) NOT NULL,
  `cod_sucursal` varchar(10) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `motivo_solicitud` text DEFAULT NULL,
  `registrado_por` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estado` enum('Pendiente','Aprobado','Denegado') NOT NULL DEFAULT 'Pendiente',
  `cod_contrato` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ia_graficos_cache`
--

CREATE TABLE `ia_graficos_cache` (
  `id` int(11) NOT NULL,
  `prompt_hash` varchar(64) NOT NULL COMMENT 'Hash MD5 del prompt',
  `prompt_original` text NOT NULL,
  `estructura_json` text NOT NULL COMMENT 'Respuesta de la IA',
  `resultado_sql` text DEFAULT NULL COMMENT 'Resultado de la consulta',
  `usuario_id` int(11) NOT NULL,
  `hits` int(11) DEFAULT 1 COMMENT 'Veces que se ha reutilizado',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL COMMENT 'Fecha de expiración (24h)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Caché de consultas procesadas';

-- --------------------------------------------------------

--
-- Table structure for table `ia_graficos_diccionario_columnas`
--

CREATE TABLE `ia_graficos_diccionario_columnas` (
  `id` int(11) NOT NULL,
  `tabla_origen` varchar(100) NOT NULL COMMENT 'Nombre de la tabla',
  `columna_bd` varchar(100) NOT NULL COMMENT 'Nombre real de la columna en BD',
  `nombre_negocio` varchar(100) NOT NULL COMMENT 'Nombre en lenguaje de negocio',
  `tipo_dato` enum('numero','texto','fecha','hora','booleano','enum') NOT NULL,
  `descripcion` text NOT NULL COMMENT 'Descripción detallada del significado',
  `es_metrica` tinyint(1) DEFAULT 0 COMMENT '¿Se puede usar para métricas?',
  `es_dimension` tinyint(1) DEFAULT 0 COMMENT '¿Se puede usar para agrupar?',
  `es_filtrable` tinyint(1) DEFAULT 1 COMMENT '¿Se puede filtrar?',
  `alias_busqueda` text DEFAULT NULL COMMENT 'Palabras clave separadas por coma',
  `valores_enum` text DEFAULT NULL COMMENT 'Valores permitidos si es enum',
  `formato_salida` varchar(50) DEFAULT NULL COMMENT 'Formato de visualización',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Diccionario de columnas para interpretación semántica';

-- --------------------------------------------------------

--
-- Table structure for table `ia_graficos_favoritos`
--

CREATE TABLE `ia_graficos_favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'CodOperario del usuario',
  `nombre_favorito` varchar(200) NOT NULL COMMENT 'Nombre descriptivo del favorito',
  `prompt_original` text NOT NULL COMMENT 'Prompt usado',
  `estructura_json` text NOT NULL COMMENT 'Estructura generada por IA',
  `tipo_grafico` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL COMMENT 'Descripción opcional',
  `veces_usado` int(11) DEFAULT 0 COMMENT 'Contador de usos',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_visualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consultas favoritas de gráficos por usuario';

-- --------------------------------------------------------

--
-- Table structure for table `ia_graficos_filtros_conceptuales`
--

CREATE TABLE `ia_graficos_filtros_conceptuales` (
  `id` int(11) NOT NULL,
  `concepto` varchar(100) NOT NULL COMMENT 'Concepto de negocio',
  `palabras_clave` text NOT NULL COMMENT 'Palabras que activan este filtro',
  `condicion_sql` text NOT NULL COMMENT 'Template de condición SQL',
  `requiere_parametros` tinyint(1) DEFAULT 0,
  `parametros_ejemplo` text DEFAULT NULL COMMENT 'Ejemplos de parámetros',
  `descripcion` text DEFAULT NULL,
  `prioridad` int(11) DEFAULT 50 COMMENT 'Orden de aplicación (1-100)',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Filtros conceptuales para optimizar prompts';

-- --------------------------------------------------------

--
-- Table structure for table `ia_graficos_metricas_predefinidas`
--

CREATE TABLE `ia_graficos_metricas_predefinidas` (
  `id` int(11) NOT NULL,
  `nombre_metrica` varchar(100) NOT NULL COMMENT 'Nombre de la métrica',
  `palabras_clave` text NOT NULL COMMENT 'Palabras que activan esta métrica',
  `funcion_sql` varchar(50) NOT NULL COMMENT 'Función SQL (SUM, AVG, COUNT, etc)',
  `columna_origen` varchar(100) NOT NULL COMMENT 'Columna de la tabla',
  `tabla_origen` varchar(100) NOT NULL COMMENT 'Tabla origen',
  `formato_salida` varchar(50) DEFAULT NULL COMMENT 'Formato de visualización',
  `descripcion` text DEFAULT NULL,
  `alias_sql` varchar(50) DEFAULT NULL COMMENT 'Alias en la consulta SQL',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Métricas predefinidas con palabras clave';

-- --------------------------------------------------------

--
-- Table structure for table `ia_graficos_validaciones`
--

CREATE TABLE `ia_graficos_validaciones` (
  `id` int(11) NOT NULL,
  `nombre_regla` varchar(100) NOT NULL,
  `tipo_validacion` enum('tipo_grafico','metrica','dimension','filtro','rango_temporal','combinacion') NOT NULL,
  `condicion` text NOT NULL COMMENT 'Condición a validar',
  `mensaje_error` text NOT NULL,
  `nivel` enum('error','advertencia','info') DEFAULT 'error',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reglas de validación predefinidas';

-- --------------------------------------------------------

--
-- Table structure for table `ia_proveedores_api`
--

CREATE TABLE `ia_proveedores_api` (
  `id` int(11) NOT NULL,
  `proveedor` varchar(50) NOT NULL COMMENT 'Ej: groq, openai',
  `api_key` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cuenta_correo` varchar(255) DEFAULT NULL COMMENT 'Correo del dueño de la API Key',
  `activa` tinyint(1) DEFAULT 1 COMMENT '1 = Activa, 0 = Inactiva',
  `limite_alcanzado_hoy` tinyint(1) DEFAULT 0 COMMENT 'Se resetea a 0 todos los dias',
  `ultimo_uso` datetime DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Llaves almacenadas para el rotador de APIs de Inteligencia Artificial';

-- --------------------------------------------------------

--
-- Table structure for table `IndicadoresSemanales`
--

CREATE TABLE `IndicadoresSemanales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `CodNivelesCargos` int(11) NOT NULL,
  `numerador_nombre` varchar(255) DEFAULT NULL,
  `denominador_nombre` varchar(255) DEFAULT NULL,
  `tipo` enum('entero','porcentaje') DEFAULT 'entero',
  `decimales` int(11) DEFAULT 0,
  `formula` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `divide` tinyint(1) DEFAULT 1,
  `tipometa` enum('arriba','abajo') DEFAULT 'arriba',
  `EnUso` varchar(100) NOT NULL DEFAULT '1',
  `automatico` tinyint(4) DEFAULT 0,
  `acumulativo` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `IndicadoresSemanalesResultados`
--

CREATE TABLE `IndicadoresSemanalesResultados` (
  `id` int(11) NOT NULL,
  `semana` int(11) NOT NULL,
  `id_indicador` int(11) NOT NULL,
  `numerador_dato` decimal(20,4) DEFAULT NULL,
  `denominador_dato` decimal(20,4) DEFAULT 1.0000,
  `meta` decimal(20,4) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `usuario_registra` int(11) DEFAULT NULL,
  `usuario_modifica` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `intent_embeddings`
--

CREATE TABLE `intent_embeddings` (
  `id` int(11) NOT NULL,
  `intent_id` int(11) NOT NULL,
  `term` varchar(100) NOT NULL COMMENT 'Término del vocabulario',
  `tfidf_weight` float NOT NULL DEFAULT 0 COMMENT 'Peso TF-IDF normalizado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_reclamos`
--

CREATE TABLE `kpi_reclamos` (
  `id` int(11) NOT NULL,
  `sucursal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cod_sucursal` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mes` int(2) NOT NULL,
  `anio` int(4) NOT NULL,
  `kpi_ventas` decimal(3,2) DEFAULT NULL,
  `reclamos_cantidad` int(11) DEFAULT 0 COMMENT 'Reclamos ya investigados final',
  `reclamos_totales` int(11) NOT NULL DEFAULT 0 COMMENT 'Reclamos en total con y sin investigación final',
  `reclamos_porcentaje` decimal(5,0) DEFAULT 100,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL,
  `cod_operario_actualizacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marcaciones`
--

CREATE TABLE `marcaciones` (
  `id` int(11) NOT NULL,
  `hora_ingreso` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `fecha` date NOT NULL,
  `CodOperario` int(11) NOT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `sucursal_codigo` varchar(10) DEFAULT NULL,
  `id_horario_semanal` int(11) DEFAULT NULL,
  `numero_semana` int(11) DEFAULT NULL,
  `nombre_operario` varchar(100) NOT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Auto (-6 hrs)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `sender_type` enum('user','bot','agent','campaign') NOT NULL,
  `message_text` text DEFAULT NULL,
  `message_type` varchar(20) NOT NULL DEFAULT 'text',
  `enviado_ok` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT convert_tz(current_timestamp(),'+00:00','-06:00')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos`
--

CREATE TABLE `mtto_equipos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL COMMENT 'Código del equipo (no autogenerado)',
  `tipo_equipo_id` int(11) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serial` varchar(100) DEFAULT NULL,
  `caracteristicas` text DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `proveedor_compra_id` int(11) DEFAULT NULL,
  `garantia_meses` int(11) DEFAULT 0,
  `frecuencia_mantenimiento_meses` int(11) NOT NULL DEFAULT 3 COMMENT 'Frecuencia de mantenimiento preventivo en meses',
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_mantenimientos`
--

CREATE TABLE `mtto_equipos_mantenimientos` (
  `id` int(11) NOT NULL,
  `mantenimiento_programado_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `solicitud_id` int(11) DEFAULT NULL COMMENT 'Solicitud vinculada (opcional)',
  `tipo` enum('preventivo','correctivo') NOT NULL,
  `proveedor_servicio_id` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `problema_encontrado` text DEFAULT NULL,
  `trabajo_realizado` text NOT NULL,
  `observaciones` text DEFAULT NULL,
  `costo_total_repuestos` decimal(10,2) DEFAULT 0.00 COMMENT 'Costo total de repuestos utilizados',
  `registrado_por` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `costo_mano_de_obra` decimal(10,2) DEFAULT 0.00 COMMENT 'Costo de mano de obra del servicio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_mantenimientos_fotos`
--

CREATE TABLE `mtto_equipos_mantenimientos_fotos` (
  `id` int(11) NOT NULL,
  `mantenimiento_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_mantenimientos_programados`
--

CREATE TABLE `mtto_equipos_mantenimientos_programados` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `tipo` enum('preventivo','correctivo') NOT NULL,
  `estado` enum('agendado','finalizado') DEFAULT 'agendado',
  `programado_por` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_mantenimientos_repuestos`
--

CREATE TABLE `mtto_equipos_mantenimientos_repuestos` (
  `id` int(11) NOT NULL,
  `mantenimiento_id` int(11) NOT NULL,
  `repuesto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` decimal(10,2) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_movimientos`
--

CREATE TABLE `mtto_equipos_movimientos` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `sucursal_origen_id` int(11) NOT NULL,
  `sucursal_destino_id` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `fecha_realizada` datetime DEFAULT NULL,
  `estado` enum('agendado','finalizado') DEFAULT 'agendado',
  `observaciones` text DEFAULT NULL,
  `programado_por` int(11) NOT NULL,
  `finalizado_por` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_repuestos`
--

CREATE TABLE `mtto_equipos_repuestos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `costo_base` decimal(10,2) DEFAULT 0.00,
  `unidad_medida` varchar(50) DEFAULT 'Unidad',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_solicitudes`
--

CREATE TABLE `mtto_equipos_solicitudes` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `descripcion_problema` text NOT NULL,
  `estado` enum('solicitado','finalizado') DEFAULT 'solicitado',
  `solicitado_por` int(11) NOT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `finalizado_por` int(11) DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `observaciones_finalizacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_solicitudes_fotos`
--

CREATE TABLE `mtto_equipos_solicitudes_fotos` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_equipos_tipos`
--

CREATE TABLE `mtto_equipos_tipos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `Grupo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_informes_diarios`
--

CREATE TABLE `mtto_informes_diarios` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `km_inicial` decimal(10,2) DEFAULT NULL,
  `km_final` decimal(10,2) DEFAULT NULL,
  `costo_km` decimal(10,2) DEFAULT 0.00,
  `reembolso_id` int(11) DEFAULT NULL,
  `km_foto_inicial` varchar(255) DEFAULT NULL,
  `km_foto_final` varchar(255) DEFAULT NULL,
  `monto_caja_chica` decimal(10,2) DEFAULT 0.00,
  `foto_caja_chica` varchar(255) DEFAULT NULL,
  `estado` enum('creado','finalizado') DEFAULT 'creado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_informe_compras`
--

CREATE TABLE `mtto_informe_compras` (
  `id` int(11) NOT NULL,
  `visita_id` int(11) NOT NULL,
  `foto_factura` varchar(255) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT 0.00,
  `detalle` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_informe_tareas`
--

CREATE TABLE `mtto_informe_tareas` (
  `id` int(11) NOT NULL,
  `visita_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `completado_100` tinyint(1) NOT NULL DEFAULT 1,
  `trabajo_realizado` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_informe_tareas_fotos`
--

CREATE TABLE `mtto_informe_tareas_fotos` (
  `id` int(11) NOT NULL,
  `tarea_id` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_informe_visitas`
--

CREATE TABLE `mtto_informe_visitas` (
  `id` int(11) NOT NULL,
  `informe_id` int(11) NOT NULL,
  `cod_sucursal` varchar(10) NOT NULL,
  `hora_llegada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `materiales_stock` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reembolso_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_materiales_frecuentes`
--

CREATE TABLE `mtto_materiales_frecuentes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_tickets`
--

CREATE TABLE `mtto_tickets` (
  `id` int(11) NOT NULL COMMENT 'codigo numerico de cada solicitud',
  `codigo` varchar(20) NOT NULL COMMENT 'codigo generado solo visual',
  `titulo` varchar(255) NOT NULL COMMENT 'titulo de la solicitud',
  `descripcion` text NOT NULL COMMENT 'detalle de la solicitud',
  `tipo_formulario` enum('mantenimiento_general','cambio_equipos') NOT NULL COMMENT 'los 2 tipos de solicitudes',
  `cod_operario` int(11) NOT NULL COMMENT 'codigo de colaborador que reporto la solicitud',
  `cod_sucursal` int(11) NOT NULL COMMENT 'codigo de sucursal donde se reporto la incidencia',
  `area_equipo` varchar(255) NOT NULL COMMENT 'Area fisica de la sucursal donde ocurrio la incidencia',
  `nivel_urgencia` int(11) DEFAULT NULL COMMENT 'del 1 al 4 nivel de urgencia de no urgente a critico',
  `fecha_inicio` date DEFAULT NULL COMMENT 'Fecha inicial cuando se programe el trabajo por el area de mantenimiento',
  `fecha_final` date DEFAULT NULL COMMENT 'fecha final de concluir el trabajo cuando sea programado por el area de mantenimiento',
  `status` enum('solicitado','clasificado','agendado','finalizado','cancelado') DEFAULT 'solicitado' COMMENT 'cuando se crea es solicitado , una vez que se agenda por el area de mantenimiento es agendado y una vez que el area de mantenimiento lo procesa lo cambia a estatus finalizado',
  `tipo_caso_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Fecha de creacion de la colicitud por la sucursal',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `detalle_trabajo` text DEFAULT NULL COMMENT 'Detalle que llena el encargado de mantenimiento una vez que se concluye el trabajo sobre todo lo que s ehiso',
  `materiales_usados` text DEFAULT NULL COMMENT 'lista de materiales usados por el area de mantenimiento una vez que termina el trabajo',
  `fecha_finalizacion` timestamp NULL DEFAULT NULL COMMENT 'fecha que reporta el area de mantenimiento como fecha que se termino la ejecucion del trabajo',
  `finalizado_por` int(11) DEFAULT NULL COMMENT 'codigo de colaborador que ejecuto el trabajo',
  `tiempo_estimado` int(11) DEFAULT 0 COMMENT 'tiempo que estima el area de mantenimiento que tomara la ejecucion del trabajo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_tickets_colaboradores`
--

CREATE TABLE `mtto_tickets_colaboradores` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `cod_operario` int(11) DEFAULT NULL,
  `asignado_por` int(11) DEFAULT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_usuario` enum('Auxiliar de Mantenimiento','Externo','Lider de Infraestructura','Conductor','Jefe de Mantenimiento') DEFAULT 'Jefe de Mantenimiento',
  `cod_tipo_usuario` int(11) DEFAULT NULL,
  `CodNivelesCargo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_tickets_fotos`
--

CREATE TABLE `mtto_tickets_fotos` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_tickets_fotos_finalizacion`
--

CREATE TABLE `mtto_tickets_fotos_finalizacion` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_tickets_materiales`
--

CREATE TABLE `mtto_tickets_materiales` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `material_nombre` varchar(255) NOT NULL,
  `detalle` text DEFAULT NULL,
  `procedencia` enum('Bodega Villa','Bodega Altamira','Compra Sinsa','Compra Ferreteria','Otros') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mtto_tipos_casos`
--

CREATE TABLE `mtto_tipos_casos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `NivelesCargos`
--

CREATE TABLE `NivelesCargos` (
  `CodNivelesCargos` int(11) NOT NULL,
  `Nombre` varchar(255) DEFAULT NULL,
  `Operaciones` bit(1) DEFAULT NULL,
  `Marcacion` bit(1) DEFAULT NULL,
  `DisponibleRegistros` bit(1) DEFAULT NULL,
  `BeneficiosAdministrativos` bit(1) DEFAULT NULL,
  `PermisosLider` bit(1) DEFAULT NULL,
  `ReportaA` int(11) DEFAULT NULL,
  `Area` varchar(225) DEFAULT NULL,
  `Peso` decimal(3,1) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `EquipoLiderazgo` tinyint(1) DEFAULT 0,
  `especialidad_area` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Operarios`
--

CREATE TABLE `Operarios` (
  `CodOperario` int(11) NOT NULL,
  `Nombre` varchar(255) DEFAULT NULL,
  `Nombre2` varchar(255) DEFAULT NULL,
  `Apellido` varchar(255) DEFAULT NULL,
  `Apellido2` varchar(255) DEFAULT NULL,
  `clave` varchar(255) DEFAULT NULL COMMENT 'Contraseña sistema',
  `clave_hash` varchar(255) DEFAULT NULL COMMENT 'Contraseña bcrypt',
  `Operativo` bit(1) DEFAULT NULL,
  `Celular` varchar(255) DEFAULT NULL,
  `Cedula` varchar(255) DEFAULT NULL,
  `Genero` enum('M','F','O') DEFAULT NULL,
  `Inicio` datetime DEFAULT NULL,
  `Fin` datetime DEFAULT NULL,
  `Cumpleanos` datetime DEFAULT NULL,
  `Sucursal` int(11) DEFAULT NULL,
  `Ciudad` varchar(255) DEFAULT NULL,
  `CodClub` int(11) DEFAULT NULL,
  `Cargo` varchar(255) DEFAULT NULL,
  `FechaRegistro` datetime DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL COMMENT 'Usuario en sistema',
  `cb_numero` varchar(100) DEFAULT NULL,
  `contacto_numero` int(11) DEFAULT NULL,
  `contacto_nombre` varchar(250) DEFAULT NULL,
  `direccion` varchar(250) DEFAULT NULL,
  `telefono_casa` varchar(20) DEFAULT NULL,
  `telefono_corporativo` varchar(20) DEFAULT NULL,
  `email_personal` varchar(255) DEFAULT NULL,
  `email_trabajo` varchar(255) DEFAULT NULL,
  `email_trabajo_clave` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `codigo_inss` varchar(50) DEFAULT NULL,
  `segurosocial` varchar(20) DEFAULT NULL,
  `InicioSeguro` date DEFAULT NULL COMMENT 'Fecha de inicio del seguro social',
  `FinSeguro` date DEFAULT NULL COMMENT 'Fecha de fin del seguro social',
  `hospital_riesgo_laboral` varchar(255) DEFAULT NULL,
  `cb_titular` varchar(100) DEFAULT NULL,
  `cb_banco` varchar(100) DEFAULT NULL,
  `cb_moneda` varchar(20) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de registro',
  `FechaCreacion` timestamp NULL DEFAULT NULL,
  `bot_github_token` varchar(500) DEFAULT NULL COMMENT 'Token GitHub cifrado AES para vault Obsidian',
  `bot_github_repo` varchar(200) DEFAULT NULL COMMENT 'Repositorio Obsidian: owner/repo',
  `bot_github_branch` varchar(50) DEFAULT 'main' COMMENT 'Rama del vault de Obsidian',
  `bot_github_vault_folder` varchar(100) DEFAULT '' COMMENT 'Carpeta raíz dentro del vault (vacío = raíz)',
  `bot_lid` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OperariosCategorias`
--

CREATE TABLE `OperariosCategorias` (
  `id` int(11) NOT NULL,
  `CodOperario` int(11) NOT NULL,
  `CodContrato` int(11) DEFAULT NULL,
  `CodAsignacionNivelesCargos` int(11) DEFAULT NULL,
  `idCategoria` int(11) DEFAULT NULL,
  `TipoAdendum` enum('inicial','cargo','salario','ambos','movimiento') DEFAULT 'inicial',
  `Salario` decimal(10,2) DEFAULT NULL,
  `CodNivelesCargos` int(11) DEFAULT NULL,
  `Sucursal` int(11) DEFAULT NULL,
  `Observaciones` text DEFAULT NULL,
  `es_activo` tinyint(1) DEFAULT 1,
  `FechaInicio` date NOT NULL DEFAULT curdate(),
  `FechaFin` date DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de registro en bd',
  `FotoExamen` varchar(255) DEFAULT NULL COMMENT 'Ruta de la foto del examen de categoría',
  `codigo_contrato_asociado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PagoAdelantos`
--

CREATE TABLE `PagoAdelantos` (
  `CodPagoAdelanto` int(11) NOT NULL,
  `cod_adelanto` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `cuota` decimal(10,2) DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PatronalesINSS`
--

CREATE TABLE `PatronalesINSS` (
  `CodPlanilla` int(11) NOT NULL,
  `nombre_planilla` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pedidos_impresiones`
--

CREATE TABLE `pedidos_impresiones` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `tipo_impresion` enum('comanda','factura','whatsapp') NOT NULL,
  `dispositivo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permisos_tools_erp`
--

CREATE TABLE `permisos_tools_erp` (
  `id` int(11) NOT NULL,
  `accion_tool_erp_id` int(11) NOT NULL,
  `CodNivelesCargos` int(11) NOT NULL,
  `permiso` enum('allow','deny') NOT NULL DEFAULT 'allow',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal`
--

CREATE TABLE `personal` (
  `id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `peso_porcentual` decimal(5,2) DEFAULT 0.00,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `mes` tinyint(2) NOT NULL DEFAULT month(curdate()),
  `anio` smallint(4) NOT NULL DEFAULT year(curdate()),
  `activo` tinyint(1) DEFAULT 1,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Registro en sistema -6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_auditorias`
--

CREATE TABLE `personal_auditorias` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `peso_porcentual` decimal(5,2) DEFAULT 0.00,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `mes` tinyint(2) NOT NULL DEFAULT 5,
  `anio` smallint(4) NOT NULL DEFAULT 2025,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pitaya_love_registros`
--

CREATE TABLE `pitaya_love_registros` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `numero_contacto` varchar(255) NOT NULL,
  `numero_cedula` varchar(255) DEFAULT NULL,
  `numero_factura` int(11) NOT NULL DEFAULT 0,
  `codigo_local` int(11) DEFAULT NULL COMMENT 'ID de la sucursal que generó la factura (Opcional)',
  `correo_electronico` varchar(255) DEFAULT NULL,
  `monto_factura` decimal(10,2) DEFAULT NULL,
  `puntos_factura` int(11) NOT NULL DEFAULT 0,
  `tipo_qr` varchar(100) NOT NULL DEFAULT 'online',
  `foto_factura` varchar(255) DEFAULT NULL,
  `validado_ia` tinyint(1) DEFAULT 0 COMMENT '0=No validado, 1=Validado por IA',
  `codigo_sorteo_ia` int(11) DEFAULT NULL COMMENT 'Código de sorteo detectado por IA',
  `puntos_ia` int(11) DEFAULT NULL COMMENT 'Puntos detectados por IA',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `valido` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plazas_cargos`
--

CREATE TABLE `plazas_cargos` (
  `id` int(11) NOT NULL,
  `cargo` int(11) NOT NULL COMMENT 'CodNivelesCargos',
  `cantidad_real` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad total necesaria',
  `cantidad_adicional` int(11) NOT NULL DEFAULT 0,
  `sucursal` varchar(10) DEFAULT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Obligatorio, 0=No obligatorio',
  `area` enum('Sucursales','Administrativo','Produccion') NOT NULL COMMENT 'Área del cargo',
  `salario_propuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `nivel_urgencia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=No urgente, 2=Medio, 3=Urgente, 4=Crítico',
  `visible_web` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Visible para postulantes externos',
  `ruta_banner_cargo` varchar(500) DEFAULT NULL,
  `ruta_pdf_cargo` varchar(500) DEFAULT NULL,
  `ruta_banner` varchar(255) DEFAULT NULL,
  `usuario_registra` int(11) NOT NULL COMMENT 'CodOperario que registra',
  `usuario_modifica` int(11) DEFAULT NULL COMMENT 'CodOperario que modifica',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `perfil_ia_destilado` longtext DEFAULT NULL,
  `perfil_ia_ultima_act` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postulacion_entrevista_telefonica`
--

CREATE TABLE `postulacion_entrevista_telefonica` (
  `id` int(11) NOT NULL,
  `id_postulacion` int(11) NOT NULL,
  `edad` int(11) NOT NULL,
  `ubicacion_tienda` int(11) DEFAULT NULL,
  `trabaja_actualmente` varchar(10) DEFAULT NULL,
  `disponibilidad` varchar(255) NOT NULL,
  `lugar_trabajo` varchar(255) DEFAULT NULL,
  `promedio_devengado` decimal(10,2) DEFAULT NULL,
  `aspiracion_salarial` decimal(10,2) NOT NULL,
  `estudias` varchar(10) DEFAULT NULL,
  `modalidad_horarios` varchar(255) DEFAULT NULL,
  `motivo_cambio` text DEFAULT NULL,
  `disponibilidad_horarios_rotativos` text DEFAULT NULL,
  `disponibilidad_traslados` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postulacion_evaluacion_jefe`
--

CREATE TABLE `postulacion_evaluacion_jefe` (
  `id` int(11) NOT NULL,
  `id_postulacion` int(11) NOT NULL,
  `p1_calificacion` int(11) DEFAULT 0 COMMENT 'Conocimiento Técnico 1: Estrellas 1-5',
  `p1_comentario` text DEFAULT NULL,
  `p2_calificacion` int(11) DEFAULT 0,
  `p2_comentario` text DEFAULT NULL,
  `p3_calificacion` int(11) DEFAULT 0,
  `p3_comentario` text DEFAULT NULL,
  `p4_calificacion` int(11) DEFAULT 0,
  `p4_comentario` text DEFAULT NULL,
  `p5_calificacion` int(11) DEFAULT 0,
  `p5_comentario` text DEFAULT NULL,
  `p6_calificacion` int(11) DEFAULT 0,
  `p6_comentario` text DEFAULT NULL,
  `promedio_estrellas` decimal(5,2) DEFAULT 0.00,
  `evidencia_ruta` varchar(255) DEFAULT NULL,
  `conclusiones_finales` text DEFAULT NULL,
  `veredicto` enum('aprobado','descartado') DEFAULT NULL,
  `usuario_evalua` int(11) NOT NULL,
  `fecha_evaluacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postulacion_evaluacion_rh`
--

CREATE TABLE `postulacion_evaluacion_rh` (
  `id` int(11) NOT NULL,
  `id_postulacion` int(11) NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `p1_calificacion` int(11) DEFAULT 0 COMMENT 'Pregunta 1: Estrellas 1-5',
  `p1_comentario` text DEFAULT NULL,
  `p2_calificacion` int(11) DEFAULT 0,
  `p2_comentario` text DEFAULT NULL,
  `p3_calificacion` int(11) DEFAULT 0,
  `p3_comentario` text DEFAULT NULL,
  `p4_calificacion` int(11) DEFAULT 0,
  `p4_comentario` text DEFAULT NULL,
  `p5_calificacion` int(11) DEFAULT 0,
  `p5_comentario` text DEFAULT NULL,
  `puntaje_acumulado` decimal(5,2) DEFAULT 0.00,
  `evidencia_ruta` varchar(255) DEFAULT NULL,
  `conclusiones_generales` text DEFAULT NULL,
  `veredicto` enum('aprobado','rechazado') DEFAULT NULL,
  `usuario_evalua` int(11) NOT NULL,
  `fecha_evaluacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postulacion_plaza`
--

CREATE TABLE `postulacion_plaza` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre completo del candidato',
  `correo` varchar(255) NOT NULL COMMENT 'Correo electrónico',
  `telefono` varchar(20) NOT NULL COMMENT 'Número de teléfono',
  `ruta_cv` varchar(500) DEFAULT NULL COMMENT 'Ruta del archivo CV (PDF, max 5MB)',
  `comentario` text DEFAULT NULL COMMENT 'Mensaje o comentarios del candidato',
  `aspiracion_salarial` decimal(10,2) DEFAULT NULL COMMENT 'Salario esperado en NIO',
  `experiencia_laboral` text DEFAULT NULL COMMENT 'Descripción de experiencia laboral',
  `notas_entrevista` text DEFAULT NULL,
  `analisis_ia` text DEFAULT NULL COMMENT 'Resultado de análisis IA (futuro)',
  `status` enum('solicitado','aprobado','rechazado','seleccionado','denegado','contratado') NOT NULL DEFAULT 'solicitado',
  `cargo_aplicado` int(11) NOT NULL COMMENT 'CodNivelesCargos del puesto',
  `sucursal_aplicada` int(11) DEFAULT NULL COMMENT 'ID de sucursal donde aplica',
  `fecha_postulacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de postulación (Nicaragua)',
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'Última modificación',
  `ip_postulacion` varchar(45) DEFAULT NULL COMMENT 'IP del postulante (IPv4/IPv6)',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'Navegador y dispositivo usado',
  `direccion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Postulaciones de candidatos externos al portal de empleo';

-- --------------------------------------------------------

--
-- Table structure for table `pos_caja_inicial`
--

CREATE TABLE `pos_caja_inicial` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL COMMENT 'Fecha del conteo',
  `sucursal_id` varchar(10) NOT NULL COMMENT 'Código de sucursal (s.codigo)',
  `tipo_cambio_usado` decimal(10,4) NOT NULL COMMENT 'Tipo de cambio NIO/USD al momento del conteo',
  `total_cordobas` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de denominaciones en córdobas',
  `total_dolares` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de denominaciones en dólares',
  `total_dolares_en_cordobas` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'total_dolares * tipo_cambio_usado',
  `total_efectivo_global` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'total_cordobas + total_dolares_en_cordobas',
  `cod_usuario` int(11) DEFAULT NULL COMMENT 'FK a Operarios/usuarios – quién hizo el conteo',
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de inserción automática del sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro maestro de conteo de caja inicial';

-- --------------------------------------------------------

--
-- Table structure for table `pos_caja_inicial_detalle`
--

CREATE TABLE `pos_caja_inicial_detalle` (
  `id` int(11) NOT NULL,
  `caja_inicial_id` int(11) NOT NULL COMMENT 'FK a pos_caja_inicial',
  `moneda` enum('NIO','USD') NOT NULL COMMENT 'Tipo de moneda',
  `denominacion` decimal(10,2) NOT NULL COMMENT 'Valor facial del billete/moneda',
  `cantidad` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de billetes/monedas',
  `total` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'denominacion * cantidad'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de denominaciones por conteo de caja inicial';

-- --------------------------------------------------------

--
-- Table structure for table `pos_facturas`
--

CREATE TABLE `pos_facturas` (
  `id` int(11) NOT NULL,
  `numero_factura` varchar(50) NOT NULL COMMENT 'Número/código de factura (editable)',
  `fecha` date NOT NULL,
  `id_proveedor` int(11) NOT NULL COMMENT 'FK a proveedores',
  `total_factura` decimal(12,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `estado` enum('activa','anulada') NOT NULL DEFAULT 'activa',
  `registrado_por` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `modificado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Facturas de compra de tienda (abastecimiento)';

-- --------------------------------------------------------

--
-- Table structure for table `pos_facturas_detalle`
--

CREATE TABLE `pos_facturas_detalle` (
  `id` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL COMMENT 'FK a pos_facturas',
  `id_presentacion` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `costo_unitario` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Calculado = costo_total_iva / cantidad',
  `costo_total_iva` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ingresado por usuario',
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de productos/servicios en facturas de tienda';

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `producto` varchar(255) NOT NULL,
  `unidad_medida` varchar(50) NOT NULL,
  `costo` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productos_delivery`
--

CREATE TABLE `productos_delivery` (
  `id` int(11) NOT NULL,
  `subgrupo_id` int(11) DEFAULT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `codigo_barras` varchar(20) DEFAULT NULL,
  `base` enum('agua','leche_yogurt','naranja','limon') DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `nombre_factura` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `tiene_tamanos` tinyint(1) DEFAULT 1,
  `precio_16oz` int(11) DEFAULT NULL,
  `precio_20oz` int(11) DEFAULT NULL,
  `precio_fijo` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `no_endulzantes` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `producto_maestro`
--

CREATE TABLE `producto_maestro` (
  `id` int(11) NOT NULL,
  `Nombre` varchar(150) NOT NULL,
  `SKU` varchar(50) NOT NULL,
  `Descripcion` varchar(500) DEFAULT NULL,
  `Id_categoria` int(11) NOT NULL,
  `Estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL,
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `producto_presentacion`
--

CREATE TABLE `producto_presentacion` (
  `id` int(11) NOT NULL,
  `SKU` varchar(100) NOT NULL COMMENT 'Código único del producto',
  `Nombre` varchar(255) NOT NULL COMMENT 'Nombre de la presentación',
  `id_producto_maestro` int(11) DEFAULT NULL COMMENT 'FK a producto_maestro',
  `id_unidad_producto` int(11) DEFAULT NULL COMMENT 'FK a unidad_producto',
  `es_vendible` enum('SI','NO') NOT NULL DEFAULT 'NO' COMMENT '¿Se puede vender?',
  `es_comprable` enum('SI','NO') NOT NULL DEFAULT 'NO' COMMENT '¿Se puede comprar?',
  `es_fabricable` enum('SI','NO') NOT NULL DEFAULT 'NO' COMMENT '¿Se fabrica?',
  `id_subgrupo_presentacion_producto` int(11) DEFAULT NULL,
  `Activo` enum('SI','NO') NOT NULL DEFAULT 'SI' COMMENT 'Estado vigente',
  `Id_receta_producto` int(11) DEFAULT NULL COMMENT 'FK a receta_producto (NULL si no tiene receta)',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios',
  `cantidad` decimal(10,2) DEFAULT 0.00,
  `compra_tienda` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 indica que el producto puede ser seleccionado para facturas de compra de tienda'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestión de presentaciones de productos';

-- --------------------------------------------------------

--
-- Table structure for table `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `condiciones` text DEFAULT NULL,
  `tipo_id` int(11) NOT NULL,
  `cliente_tipo_id` int(11) NOT NULL,
  `aplicacion_id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL COMMENT 'Valor del descuento (porcentaje o monto fijo)',
  `activo` tinyint(1) DEFAULT 1,
  `solo_local` tinyint(1) DEFAULT 0 COMMENT '1 si solo aplica en local, 0 si aplica también para delivery',
  `dia_especifico` int(11) DEFAULT NULL COMMENT 'Día de la semana (1-7) si aplica solo un día',
  `uso_interno` tinyint(1) DEFAULT 0 COMMENT '1 si es para uso interno (no visible a clientes)',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_aplicacion`
--

CREATE TABLE `promociones_aplicacion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_clientes_tipos`
--

CREATE TABLE `promociones_clientes_tipos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_grupos`
--

CREATE TABLE `promociones_grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_productos`
--

CREATE TABLE `promociones_productos` (
  `id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `subgrupo_id` int(11) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_requisitos`
--

CREATE TABLE `promociones_requisitos` (
  `id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `subgrupo_id` int(11) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promociones_tipos`
--

CREATE TABLE `promociones_tipos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_condiciones`
--

CREATE TABLE `promo_condiciones` (
  `id` int(11) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `tipo_cond` enum('A','B') NOT NULL COMMENT 'A=Contexto, B=Carrito',
  `opcion_id` int(11) DEFAULT NULL,
  `nombre_cond` varchar(50) NOT NULL COMMENT 'dia_semana|horario|fecha_rango|sucursal|tipo_cliente|canal_venta|producto|grupo_producto|tamano|cantidad_min|monto_min|combo',
  `valor_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Valores específicos de la condición serializados' CHECK (json_valid(`valor_json`)),
  `orden` tinyint(4) DEFAULT 0,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_condiciones_opciones`
--

CREATE TABLE `promo_condiciones_opciones` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('A','B') NOT NULL COMMENT 'A=Contexto, B=Carrito'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_promociones`
--

CREATE TABLE `promo_promociones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `codigo_interno` varchar(50) DEFAULT NULL,
  `descripcion_interna` text DEFAULT NULL,
  `prioridad` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Menor número = mayor prioridad',
  `estado` enum('borrador','activa','inactiva','archivada') DEFAULT 'borrador',
  `combinable` tinyint(1) DEFAULT 0,
  `ejecucion_automatica` tinyint(1) DEFAULT 0,
  `uso_unico_cliente` tinyint(1) DEFAULT 0,
  `requiere_autorizacion` tinyint(1) DEFAULT 0,
  `usos_maximos` int(11) DEFAULT NULL COMMENT 'NULL = sin límite',
  `descuento_maximo_cs` decimal(10,2) DEFAULT NULL COMMENT 'Techo de descuento en córdobas',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `objetivo_descuento` enum('todos','mas_barato','nth_item','get_y','factura','upgrade') NOT NULL DEFAULT 'todos',
  `objetivo_nth_numero` tinyint(4) DEFAULT NULL COMMENT 'Para nth_item: qué número (2, 3, 4...)',
  `objetivo_get_y_prod` int(11) DEFAULT NULL COMMENT 'FK a producto_presentacion para Get Y',
  `objetivo_get_y_cant` tinyint(4) DEFAULT 1,
  `objetivo_upgrade_de` varchar(10) DEFAULT NULL COMMENT 'Ej: 16oz',
  `objetivo_upgrade_a` varchar(10) DEFAULT NULL COMMENT 'Ej: 20oz',
  `resultado_tipo` enum('pct_producto','pct_factura','monto_producto','monto_factura') NOT NULL DEFAULT 'pct_producto',
  `resultado_valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `ruc_nit` varchar(50) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `comprasucursal` int(11) DEFAULT NULL,
  `vigente` tinyint(1) DEFAULT 1,
  `notas_internas` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL,
  `modificado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proveedores_compras_servicios`
--

CREATE TABLE `proveedores_compras_servicios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proveedor_tipo_pago`
--

CREATE TABLE `proveedor_tipo_pago` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_tipo_pago` int(11) NOT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `asignado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_offline_bank`
--

CREATE TABLE `qr_offline_bank` (
  `id` int(11) NOT NULL,
  `qr_id` int(11) NOT NULL,
  `key_hash` varchar(16) NOT NULL,
  `points` int(11) NOT NULL,
  `codigo_local` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receta_producto_global`
--

CREATE TABLE `receta_producto_global` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL COMMENT 'Nombre de la receta',
  `SKU` varchar(100) DEFAULT NULL COMMENT 'Código de la receta',
  `id_presentacion_producto` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `id_tipo_receta` int(11) NOT NULL COMMENT 'FK a tipo_receta_producto',
  `descripcion` varchar(500) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recetas maestras de productos';

-- --------------------------------------------------------

--
-- Table structure for table `reclamos`
--

CREATE TABLE `reclamos` (
  `id` int(11) NOT NULL,
  `fuente` varchar(50) NOT NULL,
  `sucursal` varchar(100) DEFAULT NULL,
  `sucursal_codigo` varchar(10) DEFAULT NULL,
  `fecha_reclamo` date NOT NULL,
  `fecha_evento` date NOT NULL,
  `hora_evento` varchar(20) NOT NULL,
  `medio_compra` varchar(50) NOT NULL,
  `tipo_reclamo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `investigacion_preliminar` text DEFAULT NULL,
  `gestor_reclamo` varchar(100) NOT NULL,
  `fecha_registro` date NOT NULL,
  `hora_registro` varchar(20) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `grupo_id` int(11) DEFAULT NULL,
  `tipo_reclamo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamos_cargos_responsables`
--

CREATE TABLE `reclamos_cargos_responsables` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `tipo_id` int(11) DEFAULT NULL,
  `cod_niveles_cargos` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamos_grupos`
--

CREATE TABLE `reclamos_grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamos_imagenes`
--

CREATE TABLE `reclamos_imagenes` (
  `id` int(11) NOT NULL,
  `reclamo_id` int(11) NOT NULL,
  `ruta_imagen` varchar(255) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamos_productos`
--

CREATE TABLE `reclamos_productos` (
  `id` int(11) NOT NULL,
  `reclamo_id` int(11) NOT NULL,
  `producto` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamos_tipos`
--

CREATE TABLE `reclamos_tipos` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamos_videos`
--

CREATE TABLE `reclamos_videos` (
  `id` int(11) NOT NULL,
  `reclamo_id` int(11) NOT NULL,
  `ruta_video` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reembolsos_detalles`
--

CREATE TABLE `reembolsos_detalles` (
  `id` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `monto_cordobas` decimal(12,2) DEFAULT NULL,
  `foto_factura` varchar(255) DEFAULT NULL,
  `extracted_json` text DEFAULT NULL COMMENT 'Full JSON from AI for traceability',
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reembolsos_solicitudes`
--

CREATE TABLE `reembolsos_solicitudes` (
  `id` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_cuenta_proveedor` int(11) DEFAULT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `ceco` int(11) DEFAULT NULL,
  `total_cordobas` decimal(12,2) DEFAULT 0.00,
  `moneda` varchar(15) DEFAULT 'Cordobas',
  `estado` enum('pendiente','aprobado','procesado','pagado','rechazado') DEFAULT 'pendiente',
  `usuario_registro` int(11) NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportes_colaboradores`
--

CREATE TABLE `reportes_colaboradores` (
  `id` int(11) NOT NULL,
  `reporte_id` int(11) NOT NULL,
  `colaborador` varchar(100) NOT NULL,
  `monto_responsabilidad` decimal(10,2) NOT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportes_investigacion`
--

CREATE TABLE `reportes_investigacion` (
  `id` int(11) NOT NULL,
  `reclamo_id` int(11) NOT NULL,
  `fecha_resolucion` date NOT NULL,
  `resolucion` varchar(50) NOT NULL,
  `investigacion` text NOT NULL,
  `plan_accion` text NOT NULL,
  `tipo_reclamo_operaciones` varchar(100) DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Restar 6 horas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisicion_personal`
--

CREATE TABLE `requisicion_personal` (
  `id` int(11) NOT NULL,
  `nombre_cargo` varchar(255) NOT NULL,
  `area_cargo` varchar(225) DEFAULT NULL COMMENT 'Área del cargo (opcional)',
  `tipo_plaza` enum('Temporal','Permanente') DEFAULT NULL,
  `sucursal` int(11) DEFAULT NULL COMMENT 's.codigo (de la tabla sucursales)',
  `cantidad` int(11) NOT NULL DEFAULT 1 COMMENT 'Cantidad de plazas solicitadas',
  `salario_propuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `nivel_urgencia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=No urgente, 2=Medio, 3=Urgente, 4=Crítico',
  `cargo_reporta_a` int(11) DEFAULT NULL COMMENT 'CodOperario del jefe directo',
  `justificacion` text NOT NULL COMMENT 'Motivo de la solicitud',
  `ruta_perfil_puesto` varchar(500) DEFAULT NULL COMMENT 'Ruta del archivo PDF',
  `comentario_aprobacion_rechazo` text DEFAULT NULL,
  `status` enum('Solicitado','Aprobado','Rechazado') NOT NULL DEFAULT 'Solicitado',
  `usuario_registra` int(11) NOT NULL COMMENT 'CodOperario que registra',
  `usuario_modifica` int(11) DEFAULT NULL COMMENT 'CodOperario que modifica',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `estudios_minimos` varchar(255) DEFAULT NULL,
  `carreras_aptas` varchar(255) DEFAULT NULL,
  `conocimientos_especificos` text DEFAULT NULL,
  `idiomas` varchar(255) DEFAULT NULL,
  `herramientas_office` varchar(255) DEFAULT NULL,
  `aptitudes_especificas` text DEFAULT NULL,
  `experiencia_deseada` text DEFAULT NULL,
  `funciones_responsabilidades` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ResenasGoogle`
--

CREATE TABLE `ResenasGoogle` (
  `locationId` varchar(50) DEFAULT NULL,
  `locationName` varchar(50) DEFAULT NULL,
  `reviewId` varchar(100) DEFAULT NULL,
  `reviewerName` varchar(100) DEFAULT NULL,
  `starRating` varchar(20) DEFAULT NULL,
  `comment` varchar(3000) DEFAULT NULL,
  `createTime` varchar(50) DEFAULT NULL,
  `updateTime` varchar(50) DEFAULT NULL,
  `reviewReplyComment` varchar(3000) DEFAULT NULL,
  `reviewReplyUpdateTime` varchar(50) DEFAULT NULL,
  `reviewReplyOwnerName` varchar(100) DEFAULT NULL,
  `reviewSource` varchar(20) DEFAULT NULL,
  `reviewType` varchar(20) DEFAULT NULL,
  `reviewIsEdited` varchar(20) DEFAULT NULL,
  `reviewHasResponse` varchar(20) DEFAULT NULL,
  `reviewResponseRate` varchar(20) DEFAULT NULL,
  `reviewImageUrls` varchar(20) DEFAULT NULL,
  `reviewVideoUrls` varchar(20) DEFAULT NULL,
  `reviewThumbnailUrls` varchar(20) DEFAULT NULL,
  `reviewOwnerVisitTime` varchar(20) DEFAULT NULL,
  `reviewOwnerComment` varchar(20) DEFAULT NULL,
  `reviewOwnerResponseTime` varchar(20) DEFAULT NULL,
  `reviewOwnerResponseComment` varchar(3000) DEFAULT NULL,
  `extractionDate` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SalarioINSS`
--

CREATE TABLE `SalarioINSS` (
  `id` int(11) NOT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `monto_salario_inss` decimal(10,2) DEFAULT NULL,
  `inicio` date DEFAULT NULL,
  `final` date DEFAULT NULL,
  `hospital_inss` varchar(100) DEFAULT NULL,
  `hospital_riesgo_laboral` varchar(100) DEFAULT NULL,
  `observaciones_inss` text DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SalarioOperario`
--

CREATE TABLE `SalarioOperario` (
  `CodSalarioOperario` int(11) NOT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `inicio` date DEFAULT NULL,
  `fin` date DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp(),
  `frecuencia_pago` enum('quincenal','mensual') DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SemanasSistema`
--

CREATE TABLE `SemanasSistema` (
  `id` int(11) NOT NULL,
  `numero_semana` int(11) NOT NULL COMMENT 'Número de semana según sistema de la empresa',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio (lunes)',
  `fecha_fin` date NOT NULL COMMENT 'Fecha de fin (domingo)',
  `anio` int(11) NOT NULL COMMENT 'Año al que pertenece la semana',
  `semana_iso` int(11) NOT NULL COMMENT 'Semana según estándar ISO',
  `creada_por` int(11) NOT NULL COMMENT 'Usuario que creó la semana',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='numeros de semana con fecha inicio y final';

-- --------------------------------------------------------

--
-- Table structure for table `servicios_delivery`
--

CREATE TABLE `servicios_delivery` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `costo_primer_envio` int(11) DEFAULT NULL,
  `costo_regular` int(11) DEFAULT NULL,
  `costo_mayor_distancia` int(11) DEFAULT NULL,
  `distancia_limite_km` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes_cotizacion`
--

CREATE TABLE `solicitudes_cotizacion` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `version` int(11) DEFAULT 1,
  `solicitante_id` int(11) NOT NULL,
  `solicitante_nombre` varchar(255) NOT NULL,
  `fecha_solicitud` date NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada','completada','cancelada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `observaciones_compras` text DEFAULT NULL,
  `compras_usuario_id` int(11) DEFAULT NULL,
  `compras_usuario_nombre` varchar(255) DEFAULT NULL,
  `fecha_observaciones_compras` datetime DEFAULT NULL,
  `gerente_aprobador_id` int(11) DEFAULT NULL,
  `gerente_aprobador_nombre` varchar(255) DEFAULT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes_cotizacion_fotos`
--

CREATE TABLE `solicitudes_cotizacion_fotos` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `foto_nombre` varchar(255) NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes_cotizacion_historial`
--

CREATE TABLE `solicitudes_cotizacion_historial` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `usuario_nombre` varchar(255) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `detalles` text DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes_cotizacion_productos`
--

CREATE TABLE `solicitudes_cotizacion_productos` (
  `id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `producto_descripcion` text NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) DEFAULT 0.00,
  `foto_referencia` varchar(255) DEFAULT NULL,
  `notas_compras` text DEFAULT NULL,
  `fecha_notas_compras` datetime DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes_vacaciones`
--

CREATE TABLE `solicitudes_vacaciones` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL COMMENT 'Colaborador que solicita vacaciones',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha inicio del rango',
  `fecha_fin` date NOT NULL COMMENT 'Fecha fin del rango',
  `cod_sucursal` varchar(10) NOT NULL,
  `tipo_solicitud` enum('Vacaciones') NOT NULL DEFAULT 'Vacaciones',
  `observaciones` text DEFAULT NULL,
  `foto_soporte` varchar(255) DEFAULT NULL COMMENT 'Foto opcional de soporte',
  `solicitado_por` int(11) NOT NULL COMMENT 'CodOperario del usuario que sube la solicitud',
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Aprobado_Operaciones','Aprobado_RH','Rechazado') NOT NULL DEFAULT 'Pendiente',
  `aprobado_operaciones_por` int(11) DEFAULT NULL COMMENT 'Código del usuario cargo 11 que aprobó',
  `fecha_aprobacion_operaciones` datetime DEFAULT NULL,
  `aprobado_rh_por` int(11) DEFAULT NULL COMMENT 'Código del usuario cargo 13 o 28 que aprobó',
  `fecha_aprobacion_rh` datetime DEFAULT NULL,
  `rechazado_por` int(11) DEFAULT NULL,
  `fecha_rechazo` datetime DEFAULT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `porcentaje_pago` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_empleo`
--

CREATE TABLE `solicitud_empleo` (
  `id` int(11) NOT NULL,
  `id_postulacion` int(11) NOT NULL COMMENT 'Relación con postulacion_plaza.id',
  `token` varchar(64) NOT NULL COMMENT 'Token único para acceso al formulario',
  `codigo_acceso` varchar(10) DEFAULT NULL,
  `link_status` enum('activo','deshabilitado') DEFAULT 'activo',
  `nombre1` varchar(100) DEFAULT NULL,
  `nombre2` varchar(100) DEFAULT NULL,
  `apellido1` varchar(100) DEFAULT NULL,
  `apellido2` varchar(100) DEFAULT NULL,
  `puesto_solicitado` varchar(255) DEFAULT NULL,
  `expectativa_salarial` decimal(15,2) DEFAULT NULL,
  `telefono_fijo` varchar(20) DEFAULT NULL,
  `telefono_celular` varchar(20) DEFAULT NULL,
  `direccion_actual` text DEFAULT NULL,
  `lugar_nacimiento` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `edad` int(3) DEFAULT NULL,
  `nacionalidad` varchar(100) DEFAULT NULL,
  `correo_electronico` varchar(255) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `estatura` decimal(5,2) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `tipo_sangre` varchar(10) DEFAULT NULL,
  `contacto_emergencia_nombre` varchar(255) DEFAULT NULL,
  `contacto_emergencia_telefono` varchar(20) DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `posee_vehiculo` tinyint(1) DEFAULT 0,
  `vehiculo_modelo` varchar(100) DEFAULT NULL,
  `vehiculo_ano` varchar(10) DEFAULT NULL,
  `numero_cedula` varchar(50) DEFAULT NULL,
  `lugar_emision_cedula` varchar(255) DEFAULT NULL,
  `fecha_emision_cedula` date DEFAULT NULL,
  `numero_inss` varchar(50) DEFAULT NULL,
  `numero_serie_licencia` varchar(50) DEFAULT NULL,
  `tiene_familiares_empresa` tinyint(1) DEFAULT 0,
  `familiar_empresa_nombre` varchar(255) DEFAULT NULL,
  `familiar_empresa_puesto` varchar(255) DEFAULT NULL,
  `familiar_empresa_lugar` varchar(255) DEFAULT NULL,
  `conocimientos_especializados` text DEFAULT NULL,
  `idiomas` text DEFAULT NULL,
  `aficiones` text DEFAULT NULL,
  `practica_deporte` tinyint(1) DEFAULT 0,
  `deportes_cuales` text DEFAULT NULL,
  `padece_enfermedad` tinyint(1) DEFAULT 0,
  `enfermedades_cuales` text DEFAULT NULL,
  `usa_lentes` tinyint(1) DEFAULT 0,
  `padece_capacidad_especial` tinyint(1) DEFAULT 0,
  `capacidad_especial_cual` varchar(255) DEFAULT NULL,
  `hospitalizado_ultimos_6_meses` tinyint(1) DEFAULT 0,
  `razon_hospitalizacion` text DEFAULT NULL,
  `areas_interes` text DEFAULT NULL,
  `tiene_pariente_amigo_empresa` tinyint(1) DEFAULT 0,
  `pariente_amigo_nombre` varchar(255) DEFAULT NULL,
  `pariente_amigo_puesto` varchar(255) DEFAULT NULL,
  `pariente_amigo_tipo` enum('Pariente','Amigo') DEFAULT NULL,
  `ha_ocupado_cargo_publico` tinyint(1) DEFAULT 0,
  `cargo_publico_desempenado` varchar(255) DEFAULT NULL,
  `periodo_cargo_publico` varchar(100) DEFAULT NULL,
  `familiar_cargo_publico` tinyint(1) DEFAULT 0,
  `familiar_cargo_publico_nombre` varchar(255) DEFAULT NULL,
  `familiar_cargo_publico_periodo` varchar(100) DEFAULT NULL,
  `familiar_cargo_publico_cargo` varchar(255) DEFAULT NULL,
  `cargo_directivo_partido_politico` tinyint(1) DEFAULT 0,
  `partido_politico_nombre` varchar(255) DEFAULT NULL,
  `partido_politico_periodo` varchar(100) DEFAULT NULL,
  `adjunto_cedula` varchar(255) DEFAULT NULL,
  `adjunto_record_ley510` varchar(255) DEFAULT NULL,
  `adjunto_constancia_judicial` varchar(255) DEFAULT NULL,
  `adjunto_certificado_salud` varchar(255) DEFAULT NULL,
  `adjunto_cotizacion_inss` varchar(255) DEFAULT NULL,
  `fecha_aplicacion` timestamp NULL DEFAULT NULL,
  `fecha_entrevista` date DEFAULT NULL,
  `autorizo_ingreso` tinyint(1) DEFAULT 0,
  `entrevistador_firma` varchar(255) DEFAULT NULL,
  `entrevistador_puesto` varchar(255) DEFAULT NULL,
  `entrevistador_depto` varchar(255) DEFAULT NULL,
  `cuenta_bancaria` varchar(50) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `porcentaje_completitud` int(3) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_empleo_estudios`
--

CREATE TABLE `solicitud_empleo_estudios` (
  `id` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `nivel_estudio` varchar(100) DEFAULT NULL,
  `lugar_estudio` varchar(255) DEFAULT NULL,
  `grado_cursado` varchar(100) DEFAULT NULL,
  `año_estudio` varchar(20) DEFAULT NULL,
  `titulo_obtenido` varchar(255) DEFAULT NULL,
  `estudiando_actualmente` tinyint(1) DEFAULT 0,
  `curso_actual` varchar(255) DEFAULT NULL,
  `nivel_actual` varchar(100) DEFAULT NULL,
  `horario_estudio` varchar(100) DEFAULT NULL,
  `lugar_estudio_actual` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_empleo_experiencia`
--

CREATE TABLE `solicitud_empleo_experiencia` (
  `id` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `tipo_empleo` enum('Ultimo','Penultimo','Antepenultimo') DEFAULT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `direccion_empresa` text DEFAULT NULL,
  `telefono_empresa` varchar(20) DEFAULT NULL,
  `jefe_inmediato` varchar(255) DEFAULT NULL,
  `fecha_desde` date DEFAULT NULL,
  `fecha_hasta` date DEFAULT NULL,
  `puesto` varchar(255) DEFAULT NULL,
  `sueldo` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_empleo_familiares`
--

CREATE TABLE `solicitud_empleo_familiares` (
  `id` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `parentesco` varchar(100) DEFAULT NULL,
  `nombre_familiar` varchar(255) DEFAULT NULL,
  `ocupacion_familiar` varchar(255) DEFAULT NULL,
  `lugar_trabajo_familiar` varchar(255) DEFAULT NULL,
  `telefono_familiar` varchar(20) DEFAULT NULL,
  `depende_economicamente` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_empleo_hijos`
--

CREATE TABLE `solicitud_empleo_hijos` (
  `id` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `nombre_hijo` varchar(255) DEFAULT NULL,
  `fecha_nacimiento_hijo` date DEFAULT NULL,
  `edad_hijo` int(3) DEFAULT NULL,
  `sexo_hijo` enum('M','F') DEFAULT NULL,
  `estudios_hijo` varchar(255) DEFAULT NULL,
  `depende_economicamente_hijo` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_empleo_referencias`
--

CREATE TABLE `solicitud_empleo_referencias` (
  `id` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `nombre_referencia` varchar(255) DEFAULT NULL,
  `direccion_referencia` text DEFAULT NULL,
  `telefono_casa_referencia` varchar(20) DEFAULT NULL,
  `telefono_oficina_referencia` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subgrupos_productos`
--

CREATE TABLE `subgrupos_productos` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `precio_16oz` int(11) DEFAULT NULL,
  `precio_20oz` int(11) DEFAULT NULL,
  `precio_normal` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `no_endulzantes` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subgrupo_presentacion_producto`
--

CREATE TABLE `subgrupo_presentacion_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `id_grupo_presentacion_producto` int(11) NOT NULL COMMENT 'FK a grupo_presentacion_producto',
  `categoria_insumo` varchar(11) DEFAULT NULL COMMENT 'Letra de la categoría de insumo para mapeo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Subgrupos de productos';

-- --------------------------------------------------------

--
-- Table structure for table `SubReceta`
--

CREATE TABLE `SubReceta` (
  `CodSubReceta` int(11) DEFAULT NULL,
  `CodIngrediente` varchar(100) DEFAULT NULL,
  `CodBatido` varchar(100) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `Tipo` varchar(100) DEFAULT NULL,
  `codporcion` int(11) DEFAULT NULL,
  `InsumoClave` tinyint(1) DEFAULT NULL,
  `tiposervido` varchar(100) DEFAULT NULL,
  `ordenreceta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sucursales`
--

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ip_direccion` varchar(45) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `Fecha_Apertura` date DEFAULT NULL COMMENT 'yyyy-mm-dd',
  `departamento` text DEFAULT NULL,
  `cod_departamento` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL COMMENT 'Correo de sucursal',
  `cod_odoo` int(11) DEFAULT NULL COMMENT 'Prueba odoo, partner_id',
  `activa` tinyint(1) DEFAULT 1,
  `sucursal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '¿Es sucursal? 1=Si 0=No',
  `viatico_nocturno` int(11) DEFAULT 0,
  `Latitude` double DEFAULT NULL,
  `Longitude` double DEFAULT NULL,
  `cod_googlebusiness` varchar(100) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  `VMTAP` tinyint(4) DEFAULT 1,
  `cookie_token` varchar(255) DEFAULT NULL,
  `pos_cookie_token` varchar(64) DEFAULT NULL COMMENT 'Token de autorizacion exclusivo del dominio pos.batidospitaya.com'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TardanzasManuales`
--

CREATE TABLE `TardanzasManuales` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha_tardanza` date NOT NULL,
  `cod_sucursal` int(11) NOT NULL,
  `minutos_tardanza` int(11) DEFAULT NULL,
  `tipo_justificacion` enum('llave','error_sistema','accidente') NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `estado` enum('Pendiente','Justificado','No Válido') NOT NULL DEFAULT 'Pendiente',
  `registrado_por` int(11) NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `cod_contrato` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TardanzasStatus`
--

CREATE TABLE `TardanzasStatus` (
  `id` int(11) NOT NULL,
  `id_marcacion` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `estado` enum('Pendiente','Justificado','No Válido') NOT NULL DEFAULT 'Pendiente',
  `observaciones` varchar(255) DEFAULT NULL,
  `tiempo_tardanza` decimal(5,2) DEFAULT 0.00 COMMENT 'Minutos de tardanza',
  `creado_por` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TipoContrato`
--

CREATE TABLE `TipoContrato` (
  `CodTipoContrato` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TipoSalida`
--

CREATE TABLE `TipoSalida` (
  `CodTipoSalida` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salida o finzalizacion de contrato';

-- --------------------------------------------------------

--
-- Table structure for table `tipos_falta`
--

CREATE TABLE `tipos_falta` (
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `porcentaje_pago` decimal(5,2) NOT NULL COMMENT 'Porcentaje que paga la empresa (puede ser negativo)',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `tipo_status` varchar(100) NOT NULL DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipo_cambio`
--

CREATE TABLE `tipo_cambio` (
  `id` int(11) NOT NULL,
  `fecha` date NOT NULL DEFAULT curdate(),
  `tasa` decimal(10,1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipo_estado_horario`
--

CREATE TABLE `tipo_estado_horario` (
  `codigo` char(250) NOT NULL,
  `tipo` enum('con_marcacion','sin_marcacion') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  `justificacion` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipo_pago_proveedores`
--

CREATE TABLE `tipo_pago_proveedores` (
  `id` int(11) NOT NULL,
  `modalidad` varchar(100) NOT NULL COMMENT 'Ej: Contado, Crédito 15 días, Crédito 30 días',
  `tipopago` varchar(100) NOT NULL COMMENT 'Ej: Transferencia, Efectivo, Cheque',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipo_receta_producto`
--

CREATE TABLE `tipo_receta_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Producto Venta, Producción, Mix Marca, etc',
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de receta (Venta, Producción, Mix, etc)';

-- --------------------------------------------------------

--
-- Table structure for table `tools_erp`
--

CREATE TABLE `tools_erp` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `tipo_componente` enum('herramienta','indicador','balance') DEFAULT 'herramienta',
  `class_name` varchar(100) DEFAULT NULL,
  `config_json` text DEFAULT NULL,
  `grupo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `url_real` varchar(255) DEFAULT NULL,
  `url_alias` varchar(255) DEFAULT NULL,
  `icono` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unidad_producto`
--

CREATE TABLE `unidad_producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Nombre de la unidad (ej: Gramos, Litros, Onzas)',
  `observaciones` varchar(255) DEFAULT NULL COMMENT 'Notas adicionales sobre la unidad',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios - Usuario que creó',
  `fecha_modificacion` datetime DEFAULT NULL,
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios - Usuario que modificó'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo maestro de unidades de medida';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','gerente','cajero','almacen') NOT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Vacaciones`
--

CREATE TABLE `Vacaciones` (
  `CodVacaciones` int(11) NOT NULL,
  `inicio` date DEFAULT NULL,
  `fin` date DEFAULT NULL,
  `cod_contrato` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_hora_reg_sys` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `validacion_cv_ia`
--

CREATE TABLE `validacion_cv_ia` (
  `id` int(11) NOT NULL,
  `id_postulacion` int(11) NOT NULL COMMENT 'FK a postulacion_plaza',
  `campo` varchar(255) NOT NULL COMMENT 'Nombre del campo analizado (ej: experiencia_años, nivel_educacion)',
  `valor` text NOT NULL COMMENT 'Valor extraído/analizado',
  `confianza` decimal(5,2) DEFAULT NULL COMMENT 'Nivel de confianza del análisis (0-100)',
  `usuario_registra` int(11) DEFAULT NULL COMMENT 'CodOperario (NULL si análisis automático)',
  `usuario_modifica` int(11) DEFAULT NULL COMMENT 'CodOperario que modifica',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `variedad_producto_presentacion`
--

CREATE TABLE `variedad_producto_presentacion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Color, forma, tamaño, etc',
  `descripcion` varchar(255) DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT 0,
  `id_presentacion_producto` int(11) NOT NULL COMMENT 'FK a producto_presentacion',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_creacion` int(11) NOT NULL COMMENT 'FK a Operarios',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `usuario_modificacion` int(11) DEFAULT NULL COMMENT 'FK a Operarios'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Variaciones de un mismo producto (colores, formas, etc)';

-- --------------------------------------------------------

--
-- Table structure for table `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `sucursal_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `direccion_pedido` text DEFAULT NULL,
  `tipo_servicio` enum('delivery','retiro_local') NOT NULL,
  `hora_retiro` time DEFAULT NULL COMMENT 'Tipo de Servicio en hora de retiro para el cliente (es hora real no se resta nada a este tiempo)',
  `servicio_delivery_id` int(11) DEFAULT NULL,
  `distancia` decimal(5,2) DEFAULT NULL,
  `conductor` varchar(100) DEFAULT NULL,
  `tipo_pago` enum('efectivo','transferencia','pos') NOT NULL,
  `monto_total` decimal(12,2) NOT NULL,
  `monto_dolares` decimal(12,2) DEFAULT NULL,
  `monto_cordobas` decimal(12,2) DEFAULT NULL,
  `pago_recibido_dolares` decimal(12,2) DEFAULT NULL,
  `pago_recibido_cordobas` decimal(12,2) DEFAULT NULL,
  `cambio_cordobas` decimal(12,2) DEFAULT NULL,
  `cargo_delivery` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendiente','completado','cancelado') DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `hora_impresion` datetime DEFAULT NULL COMMENT 'Cuando desde el mismo crearpedido.php se imprime',
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Restar 6 horas, hora de registro en sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `VentasGlobalesAccessCSV`
--

CREATE TABLE `VentasGlobalesAccessCSV` (
  `Anulado` int(11) DEFAULT NULL,
  `MotivoAnulado` text DEFAULT NULL,
  `Fecha` date DEFAULT NULL,
  `Hora` time DEFAULT NULL,
  `CodPedido` int(11) DEFAULT NULL,
  `CodCliente` int(11) DEFAULT NULL,
  `aPOS` varchar(30) DEFAULT NULL,
  `Delivery_Nombre` varchar(30) DEFAULT NULL,
  `Tipo` varchar(30) DEFAULT NULL,
  `NombreGrupo` varchar(30) DEFAULT NULL,
  `DBBatidos_Nombre` varchar(100) DEFAULT NULL,
  `Medida` varchar(30) DEFAULT NULL,
  `Cantidad` double DEFAULT NULL,
  `CodigoPromocion` int(11) DEFAULT NULL,
  `Precio` double DEFAULT NULL,
  `local` varchar(11) DEFAULT NULL,
  `Caja` varchar(100) DEFAULT NULL,
  `Modalidad` varchar(30) DEFAULT NULL,
  `Motorizado` varchar(100) DEFAULT NULL,
  `Observaciones` text DEFAULT NULL,
  `Precio_Unitario_Sin_Descuento` double DEFAULT NULL,
  `Impresiones` int(11) DEFAULT NULL,
  `HoraCreado` time DEFAULT NULL,
  `HoraIngresoProducto` time DEFAULT NULL,
  `HoraImpreso` time DEFAULT NULL,
  `Propina` double DEFAULT NULL,
  `Semana` int(11) DEFAULT NULL,
  `Puntos` double DEFAULT NULL,
  `CodProducto` varchar(20) DEFAULT NULL,
  `MontoFactura` int(11) DEFAULT NULL,
  `Sucursal_Nombre` varchar(20) DEFAULT NULL,
  `PedidoDeCentral` int(11) DEFAULT NULL,
  `CodMotorizado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `ventas_detalle`
--

CREATE TABLE `ventas_detalle` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tamano` enum('16oz','20oz','unico') DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `endulzante_id` int(11) DEFAULT NULL,
  `promocion` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `promocion_id` int(11) DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Restar 6 horas, hora de registro en sistema'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ventas_detalle_promociones`
--

CREATE TABLE `ventas_detalle_promociones` (
  `id` int(11) NOT NULL,
  `venta_detalle_id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `monto_descuento` decimal(10,2) NOT NULL,
  `fecha_aplicacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ventas_extras`
--

CREATE TABLE `ventas_extras` (
  `id` int(11) NOT NULL,
  `venta_detalle_id` int(11) NOT NULL,
  `extra_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ventas_meta`
--

CREATE TABLE `ventas_meta` (
  `id` int(11) NOT NULL,
  `cod_sucursal` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `meta` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ventas_promociones`
--

CREATE TABLE `ventas_promociones` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `monto_descuento` decimal(10,2) NOT NULL,
  `fecha_aplicacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `viaticos`
--

CREATE TABLE `viaticos` (
  `id` int(11) NOT NULL,
  `cod_operario` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'Alimentación, Transporte, Nocturno',
  `cantidad` decimal(10,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `sucursal_codigo` varchar(10) NOT NULL,
  `creado_por` int(11) NOT NULL COMMENT 'Referencia a CodOperario en tabla Operarios',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_por` int(11) DEFAULT NULL COMMENT 'Referencia a CodOperario en tabla Operarios',
  `fecha_actualizacion` datetime DEFAULT NULL,
  `fecha_pago` date DEFAULT NULL COMMENT 'Fecha a pagar por Contabilidad',
  `cod_contrato` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_deducciones_completas`
-- (See below for the actual view)
--
CREATE TABLE `view_deducciones_completas` (
`tipo` varchar(19)
,`id` int(11)
,`fecha_evento` datetime /* mariadb-5.3 */
,`fecha_deduccion` datetime /* mariadb-5.3 */
,`sucursal_id` varchar(11)
,`sucursal_nombre` varchar(50)
,`operario_id` int(11)
,`operario_nombre` varchar(1023)
,`comentarios` mediumtext
,`monto_original` double(12,2)
,`monto` double(12,2)
,`url_ver` varchar(30)
,`cod_contrato` int(11)
,`fecha_registro` timestamp /* mariadb-5.3 */
,`estado_deduccion` varchar(25)
,`fecha_aplicacion` varchar(38)
,`tipo_concepto` varchar(20)
,`detalle_combinado` longtext
,`fecha_registro_formateada` varchar(24)
,`persona_completa` text
,`monto_absoluto` double(19,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_estadisticas_por_departamentos_y_semanas_reclamos`
-- (See below for the actual view)
--
CREATE TABLE `view_estadisticas_por_departamentos_y_semanas_reclamos` (
`anio` int(5)
,`mes_numero` int(3)
,`numero_semana` int(11)
,`semana_fecha_inicio` date
,`semana_fecha_fin` date
,`cod_departamento` int(11)
,`nombre_departamento` varchar(50)
,`sucursal_codigo` varchar(10)
,`nombre_sucursal` varchar(100)
,`fuente` varchar(50)
,`total_reclamos` bigint(21)
,`reclamos_abiertos` decimal(22,0)
,`reclamos_equipo_tienda` decimal(22,0)
,`reclamos_cerrados` decimal(22,0)
,`tipos_reclamo_diferentes` bigint(21)
,`medios_compra_diferentes` bigint(21)
,`total_productos_reclamados` decimal(42,0)
,`monto_total_productos` decimal(54,2)
,`promedio_productos_por_reclamo` decimal(23,2)
,`total_colaboradores_involucrados` decimal(42,0)
,`promedio_colaboradores_por_reclamo` decimal(23,2)
,`monto_total_responsabilidad` decimal(54,2)
,`total_imagenes` decimal(42,0)
,`total_videos` decimal(42,0)
,`promedio_dias_respuesta` decimal(13,2)
,`investigaciones_completadas` decimal(32,0)
,`porcentaje_investigaciones` decimal(38,2)
,`primer_reclamo_periodo` varchar(24)
,`ultimo_reclamo_periodo` varchar(24)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_faltas_no_reportadas_septimo`
-- (See below for the actual view)
--
CREATE TABLE `view_faltas_no_reportadas_septimo` (
`codigo_contrato` varchar(11)
,`persona` varchar(1035)
,`sucursal` varchar(50)
,`fecha` date
,`observaciones` varchar(39)
,`fecha_registro` date
,`origen` varchar(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_permisos`
-- (See below for the actual view)
--
CREATE TABLE `view_permisos` (
`codigo_contrato` varchar(11)
,`persona` text
,`sucursal` varchar(50)
,`fecha` date
,`dias` int(1)
,`porcentaje_salario_pagar` decimal(5,2)
,`tipo_permiso` varchar(26)
,`observaciones` mediumtext
,`fecha_registro` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_reclamos_completa`
-- (See below for the actual view)
--
CREATE TABLE `view_reclamos_completa` (
`reclamo_id` int(11)
,`fuente` varchar(50)
,`nombre_sucursal` varchar(100)
,`sucursal_codigo` varchar(10)
,`fecha_reclamo` date
,`fecha_evento` date
,`hora_evento` varchar(20)
,`medio_compra` varchar(50)
,`tipo_reclamo` varchar(100)
,`descripcion` text
,`investigacion_preliminar` text
,`gestor_reclamo` varchar(100)
,`fecha_registro` date
,`hora_registro` varchar(20)
,`fecha_hora` timestamp
,`investigacion_id` int(11)
,`fecha_resolucion` date
,`resolucion` varchar(50)
,`tipo_reclamo_operaciones` varchar(100)
,`investigacion` text
,`plan_accion` text
,`fecha_hora_investigacion` timestamp
,`sucursal_nombre_completo` varchar(50)
,`cod_departamento` int(11)
,`nombre_departamento` varchar(50)
,`numero_semana` int(11)
,`semana_fecha_inicio` date
,`semana_fecha_fin` date
,`productos_reclamo` longtext
,`total_productos` bigint(21)
,`monto_total_productos` decimal(32,2)
,`colaboradores_involucrados` longtext
,`total_colaboradores_involucrados` bigint(21)
,`monto_total_responsabilidad` decimal(32,2)
,`total_imagenes` bigint(21)
,`total_videos` bigint(21)
,`estado_reclamo` varchar(16)
,`fecha_hora_ajustada` varchar(24)
,`fecha_ajustada_corta` varchar(38)
,`fecha_hora_formateada` varchar(47)
,`fecha_investigacion_formateada` varchar(47)
,`mes_numero` int(3)
,`anio` int(5)
,`dias_diferencia_evento_reclamo` int(8)
,`sucursal_departamento_original` text
,`sucursal_activa` tinyint(1)
,`tiene_investigacion` int(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_reporte_tardanzas`
-- (See below for the actual view)
--
CREATE TABLE `view_reporte_tardanzas` (
`Código` int(11)
,`Persona` varchar(1035)
,`Tardanzas` int(1)
,`Tardanzas_Justificadas` int(1)
,`Fecha_Registro` datetime /* mariadb-5.3 */
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_reporte_tardanzas_completo`
-- (See below for the actual view)
--
CREATE TABLE `view_reporte_tardanzas_completo` (
`Código` int(11)
,`Persona` varchar(1035)
,`Fecha_Pago` varchar(0)
,`Tardanzas` int(1)
,`Tardanzas_Justificadas` int(1)
,`CodOperario` int(11)
,`Nombre_Completo` varchar(1023)
,`CodSucursal` varchar(11)
,`Sucursal` varchar(50)
,`Fecha_Tardanza` date
,`Estado_Tardanza` varchar(11)
,`Tipo_Justificacion` varchar(13)
,`Observaciones` varchar(255)
,`Fecha_Registro` datetime /* mariadb-5.3 */
,`Hora_Entrada_Programada` time /* mariadb-5.3 */
,`Hora_Salida_Programada` time /* mariadb-5.3 */
,`Hora_Entrada_Marcada` time /* mariadb-5.3 */
,`Hora_Salida_Marcada` time /* mariadb-5.3 */
,`Minutos_Tardanza_Calculados` bigint(21)
,`Tipo_Tardanza` varchar(10)
,`Id_Unico` varchar(38)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_vacaciones`
-- (See below for the actual view)
--
CREATE TABLE `view_vacaciones` (
`codigo_contrato` varchar(11)
,`persona` text
,`sucursal` varchar(50)
,`fecha_inicio` date
,`fecha_fin` date
,`dias` int(1)
,`observaciones` mediumtext
,`tipo` varchar(11)
,`fecha_registro` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_viaticos_exportar`
-- (See below for the actual view)
--
CREATE TABLE `view_viaticos_exportar` (
`Codigo` int(11)
,`Persona` text
,`Sucursal` varchar(50)
,`Fecha_de_Pago` binary(0)
,`Total_Turnos` int(1)
,`Total_a_recibir` decimal(10,2)
,`Fecha_Viatico` date
,`Hora_Marcacion` time /* mariadb-5.3 */
,`Tipo_Hora_Mostrada` varchar(7)
,`Tipo_Viatico` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `VistaOperariosContratos`
-- (See below for the actual view)
--
CREATE TABLE `VistaOperariosContratos` (
`codigo_nombre` text
,`CodOperario` int(11)
,`Nombre y Apellido` text
,`clave_hash` varchar(255)
,`clave` varchar(255)
,`Operativo` bit(1)
,`Celular` varchar(255)
,`Genero` enum('M','F','O')
,`Cedula` varchar(255)
,`Inicio` datetime
,`Fin` datetime
,`Cumpleanos` datetime
,`Sucursal` int(11)
,`Ciudad` varchar(255)
,`CodClub` int(11)
,`Cargo` varchar(255)
,`FechaRegistro` datetime
,`usuario` varchar(100)
,`cb_numero` varchar(100)
,`contacto_numero` int(11)
,`contacto_nombre` varchar(250)
,`direccion` varchar(250)
,`telefono_casa` varchar(20)
,`telefono_corporativo` varchar(20)
,`email_personal` varchar(255)
,`email_trabajo` varchar(255)
,`foto_perfil` varchar(255)
,`codigo_inss` varchar(50)
,`cb_titular` varchar(100)
,`cb_banco` varchar(100)
,`cb_moneda` varchar(20)
,`segurosocial` varchar(20)
,`InicioSeguro` date
,`FinSeguro` date
,`registrado_por` int(11)
,`fecha_hora_regsys` timestamp
,`hospital_riesgo_laboral` varchar(255)
,`FechaCreacion` timestamp
,`CodigoOperarioRaiz` int(11)
,`FechaSalidaContrato` date
,`FechaInicioContrato` date
,`numero_cuenta` varchar(100)
,`titular` varchar(255)
,`banco_moneda` varchar(121)
);

-- --------------------------------------------------------

--
-- Table structure for table `wsp_campanas_`
--

CREATE TABLE `wsp_campanas_` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `mensaje` text NOT NULL COMMENT 'Soporta variables: {{nombre}}, {{sucursal}}',
  `imagen_url` varchar(500) DEFAULT NULL,
  `fecha_envio` datetime NOT NULL,
  `estado` enum('borrador','programada','enviando','completada','fallida','cancelada') DEFAULT 'borrador',
  `total_destinatarios` int(11) DEFAULT 0,
  `total_enviados` int(11) DEFAULT 0,
  `total_errores` int(11) DEFAULT 0,
  `filtro_sucursal` varchar(500) DEFAULT NULL COMMENT 'JSON con IDs de sucursales filtradas',
  `usuario_creacion` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT convert_tz(current_timestamp(),'+00:00','-06:00')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wsp_destinatarios_`
--

CREATE TABLE `wsp_destinatarios_` (
  `id` int(11) NOT NULL,
  `campana_id` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL COMMENT 'clientesclub.id_clienteclub',
  `nombre` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL COMMENT 'Formato +50588887777',
  `sucursal` varchar(100) DEFAULT NULL,
  `enviado` tinyint(1) DEFAULT 0,
  `error` varchar(500) DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wsp_logs_`
--

CREATE TABLE `wsp_logs_` (
  `id` int(11) NOT NULL,
  `campana_id` int(11) DEFAULT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `tipo` enum('info','exito','error','sesion') NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha` datetime DEFAULT convert_tz(current_timestamp(),'+00:00','-06:00')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wsp_notificaciones_clientesclub_pendientes_`
--

CREATE TABLE `wsp_notificaciones_clientesclub_pendientes_` (
  `id` int(11) NOT NULL,
  `celular` varchar(20) NOT NULL COMMENT 'Número de WhatsApp con código de país',
  `mensaje` text NOT NULL COMMENT 'Contenido del mensaje personalizado',
  `estado` enum('pendiente','enviando','enviado','error') DEFAULT 'pendiente',
  `instancia` varchar(30) DEFAULT 'wsp-clientes' COMMENT 'Para discriminar qué VPS lo procesa',
  `creado_at` timestamp NULL DEFAULT current_timestamp(),
  `enviado_at` datetime DEFAULT NULL,
  `error_detalle` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wsp_planilla_programaciones_`
--

CREATE TABLE `wsp_planilla_programaciones_` (
  `id` int(11) NOT NULL,
  `fecha_planilla` date NOT NULL,
  `mensaje` text NOT NULL,
  `imagen_url` varchar(500) DEFAULT NULL,
  `fecha_envio` datetime NOT NULL,
  `estado` enum('programada','enviando','completada','cancelada') NOT NULL DEFAULT 'programada',
  `total_destinatarios` int(11) NOT NULL DEFAULT 0,
  `total_enviados` int(11) NOT NULL DEFAULT 0,
  `total_errores` int(11) NOT NULL DEFAULT 0,
  `usuario_creacion` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wsp_sesion_vps_`
--

CREATE TABLE `wsp_sesion_vps_` (
  `id` int(11) NOT NULL,
  `estado` enum('desconectado','qr_pendiente','conectado','inicializando','error') DEFAULT 'desconectado',
  `qr_base64` mediumtext DEFAULT NULL,
  `ultimo_ping` datetime DEFAULT NULL,
  `ip_vps` varchar(50) DEFAULT NULL,
  `reset_solicitado` tinyint(1) NOT NULL DEFAULT 0,
  `numero_telefono` varchar(20) DEFAULT NULL,
  `instancia` varchar(30) NOT NULL DEFAULT 'wsp-clientes' COMMENT 'Nombre PM2 de la instancia: wsp-clientes, wsp-rrhh, etc.',
  `hora_inicio` time DEFAULT '00:00:00' COMMENT 'Hora de inicio de atención del bot',
  `hora_fin` time DEFAULT '23:59:59' COMMENT 'Hora de fin de atención',
  `mensaje_fuera_horario` text DEFAULT '¡Hola! 🌙 En este momento estamos cerrados. Nuestro horario de atención es de 7:00 AM a 8:00 PM. Déjanos tu mensaje y te atenderemos con gusto enseguida regresemos. 🍓' COMMENT 'Mensaje automático cuando escriben fuera de horario',
  `dias_atencion` varchar(50) DEFAULT '1,2,3,4,5,6,7' COMMENT 'Días de la semana. 1=Lunes, 7=Domingo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `acciones_tools_erp`
--
ALTER TABLE `acciones_tools_erp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_acciones_tools_erp` (`tool_erp_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `Adelantos`
--
ALTER TABLE `Adelantos`
  ADD PRIMARY KEY (`CodAdelanto`),
  ADD KEY `cod_operario` (`cod_operario`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `announcement_branches`
--
ALTER TABLE `announcement_branches`
  ADD PRIMARY KEY (`announcement_id`,`branch_id`),
  ADD KEY `fk_sucursal` (`branch_id`);

--
-- Indexes for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_announcement_user` (`announcement_id`,`user_id`),
  ADD KEY `announcement_id` (`announcement_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ArchivosAdjuntos`
--
ALTER TABLE `ArchivosAdjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_operario` (`cod_operario`),
  ADD KEY `cod_usuario_subio` (`cod_usuario_subio`),
  ADD KEY `idx_tipo_obligatorio` (`tipo_documento`,`obligatorio`),
  ADD KEY `idx_tipo_documento_id` (`id_tipo_documento`);

--
-- Indexes for table `archivos_presentacion_producto`
--
ALTER TABLE `archivos_presentacion_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archivo_producto` (`id_presentacion_producto`),
  ADD KEY `fk_archivo_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_archivo_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `AsignacionNivelesCargos`
--
ALTER TABLE `AsignacionNivelesCargos`
  ADD PRIMARY KEY (`CodAsignacionNivelesCargos`),
  ADD KEY `usuario_ultima_modificacion` (`usuario_ultima_modificacion`),
  ADD KEY `cod_usuario_creador` (`cod_usuario_creador`),
  ADD KEY `fk_asignacion_operario` (`CodOperario`),
  ADD KEY `fk_asignacion_nivelcargo` (`CodNivelesCargos`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auditoria_caja_chica`
--
ALTER TABLE `auditoria_caja_chica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sucursal_id` (`sucursal_id`),
  ADD KEY `idx_lider_tienda_codigo` (`lider_tienda_codigo`);

--
-- Indexes for table `auditoria_caja_chica_detalle`
--
ALTER TABLE `auditoria_caja_chica_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indexes for table `auditoria_facturacion`
--
ALTER TABLE `auditoria_facturacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auditoria_sucursal` (`sucursal_id`),
  ADD KEY `fk_auditoria_operario` (`cajero`);

--
-- Indexes for table `auditoria_fotos`
--
ALTER TABLE `auditoria_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indexes for table `auditoria_inventario`
--
ALTER TABLE `auditoria_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auditoria_inventario_sucursal` (`sucursal_id`);

--
-- Indexes for table `auditoria_inventario_detalle`
--
ALTER TABLE `auditoria_inventario_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indexes for table `auditoria_inventario_operarios`
--
ALTER TABLE `auditoria_inventario_operarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indexes for table `auditoria_personal`
--
ALTER TABLE `auditoria_personal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auditoria_personal_fotos`
--
ALTER TABLE `auditoria_personal_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indexes for table `auditoria_procesos`
--
ALTER TABLE `auditoria_procesos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auditoria_promociones`
--
ALTER TABLE `auditoria_promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_sucursal` (`sucursal_id`),
  ADD KEY `idx_operario` (`operario_id`);

--
-- Indexes for table `auditoria_servicio`
--
ALTER TABLE `auditoria_servicio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auditoria_servicio_fotos`
--
ALTER TABLE `auditoria_servicio_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auditoria_id` (`auditoria_id`);

--
-- Indexes for table `AutorizacionesEdicion`
--
ALTER TABLE `AutorizacionesEdicion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_semana_sucursal` (`id_semana`,`cod_sucursal`),
  ADD KEY `id_semana` (`id_semana`),
  ADD KEY `cod_sucursal` (`cod_sucursal`);

--
-- Indexes for table `BitacoraColaborador`
--
ALTER TABLE `BitacoraColaborador`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_usuario_registro` (`cod_usuario_registro`),
  ADD KEY `idx_cod_operario` (`cod_operario`),
  ADD KEY `idx_fecha` (`fecha_registro`);

--
-- Indexes for table `BoletaAguinaldo`
--
ALTER TABLE `BoletaAguinaldo`
  ADD PRIMARY KEY (`id_boleta_aguinaldo`),
  ADD KEY `idx_cod_operario` (`cod_operario`),
  ADD KEY `idx_cod_contrato` (`cod_contrato`),
  ADD KEY `idx_fecha_emision` (`fecha_emision`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Indexes for table `BoletaPago`
--
ALTER TABLE `BoletaPago`
  ADD PRIMARY KEY (`id_boleta`),
  ADD UNIQUE KEY `unique_boleta` (`cod_operario`,`fecha_planilla`),
  ADD KEY `idx_boleta_wsp_prog` (`wsp_programacion_id`);

--
-- Indexes for table `bot_crons_config`
--
ALTER TABLE `bot_crons_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indexes for table `bot_estado_confirmacion`
--
ALTER TABLE `bot_estado_confirmacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_celular` (`celular`),
  ADD KEY `idx_operario` (`cod_operario`),
  ADD KEY `idx_expira` (`expira_en`);

--
-- Indexes for table `bot_intents`
--
ALTER TABLE `bot_intents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bot_operaciones_log`
--
ALTER TABLE `bot_operaciones_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_operario` (`cod_operario`),
  ADD KEY `idx_intent` (`intent`),
  ADD KEY `idx_fecha` (`creado_en`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `caja_chica_sucursales`
--
ALTER TABLE `caja_chica_sucursales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_caja_chica_sucursales_sucursales` (`sucursal_id`);

--
-- Indexes for table `CategoriasOperarios`
--
ALTER TABLE `CategoriasOperarios`
  ADD PRIMARY KEY (`idCategoria`);

--
-- Indexes for table `categoria_producto_maestro`
--
ALTER TABLE `categoria_producto_maestro`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `CentroCostos`
--
ALTER TABLE `CentroCostos`
  ADD PRIMARY KEY (`CodCentroCostos`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `clientesclub`
--
ALTER TABLE `clientesclub`
  ADD PRIMARY KEY (`id_clienteclub`),
  ADD KEY `idx_club` (`membresia`),
  ADD KEY `idx_club_membresia` (`membresia`),
  ADD KEY `idx_club_sucursal` (`sucursal`),
  ADD KEY `idx_club_registro` (`fecha_registro`);

--
-- Indexes for table `componentes_receta_producto`
--
ALTER TABLE `componentes_receta_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_componente_receta` (`id_receta_producto_global`),
  ADD KEY `idx_componente_producto` (`id_presentacion_producto`),
  ADD KEY `idx_componente_orden` (`orden`);

--
-- Indexes for table `compra_local_configuracion_despacho`
--
ALTER TABLE `compra_local_configuracion_despacho`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_config` (`id_producto_presentacion`,`codigo_sucursal`,`dia_entrega`),
  ADD KEY `usuario_creacion` (`usuario_creacion`),
  ADD KEY `usuario_modificacion` (`usuario_modificacion`),
  ADD KEY `idx_producto` (`id_producto_presentacion`),
  ADD KEY `idx_sucursal` (`codigo_sucursal`),
  ADD KEY `idx_dia` (`dia_entrega`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_producto_sucursal` (`id_producto_presentacion`,`codigo_sucursal`),
  ADD KEY `fk_config_perfil` (`id_perfil`);

--
-- Indexes for table `compra_local_pedidos_historico`
--
ALTER TABLE `compra_local_pedidos_historico`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pedido` (`id_producto_presentacion`,`codigo_sucursal`,`fecha_entrega`),
  ADD KEY `usuario_registro` (`usuario_registro`),
  ADD KEY `idx_producto` (`id_producto_presentacion`),
  ADD KEY `idx_sucursal` (`codigo_sucursal`),
  ADD KEY `idx_fecha_entrega` (`fecha_entrega`),
  ADD KEY `idx_fecha_reportada` (`fecha_hora_reportada`),
  ADD KEY `idx_producto_sucursal` (`id_producto_presentacion`,`codigo_sucursal`),
  ADD KEY `idx_sucursal_fecha` (`codigo_sucursal`,`fecha_entrega`),
  ADD KEY `idx_producto_fecha` (`id_producto_presentacion`,`fecha_entrega`);

--
-- Indexes for table `compra_local_perfiles_despacho`
--
ALTER TABLE `compra_local_perfiles_despacho`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ContactosEmergencia`
--
ALTER TABLE `ContactosEmergencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_operario` (`cod_operario`);

--
-- Indexes for table `contacto_proveedores`
--
ALTER TABLE `contacto_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_proveedor` (`id_proveedor`),
  ADD KEY `idx_principal` (`principal`);

--
-- Indexes for table `Contratos`
--
ALTER TABLE `Contratos`
  ADD PRIMARY KEY (`CodContrato`),
  ADD KEY `cod_tipo_contrato` (`cod_tipo_contrato`),
  ADD KEY `cod_operario` (`cod_operario`),
  ADD KEY `cod_tipo_salida` (`cod_tipo_salida`),
  ADD KEY `fk_cod_usuario_creador` (`cod_usuario_creador`),
  ADD KEY `usuario_ultima_modificacion` (`usuario_ultima_modificacion`);

--
-- Indexes for table `contratos_tiposDocumentos`
--
ALTER TABLE `contratos_tiposDocumentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pestaña_clave` (`pestaña`,`nombre_clave`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conv` (`instancia`,`numero_cliente`),
  ADD KEY `idx_instancia` (`instancia`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `conversion_unidad_producto`
--
ALTER TABLE `conversion_unidad_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unidad_inicio` (`id_unidad_producto_inicio`),
  ADD KEY `idx_unidad_final` (`id_unidad_producto_final`),
  ADD KEY `idx_usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `CuentaBancaria`
--
ALTER TABLE `CuentaBancaria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_operario` (`cod_operario`);

--
-- Indexes for table `cuenta_proveedor`
--
ALTER TABLE `cuenta_proveedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registrado_por` (`registrado_por`),
  ADD KEY `idx_proveedor` (`id_proveedor`),
  ADD KEY `idx_principal` (`principal`);

--
-- Indexes for table `cupones_sucursales`
--
ALTER TABLE `cupones_sucursales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `DBBatidos`
--
ALTER TABLE `DBBatidos`
  ADD KEY `idx_codbatido` (`CodBatido`),
  ADD KEY `idx_codgrupo` (`CodGrupo`);

--
-- Indexes for table `deducciones_operaciones`
--
ALTER TABLE `deducciones_operaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operario_id` (`operario_id`),
  ADD KEY `sucursal_id` (`sucursal_id`),
  ADD KEY `tipo_id_referencia` (`tipo_deduccion`,`id_referencia`);

--
-- Indexes for table `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`codigo`);

--
-- Indexes for table `diccionario_productos_legado`
--
ALTER TABLE `diccionario_productos_legado`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mapeo_cotizacion` (`CodCotizacion`) COMMENT 'Una cotización solo puede tener un producto nuevo',
  ADD KEY `idx_ingrediente` (`CodIngrediente`),
  ADD KEY `idx_presentacion` (`id_producto_presentacion`),
  ADD KEY `idx_usuario` (`usuario_mapeo`),
  ADD KEY `idx_variedad` (`id_variedad_producto`);

--
-- Indexes for table `DVR_Sucursales`
--
ALTER TABLE `DVR_Sucursales`
  ADD PRIMARY KEY (`cod_sucursal`),
  ADD UNIQUE KEY `cod_sucursal_unique` (`cod_sucursal`);

--
-- Indexes for table `endulzantes`
--
ALTER TABLE `endulzantes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `endulzantes_asignaciones`
--
ALTER TABLE `endulzantes_asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asignacion_unica` (`endulzante_id`,`grupo_id`,`subgrupo_id`,`producto_id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `subgrupo_id` (`subgrupo_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indexes for table `entrevistas_candidatos`
--
ALTER TABLE `entrevistas_candidatos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_postulacion` (`id_postulacion`),
  ADD KEY `idx_reclutador` (`reclutador_entrevista`),
  ADD KEY `idx_fecha` (`fecha_entrevista`);

--
-- Indexes for table `extras`
--
ALTER TABLE `extras`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faltante_caja`
--
ALTER TABLE `faltante_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fecha` (`fecha`),
  ADD KEY `sucursal_id` (`sucursal_id`),
  ADD KEY `operario_id` (`operario_id`),
  ADD KEY `registrador_id` (`registrador_id`);

--
-- Indexes for table `faltante_danos`
--
ALTER TABLE `faltante_danos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faltante_danos_operarios`
--
ALTER TABLE `faltante_danos_operarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faltante_id` (`faltante_id`);

--
-- Indexes for table `faltante_inventario`
--
ALTER TABLE `faltante_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_faltante_inventario_sucursal` (`sucursal_id`);

--
-- Indexes for table `faltante_inventario_detalle`
--
ALTER TABLE `faltante_inventario_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faltante_id` (`faltante_id`);

--
-- Indexes for table `faltante_inventario_operarios`
--
ALTER TABLE `faltante_inventario_operarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faltante_id` (`faltante_id`),
  ADD KEY `operario_id` (`operario_id`);

--
-- Indexes for table `Faltas`
--
ALTER TABLE `Faltas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_operario_fecha` (`cod_operario`,`fecha`),
  ADD KEY `fk_faltas_sucursal` (`cod_sucursal`),
  ADD KEY `fk_faltas_horario` (`id_horario_programado`);

--
-- Indexes for table `faltas_manual`
--
ALTER TABLE `faltas_manual`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_faltas_manual_operario` (`cod_operario`),
  ADD KEY `fk_faltas_manual_sucursal` (`cod_sucursal`),
  ADD KEY `fk_faltas_manual_operario_registrador` (`registrado_por`),
  ADD KEY `fk_faltas_manual_operario_actualizador` (`actualizado_por`);

--
-- Indexes for table `feriadosnic`
--
ALTER TABLE `feriadosnic`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fecha` (`fecha`,`nombre`,`departamento_codigo`),
  ADD KEY `fk_feriadosnic_departamento` (`departamento_codigo`);

--
-- Indexes for table `FeriadosStatus`
--
ALTER TABLE `FeriadosStatus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_marcacion` (`id_marcacion`),
  ADD KEY `cod_operario` (`cod_operario`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `actualizado_por` (`actualizado_por`);

--
-- Indexes for table `fichatecnica_presentacion_producto`
--
ALTER TABLE `fichatecnica_presentacion_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ficha_producto` (`id_presentacion_producto`),
  ADD KEY `fk_ficha_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_ficha_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `formato_salida`
--
ALTER TABLE `formato_salida`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_contrato` (`cod_contrato`);

--
-- Indexes for table `fotos_presentacion_producto`
--
ALTER TABLE `fotos_presentacion_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_foto_producto` (`id_presentacion_producto`),
  ADD KEY `fk_foto_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_foto_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `gestion_proyectos_proyectos`
--
ALTER TABLE `gestion_proyectos_proyectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `modificado_por` (`modificado_por`),
  ADD KEY `idx_cargo` (`CodNivelesCargos`),
  ADD KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_padre` (`proyecto_padre_id`),
  ADD KEY `idx_orden` (`orden_visual`);

--
-- Indexes for table `gestion_tareas_reuniones_archivos`
--
ALTER TABLE `gestion_tareas_reuniones_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_item` (`id_item`),
  ADD KEY `idx_id_comentario` (`id_comentario`),
  ADD KEY `fk_gtr_archivos_operario` (`cod_operario_subio`);

--
-- Indexes for table `gestion_tareas_reuniones_comentarios`
--
ALTER TABLE `gestion_tareas_reuniones_comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_item` (`id_item`),
  ADD KEY `idx_cod_operario` (`cod_operario`);

--
-- Indexes for table `gestion_tareas_reuniones_items`
--
ALTER TABLE `gestion_tareas_reuniones_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_cargo_asignado` (`cod_cargo_asignado`),
  ADD KEY `idx_fecha_meta` (`fecha_meta`),
  ADD KEY `idx_fecha_reunion` (`fecha_reunion`),
  ADD KEY `idx_id_padre` (`id_padre`),
  ADD KEY `fk_gtr_items_cargo_creador` (`cod_cargo_creador`),
  ADD KEY `fk_gtr_items_operario_modificacion` (`cod_operario_ultima_modificacion`),
  ADD KEY `idx_bot_tareas` (`cod_operario_creador`,`tipo`,`estado`,`fecha_meta`);

--
-- Indexes for table `gestion_tareas_reuniones_participantes`
--
ALTER TABLE `gestion_tareas_reuniones_participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participante` (`id_item`,`cod_cargo`),
  ADD KEY `idx_id_item` (`id_item`),
  ADD KEY `idx_cod_cargo` (`cod_cargo`),
  ADD KEY `fk_gtr_participantes_operario` (`cod_operario_confirmo`);

--
-- Indexes for table `gestores_reclamos`
--
ALTER TABLE `gestores_reclamos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grupos_productos`
--
ALTER TABLE `grupos_productos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grupo_presentacion_producto`
--
ALTER TABLE `grupo_presentacion_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_grupo_nombre` (`nombre`),
  ADD KEY `fk_grupo_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_grupo_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `historial_estados_personal`
--
ALTER TABLE `historial_estados_personal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_historial_personal` (`personal_id`),
  ADD KEY `idx_historial_fechas` (`fecha_inicio`,`fecha_fin`);

--
-- Indexes for table `historial_proveedores`
--
ALTER TABLE `historial_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_cambio` (`usuario_cambio`),
  ADD KEY `idx_proveedor` (`id_proveedor`),
  ADD KEY `idx_fecha` (`fecha_cambio`),
  ADD KEY `idx_tipo` (`tipo_cambio`);

--
-- Indexes for table `HorariosSemanales`
--
ALTER TABLE `HorariosSemanales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_operario_semana` (`id_semana_sistema`,`cod_operario`,`cod_sucursal`),
  ADD KEY `cod_operario` (`cod_operario`),
  ADD KEY `cod_sucursal` (`cod_sucursal`),
  ADD KEY `id_semana_sistema` (`id_semana_sistema`),
  ADD KEY `idx_horarios_cod_contrato` (`cod_contrato`);

--
-- Indexes for table `HorariosSemanalesOperaciones`
--
ALTER TABLE `HorariosSemanalesOperaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_operario_semana` (`id_semana_sistema`,`cod_operario`,`cod_sucursal`),
  ADD KEY `cod_operario` (`cod_operario`),
  ADD KEY `cod_sucursal` (`cod_sucursal`),
  ADD KEY `id_semana_sistema` (`id_semana_sistema`),
  ADD KEY `fk_horarios_creado_por` (`creado_por`),
  ADD KEY `fk_horarios_actualizado_por` (`actualizado_por`),
  ADD KEY `idx_horarios_operaciones_cod_contrato` (`cod_contrato`);

--
-- Indexes for table `HorasExtraStatus`
--
ALTER TABLE `HorasExtraStatus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_marcacion` (`id_marcacion`),
  ADD KEY `fk_horasextra_operario` (`cod_operario`);

--
-- Indexes for table `horas_extras_manual`
--
ALTER TABLE `horas_extras_manual`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hem_operario` (`cod_operario`),
  ADD KEY `fk_hem_sucursal` (`cod_sucursal`),
  ADD KEY `fk_hem_registrador` (`registrado_por`),
  ADD KEY `fk_hem_actualizador` (`actualizado_por`);

--
-- Indexes for table `ia_graficos_cache`
--
ALTER TABLE `ia_graficos_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prompt_hash` (`prompt_hash`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `ia_graficos_diccionario_columnas`
--
ALTER TABLE `ia_graficos_diccionario_columnas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tabla_columna` (`tabla_origen`,`columna_bd`);

--
-- Indexes for table `ia_graficos_favoritos`
--
ALTER TABLE `ia_graficos_favoritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha_creacion`);

--
-- Indexes for table `ia_graficos_filtros_conceptuales`
--
ALTER TABLE `ia_graficos_filtros_conceptuales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ia_graficos_metricas_predefinidas`
--
ALTER TABLE `ia_graficos_metricas_predefinidas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ia_graficos_validaciones`
--
ALTER TABLE `ia_graficos_validaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ia_proveedores_api`
--
ALTER TABLE `ia_proveedores_api`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `IndicadoresSemanales`
--
ALTER TABLE `IndicadoresSemanales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cod_cargo` (`CodNivelesCargos`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indexes for table `IndicadoresSemanalesResultados`
--
ALTER TABLE `IndicadoresSemanalesResultados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_indicador_semana` (`id_indicador`,`semana`),
  ADD KEY `idx_semana` (`semana`),
  ADD KEY `idx_indicador` (`id_indicador`),
  ADD KEY `idx_semana_indicador` (`semana`,`id_indicador`),
  ADD KEY `usuario_registra` (`usuario_registra`),
  ADD KEY `usuario_modifica` (`usuario_modifica`);

--
-- Indexes for table `intent_embeddings`
--
ALTER TABLE `intent_embeddings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_intent_id` (`intent_id`),
  ADD KEY `idx_term` (`term`);

--
-- Indexes for table `kpi_reclamos`
--
ALTER TABLE `kpi_reclamos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sucursal_mes_anio` (`sucursal`,`mes`,`anio`),
  ADD UNIQUE KEY `idx_unico` (`sucursal`,`mes`,`anio`),
  ADD KEY `fk_kpi_reclamos_operario` (`cod_operario_actualizacion`),
  ADD KEY `idx_kpi_reclamos_cod_sucursal` (`cod_sucursal`);

--
-- Indexes for table `marcaciones`
--
ALTER TABLE `marcaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_marcaciones_fecha` (`fecha`),
  ADD KEY `idx_marcaciones_operario_fecha` (`CodOperario`,`fecha`),
  ADD KEY `fk_marcaciones_sucursal` (`sucursal_codigo`),
  ADD KEY `idx_marcaciones_operario_fecha_sucursal` (`CodOperario`,`fecha`,`sucursal_codigo`),
  ADD KEY `idx_marcaciones_numero_semana` (`numero_semana`),
  ADD KEY `idx_marcaciones_cod_contrato` (`cod_contrato`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `mtto_equipos`
--
ALTER TABLE `mtto_equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `tipo_equipo_id` (`tipo_equipo_id`),
  ADD KEY `proveedor_compra_id` (`proveedor_compra_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Indexes for table `mtto_equipos_mantenimientos`
--
ALTER TABLE `mtto_equipos_mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mantenimiento_programado_id` (`mantenimiento_programado_id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `solicitud_id` (`solicitud_id`),
  ADD KEY `proveedor_servicio_id` (`proveedor_servicio_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Indexes for table `mtto_equipos_mantenimientos_fotos`
--
ALTER TABLE `mtto_equipos_mantenimientos_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mantenimiento_id` (`mantenimiento_id`);

--
-- Indexes for table `mtto_equipos_mantenimientos_programados`
--
ALTER TABLE `mtto_equipos_mantenimientos_programados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `fecha_programada` (`fecha_programada`),
  ADD KEY `programado_por` (`programado_por`),
  ADD KEY `estado` (`estado`);

--
-- Indexes for table `mtto_equipos_mantenimientos_repuestos`
--
ALTER TABLE `mtto_equipos_mantenimientos_repuestos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mantenimiento_id` (`mantenimiento_id`),
  ADD KEY `repuesto_id` (`repuesto_id`);

--
-- Indexes for table `mtto_equipos_movimientos`
--
ALTER TABLE `mtto_equipos_movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `sucursal_origen_id` (`sucursal_origen_id`),
  ADD KEY `sucursal_destino_id` (`sucursal_destino_id`),
  ADD KEY `programado_por` (`programado_por`),
  ADD KEY `finalizado_por` (`finalizado_por`),
  ADD KEY `estado` (`estado`),
  ADD KEY `fecha_programada` (`fecha_programada`);

--
-- Indexes for table `mtto_equipos_repuestos`
--
ALTER TABLE `mtto_equipos_repuestos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mtto_equipos_solicitudes`
--
ALTER TABLE `mtto_equipos_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `sucursal_id` (`sucursal_id`),
  ADD KEY `solicitado_por` (`solicitado_por`),
  ADD KEY `finalizado_por` (`finalizado_por`),
  ADD KEY `estado` (`estado`);

--
-- Indexes for table `mtto_equipos_solicitudes_fotos`
--
ALTER TABLE `mtto_equipos_solicitudes_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_id` (`solicitud_id`);

--
-- Indexes for table `mtto_equipos_tipos`
--
ALTER TABLE `mtto_equipos_tipos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mtto_informes_diarios`
--
ALTER TABLE `mtto_informes_diarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mtto_informes_operario` (`cod_operario`);

--
-- Indexes for table `mtto_informe_compras`
--
ALTER TABLE `mtto_informe_compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mtto_compras_visita` (`visita_id`);

--
-- Indexes for table `mtto_informe_tareas`
--
ALTER TABLE `mtto_informe_tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mtto_tareas_visita` (`visita_id`);

--
-- Indexes for table `mtto_informe_tareas_fotos`
--
ALTER TABLE `mtto_informe_tareas_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mtto_tareas_fotos_tarea` (`tarea_id`);

--
-- Indexes for table `mtto_informe_visitas`
--
ALTER TABLE `mtto_informe_visitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mtto_visitas_informe` (`informe_id`);

--
-- Indexes for table `mtto_materiales_frecuentes`
--
ALTER TABLE `mtto_materiales_frecuentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indexes for table `mtto_tickets`
--
ALTER TABLE `mtto_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `tipo_caso_id` (`tipo_caso_id`),
  ADD KEY `fk_cod_operario_2` (`cod_operario`);

--
-- Indexes for table `mtto_tickets_colaboradores`
--
ALTER TABLE `mtto_tickets_colaboradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ticket_colaborador` (`ticket_id`,`cod_operario`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `cod_operario` (`cod_operario`);

--
-- Indexes for table `mtto_tickets_fotos`
--
ALTER TABLE `mtto_tickets_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `mtto_tickets_fotos_finalizacion`
--
ALTER TABLE `mtto_tickets_fotos_finalizacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `mtto_tickets_materiales`
--
ALTER TABLE `mtto_tickets_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `mtto_tipos_casos`
--
ALTER TABLE `mtto_tipos_casos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `NivelesCargos`
--
ALTER TABLE `NivelesCargos`
  ADD PRIMARY KEY (`CodNivelesCargos`);

--
-- Indexes for table `Operarios`
--
ALTER TABLE `Operarios`
  ADD PRIMARY KEY (`CodOperario`),
  ADD KEY `fk_operarios_registrado_por` (`registrado_por`),
  ADD KEY `idx_operarios_bot_lid` (`bot_lid`);

--
-- Indexes for table `OperariosCategorias`
--
ALTER TABLE `OperariosCategorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_OperarioCategoria_Operario` (`CodOperario`),
  ADD KEY `IDX_OperarioCategoria_Contrato` (`CodContrato`),
  ADD KEY `IDX_OperarioCategoria_Asignacion` (`CodAsignacionNivelesCargos`),
  ADD KEY `IDX_OperarioCategoria_Tipo` (`TipoAdendum`),
  ADD KEY `IDX_OperarioCategoria_Fechas` (`FechaInicio`,`FechaFin`);

--
-- Indexes for table `PagoAdelantos`
--
ALTER TABLE `PagoAdelantos`
  ADD PRIMARY KEY (`CodPagoAdelanto`),
  ADD KEY `cod_adelanto` (`cod_adelanto`);

--
-- Indexes for table `PatronalesINSS`
--
ALTER TABLE `PatronalesINSS`
  ADD PRIMARY KEY (`CodPlanilla`);

--
-- Indexes for table `permisos_tools_erp`
--
ALTER TABLE `permisos_tools_erp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_accion_tool_erp` (`accion_tool_erp_id`);

--
-- Indexes for table `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `personal_auditorias`
--
ALTER TABLE `personal_auditorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indexes for table `pitaya_love_registros`
--
ALTER TABLE `pitaya_love_registros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_factura_local` (`numero_factura`,`codigo_local`),
  ADD KEY `idx_factura_local` (`numero_factura`,`codigo_local`);

--
-- Indexes for table `plazas_cargos`
--
ALTER TABLE `plazas_cargos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cargo` (`cargo`),
  ADD KEY `idx_area` (`area`),
  ADD KEY `idx_sucursal` (`sucursal`),
  ADD KEY `idx_visible_web` (`visible_web`),
  ADD KEY `usuario_registra` (`usuario_registra`),
  ADD KEY `usuario_modifica` (`usuario_modifica`),
  ADD KEY `idx_cargo_sucursal` (`cargo`,`sucursal`);

--
-- Indexes for table `postulacion_entrevista_telefonica`
--
ALTER TABLE `postulacion_entrevista_telefonica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_postulacion` (`id_postulacion`);

--
-- Indexes for table `postulacion_evaluacion_jefe`
--
ALTER TABLE `postulacion_evaluacion_jefe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_postulacion` (`id_postulacion`);

--
-- Indexes for table `postulacion_evaluacion_rh`
--
ALTER TABLE `postulacion_evaluacion_rh`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_postulacion` (`id_postulacion`);

--
-- Indexes for table `postulacion_plaza`
--
ALTER TABLE `postulacion_plaza`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cargo` (`cargo_aplicado`),
  ADD KEY `idx_sucursal` (`sucursal_aplicada`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_correo` (`correo`),
  ADD KEY `idx_cargo_status` (`cargo_aplicado`,`status`),
  ADD KEY `idx_correo_cargo` (`correo`,`cargo_aplicado`),
  ADD KEY `idx_cargo_sucursal` (`cargo_aplicado`,`sucursal_aplicada`),
  ADD KEY `idx_fecha_postulacion` (`fecha_postulacion`);

--
-- Indexes for table `pos_caja_inicial`
--
ALTER TABLE `pos_caja_inicial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_caja_inicial_fecha` (`fecha`),
  ADD KEY `idx_caja_inicial_usuario` (`cod_usuario`),
  ADD KEY `idx_caja_inicial_sucursal` (`sucursal_id`);

--
-- Indexes for table `pos_caja_inicial_detalle`
--
ALTER TABLE `pos_caja_inicial_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_detalle_caja` (`caja_inicial_id`);

--
-- Indexes for table `pos_facturas`
--
ALTER TABLE `pos_facturas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_facturas_detalle`
--
ALTER TABLE `pos_facturas_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_factura` (`id_factura`),
  ADD KEY `fk_detalle_presentacion` (`id_presentacion`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `productos_delivery`
--
ALTER TABLE `productos_delivery`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `subgrupo_id` (`subgrupo_id`),
  ADD KEY `idx_subgrupo` (`subgrupo_id`);

--
-- Indexes for table `producto_maestro`
--
ALTER TABLE `producto_maestro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `SKU` (`SKU`),
  ADD UNIQUE KEY `unique_sku` (`SKU`),
  ADD KEY `fk_producto_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_producto_usuario_modificacion` (`usuario_modificacion`),
  ADD KEY `idx_estado` (`Estado`);

--
-- Indexes for table `producto_presentacion`
--
ALTER TABLE `producto_presentacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `SKU_unico` (`SKU`),
  ADD KEY `idx_producto_maestro` (`id_producto_maestro`),
  ADD KEY `idx_unidad_producto` (`id_unidad_producto`),
  ADD KEY `idx_receta_producto` (`Id_receta_producto`),
  ADD KEY `idx_usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_usuario_modificacion` (`usuario_modificacion`),
  ADD KEY `idx_es_vendible` (`es_vendible`),
  ADD KEY `idx_es_comprable` (`es_comprable`),
  ADD KEY `idx_es_fabricable` (`es_fabricable`),
  ADD KEY `idx_activo` (`Activo`);

--
-- Indexes for table `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `tipo_id` (`tipo_id`),
  ADD KEY `cliente_tipo_id` (`cliente_tipo_id`),
  ADD KEY `aplicacion_id` (`aplicacion_id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indexes for table `promociones_aplicacion`
--
ALTER TABLE `promociones_aplicacion`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promociones_clientes_tipos`
--
ALTER TABLE `promociones_clientes_tipos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promociones_grupos`
--
ALTER TABLE `promociones_grupos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promociones_productos`
--
ALTER TABLE `promociones_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `subgrupo_id` (`subgrupo_id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indexes for table `promociones_requisitos`
--
ALTER TABLE `promociones_requisitos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promocion_id` (`promocion_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `subgrupo_id` (`subgrupo_id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indexes for table `promociones_tipos`
--
ALTER TABLE `promociones_tipos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo_condiciones`
--
ALTER TABLE `promo_condiciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promo_id` (`promo_id`),
  ADD KEY `fk_promo_cond_opcion` (`opcion_id`);

--
-- Indexes for table `promo_condiciones_opciones`
--
ALTER TABLE `promo_condiciones_opciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `promo_promociones`
--
ALTER TABLE `promo_promociones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_interno` (`codigo_interno`);

--
-- Indexes for table `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vigente` (`vigente`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_sucursal` (`comprasucursal`);

--
-- Indexes for table `proveedores_compras_servicios`
--
ALTER TABLE `proveedores_compras_servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proveedor_tipo_pago`
--
ALTER TABLE `proveedor_tipo_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_proveedor_tipo` (`id_proveedor`,`id_tipo_pago`),
  ADD KEY `id_tipo_pago` (`id_tipo_pago`),
  ADD KEY `asignado_por` (`asignado_por`),
  ADD KEY `idx_proveedor` (`id_proveedor`);

--
-- Indexes for table `qr_offline_bank`
--
ALTER TABLE `qr_offline_bank`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_id` (`qr_id`),
  ADD UNIQUE KEY `key_hash` (`key_hash`),
  ADD KEY `idx_key_hash` (`key_hash`);

--
-- Indexes for table `receta_producto_global`
--
ALTER TABLE `receta_producto_global`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_receta_presentacion` (`id_presentacion_producto`),
  ADD KEY `idx_receta_tipo` (`id_tipo_receta`),
  ADD KEY `idx_receta_sku` (`SKU`);

--
-- Indexes for table `reclamos`
--
ALTER TABLE `reclamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reclamos_sucursal_codigo` (`sucursal_codigo`),
  ADD KEY `fk_reclamo_grupo` (`grupo_id`),
  ADD KEY `fk_reclamo_tipo` (`tipo_reclamo_id`);

--
-- Indexes for table `reclamos_cargos_responsables`
--
ALTER TABLE `reclamos_cargos_responsables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `tipo_id` (`tipo_id`);

--
-- Indexes for table `reclamos_grupos`
--
ALTER TABLE `reclamos_grupos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reclamos_imagenes`
--
ALTER TABLE `reclamos_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamo_id` (`reclamo_id`);

--
-- Indexes for table `reclamos_productos`
--
ALTER TABLE `reclamos_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamo_id` (`reclamo_id`);

--
-- Indexes for table `reclamos_tipos`
--
ALTER TABLE `reclamos_tipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indexes for table `reclamos_videos`
--
ALTER TABLE `reclamos_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamo_id` (`reclamo_id`);

--
-- Indexes for table `reembolsos_detalles`
--
ALTER TABLE `reembolsos_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud` (`id_solicitud`);

--
-- Indexes for table `reembolsos_solicitudes`
--
ALTER TABLE `reembolsos_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proveedor` (`id_proveedor`),
  ADD KEY `idx_usuario` (`usuario_registro`),
  ADD KEY `idx_ceco` (`ceco`);

--
-- Indexes for table `reportes_colaboradores`
--
ALTER TABLE `reportes_colaboradores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporte_id` (`reporte_id`);

--
-- Indexes for table `reportes_investigacion`
--
ALTER TABLE `reportes_investigacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamo_id` (`reclamo_id`);

--
-- Indexes for table `requisicion_personal`
--
ALTER TABLE `requisicion_personal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sucursal` (`sucursal`),
  ADD KEY `idx_nivel_urgencia` (`nivel_urgencia`),
  ADD KEY `idx_tipo_plaza` (`tipo_plaza`),
  ADD KEY `cargo_reporta_a` (`cargo_reporta_a`),
  ADD KEY `usuario_registra` (`usuario_registra`),
  ADD KEY `usuario_modifica` (`usuario_modifica`);

--
-- Indexes for table `SalarioINSS`
--
ALTER TABLE `SalarioINSS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_contrato` (`cod_contrato`);

--
-- Indexes for table `SalarioOperario`
--
ALTER TABLE `SalarioOperario`
  ADD PRIMARY KEY (`CodSalarioOperario`),
  ADD KEY `cod_contrato` (`cod_contrato`);

--
-- Indexes for table `SemanasSistema`
--
ALTER TABLE `SemanasSistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_semana_unico` (`numero_semana`);

--
-- Indexes for table `servicios_delivery`
--
ALTER TABLE `servicios_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indexes for table `solicitudes_cotizacion`
--
ALTER TABLE `solicitudes_cotizacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `solicitudes_cotizacion_fotos`
--
ALTER TABLE `solicitudes_cotizacion_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_foto_producto_cotizacion` (`producto_id`);

--
-- Indexes for table `solicitudes_cotizacion_historial`
--
ALTER TABLE `solicitudes_cotizacion_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_id` (`solicitud_id`);

--
-- Indexes for table `solicitudes_cotizacion_productos`
--
ALTER TABLE `solicitudes_cotizacion_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitud_id` (`solicitud_id`);

--
-- Indexes for table `solicitudes_vacaciones`
--
ALTER TABLE `solicitudes_vacaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cod_operario` (`cod_operario`),
  ADD KEY `solicitado_por` (`solicitado_por`),
  ADD KEY `estado` (`estado`),
  ADD KEY `aprobado_operaciones_por` (`aprobado_operaciones_por`),
  ADD KEY `aprobado_rh_por` (`aprobado_rh_por`);

--
-- Indexes for table `solicitud_empleo`
--
ALTER TABLE `solicitud_empleo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token` (`token`),
  ADD KEY `idx_postulacion` (`id_postulacion`);

--
-- Indexes for table `solicitud_empleo_estudios`
--
ALTER TABLE `solicitud_empleo_estudios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud` (`id_solicitud`);

--
-- Indexes for table `solicitud_empleo_experiencia`
--
ALTER TABLE `solicitud_empleo_experiencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud` (`id_solicitud`);

--
-- Indexes for table `solicitud_empleo_familiares`
--
ALTER TABLE `solicitud_empleo_familiares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud` (`id_solicitud`);

--
-- Indexes for table `solicitud_empleo_hijos`
--
ALTER TABLE `solicitud_empleo_hijos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud` (`id_solicitud`);

--
-- Indexes for table `solicitud_empleo_referencias`
--
ALTER TABLE `solicitud_empleo_referencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_solicitud` (`id_solicitud`);

--
-- Indexes for table `subgrupos_productos`
--
ALTER TABLE `subgrupos_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indexes for table `subgrupo_presentacion_producto`
--
ALTER TABLE `subgrupo_presentacion_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subgrupo_grupo` (`id_grupo_presentacion_producto`),
  ADD KEY `idx_subgrupo_nombre` (`nombre`),
  ADD KEY `fk_subgrupo_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_subgrupo_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_sucursales_departamento` (`cod_departamento`);

--
-- Indexes for table `TardanzasManuales`
--
ALTER TABLE `TardanzasManuales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tardanza_operario` (`cod_operario`),
  ADD KEY `idx_tardanza_sucursal` (`cod_sucursal`);

--
-- Indexes for table `TardanzasStatus`
--
ALTER TABLE `TardanzasStatus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tardanza_marcacion` (`id_marcacion`),
  ADD KEY `idx_tardanza_operario` (`cod_operario`);

--
-- Indexes for table `TipoContrato`
--
ALTER TABLE `TipoContrato`
  ADD PRIMARY KEY (`CodTipoContrato`);

--
-- Indexes for table `TipoSalida`
--
ALTER TABLE `TipoSalida`
  ADD PRIMARY KEY (`CodTipoSalida`);

--
-- Indexes for table `tipos_falta`
--
ALTER TABLE `tipos_falta`
  ADD PRIMARY KEY (`codigo`);

--
-- Indexes for table `tipo_cambio`
--
ALTER TABLE `tipo_cambio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tipo_pago_proveedores`
--
ALTER TABLE `tipo_pago_proveedores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indexes for table `tipo_receta_producto`
--
ALTER TABLE `tipo_receta_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo_receta_nombre` (`nombre`),
  ADD KEY `fk_tipo_receta_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_tipo_receta_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `tools_erp`
--
ALTER TABLE `tools_erp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo_componente` (`tipo_componente`,`activo`);

--
-- Indexes for table `unidad_producto`
--
ALTER TABLE `unidad_producto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_unico` (`nombre`),
  ADD KEY `idx_usuario_creacion` (`usuario_creacion`),
  ADD KEY `idx_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indexes for table `Vacaciones`
--
ALTER TABLE `Vacaciones`
  ADD PRIMARY KEY (`CodVacaciones`),
  ADD KEY `cod_contrato` (`cod_contrato`);

--
-- Indexes for table `validacion_cv_ia`
--
ALTER TABLE `validacion_cv_ia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_postulacion` (`id_postulacion`),
  ADD KEY `idx_campo` (`campo`),
  ADD KEY `usuario_registra` (`usuario_registra`),
  ADD KEY `usuario_modifica` (`usuario_modifica`);

--
-- Indexes for table `variedad_producto_presentacion`
--
ALTER TABLE `variedad_producto_presentacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_variedad_producto` (`id_presentacion_producto`),
  ADD KEY `fk_variedad_usuario_creacion` (`usuario_creacion`),
  ADD KEY `fk_variedad_usuario_modificacion` (`usuario_modificacion`);

--
-- Indexes for table `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `sucursal_id` (`sucursal_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `servicio_delivery_id` (`servicio_delivery_id`);

--
-- Indexes for table `VentasGlobalesAccessCSV`
--
ALTER TABLE `VentasGlobalesAccessCSV`
  ADD KEY `idx_fecha` (`Fecha`),
  ADD KEY `idx_codproducto` (`CodProducto`),
  ADD KEY `idx_fecha_hora` (`Fecha`,`Hora`),
  ADD KEY `idx_sucursal` (`Sucursal_Nombre`),
  ADD KEY `idx_membresia` (`CodCliente`),
  ADD KEY `idx_rfm_fecha` (`Fecha`),
  ADD KEY `idx_rfm_cliente_pedido` (`CodCliente`,`CodPedido`),
  ADD KEY `idx_rfm_anulado_tipo` (`Anulado`,`Tipo`),
  ADD KEY `idx_rfm_promocion` (`CodigoPromocion`),
  ADD KEY `idx_rfm_cod_cliente` (`CodCliente`),
  ADD KEY `idx_rfm_cod_pedido` (`CodPedido`),
  ADD KEY `idx_rfm_status` (`Anulado`),
  ADD KEY `idx_rfm_sucursal` (`Sucursal_Nombre`),
  ADD KEY `idx_rfm_socio_actividad` (`CodCliente`,`Fecha`),
  ADD KEY `idx_codpedido` (`CodPedido`),
  ADD KEY `idx_codcliente` (`CodCliente`),
  ADD KEY `idx_apos` (`aPOS`),
  ADD KEY `idx_delivery_nombre` (`Delivery_Nombre`),
  ADD KEY `idx_tipo` (`Tipo`),
  ADD KEY `idx_nombregrupo` (`NombreGrupo`),
  ADD KEY `idx_db_batidos_nombre` (`DBBatidos_Nombre`),
  ADD KEY `idx_medida` (`Medida`),
  ADD KEY `idx_local` (`local`),
  ADD KEY `idx_caja` (`Caja`),
  ADD KEY `idx_modalidad` (`Modalidad`),
  ADD KEY `idx_motorizado` (`Motorizado`),
  ADD KEY `idx_horacreado` (`HoraCreado`),
  ADD KEY `idx_horaingresoproducto` (`HoraIngresoProducto`),
  ADD KEY `idx_horaimpreso` (`HoraImpreso`),
  ADD KEY `idx_semana` (`Semana`),
  ADD KEY `idx_puntos` (`Puntos`),
  ADD KEY `idx_montofactura` (`MontoFactura`),
  ADD KEY `idx_pedidodecentral` (`PedidoDeCentral`),
  ADD KEY `idx_codmotorizado` (`CodMotorizado`);

--
-- Indexes for table `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `idx_venta_producto` (`venta_id`,`producto_id`);

--
-- Indexes for table `ventas_detalle_promociones`
--
ALTER TABLE `ventas_detalle_promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_detalle_id` (`venta_detalle_id`),
  ADD KEY `promocion_id` (`promocion_id`);

--
-- Indexes for table `ventas_extras`
--
ALTER TABLE `ventas_extras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_detalle_id` (`venta_detalle_id`),
  ADD KEY `extra_id` (`extra_id`);

--
-- Indexes for table `ventas_meta`
--
ALTER TABLE `ventas_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sucursal_fecha` (`cod_sucursal`,`fecha`);

--
-- Indexes for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `promocion_id` (`promocion_id`);

--
-- Indexes for table `viaticos`
--
ALTER TABLE `viaticos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_viaticos_operario` (`cod_operario`),
  ADD KEY `fk_viaticos_sucursal` (`sucursal_codigo`),
  ADD KEY `idx_viaticos_fecha` (`fecha`),
  ADD KEY `fk_viaticos_creado_por` (`creado_por`),
  ADD KEY `fk_viaticos_actualizado_por` (`actualizado_por`);

--
-- Indexes for table `wsp_campanas_`
--
ALTER TABLE `wsp_campanas_`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_envio` (`fecha_envio`);

--
-- Indexes for table `wsp_destinatarios_`
--
ALTER TABLE `wsp_destinatarios_`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campana_pendientes` (`campana_id`,`enviado`);

--
-- Indexes for table `wsp_logs_`
--
ALTER TABLE `wsp_logs_`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campana` (`campana_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indexes for table `wsp_notificaciones_clientesclub_pendientes_`
--
ALTER TABLE `wsp_notificaciones_clientesclub_pendientes_`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wsp_notif_estado_instancia` (`estado`,`instancia`);

--
-- Indexes for table `wsp_planilla_programaciones_`
--
ALTER TABLE `wsp_planilla_programaciones_`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plwsp_usuario` (`usuario_creacion`);

--
-- Indexes for table `wsp_sesion_vps_`
--
ALTER TABLE `wsp_sesion_vps_`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_instancia` (`instancia`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acciones_tools_erp`
--
ALTER TABLE `acciones_tools_erp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Adelantos`
--
ALTER TABLE `Adelantos`
  MODIFY `CodAdelanto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ArchivosAdjuntos`
--
ALTER TABLE `ArchivosAdjuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `archivos_presentacion_producto`
--
ALTER TABLE `archivos_presentacion_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `AsignacionNivelesCargos`
--
ALTER TABLE `AsignacionNivelesCargos`
  MODIFY `CodAsignacionNivelesCargos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_caja_chica`
--
ALTER TABLE `auditoria_caja_chica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_caja_chica_detalle`
--
ALTER TABLE `auditoria_caja_chica_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_facturacion`
--
ALTER TABLE `auditoria_facturacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_fotos`
--
ALTER TABLE `auditoria_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_inventario`
--
ALTER TABLE `auditoria_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_inventario_detalle`
--
ALTER TABLE `auditoria_inventario_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_inventario_operarios`
--
ALTER TABLE `auditoria_inventario_operarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_personal`
--
ALTER TABLE `auditoria_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_personal_fotos`
--
ALTER TABLE `auditoria_personal_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_procesos`
--
ALTER TABLE `auditoria_procesos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_promociones`
--
ALTER TABLE `auditoria_promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_servicio`
--
ALTER TABLE `auditoria_servicio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auditoria_servicio_fotos`
--
ALTER TABLE `auditoria_servicio_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `AutorizacionesEdicion`
--
ALTER TABLE `AutorizacionesEdicion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BitacoraColaborador`
--
ALTER TABLE `BitacoraColaborador`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BoletaAguinaldo`
--
ALTER TABLE `BoletaAguinaldo`
  MODIFY `id_boleta_aguinaldo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BoletaPago`
--
ALTER TABLE `BoletaPago`
  MODIFY `id_boleta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_crons_config`
--
ALTER TABLE `bot_crons_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_estado_confirmacion`
--
ALTER TABLE `bot_estado_confirmacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_intents`
--
ALTER TABLE `bot_intents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_operaciones_log`
--
ALTER TABLE `bot_operaciones_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caja_chica_sucursales`
--
ALTER TABLE `caja_chica_sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `CategoriasOperarios`
--
ALTER TABLE `CategoriasOperarios`
  MODIFY `idCategoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categoria_producto_maestro`
--
ALTER TABLE `categoria_producto_maestro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clientesclub`
--
ALTER TABLE `clientesclub`
  MODIFY `id_clienteclub` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `componentes_receta_producto`
--
ALTER TABLE `componentes_receta_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compra_local_configuracion_despacho`
--
ALTER TABLE `compra_local_configuracion_despacho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compra_local_pedidos_historico`
--
ALTER TABLE `compra_local_pedidos_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compra_local_perfiles_despacho`
--
ALTER TABLE `compra_local_perfiles_despacho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ContactosEmergencia`
--
ALTER TABLE `ContactosEmergencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacto_proveedores`
--
ALTER TABLE `contacto_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Contratos`
--
ALTER TABLE `Contratos`
  MODIFY `CodContrato` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contratos_tiposDocumentos`
--
ALTER TABLE `contratos_tiposDocumentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversion_unidad_producto`
--
ALTER TABLE `conversion_unidad_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `CuentaBancaria`
--
ALTER TABLE `CuentaBancaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cuenta_proveedor`
--
ALTER TABLE `cuenta_proveedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cupones_sucursales`
--
ALTER TABLE `cupones_sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deducciones_operaciones`
--
ALTER TABLE `deducciones_operaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diccionario_productos_legado`
--
ALTER TABLE `diccionario_productos_legado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `endulzantes`
--
ALTER TABLE `endulzantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `endulzantes_asignaciones`
--
ALTER TABLE `endulzantes_asignaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `entrevistas_candidatos`
--
ALTER TABLE `entrevistas_candidatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extras`
--
ALTER TABLE `extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltante_caja`
--
ALTER TABLE `faltante_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltante_danos`
--
ALTER TABLE `faltante_danos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltante_danos_operarios`
--
ALTER TABLE `faltante_danos_operarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltante_inventario`
--
ALTER TABLE `faltante_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltante_inventario_detalle`
--
ALTER TABLE `faltante_inventario_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltante_inventario_operarios`
--
ALTER TABLE `faltante_inventario_operarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Faltas`
--
ALTER TABLE `Faltas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faltas_manual`
--
ALTER TABLE `faltas_manual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feriadosnic`
--
ALTER TABLE `feriadosnic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `FeriadosStatus`
--
ALTER TABLE `FeriadosStatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fichatecnica_presentacion_producto`
--
ALTER TABLE `fichatecnica_presentacion_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `formato_salida`
--
ALTER TABLE `formato_salida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fotos_presentacion_producto`
--
ALTER TABLE `fotos_presentacion_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gestion_proyectos_proyectos`
--
ALTER TABLE `gestion_proyectos_proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gestion_tareas_reuniones_archivos`
--
ALTER TABLE `gestion_tareas_reuniones_archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gestion_tareas_reuniones_comentarios`
--
ALTER TABLE `gestion_tareas_reuniones_comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gestion_tareas_reuniones_items`
--
ALTER TABLE `gestion_tareas_reuniones_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gestion_tareas_reuniones_participantes`
--
ALTER TABLE `gestion_tareas_reuniones_participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gestores_reclamos`
--
ALTER TABLE `gestores_reclamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grupos_productos`
--
ALTER TABLE `grupos_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grupo_presentacion_producto`
--
ALTER TABLE `grupo_presentacion_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historial_estados_personal`
--
ALTER TABLE `historial_estados_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historial_proveedores`
--
ALTER TABLE `historial_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `HorariosSemanales`
--
ALTER TABLE `HorariosSemanales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `HorariosSemanalesOperaciones`
--
ALTER TABLE `HorariosSemanalesOperaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `HorasExtraStatus`
--
ALTER TABLE `HorasExtraStatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `horas_extras_manual`
--
ALTER TABLE `horas_extras_manual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_graficos_cache`
--
ALTER TABLE `ia_graficos_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_graficos_diccionario_columnas`
--
ALTER TABLE `ia_graficos_diccionario_columnas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_graficos_favoritos`
--
ALTER TABLE `ia_graficos_favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_graficos_filtros_conceptuales`
--
ALTER TABLE `ia_graficos_filtros_conceptuales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_graficos_metricas_predefinidas`
--
ALTER TABLE `ia_graficos_metricas_predefinidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_graficos_validaciones`
--
ALTER TABLE `ia_graficos_validaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ia_proveedores_api`
--
ALTER TABLE `ia_proveedores_api`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `IndicadoresSemanales`
--
ALTER TABLE `IndicadoresSemanales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `IndicadoresSemanalesResultados`
--
ALTER TABLE `IndicadoresSemanalesResultados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `intent_embeddings`
--
ALTER TABLE `intent_embeddings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_reclamos`
--
ALTER TABLE `kpi_reclamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marcaciones`
--
ALTER TABLE `marcaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos`
--
ALTER TABLE `mtto_equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_mantenimientos`
--
ALTER TABLE `mtto_equipos_mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_mantenimientos_fotos`
--
ALTER TABLE `mtto_equipos_mantenimientos_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_mantenimientos_programados`
--
ALTER TABLE `mtto_equipos_mantenimientos_programados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_mantenimientos_repuestos`
--
ALTER TABLE `mtto_equipos_mantenimientos_repuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_movimientos`
--
ALTER TABLE `mtto_equipos_movimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_repuestos`
--
ALTER TABLE `mtto_equipos_repuestos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_solicitudes`
--
ALTER TABLE `mtto_equipos_solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_solicitudes_fotos`
--
ALTER TABLE `mtto_equipos_solicitudes_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_equipos_tipos`
--
ALTER TABLE `mtto_equipos_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_informes_diarios`
--
ALTER TABLE `mtto_informes_diarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_informe_compras`
--
ALTER TABLE `mtto_informe_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_informe_tareas`
--
ALTER TABLE `mtto_informe_tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_informe_tareas_fotos`
--
ALTER TABLE `mtto_informe_tareas_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_informe_visitas`
--
ALTER TABLE `mtto_informe_visitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_materiales_frecuentes`
--
ALTER TABLE `mtto_materiales_frecuentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_tickets`
--
ALTER TABLE `mtto_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'codigo numerico de cada solicitud';

--
-- AUTO_INCREMENT for table `mtto_tickets_colaboradores`
--
ALTER TABLE `mtto_tickets_colaboradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_tickets_fotos`
--
ALTER TABLE `mtto_tickets_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_tickets_fotos_finalizacion`
--
ALTER TABLE `mtto_tickets_fotos_finalizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_tickets_materiales`
--
ALTER TABLE `mtto_tickets_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mtto_tipos_casos`
--
ALTER TABLE `mtto_tipos_casos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `NivelesCargos`
--
ALTER TABLE `NivelesCargos`
  MODIFY `CodNivelesCargos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Operarios`
--
ALTER TABLE `Operarios`
  MODIFY `CodOperario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OperariosCategorias`
--
ALTER TABLE `OperariosCategorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PagoAdelantos`
--
ALTER TABLE `PagoAdelantos`
  MODIFY `CodPagoAdelanto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PatronalesINSS`
--
ALTER TABLE `PatronalesINSS`
  MODIFY `CodPlanilla` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permisos_tools_erp`
--
ALTER TABLE `permisos_tools_erp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal`
--
ALTER TABLE `personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_auditorias`
--
ALTER TABLE `personal_auditorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pitaya_love_registros`
--
ALTER TABLE `pitaya_love_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plazas_cargos`
--
ALTER TABLE `plazas_cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postulacion_entrevista_telefonica`
--
ALTER TABLE `postulacion_entrevista_telefonica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postulacion_evaluacion_jefe`
--
ALTER TABLE `postulacion_evaluacion_jefe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postulacion_evaluacion_rh`
--
ALTER TABLE `postulacion_evaluacion_rh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postulacion_plaza`
--
ALTER TABLE `postulacion_plaza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_caja_inicial`
--
ALTER TABLE `pos_caja_inicial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_caja_inicial_detalle`
--
ALTER TABLE `pos_caja_inicial_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_facturas`
--
ALTER TABLE `pos_facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_facturas_detalle`
--
ALTER TABLE `pos_facturas_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productos_delivery`
--
ALTER TABLE `productos_delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `producto_maestro`
--
ALTER TABLE `producto_maestro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `producto_presentacion`
--
ALTER TABLE `producto_presentacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_aplicacion`
--
ALTER TABLE `promociones_aplicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_clientes_tipos`
--
ALTER TABLE `promociones_clientes_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_grupos`
--
ALTER TABLE `promociones_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_productos`
--
ALTER TABLE `promociones_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_requisitos`
--
ALTER TABLE `promociones_requisitos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promociones_tipos`
--
ALTER TABLE `promociones_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_condiciones`
--
ALTER TABLE `promo_condiciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_condiciones_opciones`
--
ALTER TABLE `promo_condiciones_opciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_promociones`
--
ALTER TABLE `promo_promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proveedores_compras_servicios`
--
ALTER TABLE `proveedores_compras_servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proveedor_tipo_pago`
--
ALTER TABLE `proveedor_tipo_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qr_offline_bank`
--
ALTER TABLE `qr_offline_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receta_producto_global`
--
ALTER TABLE `receta_producto_global`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos`
--
ALTER TABLE `reclamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos_cargos_responsables`
--
ALTER TABLE `reclamos_cargos_responsables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos_grupos`
--
ALTER TABLE `reclamos_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos_imagenes`
--
ALTER TABLE `reclamos_imagenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos_productos`
--
ALTER TABLE `reclamos_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos_tipos`
--
ALTER TABLE `reclamos_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamos_videos`
--
ALTER TABLE `reclamos_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reembolsos_detalles`
--
ALTER TABLE `reembolsos_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reembolsos_solicitudes`
--
ALTER TABLE `reembolsos_solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reportes_colaboradores`
--
ALTER TABLE `reportes_colaboradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reportes_investigacion`
--
ALTER TABLE `reportes_investigacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisicion_personal`
--
ALTER TABLE `requisicion_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `SalarioINSS`
--
ALTER TABLE `SalarioINSS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `SalarioOperario`
--
ALTER TABLE `SalarioOperario`
  MODIFY `CodSalarioOperario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `SemanasSistema`
--
ALTER TABLE `SemanasSistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `servicios_delivery`
--
ALTER TABLE `servicios_delivery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitudes_cotizacion`
--
ALTER TABLE `solicitudes_cotizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitudes_cotizacion_fotos`
--
ALTER TABLE `solicitudes_cotizacion_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitudes_cotizacion_historial`
--
ALTER TABLE `solicitudes_cotizacion_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitudes_cotizacion_productos`
--
ALTER TABLE `solicitudes_cotizacion_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitudes_vacaciones`
--
ALTER TABLE `solicitudes_vacaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitud_empleo`
--
ALTER TABLE `solicitud_empleo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitud_empleo_estudios`
--
ALTER TABLE `solicitud_empleo_estudios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitud_empleo_experiencia`
--
ALTER TABLE `solicitud_empleo_experiencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitud_empleo_familiares`
--
ALTER TABLE `solicitud_empleo_familiares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitud_empleo_hijos`
--
ALTER TABLE `solicitud_empleo_hijos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `solicitud_empleo_referencias`
--
ALTER TABLE `solicitud_empleo_referencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subgrupos_productos`
--
ALTER TABLE `subgrupos_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subgrupo_presentacion_producto`
--
ALTER TABLE `subgrupo_presentacion_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `TardanzasManuales`
--
ALTER TABLE `TardanzasManuales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `TardanzasStatus`
--
ALTER TABLE `TardanzasStatus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `TipoContrato`
--
ALTER TABLE `TipoContrato`
  MODIFY `CodTipoContrato` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `TipoSalida`
--
ALTER TABLE `TipoSalida`
  MODIFY `CodTipoSalida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipo_cambio`
--
ALTER TABLE `tipo_cambio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipo_pago_proveedores`
--
ALTER TABLE `tipo_pago_proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipo_receta_producto`
--
ALTER TABLE `tipo_receta_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tools_erp`
--
ALTER TABLE `tools_erp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unidad_producto`
--
ALTER TABLE `unidad_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Vacaciones`
--
ALTER TABLE `Vacaciones`
  MODIFY `CodVacaciones` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `validacion_cv_ia`
--
ALTER TABLE `validacion_cv_ia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `variedad_producto_presentacion`
--
ALTER TABLE `variedad_producto_presentacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas_detalle_promociones`
--
ALTER TABLE `ventas_detalle_promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas_extras`
--
ALTER TABLE `ventas_extras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas_meta`
--
ALTER TABLE `ventas_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `viaticos`
--
ALTER TABLE `viaticos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wsp_campanas_`
--
ALTER TABLE `wsp_campanas_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wsp_destinatarios_`
--
ALTER TABLE `wsp_destinatarios_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wsp_logs_`
--
ALTER TABLE `wsp_logs_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wsp_notificaciones_clientesclub_pendientes_`
--
ALTER TABLE `wsp_notificaciones_clientesclub_pendientes_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wsp_planilla_programaciones_`
--
ALTER TABLE `wsp_planilla_programaciones_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wsp_sesion_vps_`
--
ALTER TABLE `wsp_sesion_vps_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `view_deducciones_completas`
--
DROP TABLE IF EXISTS `view_deducciones_completas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_deducciones_completas`  AS SELECT `datos`.`tipo` AS `tipo`, `datos`.`id` AS `id`, `datos`.`fecha_evento` AS `fecha_evento`, `datos`.`fecha_deduccion` AS `fecha_deduccion`, `datos`.`sucursal_id` AS `sucursal_id`, `datos`.`sucursal_nombre` AS `sucursal_nombre`, `datos`.`operario_id` AS `operario_id`, `datos`.`operario_nombre` AS `operario_nombre`, `datos`.`comentarios` AS `comentarios`, `datos`.`monto_original` AS `monto_original`, `datos`.`monto_calculado` AS `monto`, `datos`.`url_ver` AS `url_ver`, `datos`.`cod_contrato` AS `cod_contrato`, `datos`.`fecha_registro` AS `fecha_registro`, CASE WHEN `datos`.`tipo` = 'faltante_caja' THEN CASE WHEN dayofmonth(`datos`.`fecha_evento`) between 5 and 12 THEN 'Planilla Primer Quincena' WHEN dayofmonth(`datos`.`fecha_evento`) between 13 and 26 THEN 'Planilla Segunda Quincena' ELSE 'Propina' END ELSE CASE WHEN dayofmonth(`datos`.`fecha_evento` - interval 6 hour) between 5 and 12 THEN 'Planilla Primer Quincena' WHEN dayofmonth(`datos`.`fecha_evento` - interval 6 hour) between 13 and 26 THEN 'Planilla Segunda Quincena' ELSE 'Propina' END END AS `estado_deduccion`, CASE WHEN `datos`.`tipo` = 'faltante_caja' THEN CASE WHEN dayofmonth(`datos`.`fecha_evento`) between 5 and 12 THEN date_format(last_day(`datos`.`fecha_evento` - interval dayofmonth(`datos`.`fecha_evento`) - 1 day + interval 14 day),'%d-%b-%y') WHEN dayofmonth(`datos`.`fecha_evento`) between 13 and 26 THEN date_format(last_day(`datos`.`fecha_evento`),'%d-%b-%y') ELSE CASE WHEN dayofmonth(`datos`.`fecha_evento`) > 7 THEN date_format(last_day(`datos`.`fecha_evento`) + interval 7 day,'%d-%b-%y') ELSE date_format(`datos`.`fecha_evento` - interval dayofmonth(`datos`.`fecha_evento`) - 1 day + interval 7 day,'%d-%b-%y') END END ELSE CASE WHEN dayofmonth(`datos`.`fecha_evento` - interval 6 hour) between 5 and 12 THEN date_format(last_day(`datos`.`fecha_evento` - interval dayofmonth(`datos`.`fecha_evento`) - 1 day + interval 14 day),'%d-%b-%y') WHEN dayofmonth(`datos`.`fecha_evento` - interval 6 hour) between 13 and 26 THEN date_format(last_day(`datos`.`fecha_evento`),'%d-%b-%y') ELSE CASE WHEN dayofmonth(`datos`.`fecha_evento` - interval 6 hour) > 7 THEN date_format(last_day(`datos`.`fecha_evento`) + interval 7 day,'%d-%b-%y') ELSE date_format(`datos`.`fecha_evento` - interval dayofmonth(`datos`.`fecha_evento`) - 1 day + interval 7 day,'%d-%b-%y') END END END AS `fecha_aplicacion`, CASE `datos`.`tipo` WHEN 'facturacion' THEN 'Caja Facturación' WHEN 'caja_chica' THEN 'Caja Chica' WHEN 'inventario' THEN 'Auditoría Inventario' WHEN 'faltante_inventario' THEN 'Faltante Inventario' WHEN 'faltante_danos' THEN 'Faltante Daños' WHEN 'faltante_caja' THEN 'Faltante de Caja' ELSE 'Desconocido' END AS `tipo_concepto`, CASE WHEN `datos`.`comentarios` is not null AND `datos`.`comentarios` <> '' THEN concat(`datos`.`comentarios`,' (',case `datos`.`tipo` when 'facturacion' then 'Caja Facturación' when 'caja_chica' then 'Caja Chica' when 'inventario' then 'Auditoría Inventario' when 'faltante_inventario' then 'Faltante Inventario' when 'faltante_danos' then 'Faltante Daños' when 'faltante_caja' then 'Faltante de Caja' else 'Desconocido' end,')') ELSE concat('(',case `datos`.`tipo` when 'facturacion' then 'Caja Facturación' when 'caja_chica' then 'Caja Chica' when 'inventario' then 'Auditoría Inventario' when 'faltante_inventario' then 'Faltante Inventario' when 'faltante_danos' then 'Faltante Daños' when 'faltante_caja' then 'Faltante de Caja' else 'Desconocido' end,')') END AS `detalle_combinado`, date_format(`datos`.`fecha_registro` - interval 6 hour,'%d-%m-%Y %H:%i:%s') AS `fecha_registro_formateada`, concat(`datos`.`cod_contrato`,' ',`datos`.`operario_nombre`) AS `persona_completa`, abs(`datos`.`monto_calculado`) AS `monto_absoluto` FROM (select 'facturacion' AS `tipo`,`af`.`id` AS `id`,`af`.`fecha_hora_regsys` AS `fecha_evento`,`af`.`fecha_deduccion` AS `fecha_deduccion`,`af`.`sucursal_id` AS `sucursal_id`,`s`.`nombre` AS `sucursal_nombre`,`af`.`cod_contrato` AS `operario_id`,concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `operario_nombre`,`af`.`comentarios` AS `comentarios`,`af`.`faltante_sobrante` AS `monto_original`,case when `af`.`faltante_sobrante` < 0 then abs(`af`.`faltante_sobrante`) else 0 end AS `monto_calculado`,'ver_auditorias_facturacion.php' AS `url_ver`,`af`.`cod_contrato` AS `cod_contrato`,`af`.`fecha_hora_regsys` AS `fecha_registro` from ((`auditoria_facturacion` `af` join `Operarios` `o` on(`af`.`cajero` = `o`.`CodOperario`)) join `sucursales` `s` on(`af`.`sucursal_id` = `s`.`codigo`)) union all select 'caja_chica' AS `tipo`,`acc`.`id` AS `id`,`acc`.`fecha_hora_regsys` AS `fecha_evento`,`acc`.`fecha_deduccion` AS `fecha_deduccion`,`acc`.`sucursal_id` AS `sucursal_id`,`s`.`nombre` AS `sucursal_nombre`,`acc`.`cod_contrato` AS `operario_id`,concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `operario_nombre`,`acc`.`comentarios` AS `comentarios`,`acc`.`faltante_sobrante` AS `monto_original`,case when `acc`.`faltante_sobrante` < 0 then abs(`acc`.`faltante_sobrante`) else 0 end AS `monto_calculado`,'ver_auditorias_caja_chica.php' AS `url_ver`,`acc`.`cod_contrato` AS `cod_contrato`,`acc`.`fecha_hora_regsys` AS `fecha_registro` from ((`auditoria_caja_chica` `acc` join `Operarios` `o` on(`acc`.`lider_tienda_codigo` = `o`.`CodOperario`)) join `sucursales` `s` on(`acc`.`sucursal_id` = `s`.`codigo`)) union all select 'inventario' AS `tipo`,`ai`.`id` AS `id`,`ai`.`fecha_hora_regsys` AS `fecha_evento`,`aio`.`fecha_deduccion` AS `fecha_deduccion`,`ai`.`sucursal_id` AS `sucursal_id`,`s`.`nombre` AS `sucursal_nombre`,`aio`.`cod_contrato` AS `operario_id`,concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `operario_nombre`,`ai`.`comentarios` AS `comentarios`,`aio`.`monto` AS `monto_original`,`aio`.`monto` AS `monto_calculado`,'ver_auditorias_inventario.php' AS `url_ver`,`aio`.`cod_contrato` AS `cod_contrato`,`ai`.`fecha_hora_regsys` AS `fecha_registro` from (((`auditoria_inventario` `ai` join `auditoria_inventario_operarios` `aio` on(`ai`.`id` = `aio`.`auditoria_id`)) join `Operarios` `o` on(`aio`.`operario_id` = `o`.`CodOperario`)) join `sucursales` `s` on(`ai`.`sucursal_id` = `s`.`codigo`)) union all select 'faltante_inventario' AS `tipo`,`fi`.`id` AS `id`,`fi`.`fecha_hora_regsys` AS `fecha_evento`,`fio`.`fecha_deduccion` AS `fecha_deduccion`,`fi`.`sucursal_id` AS `sucursal_id`,`s`.`nombre` AS `sucursal_nombre`,`fio`.`cod_contrato` AS `operario_id`,concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `operario_nombre`,`fi`.`comentarios` AS `comentarios`,`fio`.`monto` AS `monto_original`,`fio`.`monto` AS `monto_calculado`,'ver_faltante_inventario.php' AS `url_ver`,`fio`.`cod_contrato` AS `cod_contrato`,`fi`.`fecha_hora_regsys` AS `fecha_registro` from (((`faltante_inventario` `fi` join `faltante_inventario_operarios` `fio` on(`fi`.`id` = `fio`.`faltante_id`)) join `Operarios` `o` on(`fio`.`operario_id` = `o`.`CodOperario`)) join `sucursales` `s` on(`fi`.`sucursal_id` = `s`.`codigo`)) union all select 'faltante_danos' AS `tipo`,`fd`.`id` AS `id`,`fd`.`fecha_hora_regsys` AS `fecha_evento`,`fdo`.`fecha_deduccion` AS `fecha_deduccion`,`fd`.`sucursal_codigo` AS `sucursal_id`,`s`.`nombre` AS `sucursal_nombre`,`fdo`.`cod_contrato` AS `operario_id`,concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `operario_nombre`,`fd`.`comentarios` AS `comentarios`,`fdo`.`monto` AS `monto_original`,`fdo`.`monto` AS `monto_calculado`,'ver_faltante_danos.php' AS `url_ver`,`fdo`.`cod_contrato` AS `cod_contrato`,`fd`.`fecha_hora_regsys` AS `fecha_registro` from (((`faltante_danos` `fd` join `faltante_danos_operarios` `fdo` on(`fd`.`id` = `fdo`.`faltante_id`)) join `Operarios` `o` on(`fdo`.`operario_id` = `o`.`CodOperario`)) join `sucursales` `s` on(`fd`.`sucursal_codigo` = `s`.`codigo`)) union all select 'faltante_caja' AS `tipo`,`fc`.`id` AS `id`,`fc`.`fecha` AS `fecha_evento`,`fc`.`fecha_deduccion` AS `fecha_deduccion`,`fc`.`sucursal_id` AS `sucursal_id`,`s`.`nombre` AS `sucursal_nombre`,`fc`.`cod_contrato` AS `operario_id`,`fc`.`operario_nombre` AS `operario_nombre`,`fc`.`comentarios` AS `comentarios`,`fc`.`monto` AS `monto_original`,`fc`.`monto` AS `monto_calculado`,'ver_faltante_caja.php' AS `url_ver`,`fc`.`cod_contrato` AS `cod_contrato`,`fc`.`fecha_hora_regsys` AS `fecha_registro` from (`faltante_caja` `fc` join `sucursales` `s` on(`fc`.`sucursal_id` = `s`.`codigo`))) AS `datos` ORDER BY `datos`.`fecha_evento` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_estadisticas_por_departamentos_y_semanas_reclamos`
--
DROP TABLE IF EXISTS `view_estadisticas_por_departamentos_y_semanas_reclamos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_estadisticas_por_departamentos_y_semanas_reclamos`  AS SELECT `vrc`.`anio` AS `anio`, `vrc`.`mes_numero` AS `mes_numero`, `vrc`.`numero_semana` AS `numero_semana`, `vrc`.`semana_fecha_inicio` AS `semana_fecha_inicio`, `vrc`.`semana_fecha_fin` AS `semana_fecha_fin`, `vrc`.`cod_departamento` AS `cod_departamento`, `vrc`.`nombre_departamento` AS `nombre_departamento`, `vrc`.`sucursal_codigo` AS `sucursal_codigo`, `vrc`.`nombre_sucursal` AS `nombre_sucursal`, `vrc`.`fuente` AS `fuente`, count(`vrc`.`reclamo_id`) AS `total_reclamos`, sum(case when `vrc`.`estado_reclamo` = 'Abierto' then 1 else 0 end) AS `reclamos_abiertos`, sum(case when `vrc`.`estado_reclamo` = 'Equipo de Tienda' then 1 else 0 end) AS `reclamos_equipo_tienda`, sum(case when `vrc`.`estado_reclamo` = 'Cerrado' then 1 else 0 end) AS `reclamos_cerrados`, count(distinct `vrc`.`tipo_reclamo`) AS `tipos_reclamo_diferentes`, count(distinct `vrc`.`medio_compra`) AS `medios_compra_diferentes`, sum(`vrc`.`total_productos`) AS `total_productos_reclamados`, sum(`vrc`.`monto_total_productos`) AS `monto_total_productos`, round(avg(`vrc`.`total_productos`),2) AS `promedio_productos_por_reclamo`, sum(`vrc`.`total_colaboradores_involucrados`) AS `total_colaboradores_involucrados`, round(avg(`vrc`.`total_colaboradores_involucrados`),2) AS `promedio_colaboradores_por_reclamo`, sum(`vrc`.`monto_total_responsabilidad`) AS `monto_total_responsabilidad`, sum(`vrc`.`total_imagenes`) AS `total_imagenes`, sum(`vrc`.`total_videos`) AS `total_videos`, round(avg(`vrc`.`dias_diferencia_evento_reclamo`),2) AS `promedio_dias_respuesta`, sum(`vrc`.`tiene_investigacion`) AS `investigaciones_completadas`, round(sum(`vrc`.`tiene_investigacion`) / count(`vrc`.`reclamo_id`) * 100,2) AS `porcentaje_investigaciones`, min(`vrc`.`fecha_hora_ajustada`) AS `primer_reclamo_periodo`, max(`vrc`.`fecha_hora_ajustada`) AS `ultimo_reclamo_periodo` FROM `view_reclamos_completa` AS `vrc` GROUP BY `vrc`.`anio`, `vrc`.`mes_numero`, `vrc`.`numero_semana`, `vrc`.`cod_departamento`, `vrc`.`nombre_departamento`, `vrc`.`sucursal_codigo`, `vrc`.`nombre_sucursal`, `vrc`.`fuente` ORDER BY `vrc`.`anio` DESC, `vrc`.`mes_numero` DESC, `vrc`.`numero_semana` DESC, `vrc`.`nombre_departamento` ASC, `vrc`.`nombre_sucursal` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_faltas_no_reportadas_septimo`
--
DROP TABLE IF EXISTS `view_faltas_no_reportadas_septimo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_faltas_no_reportadas_septimo`  AS SELECT coalesce((select `c`.`CodContrato` from `Contratos` `c` where `c`.`cod_operario` = `fa`.`cod_operario` order by `c`.`inicio_contrato` desc,`c`.`CodContrato` desc limit 1),'') AS `codigo_contrato`, concat(coalesce((select `c`.`CodContrato` from `Contratos` `c` where `c`.`cod_operario` = `fa`.`cod_operario` order by `c`.`inicio_contrato` desc,`c`.`CodContrato` desc limit 1),''),' ',trim(concat_ws(' ',nullif(trim(`fa`.`operario_nombre`),''),nullif(trim(`fa`.`operario_nombre2`),''),nullif(trim(`fa`.`operario_apellido`),''),nullif(trim(`fa`.`operario_apellido2`),'')))) AS `persona`, `fa`.`sucursal_nombre` AS `sucursal`, `fa`.`fecha_falta` AS `fecha`, 'No se presentó' AS `observaciones`, cast(`fa`.`fecha_falta` as date) AS `fecha_registro`, 'Sistema' AS `origen` FROM (select distinct `o`.`CodOperario` AS `cod_operario`,`o`.`Nombre` AS `operario_nombre`,`o`.`Nombre2` AS `operario_nombre2`,`o`.`Apellido` AS `operario_apellido`,`o`.`Apellido2` AS `operario_apellido2`,`s`.`nombre` AS `sucursal_nombre`,`cal`.`fecha` AS `fecha_falta` from ((((((`Operarios` `o` join `AsignacionNivelesCargos` `anc` on(`o`.`CodOperario` = `anc`.`CodOperario`)) join `sucursales` `s` on(`anc`.`Sucursal` = `s`.`codigo`)) join (select curdate() - interval `tens`.`a` * 10 + `units`.`a` day AS `fecha` from ((select 0 AS `a` union select 1 AS `1` union select 2 AS `2` union select 3 AS `3` union select 4 AS `4` union select 5 AS `5` union select 6 AS `6` union select 7 AS `7` union select 8 AS `8` union select 9 AS `9`) `units` join (select 0 AS `a` union select 1 AS `1` union select 2 AS `2` union select 3 AS `3` union select 4 AS `4` union select 5 AS `5`) `tens`) where `tens`.`a` * 10 + `units`.`a` <= 60) `cal`) join `HorariosSemanalesOperaciones` `hso` on(`o`.`CodOperario` = `hso`.`cod_operario` and `s`.`codigo` = `hso`.`cod_sucursal`)) join `SemanasSistema` `ss` on(`hso`.`id_semana_sistema` = `ss`.`id` and `cal`.`fecha` between `ss`.`fecha_inicio` and `ss`.`fecha_fin`)) left join `marcaciones` `m` on(`o`.`CodOperario` = `m`.`CodOperario` and `cal`.`fecha` = `m`.`fecha` and `s`.`codigo` = `m`.`sucursal_codigo` and `cal`.`fecha` <= curdate() and (dayofweek(`cal`.`fecha`) = 2 and `hso`.`lunes_estado` = 'Activo' and `hso`.`lunes_entrada` is not null or dayofweek(`cal`.`fecha`) = 3 and `hso`.`martes_estado` = 'Activo' and `hso`.`martes_entrada` is not null or dayofweek(`cal`.`fecha`) = 4 and `hso`.`miercoles_estado` = 'Activo' and `hso`.`miercoles_entrada` is not null or dayofweek(`cal`.`fecha`) = 5 and `hso`.`jueves_estado` = 'Activo' and `hso`.`jueves_entrada` is not null or dayofweek(`cal`.`fecha`) = 6 and `hso`.`viernes_estado` = 'Activo' and `hso`.`viernes_entrada` is not null or dayofweek(`cal`.`fecha`) = 7 and `hso`.`sabado_estado` = 'Activo' and `hso`.`sabado_entrada` is not null or dayofweek(`cal`.`fecha`) = 1 and `hso`.`domingo_estado` = 'Activo' and `hso`.`domingo_entrada` is not null) and `m`.`CodOperario` is null and !exists(select 1 from `faltas_manual` `fm` where `fm`.`cod_operario` = `o`.`CodOperario` and `fm`.`fecha_falta` = `cal`.`fecha` and `fm`.`cod_sucursal` = `s`.`codigo` limit 1)))) AS `fa`union all select coalesce(`fm`.`cod_contrato`,'') AS `codigo_contrato`,concat(coalesce(`fm`.`cod_contrato`,''),' ',trim(concat_ws(' ',nullif(trim(`o`.`Nombre`),''),nullif(trim(`o`.`Nombre2`),''),nullif(trim(`o`.`Apellido`),''),nullif(trim(`o`.`Apellido2`),'')))) AS `persona`,`s`.`nombre` AS `sucursal`,`fm`.`fecha_falta` AS `fecha`,case when `fm`.`tipo_falta` = 'Dia_mas_septimo' then 'Día + Séptimo' when `fm`.`tipo_falta` = 'Pendiente' then 'Líder subió reporte, pendiente por rrhh' else `fm`.`tipo_falta` end AS `observaciones`,cast(coalesce(`fm`.`fecha_registro`,`fm`.`fecha_falta`) - interval 6 hour as date) AS `fecha_registro`,'Manual' AS `origen` from ((`faltas_manual` `fm` join `Operarios` `o` on(`fm`.`cod_operario` = `o`.`CodOperario`)) join `sucursales` `s` on(`fm`.`cod_sucursal` = `s`.`codigo`)) where `fm`.`tipo_falta` in ('Dia_mas_septimo','Pendiente')  ;

-- --------------------------------------------------------

--
-- Structure for view `view_permisos`
--
DROP TABLE IF EXISTS `view_permisos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_permisos`  AS SELECT coalesce(`fm`.`cod_contrato`,'') AS `codigo_contrato`, concat(coalesce(`fm`.`cod_contrato`,''),' ',trim(concat_ws(' ',nullif(trim(`o`.`Nombre`),''),nullif(trim(`o`.`Nombre2`),''),nullif(trim(`o`.`Apellido`),''),nullif(trim(`o`.`Apellido2`),'')))) AS `persona`, `s`.`nombre` AS `sucursal`, `fm`.`fecha_falta` AS `fecha`, 1 AS `dias`, coalesce(`fm`.`porcentaje_pago`,`tf`.`porcentaje_pago`,0) AS `porcentaje_salario_pagar`, replace(`fm`.`tipo_falta`,'_',' ') AS `tipo_permiso`, coalesce(`fm`.`observaciones_rrhh`,'Sin comentarios por rrhh') AS `observaciones`, cast(coalesce(`fm`.`fecha_registro`,`fm`.`fecha_falta`) - interval 6 hour as date) AS `fecha_registro` FROM (((`faltas_manual` `fm` join `Operarios` `o` on(`fm`.`cod_operario` = `o`.`CodOperario`)) join `sucursales` `s` on(`fm`.`cod_sucursal` = `s`.`codigo`)) left join `tipos_falta` `tf` on(`fm`.`tipo_falta` = `tf`.`codigo`)) WHERE `fm`.`tipo_falta` not in ('Vacaciones','Dia_mas_septimo','Pendiente','No_Pagado') ;

-- --------------------------------------------------------

--
-- Structure for view `view_reclamos_completa`
--
DROP TABLE IF EXISTS `view_reclamos_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_reclamos_completa`  AS SELECT `r`.`id` AS `reclamo_id`, `r`.`fuente` AS `fuente`, `r`.`sucursal` AS `nombre_sucursal`, `r`.`sucursal_codigo` AS `sucursal_codigo`, `r`.`fecha_reclamo` AS `fecha_reclamo`, `r`.`fecha_evento` AS `fecha_evento`, `r`.`hora_evento` AS `hora_evento`, `r`.`medio_compra` AS `medio_compra`, `r`.`tipo_reclamo` AS `tipo_reclamo`, `r`.`descripcion` AS `descripcion`, `r`.`investigacion_preliminar` AS `investigacion_preliminar`, `r`.`gestor_reclamo` AS `gestor_reclamo`, `r`.`fecha_registro` AS `fecha_registro`, `r`.`hora_registro` AS `hora_registro`, `r`.`fecha_hora` AS `fecha_hora`, `ri`.`id` AS `investigacion_id`, `ri`.`fecha_resolucion` AS `fecha_resolucion`, `ri`.`resolucion` AS `resolucion`, `ri`.`tipo_reclamo_operaciones` AS `tipo_reclamo_operaciones`, `ri`.`investigacion` AS `investigacion`, `ri`.`plan_accion` AS `plan_accion`, `ri`.`fecha_hora` AS `fecha_hora_investigacion`, `s`.`nombre` AS `sucursal_nombre_completo`, `s`.`cod_departamento` AS `cod_departamento`, `d`.`nombre` AS `nombre_departamento`, `ss`.`numero_semana` AS `numero_semana`, `ss`.`fecha_inicio` AS `semana_fecha_inicio`, `ss`.`fecha_fin` AS `semana_fecha_fin`, (select group_concat(concat(`rp`.`producto`,' - C$',format(`rp`.`precio`,2)) separator '; ') from `reclamos_productos` `rp` where `rp`.`reclamo_id` = `r`.`id`) AS `productos_reclamo`, (select count(0) from `reclamos_productos` `rp` where `rp`.`reclamo_id` = `r`.`id`) AS `total_productos`, (select sum(`rp`.`precio`) from `reclamos_productos` `rp` where `rp`.`reclamo_id` = `r`.`id`) AS `monto_total_productos`, (select group_concat(concat(`rc`.`colaborador`,' - C$',format(`rc`.`monto_responsabilidad`,2)) separator '; ') from `reportes_colaboradores` `rc` where `rc`.`reporte_id` = `ri`.`id`) AS `colaboradores_involucrados`, (select count(0) from `reportes_colaboradores` `rc` where `rc`.`reporte_id` = `ri`.`id`) AS `total_colaboradores_involucrados`, (select sum(`rc`.`monto_responsabilidad`) from `reportes_colaboradores` `rc` where `rc`.`reporte_id` = `ri`.`id`) AS `monto_total_responsabilidad`, (select count(0) from `reclamos_imagenes` `ri_img` where `ri_img`.`reclamo_id` = `r`.`id`) AS `total_imagenes`, (select count(0) from `reclamos_videos` `rv` where `rv`.`reclamo_id` = `r`.`id`) AS `total_videos`, CASE WHEN `ri`.`id` is null THEN 'Abierto' WHEN `ri`.`resolucion` = 'Abierto' THEN 'Abierto' WHEN `ri`.`resolucion` = 'Equipo de Tienda' THEN 'Equipo de Tienda' ELSE 'Cerrado' END AS `estado_reclamo`, date_format(convert_tz(`r`.`fecha_hora`,'+00:00','-06:00'),'%Y-%m-%d %H:%i:%s') AS `fecha_hora_ajustada`, date_format(convert_tz(`r`.`fecha_hora`,'+00:00','-06:00'),'%d-%b-%y') AS `fecha_ajustada_corta`, date_format(convert_tz(`r`.`fecha_hora`,'+00:00','-06:00'),'%d-%b-%y %h:%i %p') AS `fecha_hora_formateada`, date_format(convert_tz(`ri`.`fecha_hora`,'+00:00','-06:00'),'%d-%b-%y %h:%i %p') AS `fecha_investigacion_formateada`, month(convert_tz(`r`.`fecha_hora`,'+00:00','-06:00')) AS `mes_numero`, year(convert_tz(`r`.`fecha_hora`,'+00:00','-06:00')) AS `anio`, to_days(`r`.`fecha_reclamo`) - to_days(`r`.`fecha_evento`) AS `dias_diferencia_evento_reclamo`, `s`.`departamento` AS `sucursal_departamento_original`, `s`.`activa` AS `sucursal_activa`, CASE WHEN `ri`.`id` is not null THEN 1 ELSE 0 END AS `tiene_investigacion` FROM ((((`reclamos` `r` left join `reportes_investigacion` `ri` on(`r`.`id` = `ri`.`reclamo_id`)) left join `sucursales` `s` on(`r`.`sucursal_codigo` = `s`.`codigo`)) left join `departamentos` `d` on(`s`.`cod_departamento` = `d`.`codigo`)) left join `SemanasSistema` `ss` on(convert_tz(`r`.`fecha_hora`,'+00:00','-06:00') between `ss`.`fecha_inicio` and `ss`.`fecha_fin`)) WHERE `s`.`activa` = 1 ORDER BY `r`.`fecha_hora` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_reporte_tardanzas`
--
DROP TABLE IF EXISTS `view_reporte_tardanzas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_reporte_tardanzas`  AS SELECT coalesce(`tm`.`cod_contrato`,`c`.`CodContrato`) AS `Código`, concat(coalesce(`tm`.`cod_contrato`,`c`.`CodContrato`),case when `o`.`Nombre` is not null and `o`.`Nombre` <> '' then ' ' else '' end,ifnull(`o`.`Nombre`,''),case when `o`.`Nombre2` is not null and `o`.`Nombre2` <> '' then ' ' else '' end,ifnull(`o`.`Nombre2`,''),case when `o`.`Apellido` is not null and `o`.`Apellido` <> '' then ' ' else '' end,ifnull(`o`.`Apellido`,''),case when `o`.`Apellido2` is not null and `o`.`Apellido2` <> '' then ' ' else '' end,ifnull(`o`.`Apellido2`,'')) AS `Persona`, CASE WHEN `tm`.`id` is not null THEN CASE WHEN `tm`.`estado` <> 'Justificado' THEN 1 ELSE 0 END ELSE 1 END AS `Tardanzas`, CASE WHEN `tm`.`id` is not null AND `tm`.`estado` = 'Justificado' THEN 1 ELSE 0 END AS `Tardanzas_Justificadas`, CASE WHEN `tm`.`id` is not null THEN `tm`.`fecha_registro` ELSE coalesce(`tm`.`fecha_tardanza`,`m`.`fecha`) END AS `Fecha_Registro` FROM ((((`marcaciones` `m` join `Operarios` `o` on(`m`.`CodOperario` = `o`.`CodOperario`)) left join `Contratos` `c` on(`m`.`CodOperario` = `c`.`cod_operario`)) left join (select `hso`.`cod_operario` AS `cod_operario`,`hso`.`cod_sucursal` AS `cod_sucursal`,`ss`.`fecha_inicio` AS `fecha_inicio`,`ss`.`fecha_fin` AS `fecha_fin`,case dayofweek(`m2`.`fecha`) when 2 then `hso`.`lunes_entrada` when 3 then `hso`.`martes_entrada` when 4 then `hso`.`miercoles_entrada` when 5 then `hso`.`jueves_entrada` when 6 then `hso`.`viernes_entrada` when 7 then `hso`.`sabado_entrada` when 1 then `hso`.`domingo_entrada` end AS `hora_entrada_programada`,timestampdiff(MINUTE,case dayofweek(`m2`.`fecha`) when 2 then `hso`.`lunes_entrada` when 3 then `hso`.`martes_entrada` when 4 then `hso`.`miercoles_entrada` when 5 then `hso`.`jueves_entrada` when 6 then `hso`.`viernes_entrada` when 7 then `hso`.`sabado_entrada` when 1 then `hso`.`domingo_entrada` end,`m2`.`hora_ingreso`) AS `minutos_tardanza`,`m2`.`fecha` AS `fecha` from ((`HorariosSemanalesOperaciones` `hso` join `SemanasSistema` `ss` on(`hso`.`id_semana_sistema` = `ss`.`id`)) join `marcaciones` `m2` on(`hso`.`cod_operario` = `m2`.`CodOperario` and `hso`.`cod_sucursal` = `m2`.`sucursal_codigo`)) where `m2`.`hora_ingreso` is not null) `hso_horario` on(`m`.`CodOperario` = `hso_horario`.`cod_operario` and `m`.`sucursal_codigo` = `hso_horario`.`cod_sucursal` and `m`.`fecha` = `hso_horario`.`fecha`)) left join `TardanzasManuales` `tm` on(`m`.`CodOperario` = `tm`.`cod_operario` and `m`.`fecha` = `tm`.`fecha_tardanza` and `m`.`sucursal_codigo` = `tm`.`cod_sucursal`)) WHERE `m`.`hora_ingreso` is not null AND `hso_horario`.`minutos_tardanza` > 1 AND `hso_horario`.`hora_entrada_programada` is not nullunion allselect `tm`.`cod_contrato` AS `Código`,concat(`tm`.`cod_contrato`,case when `o`.`Nombre` is not null and `o`.`Nombre` <> '' then ' ' else '' end,ifnull(`o`.`Nombre`,''),case when `o`.`Nombre2` is not null and `o`.`Nombre2` <> '' then ' ' else '' end,ifnull(`o`.`Nombre2`,''),case when `o`.`Apellido` is not null and `o`.`Apellido` <> '' then ' ' else '' end,ifnull(`o`.`Apellido`,''),case when `o`.`Apellido2` is not null and `o`.`Apellido2` <> '' then ' ' else '' end,ifnull(`o`.`Apellido2`,'')) AS `Persona`,case when `tm`.`estado` <> 'Justificado' then 1 else 0 end AS `Tardanzas`,case when `tm`.`estado` = 'Justificado' then 1 else 0 end AS `Tardanzas_Justificadas`,`tm`.`fecha_registro` AS `Fecha_Registro` from (`TardanzasManuales` `tm` join `Operarios` `o` on(`tm`.`cod_operario` = `o`.`CodOperario`)) order by `Fecha_Registro`  ;

-- --------------------------------------------------------

--
-- Structure for view `view_reporte_tardanzas_completo`
--
DROP TABLE IF EXISTS `view_reporte_tardanzas_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_reporte_tardanzas_completo`  AS SELECT coalesce(`tm`.`cod_contrato`,`c`.`CodContrato`) AS `Código`, concat(coalesce(`tm`.`cod_contrato`,`c`.`CodContrato`),' ',ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `Persona`, '' AS `Fecha_Pago`, CASE WHEN `tm`.`id` is not null THEN CASE WHEN `tm`.`estado` <> 'Justificado' THEN 1 ELSE 0 END ELSE 1 END AS `Tardanzas`, CASE WHEN `tm`.`id` is not null AND `tm`.`estado` = 'Justificado' THEN 1 ELSE 0 END AS `Tardanzas_Justificadas`, `m`.`CodOperario` AS `CodOperario`, concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `Nombre_Completo`, coalesce(`tm`.`cod_sucursal`,`m`.`sucursal_codigo`) AS `CodSucursal`, coalesce(`s_tm`.`nombre`,`s_m`.`nombre`,'Sin asignar') AS `Sucursal`, coalesce(`tm`.`fecha_tardanza`,`m`.`fecha`) AS `Fecha_Tardanza`, coalesce(`tm`.`estado`,'Automática') AS `Estado_Tardanza`, `tm`.`tipo_justificacion` AS `Tipo_Justificacion`, `tm`.`observaciones` AS `Observaciones`, `tm`.`fecha_registro` AS `Fecha_Registro`, `hso_horario`.`hora_entrada_programada` AS `Hora_Entrada_Programada`, `hso_horario`.`hora_salida_programada` AS `Hora_Salida_Programada`, `m`.`hora_ingreso` AS `Hora_Entrada_Marcada`, `m`.`hora_salida` AS `Hora_Salida_Marcada`, `hso_horario`.`minutos_tardanza` AS `Minutos_Tardanza_Calculados`, CASE WHEN `tm`.`id` is not null THEN 'Reportada' ELSE 'Automática' END AS `Tipo_Tardanza`, CASE WHEN `tm`.`id` is not null THEN concat('MANUAL_',`tm`.`id`) ELSE concat('AUTO_',`m`.`CodOperario`,'_',`m`.`fecha`,'_',`m`.`sucursal_codigo`) END AS `Id_Unico` FROM ((((((`marcaciones` `m` join `Operarios` `o` on(`m`.`CodOperario` = `o`.`CodOperario`)) left join `sucursales` `s_m` on(`m`.`sucursal_codigo` = `s_m`.`codigo`)) left join `Contratos` `c` on(`m`.`CodOperario` = `c`.`cod_operario` and (`c`.`fin_contrato` is null or `c`.`fin_contrato` >= curdate()))) left join (select `hso`.`cod_operario` AS `cod_operario`,`hso`.`cod_sucursal` AS `cod_sucursal`,`ss`.`fecha_inicio` AS `fecha_inicio`,`ss`.`fecha_fin` AS `fecha_fin`,case dayofweek(`m2`.`fecha`) when 2 then `hso`.`lunes_entrada` when 3 then `hso`.`martes_entrada` when 4 then `hso`.`miercoles_entrada` when 5 then `hso`.`jueves_entrada` when 6 then `hso`.`viernes_entrada` when 7 then `hso`.`sabado_entrada` when 1 then `hso`.`domingo_entrada` end AS `hora_entrada_programada`,case dayofweek(`m2`.`fecha`) when 2 then `hso`.`lunes_salida` when 3 then `hso`.`martes_salida` when 4 then `hso`.`miercoles_salida` when 5 then `hso`.`jueves_salida` when 6 then `hso`.`viernes_salida` when 7 then `hso`.`sabado_salida` when 1 then `hso`.`domingo_salida` end AS `hora_salida_programada`,timestampdiff(MINUTE,case dayofweek(`m2`.`fecha`) when 2 then `hso`.`lunes_entrada` when 3 then `hso`.`martes_entrada` when 4 then `hso`.`miercoles_entrada` when 5 then `hso`.`jueves_entrada` when 6 then `hso`.`viernes_entrada` when 7 then `hso`.`sabado_entrada` when 1 then `hso`.`domingo_entrada` end,`m2`.`hora_ingreso`) AS `minutos_tardanza`,`m2`.`fecha` AS `fecha` from ((`HorariosSemanalesOperaciones` `hso` join `SemanasSistema` `ss` on(`hso`.`id_semana_sistema` = `ss`.`id`)) join `marcaciones` `m2` on(`hso`.`cod_operario` = `m2`.`CodOperario` and `hso`.`cod_sucursal` = `m2`.`sucursal_codigo`)) where `m2`.`hora_ingreso` is not null) `hso_horario` on(`m`.`CodOperario` = `hso_horario`.`cod_operario` and `m`.`sucursal_codigo` = `hso_horario`.`cod_sucursal` and `m`.`fecha` = `hso_horario`.`fecha`)) left join `TardanzasManuales` `tm` on(`m`.`CodOperario` = `tm`.`cod_operario` and `m`.`fecha` = `tm`.`fecha_tardanza` and `m`.`sucursal_codigo` = `tm`.`cod_sucursal`)) left join `sucursales` `s_tm` on(`tm`.`cod_sucursal` = `s_tm`.`codigo`)) WHERE `m`.`hora_ingreso` is not null AND `hso_horario`.`minutos_tardanza` > 1 AND `hso_horario`.`hora_entrada_programada` is not nullunion allselect `tm`.`cod_contrato` AS `Código`,concat(`tm`.`cod_contrato`,' ',ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `Persona`,'' AS `Fecha_Pago`,case when `tm`.`estado` <> 'Justificado' then 1 else 0 end AS `Tardanzas`,case when `tm`.`estado` = 'Justificado' then 1 else 0 end AS `Tardanzas_Justificadas`,`tm`.`cod_operario` AS `CodOperario`,concat(ifnull(`o`.`Nombre`,''),' ',ifnull(`o`.`Nombre2`,''),' ',ifnull(`o`.`Apellido`,''),' ',ifnull(`o`.`Apellido2`,'')) AS `Nombre_Completo`,`tm`.`cod_sucursal` AS `CodSucursal`,`s`.`nombre` AS `Sucursal`,`tm`.`fecha_tardanza` AS `Fecha_Tardanza`,`tm`.`estado` AS `Estado_Tardanza`,`tm`.`tipo_justificacion` AS `Tipo_Justificacion`,`tm`.`observaciones` AS `Observaciones`,`tm`.`fecha_registro` AS `Fecha_Registro`,(select case dayofweek(`tm`.`fecha_tardanza`) when 2 then `hso`.`lunes_entrada` when 3 then `hso`.`martes_entrada` when 4 then `hso`.`miercoles_entrada` when 5 then `hso`.`jueves_entrada` when 6 then `hso`.`viernes_entrada` when 7 then `hso`.`sabado_entrada` when 1 then `hso`.`domingo_entrada` end from (`HorariosSemanalesOperaciones` `hso` join `SemanasSistema` `ss` on(`hso`.`id_semana_sistema` = `ss`.`id`)) where `hso`.`cod_operario` = `tm`.`cod_operario` and `hso`.`cod_sucursal` = `tm`.`cod_sucursal` and `tm`.`fecha_tardanza` between `ss`.`fecha_inicio` and `ss`.`fecha_fin` limit 1) AS `Hora_Entrada_Programada`,(select case dayofweek(`tm`.`fecha_tardanza`) when 2 then `hso`.`lunes_salida` when 3 then `hso`.`martes_salida` when 4 then `hso`.`miercoles_salida` when 5 then `hso`.`jueves_salida` when 6 then `hso`.`viernes_salida` when 7 then `hso`.`sabado_salida` when 1 then `hso`.`domingo_salida` end from (`HorariosSemanalesOperaciones` `hso` join `SemanasSistema` `ss` on(`hso`.`id_semana_sistema` = `ss`.`id`)) where `hso`.`cod_operario` = `tm`.`cod_operario` and `hso`.`cod_sucursal` = `tm`.`cod_sucursal` and `tm`.`fecha_tardanza` between `ss`.`fecha_inicio` and `ss`.`fecha_fin` limit 1) AS `Hora_Salida_Programada`,(select `m`.`hora_ingreso` from `marcaciones` `m` where `m`.`CodOperario` = `tm`.`cod_operario` and `m`.`fecha` = `tm`.`fecha_tardanza` and `m`.`sucursal_codigo` = `tm`.`cod_sucursal` limit 1) AS `Hora_Entrada_Marcada`,(select `m`.`hora_salida` from `marcaciones` `m` where `m`.`CodOperario` = `tm`.`cod_operario` and `m`.`fecha` = `tm`.`fecha_tardanza` and `m`.`sucursal_codigo` = `tm`.`cod_sucursal` limit 1) AS `Hora_Salida_Marcada`,timestampdiff(MINUTE,(select case dayofweek(`tm`.`fecha_tardanza`) when 2 then `hso2`.`lunes_entrada` when 3 then `hso2`.`martes_entrada` when 4 then `hso2`.`miercoles_entrada` when 5 then `hso2`.`jueves_entrada` when 6 then `hso2`.`viernes_entrada` when 7 then `hso2`.`sabado_entrada` when 1 then `hso2`.`domingo_entrada` end from (`HorariosSemanalesOperaciones` `hso2` join `SemanasSistema` `ss2` on(`hso2`.`id_semana_sistema` = `ss2`.`id`)) where `hso2`.`cod_operario` = `tm`.`cod_operario` and `hso2`.`cod_sucursal` = `tm`.`cod_sucursal` and `tm`.`fecha_tardanza` between `ss2`.`fecha_inicio` and `ss2`.`fecha_fin` limit 1),(select `m2`.`hora_ingreso` from `marcaciones` `m2` where `m2`.`CodOperario` = `tm`.`cod_operario` and `m2`.`fecha` = `tm`.`fecha_tardanza` and `m2`.`sucursal_codigo` = `tm`.`cod_sucursal` limit 1)) AS `Minutos_Tardanza_Calculados`,'Reportada' AS `Tipo_Tardanza`,concat('MANUAL_',`tm`.`id`) AS `Id_Unico` from ((`TardanzasManuales` `tm` join `Operarios` `o` on(`tm`.`cod_operario` = `o`.`CodOperario`)) left join `sucursales` `s` on(`tm`.`cod_sucursal` = `s`.`codigo`)) order by `Fecha_Tardanza`  ;

-- --------------------------------------------------------

--
-- Structure for view `view_vacaciones`
--
DROP TABLE IF EXISTS `view_vacaciones`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_vacaciones`  AS SELECT coalesce(`fm`.`cod_contrato`,'') AS `codigo_contrato`, concat(coalesce(`fm`.`cod_contrato`,''),' ',trim(concat_ws(' ',nullif(trim(`o`.`Nombre`),''),nullif(trim(`o`.`Nombre2`),''),nullif(trim(`o`.`Apellido`),''),nullif(trim(`o`.`Apellido2`),'')))) AS `persona`, `s`.`nombre` AS `sucursal`, `fm`.`fecha_falta` AS `fecha_inicio`, `fm`.`fecha_falta` AS `fecha_fin`, 1 AS `dias`, coalesce(`fm`.`observaciones_rrhh`,'Sin comentarios por rrhh') AS `observaciones`, 'Descansadas' AS `tipo`, cast(coalesce(`fm`.`fecha_registro`,`fm`.`fecha_falta`) - interval 6 hour as date) AS `fecha_registro` FROM ((`faltas_manual` `fm` join `Operarios` `o` on(`fm`.`cod_operario` = `o`.`CodOperario`)) join `sucursales` `s` on(`fm`.`cod_sucursal` = `s`.`codigo`)) WHERE `fm`.`tipo_falta` = 'Vacaciones' ;

-- --------------------------------------------------------

--
-- Structure for view `view_viaticos_exportar`
--
DROP TABLE IF EXISTS `view_viaticos_exportar`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `view_viaticos_exportar`  AS SELECT `v`.`cod_contrato` AS `Codigo`, concat(coalesce(`v`.`cod_contrato`,''),' ',trim(concat(coalesce(`o`.`Nombre`,''),' ',coalesce(`o`.`Nombre2`,''),' ',coalesce(`o`.`Apellido`,''),' ',coalesce(`o`.`Apellido2`,'')))) AS `Persona`, `s`.`nombre` AS `Sucursal`, NULL AS `Fecha_de_Pago`, 1 AS `Total_Turnos`, `v`.`cantidad` AS `Total_a_recibir`, `v`.`fecha` AS `Fecha_Viatico`, CASE WHEN `v`.`tipo` = 'Diurno' THEN (select `m`.`hora_ingreso` from `marcaciones` `m` where `m`.`CodOperario` = `v`.`cod_operario` and `m`.`fecha` = `v`.`fecha` and `m`.`sucursal_codigo` = `v`.`sucursal_codigo` limit 1) ELSE (select `m`.`hora_salida` from `marcaciones` `m` where `m`.`CodOperario` = `v`.`cod_operario` and `m`.`fecha` = `v`.`fecha` and `m`.`sucursal_codigo` = `v`.`sucursal_codigo` limit 1) END AS `Hora_Marcacion`, CASE WHEN `v`.`tipo` = 'Diurno' THEN 'Entrada' ELSE 'Salida' END AS `Tipo_Hora_Mostrada`, `v`.`tipo` AS `Tipo_Viatico` FROM ((`viaticos` `v` join `Operarios` `o` on(`v`.`cod_operario` = `o`.`CodOperario`)) join `sucursales` `s` on(`v`.`sucursal_codigo` = `s`.`codigo`)) WHERE `v`.`tipo` in ('Nocturno','Diurno') AND `s`.`codigo` in (7,9,10,11,12,13,16,19) ORDER BY `v`.`fecha` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `VistaOperariosContratos`
--
DROP TABLE IF EXISTS `VistaOperariosContratos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u839374897_erp`@`127.0.0.1` SQL SECURITY DEFINER VIEW `VistaOperariosContratos`  AS SELECT concat_ws(' ',`c`.`CodContrato`,nullif(trim(`o`.`Nombre`),''),nullif(trim(`o`.`Nombre2`),''),nullif(trim(`o`.`Apellido`),''),nullif(trim(`o`.`Apellido2`),'')) AS `codigo_nombre`, `c`.`CodContrato` AS `CodOperario`, concat_ws(' ',nullif(trim(`o`.`Nombre`),''),nullif(trim(`o`.`Nombre2`),''),nullif(trim(`o`.`Apellido`),''),nullif(trim(`o`.`Apellido2`),'')) AS `Nombre y Apellido`, `o`.`clave_hash` AS `clave_hash`, `o`.`clave` AS `clave`, `o`.`Operativo` AS `Operativo`, `o`.`Celular` AS `Celular`, `o`.`Genero` AS `Genero`, `o`.`Cedula` AS `Cedula`, `o`.`Inicio` AS `Inicio`, `o`.`Fin` AS `Fin`, `o`.`Cumpleanos` AS `Cumpleanos`, `o`.`Sucursal` AS `Sucursal`, `o`.`Ciudad` AS `Ciudad`, `o`.`CodClub` AS `CodClub`, `o`.`Cargo` AS `Cargo`, `o`.`FechaRegistro` AS `FechaRegistro`, `o`.`usuario` AS `usuario`, `o`.`cb_numero` AS `cb_numero`, `o`.`contacto_numero` AS `contacto_numero`, `o`.`contacto_nombre` AS `contacto_nombre`, `o`.`direccion` AS `direccion`, `o`.`telefono_casa` AS `telefono_casa`, `o`.`telefono_corporativo` AS `telefono_corporativo`, `o`.`email_personal` AS `email_personal`, `o`.`email_trabajo` AS `email_trabajo`, `o`.`foto_perfil` AS `foto_perfil`, `o`.`codigo_inss` AS `codigo_inss`, `o`.`cb_titular` AS `cb_titular`, `o`.`cb_banco` AS `cb_banco`, `o`.`cb_moneda` AS `cb_moneda`, `o`.`segurosocial` AS `segurosocial`, `o`.`InicioSeguro` AS `InicioSeguro`, `o`.`FinSeguro` AS `FinSeguro`, `o`.`registrado_por` AS `registrado_por`, `o`.`fecha_hora_regsys` AS `fecha_hora_regsys`, `o`.`hospital_riesgo_laboral` AS `hospital_riesgo_laboral`, `o`.`FechaCreacion` AS `FechaCreacion`, `o`.`CodOperario` AS `CodigoOperarioRaiz`, `c`.`fecha_salida` AS `FechaSalidaContrato`, `c`.`inicio_contrato` AS `FechaInicioContrato`, `cb`.`numero_cuenta` AS `numero_cuenta`, `cb`.`titular` AS `titular`, concat_ws(' ',nullif(trim(`cb`.`banco`),''),nullif(trim(`cb`.`moneda`),'')) AS `banco_moneda` FROM ((`Operarios` `o` left join `Contratos` `c` on(`o`.`CodOperario` = `c`.`cod_operario`)) left join `CuentaBancaria` `cb` on(`o`.`CodOperario` = `cb`.`cod_operario` and `cb`.`id` = (select `cb2`.`id` from `CuentaBancaria` `cb2` where `cb2`.`cod_operario` = `o`.`CodOperario` order by `cb2`.`desde` desc,`cb2`.`fecha_hora_reg_sys` desc limit 1))) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acciones_tools_erp`
--
ALTER TABLE `acciones_tools_erp`
  ADD CONSTRAINT `fk_acciones_tools_erp` FOREIGN KEY (`tool_erp_id`) REFERENCES `tools_erp` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `Adelantos`
--
ALTER TABLE `Adelantos`
  ADD CONSTRAINT `Adelantos_ibfk_1` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_operarios` FOREIGN KEY (`created_by`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `announcement_branches`
--
ALTER TABLE `announcement_branches`
  ADD CONSTRAINT `fk_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sucursal` FOREIGN KEY (`branch_id`) REFERENCES `sucursales` (`codigo`) ON DELETE CASCADE;

--
-- Constraints for table `ArchivosAdjuntos`
--
ALTER TABLE `ArchivosAdjuntos`
  ADD CONSTRAINT `ArchivosAdjuntos_ibfk_1` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `ArchivosAdjuntos_ibfk_2` FOREIGN KEY (`cod_usuario_subio`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `archivos_presentacion_producto`
--
ALTER TABLE `archivos_presentacion_producto`
  ADD CONSTRAINT `fk_archivo_producto` FOREIGN KEY (`id_presentacion_producto`) REFERENCES `producto_presentacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_archivo_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_archivo_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `AsignacionNivelesCargos`
--
ALTER TABLE `AsignacionNivelesCargos`
  ADD CONSTRAINT `AsignacionNivelesCargos_ibfk_1` FOREIGN KEY (`usuario_ultima_modificacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `AsignacionNivelesCargos_ibfk_2` FOREIGN KEY (`cod_usuario_creador`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_asignacion_nivelcargo` FOREIGN KEY (`CodNivelesCargos`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  ADD CONSTRAINT `fk_asignacion_operario` FOREIGN KEY (`CodOperario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auditoria_caja_chica`
--
ALTER TABLE `auditoria_caja_chica`
  ADD CONSTRAINT `fk_auditoria_caja_chica_operarios` FOREIGN KEY (`lider_tienda_codigo`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_auditoria_caja_chica_sucursales` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `auditoria_caja_chica_detalle`
--
ALTER TABLE `auditoria_caja_chica_detalle`
  ADD CONSTRAINT `auditoria_caja_chica_detalle_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditoria_caja_chica` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auditoria_facturacion`
--
ALTER TABLE `auditoria_facturacion`
  ADD CONSTRAINT `fk_auditoria_operario` FOREIGN KEY (`cajero`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_auditoria_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `auditoria_fotos`
--
ALTER TABLE `auditoria_fotos`
  ADD CONSTRAINT `auditoria_fotos_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditoria` (`id`);

--
-- Constraints for table `auditoria_inventario`
--
ALTER TABLE `auditoria_inventario`
  ADD CONSTRAINT `fk_auditoria_inventario_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `auditoria_inventario_detalle`
--
ALTER TABLE `auditoria_inventario_detalle`
  ADD CONSTRAINT `auditoria_inventario_detalle_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditoria_inventario` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auditoria_inventario_operarios`
--
ALTER TABLE `auditoria_inventario_operarios`
  ADD CONSTRAINT `auditoria_inventario_operarios_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditoria_inventario` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auditoria_personal_fotos`
--
ALTER TABLE `auditoria_personal_fotos`
  ADD CONSTRAINT `auditoria_personal_fotos_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditoria_personal` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auditoria_servicio_fotos`
--
ALTER TABLE `auditoria_servicio_fotos`
  ADD CONSTRAINT `auditoria_servicio_fotos_ibfk_1` FOREIGN KEY (`auditoria_id`) REFERENCES `auditoria_servicio` (`id`);

--
-- Constraints for table `BitacoraColaborador`
--
ALTER TABLE `BitacoraColaborador`
  ADD CONSTRAINT `BitacoraColaborador_ibfk_1` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `BitacoraColaborador_ibfk_2` FOREIGN KEY (`cod_usuario_registro`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `BoletaAguinaldo`
--
ALTER TABLE `BoletaAguinaldo`
  ADD CONSTRAINT `BoletaAguinaldo_ibfk_2` FOREIGN KEY (`cod_contrato`) REFERENCES `Contratos` (`CodContrato`) ON DELETE CASCADE,
  ADD CONSTRAINT `BoletaAguinaldo_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `caja_chica_sucursales`
--
ALTER TABLE `caja_chica_sucursales`
  ADD CONSTRAINT `fk_caja_chica_sucursales_sucursales` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `categoria_producto_maestro`
--
ALTER TABLE `categoria_producto_maestro`
  ADD CONSTRAINT `fk_categoria_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_categoria_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `componentes_receta_producto`
--
ALTER TABLE `componentes_receta_producto`
  ADD CONSTRAINT `fk_componente_producto` FOREIGN KEY (`id_presentacion_producto`) REFERENCES `producto_presentacion` (`id`),
  ADD CONSTRAINT `fk_componente_receta` FOREIGN KEY (`id_receta_producto_global`) REFERENCES `receta_producto_global` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_componente_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_componente_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `compra_local_configuracion_despacho`
--
ALTER TABLE `compra_local_configuracion_despacho`
  ADD CONSTRAINT `compra_local_configuracion_despacho_ibfk_1` FOREIGN KEY (`id_producto_presentacion`) REFERENCES `producto_presentacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compra_local_configuracion_despacho_ibfk_2` FOREIGN KEY (`codigo_sucursal`) REFERENCES `sucursales` (`codigo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `compra_local_configuracion_despacho_ibfk_3` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL,
  ADD CONSTRAINT `compra_local_configuracion_despacho_ibfk_4` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_config_perfil` FOREIGN KEY (`id_perfil`) REFERENCES `compra_local_perfiles_despacho` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `compra_local_pedidos_historico`
--
ALTER TABLE `compra_local_pedidos_historico`
  ADD CONSTRAINT `compra_local_pedidos_historico_ibfk_1` FOREIGN KEY (`id_producto_presentacion`) REFERENCES `producto_presentacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compra_local_pedidos_historico_ibfk_2` FOREIGN KEY (`codigo_sucursal`) REFERENCES `sucursales` (`codigo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `compra_local_pedidos_historico_ibfk_3` FOREIGN KEY (`usuario_registro`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL;

--
-- Constraints for table `ContactosEmergencia`
--
ALTER TABLE `ContactosEmergencia`
  ADD CONSTRAINT `ContactosEmergencia_ibfk_1` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`) ON DELETE CASCADE;

--
-- Constraints for table `contacto_proveedores`
--
ALTER TABLE `contacto_proveedores`
  ADD CONSTRAINT `contacto_proveedores_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacto_proveedores_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL;

--
-- Constraints for table `Contratos`
--
ALTER TABLE `Contratos`
  ADD CONSTRAINT `Contratos_ibfk_2` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `Contratos_ibfk_4` FOREIGN KEY (`usuario_ultima_modificacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_cod_usuario_creador` FOREIGN KEY (`cod_usuario_creador`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `conversion_unidad_producto`
--
ALTER TABLE `conversion_unidad_producto`
  ADD CONSTRAINT `fk_conversion_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_conversion_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_conversion_unidad_final` FOREIGN KEY (`id_unidad_producto_final`) REFERENCES `unidad_producto` (`id`),
  ADD CONSTRAINT `fk_conversion_unidad_inicio` FOREIGN KEY (`id_unidad_producto_inicio`) REFERENCES `unidad_producto` (`id`);

--
-- Constraints for table `CuentaBancaria`
--
ALTER TABLE `CuentaBancaria`
  ADD CONSTRAINT `CuentaBancaria_ibfk_1` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `cuenta_proveedor`
--
ALTER TABLE `cuenta_proveedor`
  ADD CONSTRAINT `cuenta_proveedor_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cuenta_proveedor_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL;

--
-- Constraints for table `deducciones_operaciones`
--
ALTER TABLE `deducciones_operaciones`
  ADD CONSTRAINT `deducciones_operaciones_ibfk_1` FOREIGN KEY (`operario_id`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `deducciones_operaciones_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `diccionario_productos_legado`
--
ALTER TABLE `diccionario_productos_legado`
  ADD CONSTRAINT `fk_dic_presentacion` FOREIGN KEY (`id_producto_presentacion`) REFERENCES `producto_presentacion` (`id`),
  ADD CONSTRAINT `fk_dic_usuario` FOREIGN KEY (`usuario_mapeo`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_dic_variedad` FOREIGN KEY (`id_variedad_producto`) REFERENCES `variedad_producto_presentacion` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `entrevistas_candidatos`
--
ALTER TABLE `entrevistas_candidatos`
  ADD CONSTRAINT `fk_entrevistas_postulacion` FOREIGN KEY (`id_postulacion`) REFERENCES `postulacion_plaza` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faltante_inventario`
--
ALTER TABLE `faltante_inventario`
  ADD CONSTRAINT `fk_faltante_inventario_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `faltante_inventario_detalle`
--
ALTER TABLE `faltante_inventario_detalle`
  ADD CONSTRAINT `faltante_inventario_detalle_ibfk_1` FOREIGN KEY (`faltante_id`) REFERENCES `faltante_inventario` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `faltante_inventario_operarios`
--
ALTER TABLE `faltante_inventario_operarios`
  ADD CONSTRAINT `fk_faltante_operario` FOREIGN KEY (`operario_id`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_faltante_operario_faltante` FOREIGN KEY (`faltante_id`) REFERENCES `faltante_inventario` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `Faltas`
--
ALTER TABLE `Faltas`
  ADD CONSTRAINT `fk_faltas_horario` FOREIGN KEY (`id_horario_programado`) REFERENCES `HorariosSemanalesOperaciones` (`id`),
  ADD CONSTRAINT `fk_faltas_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_faltas_sucursal` FOREIGN KEY (`cod_sucursal`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `faltas_manual`
--
ALTER TABLE `faltas_manual`
  ADD CONSTRAINT `fk_faltas_manual_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_faltas_manual_operario_actualizador` FOREIGN KEY (`actualizado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_faltas_manual_operario_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_faltas_manual_sucursal` FOREIGN KEY (`cod_sucursal`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `feriadosnic`
--
ALTER TABLE `feriadosnic`
  ADD CONSTRAINT `fk_feriadosnic_departamento` FOREIGN KEY (`departamento_codigo`) REFERENCES `departamentos` (`codigo`);

--
-- Constraints for table `FeriadosStatus`
--
ALTER TABLE `FeriadosStatus`
  ADD CONSTRAINT `FeriadosStatus_ibfk_1` FOREIGN KEY (`id_marcacion`) REFERENCES `marcaciones` (`id`),
  ADD CONSTRAINT `FeriadosStatus_ibfk_2` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `FeriadosStatus_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `FeriadosStatus_ibfk_4` FOREIGN KEY (`actualizado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `fichatecnica_presentacion_producto`
--
ALTER TABLE `fichatecnica_presentacion_producto`
  ADD CONSTRAINT `fk_ficha_producto` FOREIGN KEY (`id_presentacion_producto`) REFERENCES `producto_presentacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ficha_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_ficha_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `formato_salida`
--
ALTER TABLE `formato_salida`
  ADD CONSTRAINT `fk_formato_salida_contrato` FOREIGN KEY (`cod_contrato`) REFERENCES `Contratos` (`CodContrato`) ON DELETE CASCADE;

--
-- Constraints for table `fotos_presentacion_producto`
--
ALTER TABLE `fotos_presentacion_producto`
  ADD CONSTRAINT `fk_foto_producto` FOREIGN KEY (`id_presentacion_producto`) REFERENCES `producto_presentacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_foto_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_foto_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `gestion_proyectos_proyectos`
--
ALTER TABLE `gestion_proyectos_proyectos`
  ADD CONSTRAINT `gestion_proyectos_proyectos_ibfk_1` FOREIGN KEY (`CodNivelesCargos`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  ADD CONSTRAINT `gestion_proyectos_proyectos_ibfk_2` FOREIGN KEY (`proyecto_padre_id`) REFERENCES `gestion_proyectos_proyectos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gestion_proyectos_proyectos_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `gestion_proyectos_proyectos_ibfk_4` FOREIGN KEY (`modificado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `gestion_tareas_reuniones_archivos`
--
ALTER TABLE `gestion_tareas_reuniones_archivos`
  ADD CONSTRAINT `fk_gtr_archivos_comentario` FOREIGN KEY (`id_comentario`) REFERENCES `gestion_tareas_reuniones_comentarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gtr_archivos_item` FOREIGN KEY (`id_item`) REFERENCES `gestion_tareas_reuniones_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gtr_archivos_operario` FOREIGN KEY (`cod_operario_subio`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `gestion_tareas_reuniones_comentarios`
--
ALTER TABLE `gestion_tareas_reuniones_comentarios`
  ADD CONSTRAINT `fk_gtr_comentarios_item` FOREIGN KEY (`id_item`) REFERENCES `gestion_tareas_reuniones_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gtr_comentarios_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `gestion_tareas_reuniones_items`
--
ALTER TABLE `gestion_tareas_reuniones_items`
  ADD CONSTRAINT `fk_gtr_items_cargo_asignado` FOREIGN KEY (`cod_cargo_asignado`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  ADD CONSTRAINT `fk_gtr_items_cargo_creador` FOREIGN KEY (`cod_cargo_creador`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  ADD CONSTRAINT `fk_gtr_items_operario_creador` FOREIGN KEY (`cod_operario_creador`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_gtr_items_operario_modificacion` FOREIGN KEY (`cod_operario_ultima_modificacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_gtr_items_padre` FOREIGN KEY (`id_padre`) REFERENCES `gestion_tareas_reuniones_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gestion_tareas_reuniones_participantes`
--
ALTER TABLE `gestion_tareas_reuniones_participantes`
  ADD CONSTRAINT `fk_gtr_participantes_cargo` FOREIGN KEY (`cod_cargo`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  ADD CONSTRAINT `fk_gtr_participantes_item` FOREIGN KEY (`id_item`) REFERENCES `gestion_tareas_reuniones_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gtr_participantes_operario` FOREIGN KEY (`cod_operario_confirmo`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `grupo_presentacion_producto`
--
ALTER TABLE `grupo_presentacion_producto`
  ADD CONSTRAINT `fk_grupo_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_grupo_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `historial_estados_personal`
--
ALTER TABLE `historial_estados_personal`
  ADD CONSTRAINT `historial_estados_personal_ibfk_1` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `historial_proveedores`
--
ALTER TABLE `historial_proveedores`
  ADD CONSTRAINT `historial_proveedores_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_proveedores_ibfk_2` FOREIGN KEY (`usuario_cambio`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL;

--
-- Constraints for table `HorariosSemanales`
--
ALTER TABLE `HorariosSemanales`
  ADD CONSTRAINT `fk_cod_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `HorariosSemanalesOperaciones`
--
ALTER TABLE `HorariosSemanalesOperaciones`
  ADD CONSTRAINT `fk_horarios_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_horarios_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_horarios_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_horarios_semana` FOREIGN KEY (`id_semana_sistema`) REFERENCES `SemanasSistema` (`numero_semana`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_horarios_sucursal` FOREIGN KEY (`cod_sucursal`) REFERENCES `sucursales` (`codigo`) ON DELETE CASCADE;

--
-- Constraints for table `HorasExtraStatus`
--
ALTER TABLE `HorasExtraStatus`
  ADD CONSTRAINT `fk_horasextra_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `horas_extras_manual`
--
ALTER TABLE `horas_extras_manual`
  ADD CONSTRAINT `fk_hem_actualizador` FOREIGN KEY (`actualizado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_hem_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_hem_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `ia_graficos_favoritos`
--
ALTER TABLE `ia_graficos_favoritos`
  ADD CONSTRAINT `fk_favoritos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `Operarios` (`CodOperario`) ON DELETE CASCADE;

--
-- Constraints for table `IndicadoresSemanalesResultados`
--
ALTER TABLE `IndicadoresSemanalesResultados`
  ADD CONSTRAINT `IndicadoresSemanalesResultados_ibfk_1` FOREIGN KEY (`id_indicador`) REFERENCES `IndicadoresSemanales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `IndicadoresSemanalesResultados_ibfk_2` FOREIGN KEY (`usuario_registra`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL,
  ADD CONSTRAINT `IndicadoresSemanalesResultados_ibfk_3` FOREIGN KEY (`usuario_modifica`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL;

--
-- Constraints for table `kpi_reclamos`
--
ALTER TABLE `kpi_reclamos`
  ADD CONSTRAINT `fk_kpi_reclamos_operario` FOREIGN KEY (`cod_operario_actualizacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `marcaciones`
--
ALTER TABLE `marcaciones`
  ADD CONSTRAINT `fk_marcaciones_numero_semana` FOREIGN KEY (`numero_semana`) REFERENCES `SemanasSistema` (`numero_semana`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_marcaciones_operario` FOREIGN KEY (`CodOperario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_marcaciones_sucursal` FOREIGN KEY (`sucursal_codigo`) REFERENCES `sucursales` (`codigo`) ON DELETE SET NULL;

--
-- Constraints for table `mtto_equipos`
--
ALTER TABLE `mtto_equipos`
  ADD CONSTRAINT `fk_equipos_operario` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_equipos_proveedor` FOREIGN KEY (`proveedor_compra_id`) REFERENCES `proveedores_compras_servicios` (`id`),
  ADD CONSTRAINT `fk_equipos_tipo` FOREIGN KEY (`tipo_equipo_id`) REFERENCES `mtto_equipos_tipos` (`id`);

--
-- Constraints for table `mtto_equipos_mantenimientos`
--
ALTER TABLE `mtto_equipos_mantenimientos`
  ADD CONSTRAINT `fk_mantenimientos_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `mtto_equipos` (`id`),
  ADD CONSTRAINT `fk_mantenimientos_operario` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_mantenimientos_programado` FOREIGN KEY (`mantenimiento_programado_id`) REFERENCES `mtto_equipos_mantenimientos_programados` (`id`),
  ADD CONSTRAINT `fk_mantenimientos_proveedor` FOREIGN KEY (`proveedor_servicio_id`) REFERENCES `proveedores_compras_servicios` (`id`),
  ADD CONSTRAINT `fk_mantenimientos_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `mtto_equipos_solicitudes` (`id`);

--
-- Constraints for table `mtto_equipos_mantenimientos_fotos`
--
ALTER TABLE `mtto_equipos_mantenimientos_fotos`
  ADD CONSTRAINT `fk_mantenimientos_fotos` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mtto_equipos_mantenimientos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_equipos_mantenimientos_programados`
--
ALTER TABLE `mtto_equipos_mantenimientos_programados`
  ADD CONSTRAINT `fk_programados_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `mtto_equipos` (`id`),
  ADD CONSTRAINT `fk_programados_operario` FOREIGN KEY (`programado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `mtto_equipos_mantenimientos_repuestos`
--
ALTER TABLE `mtto_equipos_mantenimientos_repuestos`
  ADD CONSTRAINT `fk_mtto_repuestos_mantenimiento` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mtto_equipos_mantenimientos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mtto_repuestos_repuesto` FOREIGN KEY (`repuesto_id`) REFERENCES `mtto_equipos_repuestos` (`id`);

--
-- Constraints for table `mtto_equipos_movimientos`
--
ALTER TABLE `mtto_equipos_movimientos`
  ADD CONSTRAINT `fk_movimientos_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `mtto_equipos` (`id`),
  ADD CONSTRAINT `fk_movimientos_finalizado` FOREIGN KEY (`finalizado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_movimientos_programado` FOREIGN KEY (`programado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `mtto_equipos_solicitudes`
--
ALTER TABLE `mtto_equipos_solicitudes`
  ADD CONSTRAINT `fk_solicitudes_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `mtto_equipos` (`id`),
  ADD CONSTRAINT `fk_solicitudes_finalizador` FOREIGN KEY (`finalizado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_solicitudes_solicitante` FOREIGN KEY (`solicitado_por`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `mtto_equipos_solicitudes_fotos`
--
ALTER TABLE `mtto_equipos_solicitudes_fotos`
  ADD CONSTRAINT `fk_solicitudes_fotos` FOREIGN KEY (`solicitud_id`) REFERENCES `mtto_equipos_solicitudes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_informes_diarios`
--
ALTER TABLE `mtto_informes_diarios`
  ADD CONSTRAINT `fk_mtto_informes_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `mtto_informe_compras`
--
ALTER TABLE `mtto_informe_compras`
  ADD CONSTRAINT `fk_mtto_compras_visita` FOREIGN KEY (`visita_id`) REFERENCES `mtto_informe_visitas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_informe_tareas`
--
ALTER TABLE `mtto_informe_tareas`
  ADD CONSTRAINT `fk_mtto_tareas_visita` FOREIGN KEY (`visita_id`) REFERENCES `mtto_informe_visitas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_informe_tareas_fotos`
--
ALTER TABLE `mtto_informe_tareas_fotos`
  ADD CONSTRAINT `fk_mtto_tareas_fotos_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `mtto_informe_tareas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_informe_visitas`
--
ALTER TABLE `mtto_informe_visitas`
  ADD CONSTRAINT `fk_mtto_visitas_informe` FOREIGN KEY (`informe_id`) REFERENCES `mtto_informes_diarios` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_tickets`
--
ALTER TABLE `mtto_tickets`
  ADD CONSTRAINT `fk_cod_operario_2` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `mtto_tickets_ibfk_1` FOREIGN KEY (`tipo_caso_id`) REFERENCES `mtto_tipos_casos` (`id`);

--
-- Constraints for table `mtto_tickets_colaboradores`
--
ALTER TABLE `mtto_tickets_colaboradores`
  ADD CONSTRAINT `mtto_tickets_colaboradores_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `mtto_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mtto_tickets_colaboradores_ibfk_2` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_tickets_fotos`
--
ALTER TABLE `mtto_tickets_fotos`
  ADD CONSTRAINT `mtto_tickets_fotos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `mtto_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_tickets_fotos_finalizacion`
--
ALTER TABLE `mtto_tickets_fotos_finalizacion`
  ADD CONSTRAINT `mtto_tickets_fotos_finalizacion_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `mtto_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mtto_tickets_materiales`
--
ALTER TABLE `mtto_tickets_materiales`
  ADD CONSTRAINT `mtto_tickets_materiales_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `mtto_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mtto_tickets_materiales_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `mtto_materiales_frecuentes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `Operarios`
--
ALTER TABLE `Operarios`
  ADD CONSTRAINT `fk_operarios_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `Operarios` (`CodOperario`) ON UPDATE CASCADE;

--
-- Constraints for table `OperariosCategorias`
--
ALTER TABLE `OperariosCategorias`
  ADD CONSTRAINT `FK_OperarioCategoria_Operario` FOREIGN KEY (`CodOperario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `PagoAdelantos`
--
ALTER TABLE `PagoAdelantos`
  ADD CONSTRAINT `PagoAdelantos_ibfk_1` FOREIGN KEY (`cod_adelanto`) REFERENCES `Adelantos` (`CodAdelanto`);

--
-- Constraints for table `permisos_tools_erp`
--
ALTER TABLE `permisos_tools_erp`
  ADD CONSTRAINT `fk_accion_tool_erp` FOREIGN KEY (`accion_tool_erp_id`) REFERENCES `acciones_tools_erp` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `personal_auditorias`
--
ALTER TABLE `personal_auditorias`
  ADD CONSTRAINT `personal_auditorias_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Constraints for table `plazas_cargos`
--
ALTER TABLE `plazas_cargos`
  ADD CONSTRAINT `plazas_cargos_ibfk_1` FOREIGN KEY (`cargo`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  ADD CONSTRAINT `plazas_cargos_ibfk_3` FOREIGN KEY (`usuario_registra`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `plazas_cargos_ibfk_4` FOREIGN KEY (`usuario_modifica`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `plazas_cargos_ibfk_sucursal_codigo` FOREIGN KEY (`sucursal`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `postulacion_entrevista_telefonica`
--
ALTER TABLE `postulacion_entrevista_telefonica`
  ADD CONSTRAINT `postulacion_entrevista_telefonica_ibfk_1` FOREIGN KEY (`id_postulacion`) REFERENCES `postulacion_plaza` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `postulacion_evaluacion_jefe`
--
ALTER TABLE `postulacion_evaluacion_jefe`
  ADD CONSTRAINT `postulacion_evaluacion_jefe_ibfk_1` FOREIGN KEY (`id_postulacion`) REFERENCES `postulacion_plaza` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `postulacion_evaluacion_rh`
--
ALTER TABLE `postulacion_evaluacion_rh`
  ADD CONSTRAINT `postulacion_evaluacion_rh_ibfk_1` FOREIGN KEY (`id_postulacion`) REFERENCES `postulacion_plaza` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `postulacion_plaza`
--
ALTER TABLE `postulacion_plaza`
  ADD CONSTRAINT `postulacion_plaza_ibfk_1` FOREIGN KEY (`cargo_aplicado`) REFERENCES `NivelesCargos` (`CodNivelesCargos`) ON UPDATE CASCADE,
  ADD CONSTRAINT `postulacion_plaza_ibfk_2` FOREIGN KEY (`sucursal_aplicada`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pos_caja_inicial_detalle`
--
ALTER TABLE `pos_caja_inicial_detalle`
  ADD CONSTRAINT `fk_caja_inicial_detalle` FOREIGN KEY (`caja_inicial_id`) REFERENCES `pos_caja_inicial` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `productos_delivery`
--
ALTER TABLE `productos_delivery`
  ADD CONSTRAINT `productos_delivery_ibfk_1` FOREIGN KEY (`subgrupo_id`) REFERENCES `subgrupos_productos` (`id`);

--
-- Constraints for table `producto_maestro`
--
ALTER TABLE `producto_maestro`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`Id_categoria`) REFERENCES `categoria_producto_maestro` (`id`),
  ADD CONSTRAINT `fk_producto_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_producto_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `producto_presentacion`
--
ALTER TABLE `producto_presentacion`
  ADD CONSTRAINT `fk_pp_producto_maestro` FOREIGN KEY (`id_producto_maestro`) REFERENCES `producto_maestro` (`id`),
  ADD CONSTRAINT `fk_pp_unidad_producto` FOREIGN KEY (`id_unidad_producto`) REFERENCES `unidad_producto` (`id`),
  ADD CONSTRAINT `fk_pp_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_pp_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_producto_presentacion_maestro` FOREIGN KEY (`id_producto_maestro`) REFERENCES `producto_maestro` (`id`),
  ADD CONSTRAINT `fk_producto_presentacion_receta` FOREIGN KEY (`Id_receta_producto`) REFERENCES `receta_producto_global` (`id`),
  ADD CONSTRAINT `fk_producto_presentacion_subgrupo` FOREIGN KEY (`id_subgrupo_presentacion_producto`) REFERENCES `subgrupo_presentacion_producto` (`id`),
  ADD CONSTRAINT `fk_producto_presentacion_unidad` FOREIGN KEY (`id_unidad_producto`) REFERENCES `unidad_producto` (`id`),
  ADD CONSTRAINT `fk_producto_presentacion_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_producto_presentacion_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `promociones`
--
ALTER TABLE `promociones`
  ADD CONSTRAINT `promociones_ibfk_1` FOREIGN KEY (`tipo_id`) REFERENCES `promociones_tipos` (`id`),
  ADD CONSTRAINT `promociones_ibfk_2` FOREIGN KEY (`cliente_tipo_id`) REFERENCES `promociones_clientes_tipos` (`id`),
  ADD CONSTRAINT `promociones_ibfk_3` FOREIGN KEY (`aplicacion_id`) REFERENCES `promociones_aplicacion` (`id`),
  ADD CONSTRAINT `promociones_ibfk_4` FOREIGN KEY (`grupo_id`) REFERENCES `promociones_grupos` (`id`);

--
-- Constraints for table `promociones_productos`
--
ALTER TABLE `promociones_productos`
  ADD CONSTRAINT `promociones_productos_ibfk_1` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`),
  ADD CONSTRAINT `promociones_productos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos_delivery` (`id`),
  ADD CONSTRAINT `promociones_productos_ibfk_3` FOREIGN KEY (`subgrupo_id`) REFERENCES `subgrupos_productos` (`id`),
  ADD CONSTRAINT `promociones_productos_ibfk_4` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_productos` (`id`);

--
-- Constraints for table `promociones_requisitos`
--
ALTER TABLE `promociones_requisitos`
  ADD CONSTRAINT `promociones_requisitos_ibfk_1` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`),
  ADD CONSTRAINT `promociones_requisitos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos_delivery` (`id`),
  ADD CONSTRAINT `promociones_requisitos_ibfk_3` FOREIGN KEY (`subgrupo_id`) REFERENCES `subgrupos_productos` (`id`),
  ADD CONSTRAINT `promociones_requisitos_ibfk_4` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_productos` (`id`);

--
-- Constraints for table `promo_condiciones`
--
ALTER TABLE `promo_condiciones`
  ADD CONSTRAINT `fk_promo_cond_opcion` FOREIGN KEY (`opcion_id`) REFERENCES `promo_condiciones_opciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `promo_condiciones_ibfk_1` FOREIGN KEY (`promo_id`) REFERENCES `promo_promociones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proveedor_tipo_pago`
--
ALTER TABLE `proveedor_tipo_pago`
  ADD CONSTRAINT `proveedor_tipo_pago_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proveedor_tipo_pago_ibfk_2` FOREIGN KEY (`id_tipo_pago`) REFERENCES `tipo_pago_proveedores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proveedor_tipo_pago_ibfk_3` FOREIGN KEY (`asignado_por`) REFERENCES `Operarios` (`CodOperario`) ON DELETE SET NULL;

--
-- Constraints for table `receta_producto_global`
--
ALTER TABLE `receta_producto_global`
  ADD CONSTRAINT `fk_receta_presentacion` FOREIGN KEY (`id_presentacion_producto`) REFERENCES `producto_presentacion` (`id`),
  ADD CONSTRAINT `fk_receta_tipo` FOREIGN KEY (`id_tipo_receta`) REFERENCES `tipo_receta_producto` (`id`),
  ADD CONSTRAINT `fk_receta_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_receta_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `reclamos`
--
ALTER TABLE `reclamos`
  ADD CONSTRAINT `fk_reclamo_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `reclamos_grupos` (`id`),
  ADD CONSTRAINT `fk_reclamo_tipo` FOREIGN KEY (`tipo_reclamo_id`) REFERENCES `reclamos_tipos` (`id`);

--
-- Constraints for table `reclamos_cargos_responsables`
--
ALTER TABLE `reclamos_cargos_responsables`
  ADD CONSTRAINT `reclamos_cargos_responsables_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `reclamos_grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamos_cargos_responsables_ibfk_2` FOREIGN KEY (`tipo_id`) REFERENCES `reclamos_tipos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamos_imagenes`
--
ALTER TABLE `reclamos_imagenes`
  ADD CONSTRAINT `reclamos_imagenes_ibfk_1` FOREIGN KEY (`reclamo_id`) REFERENCES `reclamos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamos_productos`
--
ALTER TABLE `reclamos_productos`
  ADD CONSTRAINT `reclamos_productos_ibfk_1` FOREIGN KEY (`reclamo_id`) REFERENCES `reclamos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamos_tipos`
--
ALTER TABLE `reclamos_tipos`
  ADD CONSTRAINT `reclamos_tipos_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `reclamos_grupos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamos_videos`
--
ALTER TABLE `reclamos_videos`
  ADD CONSTRAINT `reclamos_videos_ibfk_1` FOREIGN KEY (`reclamo_id`) REFERENCES `reclamos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reportes_colaboradores`
--
ALTER TABLE `reportes_colaboradores`
  ADD CONSTRAINT `reportes_colaboradores_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes_investigacion` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reportes_investigacion`
--
ALTER TABLE `reportes_investigacion`
  ADD CONSTRAINT `reportes_investigacion_ibfk_1` FOREIGN KEY (`reclamo_id`) REFERENCES `reclamos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requisicion_personal`
--
ALTER TABLE `requisicion_personal`
  ADD CONSTRAINT `requisicion_personal_ibfk_1` FOREIGN KEY (`sucursal`) REFERENCES `sucursales` (`id`),
  ADD CONSTRAINT `requisicion_personal_ibfk_2` FOREIGN KEY (`cargo_reporta_a`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `requisicion_personal_ibfk_3` FOREIGN KEY (`usuario_registra`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `requisicion_personal_ibfk_4` FOREIGN KEY (`usuario_modifica`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `servicios_delivery`
--
ALTER TABLE `servicios_delivery`
  ADD CONSTRAINT `servicios_delivery_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Constraints for table `solicitudes_cotizacion_fotos`
--
ALTER TABLE `solicitudes_cotizacion_fotos`
  ADD CONSTRAINT `fk_foto_producto_cotizacion` FOREIGN KEY (`producto_id`) REFERENCES `solicitudes_cotizacion_productos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solicitudes_cotizacion_historial`
--
ALTER TABLE `solicitudes_cotizacion_historial`
  ADD CONSTRAINT `solicitudes_cotizacion_historial_ibfk_1` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_cotizacion` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solicitudes_cotizacion_productos`
--
ALTER TABLE `solicitudes_cotizacion_productos`
  ADD CONSTRAINT `solicitudes_cotizacion_productos_ibfk_1` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_cotizacion` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subgrupos_productos`
--
ALTER TABLE `subgrupos_productos`
  ADD CONSTRAINT `subgrupos_productos_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_productos` (`id`);

--
-- Constraints for table `subgrupo_presentacion_producto`
--
ALTER TABLE `subgrupo_presentacion_producto`
  ADD CONSTRAINT `fk_subgrupo_grupo` FOREIGN KEY (`id_grupo_presentacion_producto`) REFERENCES `grupo_presentacion_producto` (`id`),
  ADD CONSTRAINT `fk_subgrupo_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_subgrupo_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `sucursales`
--
ALTER TABLE `sucursales`
  ADD CONSTRAINT `fk_sucursales_departamento` FOREIGN KEY (`cod_departamento`) REFERENCES `departamentos` (`codigo`);

--
-- Constraints for table `TardanzasManuales`
--
ALTER TABLE `TardanzasManuales`
  ADD CONSTRAINT `fk_tardanza_manual_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `TardanzasStatus`
--
ALTER TABLE `TardanzasStatus`
  ADD CONSTRAINT `fk_tardanza_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `tipo_receta_producto`
--
ALTER TABLE `tipo_receta_producto`
  ADD CONSTRAINT `fk_tipo_receta_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_tipo_receta_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `unidad_producto`
--
ALTER TABLE `unidad_producto`
  ADD CONSTRAINT `fk_unidad_producto_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_unidad_producto_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Constraints for table `Vacaciones`
--
ALTER TABLE `Vacaciones`
  ADD CONSTRAINT `Vacaciones_ibfk_1` FOREIGN KEY (`cod_contrato`) REFERENCES `Contratos` (`CodContrato`);

--
-- Constraints for table `validacion_cv_ia`
--
ALTER TABLE `validacion_cv_ia`
  ADD CONSTRAINT `validacion_cv_ia_ibfk_1` FOREIGN KEY (`id_postulacion`) REFERENCES `postulacion_plaza` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `validacion_cv_ia_ibfk_2` FOREIGN KEY (`usuario_registra`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `validacion_cv_ia_ibfk_3` FOREIGN KEY (`usuario_modifica`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `variedad_producto_presentacion`
--
ALTER TABLE `variedad_producto_presentacion`
  ADD CONSTRAINT `fk_variedad_producto` FOREIGN KEY (`id_presentacion_producto`) REFERENCES `producto_presentacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_variedad_usuario_creacion` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_variedad_usuario_modificacion` FOREIGN KEY (`usuario_modificacion`) REFERENCES `Operarios` (`CodOperario`);

--
-- Constraints for table `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `ventas_ibfk_4` FOREIGN KEY (`servicio_delivery_id`) REFERENCES `servicios_delivery` (`id`);

--
-- Constraints for table `ventas_detalle`
--
ALTER TABLE `ventas_detalle`
  ADD CONSTRAINT `ventas_detalle_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `ventas_detalle_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos_delivery` (`id`);

--
-- Constraints for table `ventas_detalle_promociones`
--
ALTER TABLE `ventas_detalle_promociones`
  ADD CONSTRAINT `ventas_detalle_promociones_ibfk_1` FOREIGN KEY (`venta_detalle_id`) REFERENCES `ventas_detalle` (`id`),
  ADD CONSTRAINT `ventas_detalle_promociones_ibfk_2` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

--
-- Constraints for table `ventas_extras`
--
ALTER TABLE `ventas_extras`
  ADD CONSTRAINT `ventas_extras_ibfk_1` FOREIGN KEY (`venta_detalle_id`) REFERENCES `ventas_detalle` (`id`),
  ADD CONSTRAINT `ventas_extras_ibfk_2` FOREIGN KEY (`extra_id`) REFERENCES `extras` (`id`);

--
-- Constraints for table `ventas_meta`
--
ALTER TABLE `ventas_meta`
  ADD CONSTRAINT `fk_ventas_meta_sucursal` FOREIGN KEY (`cod_sucursal`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ventas_promociones`
--
ALTER TABLE `ventas_promociones`
  ADD CONSTRAINT `ventas_promociones_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `ventas_promociones_ibfk_2` FOREIGN KEY (`promocion_id`) REFERENCES `promociones` (`id`);

--
-- Constraints for table `viaticos`
--
ALTER TABLE `viaticos`
  ADD CONSTRAINT `fk_viaticos_actualizado_por` FOREIGN KEY (`actualizado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_viaticos_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_viaticos_operario` FOREIGN KEY (`cod_operario`) REFERENCES `Operarios` (`CodOperario`),
  ADD CONSTRAINT `fk_viaticos_sucursal` FOREIGN KEY (`sucursal_codigo`) REFERENCES `sucursales` (`codigo`);

--
-- Constraints for table `wsp_destinatarios_`
--
ALTER TABLE `wsp_destinatarios_`
  ADD CONSTRAINT `wsp_destinatarios__ibfk_1` FOREIGN KEY (`campana_id`) REFERENCES `wsp_campanas_` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wsp_planilla_programaciones_`
--
ALTER TABLE `wsp_planilla_programaciones_`
  ADD CONSTRAINT `fk_plwsp_usuario` FOREIGN KEY (`usuario_creacion`) REFERENCES `Operarios` (`CodOperario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
