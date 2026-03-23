-- ============================================================
-- SQL para registrar PitayaBot en el sistema de permisos del ERP
-- Ejecutar en la BD de Hostinger (una sola vez)
-- ============================================================

-- 1. Registrar PitayaBot como herramienta en tools_erp
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, url_alias, icono)
VALUES (
    'pitayabot',
    'PitayaBot',
    'herramienta',
    'sistemas',
    'Asistente virtual por WhatsApp para colaboradores',
    '/modulos/gerencia/gestion_tareas_reuniones.php',
    '/pitayabot',
    'bi bi-robot'
)
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo);

-- 2. Registrar la acción 'usar' para PitayaBot
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'usar', 'Acceso para enviar/recibir mensajes en PitayaBot por WhatsApp'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ============================================================
-- 3. ASIGNAR PERMISOS POR CARGO
-- Reemplaza los CodNivelesCargos con los cargos que deben tener acceso
-- Ejemplo: Cargo 49 (Gerencia Proyectos), 16 (Gerencia General), 15 (TI)
-- ============================================================
INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
SELECT a.id, 49, 'allow'
FROM acciones_tools_erp a
INNER JOIN tools_erp t ON t.id = a.tool_erp_id
WHERE t.nombre = 'pitayabot' AND a.nombre_accion = 'usar'
ON DUPLICATE KEY UPDATE permiso = 'allow';

-- Para agregar más cargos, duplicar el bloque anterior cambiando el número de cargo:
-- INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
-- SELECT a.id, [CODIGO_CARGO], 'allow'
-- FROM acciones_tools_erp a
-- INNER JOIN tools_erp t ON t.id = a.tool_erp_id
-- WHERE t.nombre = 'pitayabot' AND a.nombre_accion = 'usar'
-- ON DUPLICATE KEY UPDATE permiso = 'allow';
