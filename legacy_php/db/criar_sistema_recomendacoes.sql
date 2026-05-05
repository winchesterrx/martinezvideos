-- ============================================
-- SISTEMA DE RECOMENDAÇÕES INTELIGENTES
-- ============================================
-- Este script cria a estrutura necessária para
-- um sistema de recomendações baseado em histórico
-- ============================================

-- Tabela para histórico de visualizações do usuário (se não existir)
CREATE TABLE IF NOT EXISTS usuario_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL COMMENT 'ID do usuário logado (NULL para anônimos)',
    video_id INT NOT NULL,
    setor_id INT NOT NULL,
    modulo_id INT NULL,
    visualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    tempo_assistido INT DEFAULT 0 COMMENT 'Tempo assistido em segundos',
    completou TINYINT(1) DEFAULT 0 COMMENT '1 = assistiu até o final',
    INDEX idx_usuario (usuario_id),
    INDEX idx_video (video_id),
    INDEX idx_setor (setor_id),
    INDEX idx_modulo (modulo_id),
    INDEX idx_visualizado (visualizado_em),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para preferências do usuário (cache de recomendações)
CREATE TABLE IF NOT EXISTS usuario_preferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    setores_favoritos TEXT COMMENT 'JSON com setores mais acessados',
    modulos_favoritos TEXT COMMENT 'JSON com módulos mais acessados',
    ultima_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

