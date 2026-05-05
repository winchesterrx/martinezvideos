<?php
session_start();
include 'db/conexao.php';

$data = json_decode(file_get_contents("php://input"), true);
$comentario_id = intval($data['comentario_id'] ?? 0);
$usuario_id = $_SESSION['user_id'];
$usuario_adm = $_SESSION['user_adm'] ?? 'N';

if (!$usuario_id || $comentario_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
    exit;
}

// Excluir todas as respostas associadas ao comentário (somente administradores podem)
if ($usuario_adm === 'S') {
    $query_respostas = "DELETE FROM respostas WHERE comentario_id = ?";
    $stmt_respostas = $conexao->prepare($query_respostas);
    $stmt_respostas->bind_param('i', $comentario_id);
    $stmt_respostas->execute();
}

// Excluir o comentário
if ($usuario_adm === 'S') {
    $query = "DELETE FROM comentarios WHERE id = ?";
} else {
    $query = "DELETE FROM comentarios WHERE id = ? AND usuario_id = ?";
}

$stmt = $conexao->prepare($query);
if ($usuario_adm === 'S') {
    $stmt->bind_param('i', $comentario_id);
} else {
    $stmt->bind_param('ii', $comentario_id, $usuario_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Comentário excluído com sucesso.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao excluir comentário.']);
}

$stmt->close();
$conexao->close();
?>
