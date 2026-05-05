<?php
session_start();
include 'db/conexao.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$video_id = $input['video_id'] ?? null;

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo não fornecido.']);
    exit;
}

// Verifica se já curtiu
$query = "SELECT * FROM curtidas WHERE video_id = ? AND usuario_id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param('ii', $video_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Remove curtida
    $delete_query = "DELETE FROM curtidas WHERE video_id = ? AND usuario_id = ?";
    $stmt = $conexao->prepare($delete_query);
    $stmt->bind_param('ii', $video_id, $usuario_id);
    $stmt->execute();
    $action = 'removed';
} else {
    // Adiciona curtida
    $insert_query = "INSERT INTO curtidas (video_id, usuario_id) VALUES (?, ?)";
    $stmt = $conexao->prepare($insert_query);
    $stmt->bind_param('ii', $video_id, $usuario_id);
    $stmt->execute();
    $action = 'added';
}

// Conta total de curtidas
$count_query = "SELECT COUNT(*) AS total FROM curtidas WHERE video_id = ?";
$stmt = $conexao->prepare($count_query);
$stmt->bind_param('i', $video_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

echo json_encode(['success' => true, 'action' => $action, 'curtidas' => $total]);
