<?php
session_start();
include 'db/conexao.php';
include 'functions.php'; // Inclui a função de log


// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Método não permitido.';
    exit;
}

// Recupera os dados do formulário
$nome = $_POST['nome'] ?? null;
$cidade_id = $_POST['cidade'] ?? null;
$email = $_POST['email'] ?? null;
$senha = $_POST['senha'] ?? null;

// Validação básica
if (!$nome || !$cidade_id || !$email || !$senha) {
    echo 'Todos os campos são obrigatórios.';
    exit;
}

// Verifica se o email já está registrado
$sql_verifica_email = "SELECT id FROM usuarios WHERE email = ?";
$stmt = $conexao->prepare($sql_verifica_email);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo 'Este email já está registrado.';
    exit;
}

// Criptografa a senha
$senha_hash = password_hash($senha, PASSWORD_BCRYPT);

// Insere o novo usuário no banco de dados
$sql_cadastro = "INSERT INTO usuarios (nome, cidade_id, email, senha) VALUES (?, ?, ?, ?)";
$stmt = $conexao->prepare($sql_cadastro);

if ($stmt) {
    $stmt->bind_param("siss", $nome, $cidade_id, $email, $senha_hash);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo 'Conta criada com sucesso.';
        // Redireciona para a página inicial ou faz login automático
        $_SESSION['usuario_id'] = $stmt->insert_id;
        header('Location: index.php'); // Ajuste o caminho para a página inicial
    } else {
        echo 'Erro ao criar conta.';
    }
} else {
    echo 'Erro na preparação da consulta.';
}

$conexao->close();
?>
