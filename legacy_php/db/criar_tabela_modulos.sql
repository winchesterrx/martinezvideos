-- Script para criar tabela de módulos relacionados a setores
-- IMPORTANTE: A tabela 'setores' deve existir antes de executar este script

-- Tabela de Módulos
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

-- Adiciona a foreign key separadamente (após criar a tabela)
-- Isso evita erros se a tabela setores não existir
ALTER TABLE modulos 
ADD CONSTRAINT fk_modulos_setor 
FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE;

-- Exemplos de módulos para o sistema "Bancada 1 - Saúde"
-- (Execute apenas se o sistema "Bancada 1 - Saúde" existir)
-- INSERT INTO modulos (sistema_id, nome, descricao, icone, cor) 
-- SELECT id, 'Farmácia', 'Módulo de gestão farmacêutica', 'fas fa-pills', '#e74c3c' FROM sistemas WHERE nome = 'Bancada 1 - Saúde' LIMIT 1;
-- INSERT INTO modulos (sistema_id, nome, descricao, icone, cor) 
-- SELECT id, 'Ambulatório', 'Módulo de gestão ambulatorial', 'fas fa-hospital', '#3498db' FROM sistemas WHERE nome = 'Bancada 1 - Saúde' LIMIT 1;
-- INSERT INTO modulos (sistema_id, nome, descricao, icone, cor) 
-- SELECT id, 'Transporte', 'Módulo de transporte de pacientes', 'fas fa-ambulance', '#2ecc71' FROM sistemas WHERE nome = 'Bancada 1 - Saúde' LIMIT 1;
-- INSERT INTO modulos (sistema_id, nome, descricao, icone, cor) 
-- SELECT id, 'Consultório', 'Módulo de gestão de consultórios', 'fas fa-stethoscope', '#f39c12' FROM sistemas WHERE nome = 'Bancada 1 - Saúde' LIMIT 1;

