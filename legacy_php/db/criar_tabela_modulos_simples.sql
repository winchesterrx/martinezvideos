-- Script SIMPLIFICADO para criar tabela de módulos
-- Use este script se o anterior der erro de foreign key
-- IMPORTANTE: Certifique-se de que a tabela 'setores' já existe!

-- Primeiro, cria a tabela sem foreign key
CREATE TABLE IF NOT EXISTS modulos (
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
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Depois, adiciona a foreign key (só se a tabela setores existir)
-- Se der erro aqui, significa que a tabela setores não existe
ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

