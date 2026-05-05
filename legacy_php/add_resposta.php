<?php
session_start();
include 'db/conexao.php';
include 'db/funcoes_permissoes.php';
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para responder.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$usuario_adm = isset($_SESSION['user_adm']) && $_SESSION['user_adm'] === true;

// Verifica se os dados foram enviados via JSON ou formulário HTML
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
    $comentario_id = intval($data['comentario_id'] ?? 0);
    $resposta_conteudo = trim($data['conteudo'] ?? '');
} else {
    $comentario_id = intval($_POST['comentario_id'] ?? 0);
    $resposta_conteudo = trim($_POST['conteudo'] ?? '');
}

$usuario_id = $_SESSION['user_id'];
$usuario_nome = $_SESSION['user_nome'] ?? '';

// Verifica se os dados são válidos
if (empty($comentario_id) || empty($resposta_conteudo)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos ou resposta vazia.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verifica se o usuário pode responder
// Admin e clientes podem responder qualquer comentário
// Usuários (com setores) podem responder apenas vídeos dos seus setores
if (!$usuario_adm) {
    // Verifica se é cliente (sem setores vinculados)
    $eh_cliente = usuario_eh_cliente($conexao, $usuario_id);
    
    // Se não for cliente, verifica se tem acesso ao setor do vídeo
    if (!$eh_cliente) {
        // Busca o vídeo relacionado ao comentário
        $query_video = "SELECT v.setor_id FROM comentarios c 
                        JOIN videos v ON c.video_id = v.id 
                        WHERE c.id = ?";
        $stmt_video = $conexao->prepare($query_video);
        $stmt_video->bind_param('i', $comentario_id);
        $stmt_video->execute();
        $result_video = $stmt_video->get_result();
        $video_data = $result_video->fetch_assoc();
        $stmt_video->close();
        
        if (!$video_data) {
            echo json_encode(['success' => false, 'error' => 'Comentário não encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Verifica se o usuário tem acesso ao setor do vídeo
        if (!usuario_tem_acesso_setor($conexao, $usuario_id, $video_data['setor_id'])) {
            echo json_encode(['success' => false, 'error' => 'Você não tem permissão para responder comentários deste vídeo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    // Se for cliente, pode responder livremente (não precisa verificar setor)
}

// Insere a resposta no banco de dados
$query = "INSERT INTO respostas (comentario_id, usuario_id, conteudo, data) VALUES (?, ?, ?, NOW())";
$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar a consulta.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('iis', $comentario_id, $usuario_id, $resposta_conteudo);

if ($stmt->execute()) {
    $resposta_id = $stmt->insert_id;
    
    // Buscar video_id do comentário
    $video_query = "SELECT video_id FROM comentarios WHERE id = ?";
    $video_stmt = $conexao->prepare($video_query);
    $video_stmt->bind_param('i', $comentario_id);
    $video_stmt->execute();
    $video_result = $video_stmt->get_result();
    $video_data = $video_result->fetch_assoc();
    $video_id = $video_data['video_id'] ?? 0;
    $video_stmt->close();
    
    // Criar notificação para o autor do comentário
    require_once 'criar_notificacao.php';
    criarNotificacaoResposta($conexao, $resposta_id, $comentario_id, $video_id, $usuario_id);
    
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        // Retorna JSON se for uma requisição AJAX
        echo json_encode([
            'success' => true,
            'message' => 'Resposta adicionada com sucesso.',
            'usuario_nome' => htmlspecialchars($usuario_nome, ENT_QUOTES, 'UTF-8'),
            'data' => date('Y-m-d H:i:s') // Retorna a data atual
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Redireciona de volta se for um formulário HTML
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao adicionar a resposta.'], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conexao->close();
?>
