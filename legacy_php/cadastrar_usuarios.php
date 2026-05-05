<?php
header('Content-Type: application/json');
include 'db/conexao.php';

$data = json_decode(file_get_contents('php://input'), true);
$nomeUsuario = $data['nomeUsuario'] ?? '';
$emailUsuario = $data['emailUsuario'] ?? '';
$senhaUsuario = $data['senhaUsuario'] ?? '';

if (!empty($nomeUsuario) && !empty($emailUsuario) && !empty($senhaUsuario)) {
    $senhaHash = password_hash($senhaUsuario, PASSWORD_BCRYPT);
    $query = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param('sss', $nomeUsuario, $emailUsuario, $senhaHash);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco de dados.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios.']);
}
?>
