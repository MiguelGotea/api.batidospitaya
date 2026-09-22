# Base de datos principales - Batidos Pitaya ERP

## Operarios: Todos los datos de los colaboradores

CREATE TABLE `Operarios` (
  `CodOperario` int(11) NOT NULL AUTO_INCREMENT,
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
  `foto_perfil` varchar(255) DEFAULT NULL,
  `codigo_inss` varchar(50) DEFAULT NULL,
  `cb_titular` varchar(100) DEFAULT NULL,
  `cb_banco` varchar(100) DEFAULT NULL,
  `cb_moneda` varchar(20) DEFAULT NULL,
  `segurosocial` varchar(20) DEFAULT NULL,
  `InicioSeguro` date DEFAULT NULL COMMENT 'Fecha de inicio del seguro social',
  `FinSeguro` date DEFAULT NULL COMMENT 'Fecha de fin del seguro social',
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha y hora de registro',
  `hospital_riesgo_laboral` varchar(255) DEFAULT NULL,
  `FechaCreacion` timestamp NULL DEFAULT NULL,
  `email_trabajo_clave` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`CodOperario`)
) ENGINE=InnoDB AUTO_INCREMENT=716 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## NivelesCargos: Todos los cargos existentes con sus permisos y niveles

CREATE TABLE `NivelesCargos` (
  `CodNivelesCargos` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`CodNivelesCargos`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## AsignacionNivelesCargos: Asignacion de cargos a colaboradores a nivel historico, para saber que cargo tiene un colaborador se debe hacer una union con la tabla NivelesCargos y se extrae el codoperario para ese cargo con la logica que tenga el dato Fin nulo o mayor a la fecha actual y el dato Fecha(que es la fecha de inicio) menor que la fecha actual, de esa manera se busca el colaborador actual asignado a un cargo (CodOperario)

CREATE TABLE `AsignacionNivelesCargos` (
  `CodAsignacionNivelesCargos` int(11) NOT NULL AUTO_INCREMENT,
  `CodOperario` int(11) DEFAULT NULL,
  `CodNivelesCargos` int(11) DEFAULT NULL,
  `Fecha` date DEFAULT NULL COMMENT 'Fecha de inicio',
  `Sucursal` int(11) DEFAULT NULL,
  `CodTipoContrato` int(11) DEFAULT NULL,
  `Fin` date DEFAULT NULL COMMENT 'Fecha final de operario',
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
  `FotoExamen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`CodAsignacionNivelesCargos`),
  KEY `usuario_ultima_modificacion` (`usuario_ultima_modificacion`),
  KEY `cod_usuario_creador` (`cod_usuario_creador`),
  KEY `fk_asignacion_operario` (`CodOperario`),
  KEY `fk_asignacion_nivelcargo` (`CodNivelesCargos`),
  CONSTRAINT `AsignacionNivelesCargos_ibfk_1` FOREIGN KEY (`usuario_ultima_modificacion`) REFERENCES `Operarios` (`CodOperario`),
  CONSTRAINT `AsignacionNivelesCargos_ibfk_2` FOREIGN KEY (`cod_usuario_creador`) REFERENCES `Operarios` (`CodOperario`),
  CONSTRAINT `fk_asignacion_nivelcargo` FOREIGN KEY (`CodNivelesCargos`) REFERENCES `NivelesCargos` (`CodNivelesCargos`),
  CONSTRAINT `fk_asignacion_operario` FOREIGN KEY (`CodOperario`) REFERENCES `Operarios` (`CodOperario`)
) ENGINE=InnoDB AUTO_INCREMENT=1050 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## Sucursales: Todas las sucursales existentes y sus caracteristicas

CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ip_direccion` varchar(45) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `Fecha_Apertura` date DEFAULT NULL COMMENT 'yyyy-mm-dd',
  `departamento` text DEFAULT NULL,
  `cod_departamento` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL COMMENT 'Correo de sucursal',
  `activa` tinyint(1) DEFAULT 1,
  `sucursal` tinyint(1) NOT NULL DEFAULT 1 COMMENT '¿Es sucursal? 1=Si 0=No',
  `viatico_nocturno` int(11) DEFAULT 0,
  `Latitude` double DEFAULT NULL,
  `Longitude` double DEFAULT NULL,
  `cod_googlebusiness` varchar(100) DEFAULT NULL,
  `fecha_hora_regsys` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_sucursales_departamento` (`cod_departamento`),
  CONSTRAINT `fk_sucursales_departamento` FOREIGN KEY (`cod_departamento`) REFERENCES `departamentos` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## tools_erp: Registro de herramientas, indicadores y balances del sistema ERP

**Descripción:** Catálogo de todas las páginas, herramientas, indicadores y balances del sistema. Cada registro representa un componente accesible del ERP con sus metadatos, permisos y configuración.

```sql
CREATE TABLE `tools_erp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `tipo_componente` enum('herramienta','indicador','balance','alerta','notificacion_email') NOT NULL DEFAULT 'herramienta' COMMENT 'Tipo de componente del sistema',
  `class_name` varchar(100) DEFAULT NULL,
  `config_json` text DEFAULT NULL,
  `grupo` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `descripcion_breve` varchar(255) DEFAULT NULL COMMENT 'Resumen conciso (<=200 chars) del propósito de la herramienta para onboarding RRHH',
  `url_real` varchar(255) DEFAULT NULL,
  `url_alias` varchar(255) DEFAULT NULL,
  `icono` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `en_pruebas` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si es 1, la herramienta está en pruebas/testing y se excluye del catálogo oficial',
  PRIMARY KEY (`id`),
  KEY `idx_tipo_componente` (`tipo_componente`,`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=202 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campos Principales

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `nombre` | varchar(255) | ✅ | Identificador único en snake_case | `'pos_ferias'` |
| `titulo` | varchar(100) | ✅ | Título mostrado en la interfaz | `'POS Ferias'` |
| `tipo_componente` | enum | ✅ NOT NULL | `herramienta`, `indicador`, `balance`, `alerta`, `notificacion_email` | `'herramienta'` |
| `grupo` | varchar(255) | ✅ | Módulo del sistema | `'Sucursales'` |
| `descripcion` | varchar(255) | ⚪ | Descripción funcional larga | `'POS de facturación para ferias'` |
| `descripcion_breve` | varchar(255) | ⚪ | Resumen ≤200 chars para onboarding RRHH | `'POS ferias'` |
| `url_real` | varchar(255) | ⚪ | Ruta del archivo PHP | `'/modulos/sucursales/ferias/index_ferias.php'` |
| `url_alias` | varchar(255) | ⚪ | URL amigable | `'pos-ferias'` |
| `icono` | varchar(255) | ⚪ | Icono Font Awesome | `'fas fa-people-carry'` |
| `orden` | int(11) | ⚪ | Orden de visualización (default 0) | `10` |
| `activo` | tinyint(4) | ⚪ | Estado (default: 1) | `1` |
| `en_pruebas` | tinyint(1) | ⚪ | En testing, excluye del catálogo (default: 0) | `0` |

### Template SQL para Registro

```sql
-- Template completo para registrar nueva herramienta
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, activo)
SELECT
    'nombre_herramienta',
    'Título Herramienta',
    'herramienta',
    'nombre_modulo',
    'Descripción de la herramienta',
    '/modulos/nombre_modulo/archivo.php',
    1
WHERE NOT EXISTS (SELECT 1 FROM tools_erp WHERE nombre = 'nombre_herramienta');
```

### Ejemplo Real

```sql
-- Ejemplo: POS de Ferias
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, activo)
SELECT
    'pos_ferias',
    'POS Ferias',
    'herramienta',
    'Sucursales',
    'POS de facturación para ferias y eventos externos',
    '/modulos/sucursales/ferias/index_ferias.php',
    1
WHERE NOT EXISTS (SELECT 1 FROM tools_erp WHERE nombre = 'pos_ferias');
```

### Valores de tipo_componente

| Valor | Uso |
|---|---|
| `herramienta` | Página/módulo navegable (default) |
| `indicador` | KPI o métrica del dashboard |
| `balance` | Balance financiero/contable |
| `alerta` | Alerta o notificación en UI |
| `notificacion_email` | Notificación enviada por correo |

### Iconos Comunes (Font Awesome)

- **Gestión:** `fas fa-cog`, `fas fa-tools`, `fas fa-tasks`
- **Reportes:** `fas fa-chart-bar`, `fas fa-file-alt`, `fas fa-table`
- **Usuarios:** `fas fa-users`, `fas fa-user-tie`, `fas fa-id-card`
- **Marketing:** `fas fa-bullhorn`, `fas fa-gift`, `fas fa-star`
- **Finanzas:** `fas fa-dollar-sign`, `fas fa-wallet`, `fas fa-receipt`

## acciones_tools_erp: Todas las acciones que se le pueden asignar a cada herramienta, siempre tiene que existir vista que da permiso de poder ver una herramienta

```sql
CREATE TABLE `acciones_tools_erp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool_erp_id` int(11) NOT NULL,
  `nombre_accion` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tool_accion` (`tool_erp_id`,`nombre_accion`),
  CONSTRAINT `fk_acciones_tools_erp` FOREIGN KEY (`tool_erp_id`) REFERENCES `tools_erp` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Nota:** La combinación (`tool_erp_id`, `nombre_accion`) es única — no puede existir la misma acción dos veces para la misma herramienta.

**Acciones estándar:**
- `vista` — siempre requerida; permite ver/acceder a la herramienta
- `nuevo` — crear registros
- `edicion` — editar registros existentes
- `eliminar` — eliminar registros

## permisos_tools_erp: Seleccion de cargos de niveles cargos que tienen permiso para cada accion creada de cada herramienta o pagina

```sql
CREATE TABLE `permisos_tools_erp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accion_tool_erp_id` int(11) NOT NULL,
  `CodNivelesCargos` int(11) NOT NULL,
  `permiso` enum('allow','deny') NOT NULL DEFAULT 'allow',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_accion_tool_erp` FOREIGN KEY (`accion_tool_erp_id`) REFERENCES `acciones_tools_erp` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Flujo completo de registro (idempotente):**

```sql
-- 1. Herramienta
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, activo)
SELECT 'mi_tool','Mi Tool','herramienta','Modulo','Descripcion','/modulos/ruta.php',1
WHERE NOT EXISTS (SELECT 1 FROM tools_erp WHERE nombre = 'mi_tool');

-- 2. Acción vista
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT t.id, 'vista', 'Acceso a Mi Tool'
FROM tools_erp t WHERE t.nombre = 'mi_tool'
AND NOT EXISTS (SELECT 1 FROM acciones_tools_erp a WHERE a.tool_erp_id = t.id AND a.nombre_accion = 'vista');

-- 3. Permiso por cargo (repetir por cada cargo)
INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
SELECT a.id, 27, 'allow'
FROM tools_erp t INNER JOIN acciones_tools_erp a ON t.id = a.tool_erp_id
WHERE t.nombre = 'mi_tool' AND a.nombre_accion = 'vista'
AND NOT EXISTS (SELECT 1 FROM permisos_tools_erp p WHERE p.accion_tool_erp_id = a.id AND p.CodNivelesCargos = 27);
```

## clientesclub: Lista de clientes registrado en las sucursales con provilegios de club

CREATE TABLE `clientesclub` (
  `id_clienteclub` int(11) NOT NULL AUTO_INCREMENT,
  `sucursal` int(11) NOT NULL,
  `membresia` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `celular` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `puntos_iniciales` int(11) DEFAULT NULL,
  `nombre_sucursal` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_clienteclub`),
  KEY `idx_club` (`membresia`)
) ENGINE=InnoDB AUTO_INCREMENT=2700769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## VentasGlobalesAccessCSV: Hitorial de ventas de cada sucursal a clientes club o general, estas ventas se registran en un sistema Access y se suben de manera automatizada a esta tabla ya resumido

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
  `CodMotorizado` int(11) DEFAULT NULL,
  KEY `idx_fecha` (`Fecha`),
  KEY `idx_codproducto` (`CodProducto`),
  KEY `idx_fecha_hora` (`Fecha`,`Hora`),
  KEY `idx_sucursal` (`Sucursal_Nombre`),
  KEY `idx_membresia` (`CodCliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

## SemanasSistema: Numero de semana asignado a cada fecha calendario, este numero de semana es una secuencia interna de la empresa almacenado en esta tabla y todo control administrativo se hace siempre por semana o mes

CREATE TABLE `SemanasSistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_semana` int(11) NOT NULL COMMENT 'Número de semana según sistema de la empresa',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio (lunes)',
  `fecha_fin` date NOT NULL COMMENT 'Fecha de fin (domingo)',
  `anio` int(11) NOT NULL COMMENT 'Año al que pertenece la semana',
  `semana_iso` int(11) NOT NULL COMMENT 'Semana según estándar ISO',
  `creada_por` int(11) NOT NULL COMMENT 'Usuario que creó la semana',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_semana_unico` (`numero_semana`)
) ENGINE=InnoDB AUTO_INCREMENT=1828 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='numeros de semana con fecha inicio y final';