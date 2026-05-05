<?php
session_start();
include 'db/conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado para curtir.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$video_id = $data['video_id'] ?? null;

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo não fornecido.']);
    exit;
}

// Verifica se o usuário já curtiu o vídeo
$sql_verificar = "SELECT * FROM curtidas WHERE video_id = ? AND usuario_id = ?";
$stmt_verificar = $conexao->prepare($sql_verificar);
$stmt_verificar->bind_param("ii", $video_id, $usuario_id);
$stmt_verificar->execute();
$result = $stmt_verificar->get_result();

if ($result->num_rows > 0) {
    // Se já curtiu, remove a curtida
    $sql_remover = "DELETE FROM curtidas WHERE video_id = ? AND usuario_id = ?";
    $stmt_remover = $conexao->prepare($sql_remover);
    $stmt_remover->bind_param("ii", $video_id, $usuario_id);
    $stmt_remover->execute();

    if ($stmt_remover->affected_rows > 0) {
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Curtida removida com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao remover a curtida.']);
    }
} else {
    // Se não curtiu, adiciona a curtida
    $sql_adicionar = "INSERT INTO curtidas (video_id, usuario_id) VALUES (?, ?)";
    $stmt_adicionar = $conexao->prepare($sql_adicionar);
    $stmt_adicionar->bind_param("ii", $video_id, $usuario_id);
    $stmt_adicionar->execute();

    if ($stmt_adicionar->affected_rows > 0) {
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Curtida registrada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar a curtida.']);
    }
}

$conexao->close();
?>
