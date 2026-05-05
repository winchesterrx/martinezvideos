-- ============================================
-- RECRIAR MÓDULOS DO ZERO - PASSO A PASSO
-- Execute cada bloco separadamente
-- ============================================

-- ============================================
-- BLOCO 1: LIMPEZA - Remove tudo relacionado a módulos
-- ============================================

-- 1.1 Remove foreign key da tabela videos (se existir)
ALTER TABLE videos DROP FOREIGN KEY fk_videos_modulo;

-- 1.2 Remove a coluna modulo_id da tabela videos (se existir)
ALTER TABLE videos DROP COLUMN modulo_id;

-- 1.3 Remove índice da tabela videos (se existir)
DROP INDEX idx_modulo_id ON videos;

-- 1.4 Remove a tabela modulos completamente
DROP TABLE IF EXISTS modulos;

-- ============================================
-- BLOCO 2: CRIAÇÃO - Cria tudo do zero corretamente
-- ============================================

-- 2.1 Cria a tabela modulos com setor_id (nome CORRETO)
CREATE TABLE modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setor_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    icone VARCHAR(100) DEFAULT 'fas fa-cube',
    cor VARCHAR(7) DEFAULT '#6366f1',
    ativo CHAR(1) DEFAULT 'S',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setor_id (setor_id),
    INDEX idx_ativo (ativo),
    CONSTRAINT fk_modulos_setor 
        FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 Adiciona coluna modulo_id na tabela videos
ALTER TABLE videos 
ADD COLUMN modulo_id INT NULL AFTER setor_id;

-- 2.3 Cria índice na coluna modulo_id
CREATE INDEX idx_modulo_id ON videos (modulo_id);

-- 2.4 Adiciona foreign key na tabela videos
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- ============================================
-- BLOCO 3: VERIFICAÇÃO - Confirma que está tudo correto
-- ============================================

-- 3.1 Ver estrutura da tabela modulos (deve mostrar setor_id)
DESCRIBE modulos;

-- 3.2 Ver estrutura da tabela videos (deve mostrar modulo_id)
DESCRIBE videos;

