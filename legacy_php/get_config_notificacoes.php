<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'config' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $query = "SELECT * FROM usuario_notificacoes_config WHERE usuario_id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $config = $result->fetch_assoc();
        // Converter S/N para boolean
        $config['notificar_videos_novos'] = $config['notificar_videos_novos'] === 'S';
        $config['notificar_comentarios'] = $config['notificar_comentarios'] === 'S';
        $config['notificar_respostas'] = $config['notificar_respostas'] === 'S';
        $config['notificar_lives'] = $config['notificar_lives'] === 'S';
        $config['notificar_apenas_favoritos'] = $config['notificar_apenas_favoritos'] === 'S';
        $config['email_notificacoes'] = $config['email_notificacoes'] === 'S';
        $config['push_notificacoes'] = $config['push_notificacoes'] === 'S';
        
        echo json_encode(['success' => true, 'config' => $config], JSON_UNESCAPED_UNICODE);
    } else {
        // Retornar configurações padrão
        $config_padrao = [
            'notificar_videos_novos' => true,
            'notificar_comentarios' => true,
            'notificar_respostas' => true,
            'notificar_lives' => true,
            'notificar_apenas_favoritos' => false,
            'email_notificacoes' => false,
            'push_notificacoes' => true
        ];
        echo json_encode(['success' => true, 'config' => $config_padrao], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'config' => null, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

