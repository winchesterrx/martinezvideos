-- Script para renomear a coluna sistema_id para setor_id na tabela modulos
-- Execute este script se a tabela modulos foi criada com sistema_id

-- Primeiro, remove a foreign key antiga (se existir)
ALTER TABLE modulos 
DROP FOREIGN KEY IF EXISTS fk_modulos_sistema;

-- Remove o índice antigo (se existir)
DROP INDEX IF EXISTS idx_sistema_id ON modulos;

-- Renomeia a coluna de sistema_id para setor_id
ALTER TABLE modulos 
CHANGE COLUMN sistema_id setor_id INT NOT NULL;

-- Cria o novo índice
CREATE INDEX idx_setor_id ON modulos (setor_id);

-- Adiciona a nova foreign key referenciando setores
ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

