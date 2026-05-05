<?php
session_start();
include 'db/conexao.php';
include 'db/funcoes_permissoes.php';
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado para editar vídeos.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Obtém os dados do formulário
$video_id = intval($_POST['video_id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$setor_id = intval($_POST['setor_id'] ?? 0);

// Validações
if (!$video_id || empty($titulo) || empty($descricao) || !$setor_id) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

// Clientes não podem editar vídeos
if (usuario_eh_cliente($conexao, $usuario_id)) {
    echo json_encode(['success' => false, 'error' => 'Clientes não podem editar vídeos.']);
    exit;
}

// Verifica se o usuário pode editar este vídeo (verifica setor atual do vídeo)
if (!usuario_pode_editar_video($conexao, $usuario_id, $video_id)) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para editar este vídeo.']);
    exit;
}

// Se está mudando o setor, verifica se tem permissão no novo setor
if ($setor_id > 0) {
    // Busca o setor atual do vídeo
    $query_video = "SELECT setor_id FROM videos WHERE id = ?";
    $stmt_check = $conexao->prepare($query_video);
    $stmt_check->bind_param('i', $video_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $video_atual = $result_check->fetch_assoc();
    $stmt_check->close();
    
    // Se está mudando para outro setor, verifica permissão
    if ($video_atual && $video_atual['setor_id'] != $setor_id) {
        if (!usuario_pode_upload_setor($conexao, $usuario_id, $setor_id)) {
            echo json_encode(['success' => false, 'error' => 'Você não tem permissão para mover este vídeo para este setor.']);
            exit;
        }
    }
}

// Atualiza o vídeo no banco de dados usando prepared statement
$query = "UPDATE videos SET titulo = ?, descricao = ?, setor_id = ? WHERE id = ?";
$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar a consulta: ' . $conexao->error]);
    exit;
}

$stmt->bind_param("ssii", $titulo, $descricao, $setor_id, $video_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar o vídeo: ' . $stmt->error]);
}

$stmt->close();
?>
