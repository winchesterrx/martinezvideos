-- Script para criar tabela de relacionamento Usuário-Setor (Many-to-Many)
-- Permite que um usuário faça parte de múltiplos setores

CREATE TABLE IF NOT EXISTS usuario_setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    setor_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_setor (usuario_id, setor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

