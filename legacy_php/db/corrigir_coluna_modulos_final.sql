-- Script para corrigir a coluna na tabela modulos
-- Este script verifica e corrige automaticamente o nome da coluna

-- Primeiro, vamos verificar qual coluna existe e corrigir
-- Execute cada bloco separadamente se der erro

-- ============================================
-- PASSO 1: Remove foreign keys antigas (se existirem)
-- ============================================
-- Se der erro "foreign key não existe", ignore e continue
ALTER TABLE modulos DROP FOREIGN KEY fk_modulos_sistema;
ALTER TABLE modulos DROP FOREIGN KEY fk_modulos_setor;

-- ============================================
-- PASSO 2: Remove índices antigos (se existirem)
-- ============================================
-- Se der erro "índice não existe", ignore e continue
DROP INDEX idx_sistema_id ON modulos;
DROP INDEX idx_setor_id ON modulos;

-- ============================================
-- PASSO 3: Renomeia a coluna (escolha a opção correta)
-- ============================================

-- OPÇÃO A: Se a coluna se chama 'sistema_id' (com underscore)
ALTER TABLE modulos 
CHANGE COLUMN sistema_id setor_id INT NOT NULL;

-- OU OPÇÃO B: Se a coluna se chama 'setor id' (com espaço) - descomente esta linha e comente a OPÇÃO A
-- ALTER TABLE modulos CHANGE COLUMN `setor id` `setor_id` INT NOT NULL;

-- OU OPÇÃO C: Se a coluna já se chama 'setor_id', pule este passo

-- ============================================
-- PASSO 4: Cria o índice correto
-- ============================================
CREATE INDEX idx_setor_id ON modulos (setor_id);

-- ============================================
-- PASSO 5: Adiciona a foreign key correta
-- ============================================
ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

