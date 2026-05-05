-- ============================================
-- SISTEMA DE SEQUÊNCIAS DE VÍDEOS - VERSÃO SIMPLES
-- ============================================
-- Execute este script se a versão dinâmica der erro
-- Execute cada bloco separadamente se necessário
-- ============================================

-- 1. Criar tabela de sequências
CREATE TABLE IF NOT EXISTS sequencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL COMMENT 'Título da sequência (ex: "Curso de Consultório - Parte 1, 2, 3...")',
    setor_id INT NOT NULL,
    modulo_id INT NULL,
    descricao TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL,
    INDEX idx_setor_modulo (setor_id, modulo_id),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Adicionar campo is_sequencia
-- Execute este comando mesmo se der aviso de que a coluna já existe
ALTER TABLE videos 
ADD COLUMN is_sequencia TINYINT(1) DEFAULT 0 COMMENT '1 = faz parte de sequência, 0 = não';

-- 3. Adicionar campo sequencia_id
-- Execute este comando mesmo se der aviso de que a coluna já existe
ALTER TABLE videos 
ADD COLUMN sequencia_id INT NULL COMMENT 'ID do grupo de sequência';

-- 4. Adicionar campo sequencia_ordem
-- Execute este comando mesmo se der aviso de que a coluna já existe
ALTER TABLE videos 
ADD COLUMN sequencia_ordem INT NULL COMMENT 'Ordem na sequência (1, 2, 3...)';

-- 5. Criar índices
-- Execute mesmo se der aviso de que já existe
CREATE INDEX idx_videos_sequencia ON videos(sequencia_id, sequencia_ordem);
CREATE INDEX idx_videos_is_sequencia ON videos(is_sequencia);

-- 6. Adicionar foreign key
-- Execute este comando mesmo se der aviso de que já existe
-- Se der erro de foreign key duplicada, ignore e continue
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_sequencia 
FOREIGN KEY (sequencia_id) REFERENCES sequencias(id) ON DELETE SET NULL;

-- 7. Verificar estrutura criada (opcional - pode dar erro de permissão)
-- Se der erro de permissão no information_schema, ignore esta parte
-- A estrutura foi criada mesmo sem essa verificação

