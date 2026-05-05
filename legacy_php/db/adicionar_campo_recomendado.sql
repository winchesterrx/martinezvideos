-- Adiciona campo 'recomendado' na tabela videos
-- Este campo permite marcar vídeos manualmente como recomendados

-- Verifica se a coluna já existe antes de adicionar
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'videos' 
AND COLUMN_NAME = 'recomendado';

-- Adiciona a coluna apenas se não existir
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE videos ADD COLUMN recomendado TINYINT(1) DEFAULT 0 COMMENT ''1 = vídeo recomendado manualmente'' AFTER visualizacoes',
    'SELECT ''Coluna recomendado já existe'' AS mensagem');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verifica se o índice já existe antes de criar
SET @idx_exists = 0;
SELECT COUNT(*) INTO @idx_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'videos' 
AND INDEX_NAME = 'idx_recomendado';

-- Cria o índice apenas se não existir
SET @sql_idx = IF(@idx_exists = 0,
    'CREATE INDEX idx_recomendado ON videos(recomendado)',
    'SELECT ''Índice idx_recomendado já existe'' AS mensagem');
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Exemplo: Marcar um vídeo como recomendado
-- UPDATE videos SET recomendado = 1 WHERE id = 1;
