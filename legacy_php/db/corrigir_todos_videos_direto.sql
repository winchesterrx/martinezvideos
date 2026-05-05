-- Script DIRETO para corrigir TODOS os vídeos com encoding incorreto
-- Este script faz correções específicas baseadas em padrões conhecidos
-- IMPORTANTE: Faça backup antes!

-- 1. Corrige padrões específicos conhecidos
UPDATE videos 
SET titulo = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    titulo,
    'MÃ Ã Ã Â©dico', 'Médico'),
    'MÃ Ã©dico', 'Médico'),
    'MÃ©dico', 'Médico'),
    'apresentaÃ¤ÂÃ Â§Ã¤Ã¤Ã¤Â£o', 'apresentação'),
    'apresentaÃ§Ã£o', 'apresentação'),
    'consultÃ Ã Ã Â³rio', 'consultório'),
    'consultÃ³rio', 'consultório'),
    'Ã©', 'é'),
    'Ã§', 'ç'),
    'Ã£', 'ã')
WHERE titulo LIKE '%Ã%';

UPDATE videos 
SET descricao = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    descricao,
    'MÃ Ã Ã Â©dico', 'Médico'),
    'MÃ Ã©dico', 'Médico'),
    'MÃ©dico', 'Médico'),
    'apresentaÃ¤ÂÃ Â§Ã¤Ã¤Ã¤Â£o', 'apresentação'),
    'apresentaÃ§Ã£o', 'apresentação'),
    'consultÃ Ã Ã Â³rio', 'consultório'),
    'consultÃ³rio', 'consultório'),
    'Ã©', 'é'),
    'Ã§', 'ç'),
    'Ã£', 'ã')
WHERE descricao LIKE '%Ã%';

-- 2. Corrige outros padrões comuns
UPDATE videos 
SET titulo = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    titulo,
    'Ã³', 'ó'),
    'Ã¡', 'á'),
    'Ã­', 'í'),
    'Ãº', 'ú'),
    'Ã', 'à'),
    'Ãª', 'ê'),
    'Ã´', 'ô'),
    'Ãµ', 'õ'),
    'Ã', 'À')
WHERE titulo LIKE '%Ã%';

UPDATE videos 
SET descricao = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    descricao,
    'Ã³', 'ó'),
    'Ã¡', 'á'),
    'Ã­', 'í'),
    'Ãº', 'ú'),
    'Ã', 'à'),
    'Ãª', 'ê'),
    'Ã´', 'ô'),
    'Ãµ', 'õ'),
    'Ã', 'À')
WHERE descricao LIKE '%Ã%';

-- 3. Verifica quantos ainda estão com problema
SELECT COUNT(*) as videos_com_problema FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%';

-- 4. Mostra os que ainda têm problema (para correção manual se necessário)
SELECT id, titulo, descricao FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%' LIMIT 10;

