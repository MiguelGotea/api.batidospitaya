-- =============================================================================
-- Actualización robusta de la tabla unidad_producto
-- Fecha: 2026-04-12
-- Propósito: Completar abreviado y nombres_opcionales con todos los tokens
--            que suelen aparecer en DBIngredientes.Unidad del sistema Access,
--            de modo que resolverUnidadERP() no necesite mapa hardcodeado.
-- =============================================================================

-- ── ID 1: Gramos ──────────────────────────────────────────────────────────────
-- Access usa: gr, g, g., grs, gramos, gramo
UPDATE unidad_producto
SET abreviado = 'g',
    nombres_opcionales = 'gr,grs,g.,gramo,gramos'
WHERE id = 1;

-- ── ID 2: Kilogramos ──────────────────────────────────────────────────────────
-- Access usa: kg, kilo, kilos, kilogramo, kilogramos
UPDATE unidad_producto
SET abreviado = 'kg',
    nombres_opcionales = 'kilo,kilos,kilogramo,kilogramos'
WHERE id = 2;

-- ── ID 3: Libras ──────────────────────────────────────────────────────────────
-- Access usa: lb, lbs, libra, libras
UPDATE unidad_producto
SET abreviado = 'lb',
    nombres_opcionales = 'lbs,libra,libras'
WHERE id = 3;

-- ── ID 4: Mililitros ──────────────────────────────────────────────────────────
-- Access usa: ml, ml., mls, mililitro, mililitros
UPDATE unidad_producto
SET abreviado = 'ml',
    nombres_opcionales = 'ml.,mls,mililitro,mililitros'
WHERE id = 4;

-- ── ID 5: Litros ──────────────────────────────────────────────────────────────
-- Access usa: lt, l, l., lts, litro, litros
UPDATE unidad_producto
SET abreviado = 'lt',
    nombres_opcionales = 'l,l.,lts,litro,litros'
WHERE id = 5;

-- ── ID 6: Onzas Peso ─────────────────────────────────────────────────────────
-- Access usa: oz, wt oz, onza peso, onzas peso
-- NOTA: 'oz' sin calificador se interpreta como peso (wt oz).
--       Los ingredientes líquidos medidos en oz en Access deberían
--       tener 'fl oz' → Onzas Líquidas (id=13).
UPDATE unidad_producto
SET abreviado = 'oz',
    nombres_opcionales = 'wt oz,onza peso,onzas peso'
WHERE id = 6;

-- ── ID 7: Tazas ───────────────────────────────────────────────────────────────
-- Access usa: tza, tz, taza, tazas
UPDATE unidad_producto
SET abreviado = 'tz',
    nombres_opcionales = 'tza,taza,tazas'
WHERE id = 7;

-- ── ID 8: Cucharadas ─────────────────────────────────────────────────────────
-- Access usa: cda, cdas, tbsp, cucharada, cucharadas
UPDATE unidad_producto
SET abreviado = 'cda',
    nombres_opcionales = 'cdas,tbsp,cucharada,cucharadas'
WHERE id = 8;

-- ── ID 9: Unidades ───────────────────────────────────────────────────────────
-- Access usa: u, un, und, unid, pz, pza, pzas, pieza, piezas, unidad, unidades
UPDATE unidad_producto
SET abreviado = 'u',
    nombres_opcionales = 'un,und,unid,pz,pza,pzas,pieza,piezas,unidad,unidades'
WHERE id = 9;

-- ── ID 10: Rama ──────────────────────────────────────────────────────────────
UPDATE unidad_producto
SET abreviado = 'rama',
    nombres_opcionales = 'ramas'
WHERE id = 10;

-- ── ID 11: Moño ──────────────────────────────────────────────────────────────
UPDATE unidad_producto
SET abreviado = 'moño',
    nombres_opcionales = 'moños,mono,monos'
WHERE id = 11;

-- ── ID 12: Cajilla ───────────────────────────────────────────────────────────
UPDATE unidad_producto
SET abreviado = 'cajilla',
    nombres_opcionales = 'cajillas'
WHERE id = 12;

-- ── ID 13: Onzas Líquidas ────────────────────────────────────────────────────
-- Access usa: fl oz, oz fl, onza liquida, onzas liquidas
UPDATE unidad_producto
SET abreviado = 'fl oz',
    nombres_opcionales = 'oz fl,onza liquida,onzas liquidas'
WHERE id = 13;

-- =============================================================================
-- Verificación: mostrar todos los registros actualizados
-- =============================================================================
SELECT id, nombre, abreviado, nombres_opcionales
FROM unidad_producto
ORDER BY id;
