-- Script para verificar se a foreign key já existe na tabela videos

-- Verifica foreign keys existentes na tabela videos
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Se a foreign key fk_videos_modulo já existir, você verá ela listada acima
-- Nesse caso, não precisa criar novamente!

