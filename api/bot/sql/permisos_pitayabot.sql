-- ═══════════════════════════════════════════════════════════════════
--  PitayaBot — Registro en tools_erp como herramienta INDEPENDIENTE
--
--  El tool 'pitayabot' agrupa TODAS las acciones del bot.
--  No se mezclan con 'gestion_tareas_reuniones' ni con otros tools.
--  Cuando se cree la herramienta dedicada de PitayaBot en el ERP,
--  todos estos permisos ya estarán registrados correctamente.
--
--  Estructura del sistema de permisos:
--    tools_erp
--      └── acciones_tools_erp  (tool_erp_id, nombre_accion)
--            └── permisos_tools_erp  (accion_tool_erp_id, CodNivelesCargos, permiso)
--
--  Ejecutar manualmente en la BD de Hostinger (u839374897_erp)
--
--  ⚠️  ANTES DE EJECUTAR:
--      Verifica los CodNivelesCargos con:
--      SELECT CodNivelesCargos, Nombre FROM NivelesCargos ORDER BY CodNivelesCargos;
-- ═══════════════════════════════════════════════════════════════════


-- ── 1. Registrar la herramienta 'pitayabot' en tools_erp ─────────
INSERT INTO tools_erp (nombre, titulo, tipo_componente, grupo, descripcion, url_real, icono, activo)
VALUES (
    'pitayabot',
    'PitayaBot — Asistente Virtual WhatsApp',
    'modulo',
    'gerencia',
    'Asistente virtual por WhatsApp para colaboradores de Batidos Pitaya. Gestiona tareas, reuniones, notas y correos mediante IA.',
    '/modulos/gerencia/gestion_tareas_reuniones.php',
    'bi-whatsapp',
    1
)
ON DUPLICATE KEY UPDATE
    titulo      = VALUES(titulo),
    descripcion = VALUES(descripcion),
    icono       = VALUES(icono);


-- ── 2. Registrar acciones del bot ────────────────────────────────

-- Acción de vista/administración del panel del bot
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'vista', 'Acceder al panel de administración de PitayaBot'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Ver el badge de estado de conexión de WhatsApp
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'ver_estado', 'Ver el indicador de conexión de PitayaBot (badge + número vinculado)'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Botón prueba de envío
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'prueba_envio', 'Enviar un mensaje de prueba desde PitayaBot a cualquier número de WhatsApp'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Botón cambiar número / resetear sesión
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'resetear_sesion', 'Solicitar cierre de sesión de WhatsApp para vincular un número nuevo'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Ver logs de operaciones del bot
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'ver_logs', 'Ver el historial de mensajes e interacciones procesadas por PitayaBot'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Gestionar colaboradores habilitados para usar el bot (bot_activo)
INSERT INTO acciones_tools_erp (tool_erp_id, nombre_accion, descripcion)
SELECT id, 'gestionar_usuarios', 'Habilitar o deshabilitar acceso al bot para cada colaborador'
FROM tools_erp WHERE nombre = 'pitayabot'
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);


-- ── 3. Asignar permisos por cargo ────────────────────────────────
--  Ajusta los CodNivelesCargos según tu tabla NivelesCargos.
--  Referencia del sistema: 16 = Gerencia General (ejemplo de otros módulos)

-- ── 3a. Gerencia General — acceso COMPLETO ───────────────────────
INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
SELECT a.id, 16, 'allow'
FROM acciones_tools_erp a
JOIN tools_erp t ON a.tool_erp_id = t.id
WHERE t.nombre = 'pitayabot'
  AND a.nombre_accion IN ('vista','ver_estado','prueba_envio','resetear_sesion','ver_logs','gestionar_usuarios')
ON DUPLICATE KEY UPDATE permiso = 'allow';


-- ── 3b. Sistemas / TI — acceso COMPLETO ──────────────────────────
--  Descomenta y ajusta el CodNivelesCargos correcto de Sistemas/TI

-- INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
-- SELECT a.id, 2, 'allow'
-- FROM acciones_tools_erp a
-- JOIN tools_erp t ON a.tool_erp_id = t.id
-- WHERE t.nombre = 'pitayabot'
--   AND a.nombre_accion IN ('vista','ver_estado','prueba_envio','resetear_sesion','ver_logs','gestionar_usuarios')
-- ON DUPLICATE KEY UPDATE permiso = 'allow';


-- ── 3c. Otros cargos — solo VER ESTADO ────────────────────────────
--  Colaboradores que ven el badge pero no pueden administrar el bot.
--  Ajusta los CodNivelesCargos en la lista IN().

-- INSERT INTO permisos_tools_erp (accion_tool_erp_id, CodNivelesCargos, permiso)
-- SELECT a.id, c.CodNivelesCargos, 'allow'
-- FROM acciones_tools_erp a
-- JOIN tools_erp t ON a.tool_erp_id = t.id
-- CROSS JOIN (
--     SELECT CodNivelesCargos FROM NivelesCargos
--     WHERE CodNivelesCargos IN (3, 4, 5, 6)   -- << Ajustar cargos
-- ) c
-- WHERE t.nombre = 'pitayabot'
--   AND a.nombre_accion = 'ver_estado'
-- ON DUPLICATE KEY UPDATE permiso = 'allow';


-- ── 4. Verificación ──────────────────────────────────────────────
-- SELECT t.nombre AS herramienta, a.nombre_accion, p.CodNivelesCargos, nc.Nombre AS cargo, p.permiso
-- FROM permisos_tools_erp p
-- JOIN acciones_tools_erp a ON a.id = p.accion_tool_erp_id
-- JOIN tools_erp t ON t.id = a.tool_erp_id
-- JOIN NivelesCargos nc ON nc.CodNivelesCargos = p.CodNivelesCargos
-- WHERE t.nombre = 'pitayabot'
-- ORDER BY p.CodNivelesCargos, a.nombre_accion;

-- ── Fin del script ────────────────────────────────────────────────
