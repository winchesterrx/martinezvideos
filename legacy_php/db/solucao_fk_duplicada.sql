-- Solução para erro de chave duplicada ao criar foreign key
-- Execute os comandos UM POR VEZ

-- 1. Verifica o engine da tabela (deve ser InnoDB)
SHOW CREATE TABLE videos;

-- 2. Se não for InnoDB, converta:
-- ALTER TABLE videos ENGINE=InnoDB;

-- 3. Remove TODOS os índices na coluna modulo_id
SHOW INDEX FROM videos WHERE Column_name = 'modulo_id';

-- Se aparecer algum índice, remova com:
-- DROP INDEX nome_do_indice ON videos;

-- 4. Remove a foreign key se existir com outro nome
-- Primeiro, veja todas as foreign keys:
SELECT CONSTRAINT_NAME 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND REFERENCED_TABLE_NAME = 'modulos';

-- Se aparecer alguma, remova:
-- ALTER TABLE videos DROP FOREIGN KEY nome_da_fk;

-- 5. Recria o índice limpo
DROP INDEX IF EXISTS idx_modulo_id ON videos;
CREATE INDEX idx_modulo_id ON videos (modulo_id);

-- 6. Agora cria a foreign key
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

