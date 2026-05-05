<?php
header('Content-Type: application/json');
include 'db/conexao.php';

$data = json_decode(file_get_contents('php://input'), true);
$nomeSetor = $data['nomeSetor'] ?? '';

if (!empty($nomeSetor)) {
    $query = "INSERT INTO setores (nome) VALUES (?)";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param('s', $nomeSetor);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco de dados.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'O nome do setor é obrigatório.']);
}
?>
