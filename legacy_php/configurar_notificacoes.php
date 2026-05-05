<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Verificar se já existe configuração
    $check_query = "SELECT id FROM usuario_notificacoes_config WHERE usuario_id = ?";
    $check_stmt = $conexao->prepare($check_query);
    $check_stmt->bind_param("i", $usuario_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Atualizar
        $query = "UPDATE usuario_notificacoes_config SET
                    notificar_videos_novos = ?,
                    notificar_comentarios = ?,
                    notificar_respostas = ?,
                    notificar_lives = ?,
                    notificar_apenas_favoritos = ?,
                    email_notificacoes = ?,
                    push_notificacoes = ?
                  WHERE usuario_id = ?";
        
        $notificar_videos_novos = isset($data['notificar_videos_novos']) ? ($data['notificar_videos_novos'] ? 'S' : 'N') : 'S';
        $notificar_comentarios = isset($data['notificar_comentarios']) ? ($data['notificar_comentarios'] ? 'S' : 'N') : 'S';
        $notificar_respostas = isset($data['notificar_respostas']) ? ($data['notificar_respostas'] ? 'S' : 'N') : 'S';
        $notificar_lives = isset($data['notificar_lives']) ? ($data['notificar_lives'] ? 'S' : 'N') : 'S';
        $notificar_apenas_favoritos = isset($data['notificar_apenas_favoritos']) ? ($data['notificar_apenas_favoritos'] ? 'S' : 'N') : 'N';
        $email_notificacoes = isset($data['email_notificacoes']) ? ($data['email_notificacoes'] ? 'S' : 'N') : 'N';
        $push_notificacoes = isset($data['push_notificacoes']) ? ($data['push_notificacoes'] ? 'S' : 'N') : 'S';
        
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("sssssssi", 
            $notificar_videos_novos,
            $notificar_comentarios,
            $notificar_respostas,
            $notificar_lives,
            $notificar_apenas_favoritos,
            $email_notificacoes,
            $push_notificacoes,
            $usuario_id
        );
    } else {
        // Inserir
        $query = "INSERT INTO usuario_notificacoes_config 
                  (usuario_id, notificar_videos_novos, notificar_comentarios, notificar_respostas, 
                   notificar_lives, notificar_apenas_favoritos, email_notificacoes, push_notificacoes)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $notificar_videos_novos = isset($data['notificar_videos_novos']) ? ($data['notificar_videos_novos'] ? 'S' : 'N') : 'S';
        $notificar_comentarios = isset($data['notificar_comentarios']) ? ($data['notificar_comentarios'] ? 'S' : 'N') : 'S';
        $notificar_respostas = isset($data['notificar_respostas']) ? ($data['notificar_respostas'] ? 'S' : 'N') : 'S';
        $notificar_lives = isset($data['notificar_lives']) ? ($data['notificar_lives'] ? 'S' : 'N') : 'S';
        $notificar_apenas_favoritos = isset($data['notificar_apenas_favoritos']) ? ($data['notificar_apenas_favoritos'] ? 'S' : 'N') : 'N';
        $email_notificacoes = isset($data['email_notificacoes']) ? ($data['email_notificacoes'] ? 'S' : 'N') : 'N';
        $push_notificacoes = isset($data['push_notificacoes']) ? ($data['push_notificacoes'] ? 'S' : 'N') : 'S';
        
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("isssssss", 
            $usuario_id,
            $notificar_videos_novos,
            $notificar_comentarios,
            $notificar_respostas,
            $notificar_lives,
            $notificar_apenas_favoritos,
            $email_notificacoes,
            $push_notificacoes
        );
    }
    
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso'], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

