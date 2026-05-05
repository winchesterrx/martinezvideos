<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$resposta_id = intval($data['resposta_id'] ?? 0);
$usuario_id = $_SESSION['user_id'];
$usuario_adm = $_SESSION['user_adm'] ?? 'N';

if ($resposta_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Resposta inválida.']);
    exit;
}

// Verifica permissões: administradores podem excluir qualquer resposta, usuários apenas suas próprias
if ($usuario_adm === 'S') {
    $query = "DELETE FROM respostas WHERE id = ?";
} else {
    $query = "DELETE FROM respostas WHERE id = ? AND usuario_id = ?";
}

$stmt = $conexao->prepare($query);
if ($usuario_adm === 'S') {
    $stmt->bind_param('i', $resposta_id);
} else {
    $stmt->bind_param('ii', $resposta_id, $usuario_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resposta excluída com sucesso.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao excluir resposta.']);
}

$stmt->close();
$conexao->close();
?>
