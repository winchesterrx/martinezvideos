<?php
session_start();
include 'db/conexao.php'; // Conexão com o banco de dados
header('Content-Type: application/json');

// Verifica se o usuário é administrador
if (!isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(["success" => false, "error" => "Acesso negado."]);
    exit;
}

// Verifica se os dados foram enviados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = isset($_POST['titulo']) ? $_POST['titulo'] : null;
    $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : null;

    if ($titulo && $descricao) {
        // Atualiza a live sem parar a transmissão
        $query = "UPDATE transmissao_ao_vivo SET titulo = ?, descricao = ? WHERE ativo = 1";
        $stmt = mysqli_prepare($conexao, $query);
        mysqli_stmt_bind_param($stmt, "ss", $titulo, $descricao);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(["success" => true, "message" => "Live atualizada com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "error" => "Erro ao atualizar a live"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Título e descrição são obrigatórios."]);
    }
    exit;
}

echo json_encode(["success" => false, "error" => "Requisição inválida"]);
