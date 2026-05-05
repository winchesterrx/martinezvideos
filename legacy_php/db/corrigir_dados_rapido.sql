-- Script RÁPIDO para corrigir dados com encoding incorreto
-- Execute este script no phpMyAdmin ou MySQL Workbench
-- IMPORTANTE: Faça backup antes!

-- Corrige vídeos
UPDATE videos 
SET titulo = CONVERT(CAST(CONVERT(titulo USING latin1) AS BINARY) USING utf8mb4),
    descricao = CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4)
WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%';

-- Corrige comentários
UPDATE comentarios 
SET conteudo = CONVERT(CAST(CONVERT(conteudo USING latin1) AS BINARY) USING utf8mb4)
WHERE conteudo LIKE '%Ã%';

-- Corrige respostas
UPDATE respostas 
SET conteudo = CONVERT(CAST(CONVERT(conteudo USING latin1) AS BINARY) USING utf8mb4)
WHERE conteudo LIKE '%Ã%';

-- Corrige setores
UPDATE setores 
SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4)
WHERE nome LIKE '%Ã%';

-- Corrige módulos
UPDATE modulos 
SET nome = CONVERT(CAST(CONVERT(nome USING latin1) AS BINARY) USING utf8mb4),
    descricao = CONVERT(CAST(CONVERT(descricao USING latin1) AS BINARY) USING utf8mb4)
WHERE nome LIKE '%Ã%' OR descricao LIKE '%Ã%';

-- Verifica quantos registros foram corrigidos
SELECT 'Vídeos corrigidos:' AS tipo, COUNT(*) AS total FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%'
UNION ALL
SELECT 'Comentários corrigidos:', COUNT(*) FROM comentarios WHERE conteudo LIKE '%Ã%'
UNION ALL
SELECT 'Setores corrigidos:', COUNT(*) FROM setores WHERE nome LIKE '%Ã%';

