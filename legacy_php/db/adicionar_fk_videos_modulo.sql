-- Script para adicionar foreign key na tabela videos
-- Remove a foreign key antiga primeiro (se existir) e cria uma nova

-- PASSO 1: Remove a foreign key antiga (se existir)
-- Se der erro "não existe", ignore e continue
ALTER TABLE videos DROP FOREIGN KEY fk_videos_modulo;

-- PASSO 2: Adiciona a foreign key
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

