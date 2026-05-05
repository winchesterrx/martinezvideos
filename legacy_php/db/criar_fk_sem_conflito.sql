-- Solução definitiva para criar foreign key sem conflito
-- Como o índice já existe, vamos remover e deixar o MySQL criar automaticamente

-- PASSO 1: Remove o índice existente
DROP INDEX idx_modulo_id ON videos;

-- PASSO 2: Cria a foreign key (o MySQL criará o índice automaticamente)
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- PASSO 3: Verifica se foi criada
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND CONSTRAINT_NAME = 'fk_videos_modulo';

