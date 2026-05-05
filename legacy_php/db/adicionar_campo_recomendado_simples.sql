-- Versão SIMPLES - Execute apenas se a coluna não existir
-- Se der erro dizendo que a coluna já existe, ignore o erro

-- Adiciona campo 'recomendado' na tabela videos
ALTER TABLE videos 
ADD COLUMN recomendado TINYINT(1) DEFAULT 0 COMMENT '1 = vídeo recomendado manualmente' 
AFTER visualizacoes;

-- Adiciona índice para melhorar performance nas consultas de recomendações
CREATE INDEX idx_recomendado ON videos(recomendado);

-- Exemplo: Marcar um vídeo como recomendado
-- UPDATE videos SET recomendado = 1 WHERE id = 1;

