-- =====================================================
-- SCRIPT DE VERIFICAÇÃO: ESTRUTURA ATUAL DO BANCO
-- =====================================================
-- Execute este script e me envie os resultados para análise
-- =====================================================

-- =====================================================
-- 1. VERIFICAR TABELAS EXISTENTES
-- =====================================================

SELECT 
    'Tabelas relacionadas a módulos e playlists:' AS tipo,
    TABLE_NAME AS nome_tabela,
    TABLE_ROWS AS total_registros,
    CREATE_TIME AS data_criacao
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('modulos', 'playlists', 'playlist_videos', 'setores', 'videos', 'usuarios')
ORDER BY TABLE_NAME;

-- =====================================================
-- 2. VERIFICAR ESTRUTURA DA TABELA MODULOS (SE EXISTIR)
-- =====================================================

SELECT 
    'Estrutura da tabela modulos:' AS tipo,
    COLUMN_NAME AS coluna,
    COLUMN_TYPE AS tipo,
    IS_NULLABLE AS permite_null,
    COLUMN_DEFAULT AS valor_padrao,
    COLUMN_KEY AS chave
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'modulos'
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- 3. VERIFICAR FOREIGN KEYS DA TABELA MODULOS
-- =====================================================

SELECT 
    'Foreign Keys da tabela modulos:' AS tipo,
    CONSTRAINT_NAME AS nome_fk,
    COLUMN_NAME AS coluna,
    REFERENCED_TABLE_NAME AS tabela_referenciada,
    REFERENCED_COLUMN_NAME AS coluna_referenciada
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'modulos'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- 4. VERIFICAR CAMPO modulo_id NA TABELA VIDEOS
-- =====================================================

SELECT 
    'Campo modulo_id na tabela videos:' AS tipo,
    COLUMN_NAME AS coluna,
    COLUMN_TYPE AS tipo,
    IS_NULLABLE AS permite_null,
    COLUMN_DEFAULT AS valor_padrao
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id';

-- =====================================================
-- 5. VERIFICAR FOREIGN KEYS DA TABELA VIDEOS RELACIONADAS A modulo_id
-- =====================================================

SELECT 
    'Foreign Keys da tabela videos (modulo_id):' AS tipo,
    CONSTRAINT_NAME AS nome_fk,
    COLUMN_NAME AS coluna,
    REFERENCED_TABLE_NAME AS tabela_referenciada,
    REFERENCED_COLUMN_NAME AS coluna_referenciada
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- 6. VERIFICAR ÍNDICES DA TABELA VIDEOS RELACIONADOS A modulo_id
-- =====================================================

SELECT 
    'Índices da tabela videos (modulo_id):' AS tipo,
    INDEX_NAME AS nome_indice,
    COLUMN_NAME AS coluna,
    NON_UNIQUE AS nao_unico,
    SEQ_IN_INDEX AS sequencia
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'videos'
    AND COLUMN_NAME = 'modulo_id';

-- =====================================================
-- 7. VERIFICAR ESTRUTURA DA TABELA PLAYLISTS (SE EXISTIR)
-- =====================================================

SELECT 
    'Estrutura da tabela playlists:' AS tipo,
    COLUMN_NAME AS coluna,
    COLUMN_TYPE AS tipo,
    IS_NULLABLE AS permite_null,
    COLUMN_DEFAULT AS valor_padrao,
    COLUMN_KEY AS chave
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'playlists'
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- 8. VERIFICAR ESTRUTURA DA TABELA PLAYLIST_VIDEOS (SE EXISTIR)
-- =====================================================

SELECT 
    'Estrutura da tabela playlist_videos:' AS tipo,
    COLUMN_NAME AS coluna,
    COLUMN_TYPE AS tipo,
    IS_NULLABLE AS permite_null,
    COLUMN_DEFAULT AS valor_padrao,
    COLUMN_KEY AS chave
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'playlist_videos'
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- 9. VERIFICAR FOREIGN KEYS DAS TABELAS DE PLAYLIST
-- =====================================================

SELECT 
    'Foreign Keys das tabelas de playlist:' AS tipo,
    TABLE_NAME AS tabela,
    CONSTRAINT_NAME AS nome_fk,
    COLUMN_NAME AS coluna,
    REFERENCED_TABLE_NAME AS tabela_referenciada,
    REFERENCED_COLUMN_NAME AS coluna_referenciada
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('playlists', 'playlist_videos')
    AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- =====================================================
-- 10. VERIFICAR DADOS INCOMPATÍVEIS (se houver)
-- =====================================================

-- Verifica se há videos com modulo_id que não existe em modulos
SELECT 
    'Vídeos com modulo_id inválido:' AS tipo,
    COUNT(*) AS total
FROM videos v
LEFT JOIN modulos m ON v.modulo_id = m.id
WHERE v.modulo_id IS NOT NULL 
    AND m.id IS NULL;

-- Verifica se há modulos com setor_id que não existe em setores
SELECT 
    'Módulos com setor_id inválido:' AS tipo,
    COUNT(*) AS total
FROM modulos m
LEFT JOIN setores s ON m.setor_id = s.id
WHERE s.id IS NULL;

-- =====================================================
-- 11. RESUMO GERAL
-- =====================================================

SELECT 
    'RESUMO GERAL' AS tipo,
    CASE 
        WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'modulos') 
        THEN 'Tabela modulos: EXISTE'
        ELSE 'Tabela modulos: NÃO EXISTE'
    END AS status_modulos,
    CASE 
        WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'videos' AND COLUMN_NAME = 'modulo_id') 
        THEN 'Campo modulo_id em videos: EXISTE'
        ELSE 'Campo modulo_id em videos: NÃO EXISTE'
    END AS status_modulo_id,
    CASE 
        WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'playlists') 
        THEN 'Tabela playlists: EXISTE'
        ELSE 'Tabela playlists: NÃO EXISTE'
    END AS status_playlists,
    CASE 
        WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'playlist_videos') 
        THEN 'Tabela playlist_videos: EXISTE'
        ELSE 'Tabela playlist_videos: NÃO EXISTE'
    END AS status_playlist_videos;

-- =====================================================
-- FIM DO SCRIPT DE VERIFICAÇÃO
-- =====================================================

