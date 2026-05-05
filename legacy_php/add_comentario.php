<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Recebe os dados enviados via JSON
$input = json_decode(file_get_contents('php://input'), true);
$video_id = isset($input['video_id']) ? intval($input['video_id']) : null;
$conteudo = isset($input['conteudo']) ? trim($input['conteudo']) : null;

// Valida os dados recebidos
if (!$video_id || empty($conteudo)) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo ou conteúdo não fornecido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Insere o comentário no banco de dados
$sql = "INSERT INTO comentarios (video_id, usuario_id, conteudo, data) VALUES (?, ?, ?, NOW())";
$stmt = $conexao->prepare($sql);

if ($stmt) {
    $usuario_id = $_SESSION['user_id'];
    $stmt->bind_param("iis", $video_id, $usuario_id, $conteudo);
    if ($stmt->execute()) {
        $comentario_id = $stmt->insert_id;
        
        // Criar notificações para outros usuários que comentaram
        require_once 'criar_notificacao.php';
        criarNotificacaoComentario($conexao, $comentario_id, $video_id, $usuario_id);
        
        echo json_encode([
            'success' => true,
            'conteudo' => htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8'),
            'data' => date('Y-m-d H:i:s'),
            'usuario_nome' => htmlspecialchars($_SESSION['user_nome'], ENT_QUOTES, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar comentário.'], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor.'], JSON_UNESCAPED_UNICODE);
}

$conexao->close();
?>
