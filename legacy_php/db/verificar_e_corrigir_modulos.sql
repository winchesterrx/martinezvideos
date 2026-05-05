-- Script para verificar a estrutura atual e corrigir a tabela modulos
-- Execute este script passo a passo

-- ============================================
-- PASSO 1: Verificar estrutura atual da tabela
-- ============================================
-- Execute este comando primeiro para ver as colunas:
DESCRIBE modulos;

-- ============================================
-- PASSO 2: Remover constraints antigas
-- ============================================
-- Execute estes comandos um por vez (ignore erros de "não existe"):

-- Remove foreign key antiga (se existir)
SET @fk_name = (SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'modulos' 
                AND COLUMN_NAME IN ('sistema_id', 'setor_id') 
                AND REFERENCED_TABLE_NAME IS NOT NULL 
                LIMIT 1);

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE modulos DROP FOREIGN KEY ', @fk_name), 
    'SELECT "Nenhuma foreign key encontrada"');
    
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove índices antigos
DROP INDEX IF EXISTS idx_sistema_id ON modulos;
DROP INDEX IF EXISTS idx_setor_id ON modulos;

-- ============================================
-- PASSO 3: Renomear coluna (se necessário)
-- ============================================
-- Verifica qual coluna existe e renomeia
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'modulos' 
                   AND COLUMN_NAME = 'sistema_id');

SET @sql = IF(@col_exists > 0,
    'ALTER TABLE modulos CHANGE COLUMN sistema_id setor_id INT NOT NULL',
    'SELECT "Coluna sistema_id não existe, pode já estar como setor_id"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- PASSO 4: Criar índice e foreign key
-- ============================================
CREATE INDEX idx_setor_id ON modulos (setor_id);

ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

