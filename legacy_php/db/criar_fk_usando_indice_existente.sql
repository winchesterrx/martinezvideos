-- Solução: Criar foreign key usando o índice existente
-- O índice idx_modulo_id já existe, então vamos criar a FK diretamente

-- Opção 1: Criar a foreign key (o MySQL usará o índice existente)
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- Se der erro, tente a Opção 2 abaixo:

-- Opção 2: Remover o índice e deixar o MySQL criar automaticamente
-- DROP INDEX idx_modulo_id ON videos;
-- ALTER TABLE videos 
-- ADD CONSTRAINT fk_videos_modulo 
-- FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- Verifica se foi criada
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND CONSTRAINT_NAME = 'fk_videos_modulo';

