-- ============================================
-- SISTEMA DE SEQUÊNCIAS DE VÍDEOS - VERSÃO ULTRA SIMPLES
-- ============================================
-- Execute este script linha por linha se necessário
-- Ignore avisos de "coluna já existe" ou "índice já existe"
-- ============================================

-- PASSO 1: Criar tabela de sequências
CREATE TABLE IF NOT EXISTS sequencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL COMMENT 'Título da sequência',
    setor_id INT NOT NULL,
    modulo_id INT NULL,
    descricao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL,
    INDEX idx_setor_modulo (setor_id, modulo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PASSO 2: Adicionar campo is_sequencia
-- Se der erro de coluna já existe, ignore e continue
ALTER TABLE videos 
ADD COLUMN is_sequencia TINYINT(1) DEFAULT 0 COMMENT '1 = faz parte de sequência, 0 = não';

-- PASSO 3: Adicionar campo sequencia_id
-- Se der erro de coluna já existe, ignore e continue
ALTER TABLE videos 
ADD COLUMN sequencia_id INT NULL COMMENT 'ID do grupo de sequência';

-- PASSO 4: Adicionar campo sequencia_ordem
-- Se der erro de coluna já existe, ignore e continue
ALTER TABLE videos 
ADD COLUMN sequencia_ordem INT NULL COMMENT 'Ordem na sequência (1, 2, 3...)';

-- PASSO 5: Criar índice idx_videos_sequencia
-- Se der erro de índice já existe, ignore e continue
CREATE INDEX idx_videos_sequencia ON videos(sequencia_id, sequencia_ordem);

-- PASSO 6: Criar índice idx_videos_is_sequencia
-- Se der erro de índice já existe, ignore e continue
CREATE INDEX idx_videos_is_sequencia ON videos(is_sequencia);

-- PASSO 7: Adicionar foreign key
-- Se der erro de foreign key já existe, ignore e continue
-- Se der erro de foreign key duplicada, pode ser que já exista, ignore
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_sequencia 
FOREIGN KEY (sequencia_id) REFERENCES sequencias(id) ON DELETE SET NULL;

