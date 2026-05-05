-- ============================================
-- SISTEMA DE HISTÓRICO E NOTIFICAÇÕES (VERSÃO SEGURA)
-- ============================================
-- Esta versão verifica se as colunas/índices existem antes de adicionar
-- Compatível com todas as versões do MySQL
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
-- PROCEDURE PARA ADICIONAR COLUNAS COM SEGURANÇA
-- ============================================

DELIMITER $$

-- Procedure para adicionar coluna duracao_video
DROP PROCEDURE IF EXISTS add_duracao_video_column$$
CREATE PROCEDURE add_duracao_video_column()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'usuario_historico' 
        AND COLUMN_NAME = 'duracao_video'
    ) THEN
        ALTER TABLE usuario_historico 
        ADD COLUMN duracao_video INT DEFAULT 0 COMMENT 'Duração total do vídeo em segundos';
    END IF;
END$$

-- Procedure para adicionar coluna porcentagem_assistida
DROP PROCEDURE IF EXISTS add_porcentagem_assistida_column$$
CREATE PROCEDURE add_porcentagem_assistida_column()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'usuario_historico' 
        AND COLUMN_NAME = 'porcentagem_assistida'
    ) THEN
        ALTER TABLE usuario_historico 
        ADD COLUMN porcentagem_assistida DECIMAL(5,2) DEFAULT 0 COMMENT 'Porcentagem assistida (0-100)';
    END IF;
END$$

-- Procedure para adicionar índice idx_completou
DROP PROCEDURE IF EXISTS add_idx_completou$$
CREATE PROCEDURE add_idx_completou()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'usuario_historico' 
        AND INDEX_NAME = 'idx_completou'
    ) THEN
        ALTER TABLE usuario_historico 
        ADD INDEX idx_completou (completou);
    END IF;
END$$

-- Procedure para adicionar índice idx_tempo_assistido
DROP PROCEDURE IF EXISTS add_idx_tempo_assistido$$
CREATE PROCEDURE add_idx_tempo_assistido()
BEGIN
    IF NOT EXISTS (
        SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'usuario_historico' 
        AND INDEX_NAME = 'idx_tempo_assistido'
    ) THEN
        ALTER TABLE usuario_historico 
        ADD INDEX idx_tempo_assistido (tempo_assistido);
    END IF;
END$$

DELIMITER ;

-- Executar as procedures
CALL add_duracao_video_column();
CALL add_porcentagem_assistida_column();
CALL add_idx_completou();
CALL add_idx_tempo_assistido();

-- Limpar as procedures após uso
DROP PROCEDURE IF EXISTS add_duracao_video_column;
DROP PROCEDURE IF EXISTS add_porcentagem_assistida_column;
DROP PROCEDURE IF EXISTS add_idx_completou;
DROP PROCEDURE IF EXISTS add_idx_tempo_assistido;

