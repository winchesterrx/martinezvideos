-- Script completo para criar foreign key na tabela videos
-- Este script verifica e corrige tudo antes de criar

-- ============================================
-- PASSO 1: Verificar engine da tabela
-- ============================================
-- Execute este comando primeiro para ver o engine:
-- SHOW CREATE TABLE videos;

-- Se o engine não for InnoDB, converta:
-- ALTER TABLE videos ENGINE=InnoDB;

-- ============================================
-- PASSO 2: Verificar índices existentes
-- ============================================
-- Lista todos os índices na coluna modulo_id
SHOW INDEX FROM videos WHERE Column_name = 'modulo_id';

-- ============================================
-- PASSO 3: Remover índices duplicados (se houver)
-- ============================================
-- Se houver múltiplos índices, remova os extras:
-- DROP INDEX nome_do_indice ON videos;

-- ============================================
-- PASSO 4: Verificar foreign keys existentes
-- ============================================
-- Lista todas as foreign keys da tabela videos
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ============================================
-- PASSO 5: Remover foreign keys antigas (se existirem)
-- ============================================
-- Se aparecer alguma foreign key acima, remova com:
-- ALTER TABLE videos DROP FOREIGN KEY nome_da_fk;

-- ============================================
-- PASSO 6: Garantir que o índice existe
-- ============================================
-- Cria o índice se não existir (ou recria se necessário)
DROP INDEX IF EXISTS idx_modulo_id ON videos;
CREATE INDEX idx_modulo_id ON videos (modulo_id);

-- ============================================
-- PASSO 7: Criar a foreign key
-- ============================================
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- ============================================
-- PASSO 8: Verificar se foi criada
-- ============================================
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND CONSTRAINT_NAME = 'fk_videos_modulo';

