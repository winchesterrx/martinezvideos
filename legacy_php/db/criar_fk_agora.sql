-- Script para criar a foreign key na tabela videos
-- Como não há dados incompatíveis, podemos criar diretamente

-- Cria a foreign key
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- Verifica se foi criada (deve retornar 1 registro)
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND CONSTRAINT_NAME = 'fk_videos_modulo';

