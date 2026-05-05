-- Script para CORRIGIR DADOS EXISTENTES com encoding incorreto
-- Este script converte dados que foram salvos com encoding errado (latin1/iso-8859-1) para utf8mb4

-- IMPORTANTE: Faça backup do banco antes de executar este script!

-- 1. Verifica dados com encoding incorreto (exemplo: "MÃ©dico" em vez de "Médico")
SELECT 'Verificando dados com encoding incorreto...' AS info;

-- Exemplos de padrões comuns de encoding incorreto:
-- "MÃ©dico" = "Médico" (latin1 interpretado como utf8)
-- "apresentaÃ§Ã£o" = "apresentação" (latin1 interpretado como utf8)
-- "Ã©" = "é"
-- "Ã§" = "ç"
-- "Ã£" = "ã"
-- "Ã³" = "ó"

-- 2. Corrige dados na tabela videos
-- Converte de latin1 para utf8mb4
UPDATE videos 
SET titulo = CONVERT(CAST(CONVERT(titulo USING latin1) AS BINARY) USING utf8mb4),
    descricao = CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4)
WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%';

-- 3. Corrige dados na tabela comentarios
UPDATE comentarios 
SET conteudo = CONVERT(CAST(CONVERT(conteudo USING latin1) AS BINARY) USING utf8mb4)
WHERE conteudo LIKE '%Ã%';

-- 4. Corrige dados na tabela respostas
UPDATE respostas 
SET conteudo = CONVERT(CAST(CONVERT(conteudo USING latin1) AS BINARY) USING utf8mb4)
WHERE conteudo LIKE '%Ã%';

-- 5. Corrige dados na tabela setores
UPDATE setores 
SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4)
WHERE nome LIKE '%Ã%';

-- 6. Corrige dados na tabela modulos
UPDATE modulos 
SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4),
    descricao = CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4)
WHERE nome LIKE '%Ã%' OR descricao LIKE '%Ã%';

-- 7. Corrige dados na tabela usuarios
UPDATE usuarios 
SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4)
WHERE nome LIKE '%Ã%';

-- 8. Corrige dados na tabela clientes
UPDATE clientes 
SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4)
WHERE nome LIKE '%Ã%';

-- 9. Verifica se ainda há dados com encoding incorreto
SELECT 'Verificação final - Dados ainda com encoding incorreto:' AS info;
SELECT COUNT(*) AS videos_com_erro FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%';
SELECT COUNT(*) AS comentarios_com_erro FROM comentarios WHERE conteudo LIKE '%Ã%';
SELECT COUNT(*) AS setores_com_erro FROM setores WHERE nome LIKE '%Ã%';

