-- Script para criar tabelas de sistemas e relacionamento com usuários

-- Tabela de Sistemas
CREATE TABLE IF NOT EXISTS sistemas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    icone VARCHAR(100) DEFAULT 'fas fa-cog',
    cor VARCHAR(7) DEFAULT '#ff6f00',
    ativo CHAR(1) DEFAULT 'S',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento Usuário-Sistema (Many-to-Many)
CREATE TABLE IF NOT EXISTS usuario_sistemas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    sistema_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (sistema_id) REFERENCES sistemas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_sistema (usuario_id, sistema_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir alguns sistemas padrão baseados no organograma
INSERT INTO sistemas (nome, descricao, icone, cor) VALUES
('Bancada 1 - Saúde', 'Sistema de gestão da área de saúde', 'fas fa-heartbeat', '#e74c3c'),
('Bancada 2 - Tributos', 'Sistema de gestão tributária', 'fas fa-file-invoice-dollar', '#3498db'),
('Bancada 3 - Compras/Licitação', 'Sistema de compras e licitações', 'fas fa-shopping-cart', '#2ecc71'),
('Bancada 4 - Contabilidade', 'Sistema contábil e financeiro', 'fas fa-calculator', '#f39c12'),
('Bancada 5 - Recursos Humanos', 'Sistema de gestão de RH', 'fas fa-users', '#9b59b6'),
('Administração', 'Sistema administrativo geral', 'fas fa-building', '#34495e'),
('Flowdocs', 'Sistema de documentos e fluxos', 'fas fa-file-alt', '#16a085'),
('Assistência Social', 'Sistema de assistência social', 'fas fa-hands-helping', '#e67e22'),
('Ensino', 'Sistema de gestão educacional', 'fas fa-graduation-cap', '#1abc9c'),
('Biblioteca', 'Sistema de biblioteca', 'fas fa-book', '#8e44ad'),
('Ouvidoria', 'Sistema de ouvidoria', 'fas fa-comments', '#27ae60'),
('Protocolo', 'Sistema de protocolo', 'fas fa-clipboard-list', '#c0392b'),
('Frotas', 'Sistema de gestão de frotas', 'fas fa-car', '#d35400'),
('Almoxarifado', 'Sistema de almoxarifado', 'fas fa-warehouse', '#7f8c8d'),
('Patrimônio', 'Sistema de gestão patrimonial', 'fas fa-boxes', '#95a5a6'),
('Custos', 'Sistema de custos', 'fas fa-chart-line', '#2980b9'),
('Terceiro Setor', 'Sistema do terceiro setor', 'fas fa-hand-holding-heart', '#e91e63'),
('Controle Interno', 'Sistema de controle interno', 'fas fa-shield-alt', '#673ab7'),
('Gestor Municipal', 'Sistema do gestor municipal', 'fas fa-user-tie', '#009688'),
('Documentos Eletrônicos', 'Sistema de documentos eletrônicos', 'fas fa-file-pdf', '#ff5722'),
('Folha de Pagamento', 'Sistema de folha de pagamento', 'fas fa-money-check-alt', '#4caf50');

