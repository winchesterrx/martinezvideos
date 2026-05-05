-- Script para criar tabela de clientes
-- Clientes têm acesso ao sistema para ver vídeos, comentar, curtir, etc.

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(191) UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cpf_cnpj VARCHAR(20),
    estado_id INT,
    municipio_id INT,
    endereco TEXT,
    observacoes TEXT,
    ativo CHAR(1) DEFAULT 'S',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estado_id) REFERENCES UF(id) ON DELETE SET NULL,
    FOREIGN KEY (municipio_id) REFERENCES municipio(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento Cliente-Setor (Many-to-Many)
CREATE TABLE IF NOT EXISTS cliente_setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    setor_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cliente_setor (cliente_id, setor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para melhor performance
-- Usando prefixo no índice de nome para evitar erro de tamanho
CREATE INDEX idx_cliente_ativo ON clientes(ativo);
CREATE INDEX idx_cliente_nome ON clientes(nome(100));

