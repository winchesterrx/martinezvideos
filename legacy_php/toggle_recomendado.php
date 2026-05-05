<?php
session_start();
include 'db/conexao.php';
include 'db/funcoes_permissoes.php';
header('Content-Type: application/json; charset=utf-8');

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$usuario_adm = isset($_SESSION['user_adm']) && $_SESSION['user_adm'] === true;

// Apenas admins podem marcar vídeos como recomendados
if (!$usuario_adm) {
    echo json_encode(['success' => false, 'error' => 'Apenas administradores podem marcar vídeos como recomendados.']);
    exit;
}

// Obtém o ID do vídeo
$video_id = intval($_POST['video_id'] ?? $_GET['video_id'] ?? 0);

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo não fornecido.']);
    exit;
}

try {
    // Verifica se o campo recomendado existe, se não existir, cria
    $check_column = $conexao->query("SHOW COLUMNS FROM videos LIKE 'recomendado'");
    if ($check_column->num_rows == 0) {
        // Adiciona o campo se não existir
        $conexao->query("ALTER TABLE videos ADD COLUMN recomendado TINYINT(1) DEFAULT 0 COMMENT '1 = vídeo recomendado manualmente' AFTER visualizacoes");
        $conexao->query("CREATE INDEX IF NOT EXISTS idx_recomendado ON videos(recomendado)");
    }
    
    // Busca o estado atual do vídeo
    $query_atual = "SELECT recomendado FROM videos WHERE id = ?";
    $stmt_atual = $conexao->prepare($query_atual);
    $stmt_atual->bind_param('i', $video_id);
    $stmt_atual->execute();
    $result_atual = $stmt_atual->get_result();
    $video = $result_atual->fetch_assoc();
    $stmt_atual->close();
    
    if (!$video) {
        echo json_encode(['success' => false, 'error' => 'Vídeo não encontrado.']);
        exit;
    }
    
    // Alterna o estado (0 -> 1 ou 1 -> 0)
    $novo_estado = $video['recomendado'] == 1 ? 0 : 1;
    
    // Atualiza o vídeo
    $query_update = "UPDATE videos SET recomendado = ? WHERE id = ?";
    $stmt_update = $conexao->prepare($query_update);
    $stmt_update->bind_param('ii', $novo_estado, $video_id);
    
    if ($stmt_update->execute()) {
        // Verifica se realmente foi atualizado
        $query_verifica = "SELECT recomendado FROM videos WHERE id = ?";
        $stmt_verifica = $conexao->prepare($query_verifica);
        $stmt_verifica->bind_param('i', $video_id);
        $stmt_verifica->execute();
        $result_verifica = $stmt_verifica->get_result();
        $video_verifica = $result_verifica->fetch_assoc();
        $stmt_verifica->close();
        
        $recomendado_atual = $video_verifica['recomendado'] == 1;
        
        echo json_encode([
            'success' => true,
            'recomendado' => $recomendado_atual,
            'message' => $recomendado_atual ? 'Vídeo adicionado aos recomendados!' : 'Vídeo removido dos recomendados.',
            'debug' => [
                'video_id' => $video_id,
                'estado_anterior' => $video['recomendado'],
                'estado_novo' => $novo_estado,
                'estado_verificado' => $video_verifica['recomendado']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $stmt_update->error]);
    }
    
    $stmt_update->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro: ' . $e->getMessage()]);
}
?>

