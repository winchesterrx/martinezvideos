-- Correção ESPECÍFICA para o vídeo ID 103
-- Execute este script diretamente no phpMyAdmin

-- Corrige o vídeo 103 que está com "Atendimento MÃ Ã Ã Â©dico"
UPDATE videos 
SET titulo = 'Atendimento Médico',
    descricao = 'video apresentação do novo consultório Médico'
WHERE id = 103;

-- Verifica se foi corrigido
SELECT id, titulo, descricao FROM videos WHERE id = 103;

