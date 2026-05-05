<?php
session_start();
include 'db/conexao.php';

// Configura o cabeçalho para garantir que o retorno seja JSON
header('Content-Type: application/json');

try {
    // Verifica se o usuário está logado como administrador
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
        throw new Exception('Acesso negado. Apenas administradores podem excluir usuários.');
    }

    // Obtém o ID enviado e valida
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        throw new Exception('ID inválido ou não informado.');
    }

    // Prepara e executa a exclusão do banco de dados
    $query = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query);

    if (!$stmt) {
        throw new Exception('Erro ao preparar a consulta SQL.');
    }

    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao executar a exclusão: ' . $stmt->error);
    }

    $stmt->close();
    $conexao->close();
} catch (Exception $e) {
    // Em caso de erro, retorna uma mensagem clara ao cliente
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
