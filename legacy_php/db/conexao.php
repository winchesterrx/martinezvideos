<?php
include("config.php");

$conexao = mysqli_init();
if (!$conexao) {
    die("mysqli_init failed");
}

// Configura timeout de conexão para evitar hangs longos
$conexao->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

try {
    // Porta padrão do MySQL é 3306 - é importante passar explicitamente em alguns ambientes
    if (!$conexao->real_connect(SERVIDOR, USUARIO, SENHA, BANCO, 3306)) {
        die("Erro ao conectar ao banco (" . mysqli_connect_errno() . "): " . mysqli_connect_error());
    }
} catch (mysqli_sql_exception $e) {
    die("Erro de Conexão: " . $e->getMessage());
}

// Configura charset para UTF-8 (utf8mb4) para suportar caracteres especiais e emojis
mysqli_set_charset($conexao, "utf8mb4");

// Define o charset da conexão também via query
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
mysqli_query($conexao, "SET CHARACTER SET utf8mb4");
mysqli_query($conexao, "SET character_set_connection=utf8mb4");
mysqli_query($conexao, "SET character_set_client=utf8mb4");
mysqli_query($conexao, "SET character_set_results=utf8mb4");
?>