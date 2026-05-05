-- Script para criar a foreign key na tabela videos
-- Execute este script agora que confirmamos que a FK não existe

-- Verifica se a coluna modulo_id existe e tem dados compatíveis
-- Se houver valores em modulo_id que não existem em modulos.id, isso dará erro
-- Nesse caso, você precisa limpar os dados primeiro

-- Cria a foreign key
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- Verifica se foi criada corretamente
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND CONSTRAINT_NAME = 'fk_videos_modulo';

