-- ============================================
-- SISTEMA DE SEQUÊNCIAS DE VÍDEOS - VERSÃO CORRIGIDA
-- ============================================
-- Este script cria a estrutura necessária para
-- organizar vídeos em sequências numeradas
-- ============================================

-- 1. Criar tabela de sequências
CREATE TABLE IF NOT EXISTS sequencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL COMMENT 'Título da sequência (ex: "Curso de Consultório - Parte 1, 2, 3...")',
    setor_id INT NOT NULL,
    modulo_id INT NULL,
    descricao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL,
    INDEX idx_setor_modulo (setor_id, modulo_id),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Adicionar campos na tabela videos (versão simplificada)
-- Verificar e adicionar is_sequencia
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'videos' 
    AND COLUMN_NAME = 'is_sequencia'
);

SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE videos ADD COLUMN is_sequencia TINYINT(1) DEFAULT 0 COMMENT ''1 = faz parte de sequência, 0 = não'''),
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar sequencia_id
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'videos' 
    AND COLUMN_NAME = 'sequencia_id'
);

SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE videos ADD COLUMN sequencia_id INT NULL COMMENT ''ID do grupo de sequência'''),
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar sequencia_ordem
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'videos' 
    AND COLUMN_NAME = 'sequencia_ordem'
);

SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE videos ADD COLUMN sequencia_ordem INT NULL COMMENT ''Ordem na sequência (1, 2, 3...)'''),
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Criar índices (usando IF NOT EXISTS através de verificação)
-- Índice idx_videos_sequencia
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'videos' 
    AND INDEX_NAME = 'idx_videos_sequencia'
);

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_videos_sequencia ON videos(sequencia_id, sequencia_ordem)',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índice idx_videos_is_sequencia
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'videos' 
    AND INDEX_NAME = 'idx_videos_is_sequencia'
);

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_videos_is_sequencia ON videos(is_sequencia)',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Adicionar foreign key (se não existir)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'videos' 
    AND CONSTRAINT_NAME = 'fk_videos_sequencia'
);

-- Se a foreign key não existe, adicionar
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE videos ADD CONSTRAINT fk_videos_sequencia FOREIGN KEY (sequencia_id) REFERENCES sequencias(id) ON DELETE SET NULL',
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Verificar estrutura criada
SELECT 
    'Tabela sequencias criada com sucesso!' AS status,
    COUNT(*) AS total_sequencias
FROM sequencias;

SELECT 
    'Campos adicionados na tabela videos' AS status,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'videos'
AND COLUMN_NAME IN ('is_sequencia', 'sequencia_id', 'sequencia_ordem');

SELECT 
    'Índices criados' AS status,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'videos'
AND INDEX_NAME LIKE '%sequencia%';

