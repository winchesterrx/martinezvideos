-- ============================================
-- SISTEMA DE HISTÓRICO E NOTIFICAÇÕES
-- ============================================
-- Este script cria a estrutura necessária para
-- histórico completo e sistema de notificações
-- ============================================

-- Tabela para favoritos de setores/módulos
CREATE TABLE IF NOT EXISTS usuario_favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('setor', 'modulo') NOT NULL,
    item_id INT NOT NULL COMMENT 'ID do setor ou módulo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorito (usuario_id, tipo, item_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo_item (tipo, item_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL COMMENT 'video_novo, comentario, resposta, live',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT,
    link VARCHAR(500),
    lida CHAR(1) DEFAULT 'N',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_lida (lida),
    INDEX idx_created (created_at),
    INDEX idx_tipo (tipo),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para configurações de notificações do usuário
CREATE TABLE IF NOT EXISTS usuario_notificacoes_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    notificar_videos_novos CHAR(1) DEFAULT 'S',
    notificar_comentarios CHAR(1) DEFAULT 'S',
    notificar_respostas CHAR(1) DEFAULT 'S',
    notificar_lives CHAR(1) DEFAULT 'S',
    notificar_apenas_favoritos CHAR(1) DEFAULT 'N' COMMENT 'S = apenas setores/módulos favoritos',
    email_notificacoes CHAR(1) DEFAULT 'N',
    push_notificacoes CHAR(1) DEFAULT 'S',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ADICIONAR COLUNAS NA TABELA usuario_historico
-- ============================================
-- Execute estas instruções UMA POR VEZ se as colunas não existirem
-- Se der erro dizendo que a coluna já existe, pode ignorar e continuar

-- Adicionar coluna duracao_video
ALTER TABLE usuario_historico 
ADD COLUMN duracao_video INT DEFAULT 0 COMMENT 'Duração total do vídeo em segundos';

-- Adicionar coluna porcentagem_assistida
ALTER TABLE usuario_historico 
ADD COLUMN porcentagem_assistida DECIMAL(5,2) DEFAULT 0 COMMENT 'Porcentagem assistida (0-100)';

-- ============================================
-- ADICIONAR ÍNDICES PARA MELHOR PERFORMANCE
-- ============================================
-- Execute estas instruções UMA POR VEZ se os índices não existirem
-- Se der erro dizendo que o índice já existe, pode ignorar e continuar

-- Adicionar índice idx_completou
ALTER TABLE usuario_historico 
ADD INDEX idx_completou (completou);

-- Adicionar índice idx_tempo_assistido
ALTER TABLE usuario_historico 
ADD INDEX idx_tempo_assistido (tempo_assistido);
