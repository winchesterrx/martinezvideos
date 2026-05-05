-- Script SIMPLIFICADO para corrigir apenas o problema do email
-- Use este script se o script completo der erro

-- Remove o índice UNIQUE da coluna email (usuarios)
ALTER TABLE usuarios DROP INDEX email;

-- Modifica a coluna email para utf8mb4
ALTER TABLE usuarios MODIFY email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Recria o índice UNIQUE com prefixo de 191 caracteres (dentro do limite de 767 bytes)
ALTER TABLE usuarios ADD UNIQUE KEY email (email(191));

-- Repete para clientes (se necessário)
-- ALTER TABLE clientes DROP INDEX email;
-- ALTER TABLE clientes MODIFY email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE clientes ADD UNIQUE KEY email (email(191));

