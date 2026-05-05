-- =====================================================
-- SCRIPT SEGURO: CRIAR MÓDULOS E PLAYLISTS (SEM APAGAR DADOS)
-- =====================================================
-- Este script cria apenas o que não existe, sem remover dados
-- Use este script se quiser preservar dados existentes
-- =====================================================

-- =====================================================
-- PARTE 1: CRIAR TABELA MODULOS (SE NÃO EXISTIR)
-- =====================================================

CREATE TABLE IF NOT EXISTS modulos (
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

-- Verifica se a foreign key já existe antes de criar
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'modulos'
        AND CONSTRAINT_NAME = 'fk_modulos_setor'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE modulos ADD CONSTRAINT fk_modulos_setor FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PARTE 2: CORRIGIR COLUNA sistema_id PARA setor_id (SE NECESSÁRIO)
-- =====================================================

-- Verifica se existe coluna sistema_id
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'modulos'
        AND COLUMN_NAME = 'sistema_id'
);

-- Se existir sistema_id e não existir setor_id, renomeia
SET @setor_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'modulos'
        AND COLUMN_NAME = 'setor_id'
);

SET @sql = IF(@col_exists > 0 AND @setor_exists = 0,
    'ALTER TABLE modulos CHANGE sistema_id setor_id INT NOT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PARTE 3: ADICIONAR CAMPO modulo_id EM VIDEOS (SE NÃO EXISTIR)
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

-- Cria índice se não existir
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'videos'
        AND INDEX_NAME = 'idx_modulo_id'
);

SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_modulo_id ON videos (modulo_id)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona foreign key se não existir
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'videos'
        AND CONSTRAINT_NAME = 'fk_videos_modulo'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE videos ADD CONSTRAINT fk_videos_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PARTE 4: CRIAR TABELA PLAYLISTS (SE NÃO EXISTIR)
-- =====================================================

CREATE TABLE IF NOT EXISTS playlists (
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

-- Adiciona foreign key se não existir
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'playlists'
        AND CONSTRAINT_NAME = 'fk_playlists_usuario'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE playlists ADD CONSTRAINT fk_playlists_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PARTE 5: CRIAR TABELA PLAYLIST_VIDEOS (SE NÃO EXISTIR)
-- =====================================================

CREATE TABLE IF NOT EXISTS playlist_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    video_id INT NOT NULL,
    ordem INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_playlist_video (playlist_id, video_id),
    INDEX idx_playlist_ordem (playlist_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adiciona foreign keys se não existirem
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'playlist_videos'
        AND CONSTRAINT_NAME = 'fk_playlist_videos_playlist'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE playlist_videos ADD CONSTRAINT fk_playlist_videos_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'playlist_videos'
        AND CONSTRAINT_NAME = 'fk_playlist_videos_video'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE playlist_videos ADD CONSTRAINT fk_playlist_videos_video FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- PARTE 6: VERIFICAÇÃO FINAL
-- =====================================================

-- Verifica estrutura criada
SELECT 
    'Tabelas verificadas:' AS status,
    COUNT(*) AS total
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('modulos', 'playlists', 'playlist_videos');

-- Mostra estrutura das tabelas
SHOW CREATE TABLE modulos;
SHOW CREATE TABLE playlists;
SHOW CREATE TABLE playlist_videos;

-- Verifica coluna modulo_id em videos
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id';

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

