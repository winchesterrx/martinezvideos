-- Script COMPLETO para corrigir charset de TODAS as tabelas para utf8mb4
-- CORRIGIDO: Trata índices UNIQUE em colunas VARCHAR(255)
-- Execute este script para garantir que todas as tabelas e colunas usem utf8mb4

-- 1. Altera o charset do banco de dados
ALTER DATABASE martinezvideo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Converte todas as tabelas principais para utf8mb4
ALTER TABLE videos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE comentarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE respostas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE setores CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE modulos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE clientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE curtidas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE playlists CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE playlist_videos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuario_setores CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3. Corrige colunas específicas que armazenam texto (garantia extra)
ALTER TABLE videos MODIFY titulo VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE videos MODIFY descricao TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE comentarios MODIFY conteudo TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE respostas MODIFY conteudo TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE setores MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE modulos MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE modulos MODIFY descricao TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 4. CORREÇÃO: Remove índice UNIQUE da coluna email antes de modificar
-- Verifica e remove o índice se existir
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'martinezvideo' 
    AND TABLE_NAME = 'usuarios' 
    AND INDEX_NAME = 'email'
);

SET @sql = IF(@index_exists > 0, 
    'ALTER TABLE usuarios DROP INDEX email', 
    'SELECT "Índice email não existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Modifica a coluna email para utf8mb4
ALTER TABLE usuarios MODIFY email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 6. Recria o índice UNIQUE com prefixo (191 caracteres = 764 bytes, dentro do limite de 767)
ALTER TABLE usuarios ADD UNIQUE KEY email (email(191));

-- 7. Repete o processo para a tabela clientes (se tiver índice UNIQUE em email)
SET @index_exists_clientes = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'martinezvideo' 
    AND TABLE_NAME = 'clientes' 
    AND INDEX_NAME = 'email'
);

SET @sql_clientes = IF(@index_exists_clientes > 0, 
    'ALTER TABLE clientes DROP INDEX email', 
    'SELECT "Índice email não existe em clientes" AS info'
);
PREPARE stmt_clientes FROM @sql_clientes;
EXECUTE stmt_clientes;
DEALLOCATE PREPARE stmt_clientes;

-- Modifica a coluna email de clientes
ALTER TABLE clientes MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE clientes MODIFY email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Recria o índice UNIQUE com prefixo para clientes (se existia antes)
SET @index_exists_clientes_after = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'martinezvideo' 
    AND TABLE_NAME = 'clientes' 
    AND INDEX_NAME = 'email'
);

SET @sql_clientes_add = IF(@index_exists_clientes > 0 AND @index_exists_clientes_after = 0, 
    'ALTER TABLE clientes ADD UNIQUE KEY email (email(191))', 
    'SELECT "Índice email já existe ou não precisa ser recriado" AS info'
);
PREPARE stmt_clientes_add FROM @sql_clientes_add;
EXECUTE stmt_clientes_add;
DEALLOCATE PREPARE stmt_clientes_add;

-- 8. Corrige playlists
ALTER TABLE playlists MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE playlists MODIFY descricao TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 9. Verifica o resultado
SHOW CREATE DATABASE martinezvideo;
SHOW CREATE TABLE usuarios;
SHOW CREATE TABLE clientes;

