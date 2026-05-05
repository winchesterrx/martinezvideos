-- Script SIMPLES para corrigir a tabela modulos
-- Execute os comandos UM POR VEZ, ignorando erros de "não existe"

-- 1. Verifique primeiro qual coluna existe:
-- DESCRIBE modulos;

-- 2. Se a coluna se chama 'sistema_id', execute este comando:
ALTER TABLE modulos CHANGE COLUMN sistema_id setor_id INT NOT NULL;

-- 3. Se a coluna se chama 'setor id' (com espaço), execute este comando:
-- ALTER TABLE modulos CHANGE COLUMN `setor id` `setor_id` INT NOT NULL;

-- 4. Cria o índice:
CREATE INDEX idx_setor_id ON modulos (setor_id);

-- 5. Adiciona a foreign key:
ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

