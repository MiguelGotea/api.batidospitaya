-- Actualización de la tabla unidad_producto
-- Fecha: 2026-04-12
-- Descripción: Agregar columnas abreviado y nombres_opcionales y poblar de datos robustos

-- 1. Agregar las nuevas columnas
ALTER TABLE unidad_producto 
ADD COLUMN abreviado VARCHAR(50) DEFAULT NULL AFTER nombre,
ADD COLUMN nombres_opcionales TEXT DEFAULT NULL AFTER abreviado;

-- 2. Actualizar los registros actuales con información robusta
-- ID 1: Gramos
UPDATE unidad_producto SET abreviado = 'g', nombres_opcionales = 'gr, gramo' WHERE id = 1;

-- ID 2: Kilogramos
UPDATE unidad_producto SET abreviado = 'kg', nombres_opcionales = 'kilo, kilos' WHERE id = 2;

-- ID 3: Libras
UPDATE unidad_producto SET abreviado = 'lb', nombres_opcionales = 'lbs, libra' WHERE id = 3;

-- ID 4: Mililitros
UPDATE unidad_producto SET abreviado = 'ml', nombres_opcionales = 'ml., mililitro' WHERE id = 4;

-- ID 5: Litros
UPDATE unidad_producto SET abreviado = 'l', nombres_opcionales = 'lt, lts, litro' WHERE id = 5;

-- ID 6: Onzas Peso
UPDATE unidad_producto SET abreviado = 'oz', nombres_opcionales = 'onza peso' WHERE id = 6;

-- ID 7: Tazas
UPDATE unidad_producto SET abreviado = 'tz', nombres_opcionales = 'tza, taza, tazas' WHERE id = 7;

-- ID 8: Cucharadas
UPDATE unidad_producto SET abreviado = 'cda', nombres_opcionales = 'cdas, cucharada, cucharadas' WHERE id = 8;

-- ID 9: Unidades
UPDATE unidad_producto SET abreviado = 'u', nombres_opcionales = 'un, und, unidad, unidades' WHERE id = 9;

-- ID 10: Rama
UPDATE unidad_producto SET abreviado = 'rama', nombres_opcionales = '' WHERE id = 10;

-- ID 11: Moño
UPDATE unidad_producto SET abreviado = 'moño', nombres_opcionales = '' WHERE id = 11;

-- ID 12: Cajilla
UPDATE unidad_producto SET abreviado = 'cajilla', nombres_opcionales = '' WHERE id = 12;

-- ID 13: Onzas Liquidas
UPDATE unidad_producto SET abreviado = 'oz fl', nombres_opcionales = 'fl oz, onza liquida, onzas liquidas' WHERE id = 13;
