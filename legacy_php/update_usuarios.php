<?php
session_start();
include 'db/conexao.php';
$conexao->set_charset("utf8");

// VERIFICAÇÃO DE SEGURANÇA: Apenas administradores podem editar usuários
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    header('Location: login.php?erro=acesso_negado');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cidade = $_POST['cidade'];
    $adm = $_POST['adm'];

    $sql = "UPDATE usuarios SET nome = ?, email = ?, municipio_id = ?, ADM = ? WHERE id = ?";
    $stmt = $conexao->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('sssii', $nome, $email, $cidade, $adm, $id);
        if ($stmt->execute()) {
            header('Location: listar_usuarios.php?msg=success');
            exit;
        } else {
            die("Erro ao atualizar o usuário: " . $stmt->error);
        }
    } else {
        die("Erro ao preparar a consulta: " . $conexao->error);
    }
}
?>
