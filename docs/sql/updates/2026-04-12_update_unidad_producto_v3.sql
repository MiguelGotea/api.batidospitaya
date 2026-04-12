-- =============================================================================
-- Patch v3: Agregar "oz" como alias de Onzas Líquidas
-- Fecha: 2026-04-12
-- Propósito: "oz" en Access puede ser peso (wt oz) o volumen (fl oz).
--            El abreviado principal de Onzas Peso es "oz" y el de
--            Onzas Líquidas es "fl oz".
--            Al agregar "oz" en nombres_opcionales de Onzas Líquidas,
--            resolverUnidadERP() puede encontrar AMBAS como coincidencias,
--            permitiendo al sistema elegir la presentación correcta según
--            las disponibles en el ERP (ej: Leche Entera oz vs Caja 1L).
-- =============================================================================

UPDATE unidad_producto
SET nombres_opcionales = 'oz,oz fl,onza liquida,onzas liquidas'
WHERE id = 13;  -- Onzas Liquidas

-- Verificar resultado
SELECT id, nombre, abreviado, nombres_opcionales
FROM unidad_producto
WHERE id = 13;
