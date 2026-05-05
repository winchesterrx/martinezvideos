<?php
session_start();
include 'db/conexao.php';
include 'db/funcoes_permissoes.php';
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado para excluir vídeos.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Obtém o ID do vídeo
$video_id = intval($_POST['video_id'] ?? 0);

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo inválido.']);
    exit;
}

// Clientes não podem excluir vídeos
if (usuario_eh_cliente($conexao, $usuario_id)) {
    echo json_encode(['success' => false, 'error' => 'Clientes não podem excluir vídeos.']);
    exit;
}

// Verifica se o usuário pode editar/excluir este vídeo
if (!usuario_pode_editar_video($conexao, $usuario_id, $video_id)) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para excluir este vídeo.']);
    exit;
}

// Executa a exclusão usando prepared statement
$query = "DELETE FROM videos WHERE id = ?";
$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar a consulta: ' . $conexao->error]);
    exit;
}

$stmt->bind_param("i", $video_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao excluir o vídeo: ' . $stmt->error]);
}

$stmt->close();
?>
