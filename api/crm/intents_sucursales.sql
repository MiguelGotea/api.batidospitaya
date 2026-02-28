-- Insertar intenciones específicas de dirección para cada una de las 13 sucursales
-- Instancia asignada: wsp-crmbot (o NULL si quieres que sea global)
-- Las direcciones dicen "DIRECCION_AQUI" para que las actualices luego desde la interfaz o en la BD.

INSERT INTO `bot_intents` (`intent_name`, `keywords`, `response_templates`, `priority`, `is_active`, `instancia`) VALUES

-- MANAGUA (6 sucursales)
('dir_altamira', 'altamira', '["Nuestra sucursal de *Altamira* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos para disfrutar de tu batido favorito! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_villa_fontana', 'villa fontana, fontana', '["Nuestra sucursal de *Villa Fontana* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_las_colinas', 'colinas, las colinas', '["Nuestra sucursal de *Las Colinas* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_las_brisas', 'brisas, las brisas', '["Nuestra sucursal de *Las Brisas* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_unica', 'unica, la unica, universidad catolica', '["Nuestra sucursal en la *UNICA* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_plaza_natura', 'plaza natura, natura', '["Nuestra sucursal de *Plaza Natura* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),

-- DEPARTAMENTOS (7 sucursales)
('dir_leon', 'leon, león', '["Nuestra sucursal en *León* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos con el mejor sabor a fruta! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_matagalpa', 'matagalpa', '["Nuestra sucursal en *Matagalpa* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_esteli', 'esteli, estelí', '["Nuestra sucursal en *Estelí* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_masaya', 'masaya', '["Nuestra sucursal en *Masaya* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_granada', 'granada', '["Nuestra sucursal en *Granada* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_rivas', 'rivas', '["Nuestra sucursal en *Rivas* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot'),
('dir_ticuantepe', 'ticuantepe', '["Nuestra sucursal en *Ticuantepe* está ubicada en: \\"DIRECCION_AQUI\\". ¡Te esperamos! 🍓"]', 8, 1, 'wsp-crmbot');
