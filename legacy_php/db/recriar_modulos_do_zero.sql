-- ============================================
-- SCRIPT PARA RECRIAR MÓDULOS DO ZERO
-- Este script remove tudo e recria corretamente
-- Execute passo a passo ou tudo de uma vez
-- ============================================

-- PASSO 1: Remove a foreign key da tabela videos (se existir)
-- Se der erro "não existe", ignore e continue
ALTER TABLE videos DROP FOREIGN KEY fk_videos_modulo;

-- PASSO 2: Remove a coluna modulo_id da tabela videos (se existir)
-- Se der erro "não existe", ignore e continue
ALTER TABLE videos DROP COLUMN modulo_id;

-- PASSO 3: Remove índices da tabela videos (se existirem)
-- Se der erro "não existe", ignore e continue
DROP INDEX idx_modulo_id ON videos;

-- PASSO 4: Remove a tabela modulos completamente (se existir)
-- Se der erro "não existe", ignore e continue
DROP TABLE IF EXISTS modulos;

-- PASSO 5: Cria a tabela modulos do zero com os nomes CORRETOS
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

-- PASSO 6: Adiciona a coluna modulo_id na tabela videos
ALTER TABLE videos 
ADD COLUMN modulo_id INT NULL AFTER setor_id;

-- PASSO 7: Cria índice na coluna modulo_id
CREATE INDEX idx_modulo_id ON videos (modulo_id);

-- PASSO 8: Adiciona foreign key na tabela videos
ALTER TABLE videos 
ADD CONSTRAINT fk_videos_modulo 
FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL;

-- ============================================
-- VERIFICAÇÃO FINAL
-- ============================================
-- Execute estes comandos para verificar se está tudo correto:

-- Ver estrutura da tabela modulos
DESCRIBE modulos;

-- Ver estrutura da tabela videos (verifique se modulo_id existe)
DESCRIBE videos;

-- Ver foreign keys criadas
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('modulos', 'videos')
    AND REFERENCED_TABLE_NAME IS NOT NULL;

