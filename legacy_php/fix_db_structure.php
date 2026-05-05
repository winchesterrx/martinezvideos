<?php
// fix_db_structure.php
// Script para corrigir a estrutura do banco de dados (adicionar coluna senha e tabela cliente_setores)

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db/config.php';

// Conexão direta usando MySQLi
$conexao = new mysqli(SERVIDOR, USUARIO, SENHA, BANCO);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Configurar charset
$conexao->set_charset("utf8mb4");

echo "<h2>Iniciando verificação e correção do banco de dados...</h2><hr>";

// 1. Verificar e corrigir tabela 'clientes' (coluna 'senha')
echo "<h3>1. Verificando tabela 'clientes'</h3>";
$queryCheck = "SHOW COLUMNS FROM clientes LIKE 'senha'";
$resultCheck = $conexao->query($queryCheck);

if ($resultCheck && $resultCheck->num_rows == 0) {
    echo "Coluna 'senha' não encontrada. Adicionando...<br>";
    // Adicionar coluna senha após email (ou onde preferir)
    // VARCHAR(255) para suportar hash
    $alterQuery = "ALTER TABLE clientes ADD COLUMN senha VARCHAR(255) NOT NULL AFTER email";
    
    if ($conexao->query($alterQuery)) {
        echo "<span style='color:green'>Sucesso: Coluna 'senha' adicionada com sucesso!</span><br>";
    } else {
        echo "<span style='color:red'>Erro ao adicionar coluna 'senha': " . $conexao->error . "</span><br>";
    }
} else {
    echo "<span style='color:blue'>A coluna 'senha' já existe.</span><br>";
}

// 2. Verificar e corrigir tabela 'cliente_setores'
echo "<hr><h3>2. Verificando tabela 'cliente_setores'</h3>";
$queryCheckTable = "SHOW TABLES LIKE 'cliente_setores'";
$resultCheckTable = $conexao->query($queryCheckTable);

if ($resultCheckTable && $resultCheckTable->num_rows == 0) {
    echo "Tabela 'cliente_setores' não encontrada. Criando...<br>";
    
    $createTableQuery = "CREATE TABLE `cliente_setores` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `cliente_id` int(11) NOT NULL,
      `setor_id` int(11) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `cliente_id` (`cliente_id`),
      KEY `setor_id` (`setor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    if ($conexao->query($createTableQuery)) {
        echo "<span style='color:green'>Sucesso: Tabela 'cliente_setores' criada com sucesso!</span><br>";
    } else {
        echo "<span style='color:red'>Erro ao criar tabela 'cliente_setores': " . $conexao->error . "</span><br>";
    }
} else {
    echo "<span style='color:blue'>A tabela 'cliente_setores' já existe.</span><br>";
}

echo "<hr><p><b>Processo finalizado.</b> Tente registrar um usuário novamente.</p>";

$conexao->close();
?>
