-- Script para verificar e corrigir charset do banco de dados
-- Execute este script para garantir que todas as tabelas usem utf8mb4

-- Verifica o charset atual do banco
SHOW CREATE DATABASE martinezvideo;

-- Altera o charset do banco (se necessário)
-- ALTER DATABASE martinezvideo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verifica charset das tabelas principais
SHOW CREATE TABLE videos;
SHOW CREATE TABLE comentarios;
SHOW CREATE TABLE respostas;
SHOW CREATE TABLE setores;
SHOW CREATE TABLE modulos;
SHOW CREATE TABLE usuarios;
SHOW CREATE TABLE clientes;

-- Para corrigir o charset de uma tabela específica, use:
-- ALTER TABLE videos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE comentarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE respostas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE setores CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE modulos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE clientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Para corrigir colunas específicas (se necessário):
-- ALTER TABLE videos MODIFY titulo VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE videos MODIFY descricao TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE comentarios MODIFY conteudo TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE respostas MODIFY conteudo TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE setores MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE modulos MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE modulos MODIFY descricao TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE usuarios MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE clientes MODIFY nome VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

