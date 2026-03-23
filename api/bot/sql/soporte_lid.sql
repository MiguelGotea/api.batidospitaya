-- sql/soporte_lid.sql
-- Añadir columna para guardar la identidad técnica (LID) de WhatsApp
ALTER TABLE Operarios ADD COLUMN bot_lid VARCHAR(50) DEFAULT NULL;
CREATE INDEX idx_operarios_bot_lid ON Operarios(bot_lid);
