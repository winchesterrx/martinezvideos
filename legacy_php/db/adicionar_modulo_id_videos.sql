-- Script para adicionar coluna modulo_id na tabela videos
-- IMPORTANTE: Execute primeiro o script criar_tabela_modulos.sql se ainda não executou

-- Verifica se a coluna já existe antes de adicionar
SET @dbname = DATABASE();
SET @tablename = 'videos';
SET @columnname = 'modulo_id';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1', -- Coluna já existe, não faz nada
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' INT NULL AFTER setor_id')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adiciona índice se não existir
SET @indexname = 'idx_modulo_id';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (INDEX_NAME = @indexname)
    ) > 0,
    'SELECT 1', -- Índice já existe, não faz nada
    CONCAT('CREATE INDEX ', @indexname, ' ON ', @tablename, ' (modulo_id)')
));
PREPARE createIndexIfNotExists FROM @preparedStatement;
EXECUTE createIndexIfNotExists;
DEALLOCATE PREPARE createIndexIfNotExists;

-- Adiciona foreign key se não existir
SET @fkname = 'fk_videos_modulo';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (CONSTRAINT_NAME = @fkname)
    ) > 0,
    'SELECT 1', -- Foreign key já existe, não faz nada
    CONCAT('ALTER TABLE ', @tablename, ' ADD CONSTRAINT ', @fkname, ' FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL')
));
PREPARE createFKIfNotExists FROM @preparedStatement;
EXECUTE createFKIfNotExists;
DEALLOCATE PREPARE createFKIfNotExists;

