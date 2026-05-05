<?php
include 'db/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);

    if ($id && $nome && $email) {
        $query = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param('ssi', $nome, $email, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao atualizar no banco de dados.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    }
    exit;
}
?>
