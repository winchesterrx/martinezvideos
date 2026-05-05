-- Script para inserir setores faltantes na tabela setores
-- Apenas os setores individuais, SEM as bancadas

-- Inserir apenas se não existir (usando IGNORE para evitar duplicatas)
INSERT IGNORE INTO setores (nome, ativo) VALUES
('Saúde', 'S'),
('Assistência Social', 'S'),
('Ensino', 'S'),
('Biblioteca', 'S'),
('Flowdocs', 'S'),
('Tributos', 'S'),
('Ouvidoria', 'S'),
('Protocolo', 'S'),
('Compras', 'S'),
('Licitação', 'S'),
('Frotas', 'S'),
('Almoxarifado', 'S'),
('Patrimônio', 'S'),
('Contabilidade', 'S'),
('Custos', 'S'),
('Terceiro Setor', 'S'),
('Controle Interno', 'S'),
('Gestor Municipal', 'S'),
('Documentos Eletrônicos', 'S'),
('Recursos Humanos', 'S'),
('Folha de Pagamento', 'S'),
('Administração', 'S');

