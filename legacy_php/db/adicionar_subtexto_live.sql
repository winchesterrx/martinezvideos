-- Adiciona coluna subtexto à tabela transmissao_ao_vivo
-- Execute este script se a coluna não existir

ALTER TABLE transmissao_ao_vivo 
ADD COLUMN IF NOT EXISTS subtexto VARCHAR(255) DEFAULT NULL 
AFTER descricao;

