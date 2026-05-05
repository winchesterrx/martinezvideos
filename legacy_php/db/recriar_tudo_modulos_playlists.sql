-- =====================================================
-- SCRIPT COMPLETO: RECRIAR MÓDULOS E PLAYLISTS DO ZERO
-- =====================================================
-- Este script remove e recria toda a estrutura de módulos e playlists
-- IMPORTANTE: Faça backup do banco antes de executar!
-- =====================================================

-- =====================================================
-- PARTE 1: REMOVER ESTRUTURAS ANTIGAS (SE EXISTIREM)
-- =====================================================

-- Remove foreign keys da tabela videos relacionadas a modulo_id
SET @fk_name = NULL;
SELECT CONSTRAINT_NAME INTO @fk_name
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id'
    AND REFERENCED_TABLE_NAME = 'modulos'
LIMIT 1;

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE videos DROP FOREIGN KEY ', @fk_name), 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove índices relacionados a modulo_id na tabela videos
SET @index_name = NULL;
SELECT INDEX_NAME INTO @index_name
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id'
LIMIT 1;

SET @sql = IF(@index_name IS NOT NULL, 
    CONCAT('DROP INDEX ', @index_name, ' ON videos'), 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove foreign keys da tabela modulos relacionadas a setor_id
SET @fk_name = NULL;
SELECT CONSTRAINT_NAME INTO @fk_name
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'modulos'
    AND COLUMN_NAME = 'setor_id'
    AND REFERENCED_TABLE_NAME = 'setores'
LIMIT 1;

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE modulos DROP FOREIGN KEY ', @fk_name), 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove foreign keys da tabela playlist_videos
SET @fk_name = NULL;
SELECT CONSTRAINT_NAME INTO @fk_name
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'playlist_videos'
    AND REFERENCED_TABLE_NAME = 'playlists'
LIMIT 1;

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE playlist_videos DROP FOREIGN KEY ', @fk_name), 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_name = NULL;
SELECT CONSTRAINT_NAME INTO @fk_name
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'playlist_videos'
    AND REFERENCED_TABLE_NAME = 'videos'
LIMIT 1;

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE playlist_videos DROP FOREIGN KEY ', @fk_name), 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove foreign keys da tabela playlists
SET @fk_name = NULL;
SELECT CONSTRAINT_NAME INTO @fk_name
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'playlists'
    AND REFERENCED_TABLE_NAME = 'usuarios'
LIMIT 1;

SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE playlists DROP FOREIGN KEY ', @fk_name), 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove tabelas (CUIDADO: Isso apaga todos os dados!)
-- Descomente apenas se quiser remover completamente:
-- DROP TABLE IF EXISTS playlist_videos;
-- DROP TABLE IF EXISTS playlists;
-- DROP TABLE IF EXISTS modulos;

-- =====================================================
-- PARTE 2: CRIAR TABELA MODULOS
-- =====================================================

-- Remove a tabela modulos se existir (CUIDADO: apaga dados!)
DROP TABLE IF EXISTS modulos;

-- Cria a tabela modulos com a estrutura correta
CREATE TABLE modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setor_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    icone VARCHAR(100) DEFAULT 'fas fa-cube',
    cor VARCHAR(7) DEFAULT '#6366f1',
    ativo CHAR(1) DEFAULT 'S',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setor_id (setor_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona foreign key para setores
ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

-- =====================================================
-- PARTE 3: ADICIONAR CAMPO modulo_id EM VIDEOS
-- =====================================================

-- Verifica se a coluna modulo_id já existe
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'videos'
        AND COLUMN_NAME = 'modulo_id'
);

-- Adiciona a coluna se não existir
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE videos ADD COLUMN modulo_id INT NULL AFTER setor_id',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cria índice para modulo_id
CREATE INDEX IF NOT EXISTS idx_modulo_id ON videos (modulo_id);

-- Adiciona foreign key para modulos
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- =====================================================
-- PARTE 4: CRIAR TABELA PLAYLISTS
-- =====================================================

-- Remove a tabela playlists se existir (CUIDADO: apaga dados!)
DROP TABLE IF EXISTS playlists;

-- Cria a tabela playlists
CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    usuario_id INT NOT NULL,
    cor VARCHAR(7) DEFAULT '#6366f1',
    ativo CHAR(1) DEFAULT 'S',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona foreign key para usuarios
ALTER TABLE playlists 
ADD CONSTRAINT fk_playlists_usuario 
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- =====================================================
-- PARTE 5: CRIAR TABELA PLAYLIST_VIDEOS
-- =====================================================

-- Remove a tabela playlist_videos se existir (CUIDADO: apaga dados!)
DROP TABLE IF EXISTS playlist_videos;

-- Cria a tabela playlist_videos
CREATE TABLE playlist_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    video_id INT NOT NULL,
    ordem INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_playlist_video (playlist_id, video_id),
    INDEX idx_playlist_ordem (playlist_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PARTE 6: VERIFICAÇÃO FINAL
-- =====================================================

-- Verifica se tudo foi criado corretamente
SELECT 
    'Tabelas criadas:' AS status,
    COUNT(*) AS total
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('modulos', 'playlists', 'playlist_videos');

SELECT 
    'Foreign Keys criadas:' AS status,
    COUNT(*) AS total
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('modulos', 'videos', 'playlists', 'playlist_videos')
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Mostra estrutura das tabelas
SHOW CREATE TABLE modulos;
SHOW CREATE TABLE playlists;
SHOW CREATE TABLE playlist_videos;

-- Verifica se a coluna modulo_id foi adicionada em videos
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id';

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

