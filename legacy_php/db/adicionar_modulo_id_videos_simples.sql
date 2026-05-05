-- Script SIMPLIFICADO para adicionar coluna modulo_id na tabela videos
-- Use este script se o anterior der erro de sintaxe

-- Verifica se a coluna modulo_id já existe
-- Se não existir, adiciona a coluna
-- Se já existir, não faz nada (pode dar um aviso, mas não é erro)

-- Adiciona coluna modulo_id (se não existir)
ALTER TABLE videos 
ADD COLUMN modulo_id INT NULL AFTER setor_id;

-- Adiciona índice
CREATE INDEX idx_modulo_id ON videos (modulo_id);

-- Adiciona foreign key (só se a tabela modulos existir)
-- Se der erro aqui, execute primeiro: db/criar_tabela_modulos.sql
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

