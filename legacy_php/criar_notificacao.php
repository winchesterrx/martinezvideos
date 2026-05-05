<?php
/**
 * Função para criar notificações automaticamente
 * Pode ser chamada quando:
 * - Novo vídeo é publicado
 * - Novo comentário é feito
 * - Nova resposta é feita
 * - Live começa
 */

require_once 'db/conexao.php';

function criarNotificacao($conexao, $usuario_id, $tipo, $titulo, $mensagem = '', $link = '') {
    // Verificar configurações do usuário
    $config_query = "SELECT * FROM usuario_notificacoes_config WHERE usuario_id = ?";
    $config_stmt = $conexao->prepare($config_query);
    $config_stmt->bind_param("i", $usuario_id);
    $config_stmt->execute();
    $config_result = $config_stmt->get_result();
    $config = $config_result->fetch_assoc();
    
    // Se não tem configuração, usar padrões (permitir tudo)
    $permitir = true;
    if ($config) {
        switch($tipo) {
            case 'video_novo':
                $permitir = $config['notificar_videos_novos'] === 'S';
                break;
            case 'comentario':
                $permitir = $config['notificar_comentarios'] === 'S';
                break;
            case 'resposta':
                $permitir = $config['notificar_respostas'] === 'S';
                break;
            case 'live':
                $permitir = $config['notificar_lives'] === 'S';
                break;
        }
    }
    
    if (!$permitir) {
        return false;
    }
    
    // Criar notificação
    $query = "INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("issss", $usuario_id, $tipo, $titulo, $mensagem, $link);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function criarNotificacaoVideoNovo($conexao, $video_id, $setor_id, $modulo_id = null) {
    // Buscar usuários que devem ser notificados
    $where = "1=1";
    $params = [];
    $types = "";
    
    // Se há configuração de apenas favoritos, buscar apenas favoritos
    // Por enquanto, notificar todos os usuários que têm o setor/módulo como favorito
    // ou todos se não tiverem essa preferência
    
    $query = "SELECT DISTINCT u.id 
              FROM usuarios u
              LEFT JOIN usuario_notificacoes_config unc ON u.id = unc.usuario_id
              WHERE (unc.notificar_videos_novos = 'S' OR unc.notificar_videos_novos IS NULL)
              AND (unc.notificar_apenas_favoritos = 'N' OR unc.notificar_apenas_favoritos IS NULL
                   OR EXISTS (
                       SELECT 1 FROM usuario_favoritos uf 
                       WHERE uf.usuario_id = u.id 
                       AND ((uf.tipo = 'setor' AND uf.item_id = ?) 
                            OR (uf.tipo = 'modulo' AND uf.item_id = ?))
                   ))";
    
    $params[] = $setor_id;
    $types .= "i";
    if ($modulo_id) {
        $params[] = $modulo_id;
        $types .= "i";
    } else {
        $params[] = 0; // Placeholder
        $types .= "i";
    }
    
    $stmt = $conexao->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Buscar dados do vídeo
    $video_query = "SELECT titulo, setor_id, modulo_id FROM videos WHERE id = ?";
    $video_stmt = $conexao->prepare($video_query);
    $video_stmt->bind_param("i", $video_id);
    $video_stmt->execute();
    $video_data = $video_stmt->get_result()->fetch_assoc();
    
    $notificados = 0;
    while ($user = $result->fetch_assoc()) {
        $titulo = "Novo vídeo: " . $video_data['titulo'];
        $mensagem = "Um novo vídeo foi publicado";
        $link = "video_detalhes.php?id=" . $video_id;
        
        if (criarNotificacao($conexao, $user['id'], 'video_novo', $titulo, $mensagem, $link)) {
            $notificados++;
        }
    }
    
    return $notificados;
}

function criarNotificacaoComentario($conexao, $comentario_id, $video_id, $usuario_comentou_id) {
    // Notificar usuários que já comentaram neste vídeo (exceto quem comentou)
    $query = "SELECT DISTINCT c.usuario_id 
              FROM comentarios c
              JOIN usuarios u ON c.usuario_id = u.id
              LEFT JOIN usuario_notificacoes_config unc ON u.id = unc.usuario_id
              WHERE c.video_id = ? 
              AND c.usuario_id != ?
              AND (unc.notificar_comentarios = 'S' OR unc.notificar_comentarios IS NULL)";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("ii", $video_id, $usuario_comentou_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Buscar dados do vídeo
    $video_query = "SELECT titulo FROM videos WHERE id = ?";
    $video_stmt = $conexao->prepare($video_query);
    $video_stmt->bind_param("i", $video_id);
    $video_stmt->execute();
    $video_data = $video_stmt->get_result()->fetch_assoc();
    
    $notificados = 0;
    while ($user = $result->fetch_assoc()) {
        $titulo = "Novo comentário em: " . $video_data['titulo'];
        $mensagem = "Alguém comentou em um vídeo que você também comentou";
        $link = "video_detalhes.php?id=" . $video_id;
        
        if (criarNotificacao($conexao, $user['id'], 'comentario', $titulo, $mensagem, $link)) {
            $notificados++;
        }
    }
    
    return $notificados;
}

function criarNotificacaoResposta($conexao, $resposta_id, $comentario_id, $video_id, $usuario_respondeu_id) {
    // Notificar o autor do comentário original
    $query = "SELECT usuario_id FROM comentarios WHERE id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $comentario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comentario = $result->fetch_assoc();
    
    if (!$comentario || $comentario['usuario_id'] == $usuario_respondeu_id) {
        return 0; // Não notificar a si mesmo
    }
    
    // Verificar se usuário quer receber notificações de respostas
    $config_query = "SELECT notificar_respostas FROM usuario_notificacoes_config WHERE usuario_id = ?";
    $config_stmt = $conexao->prepare($config_query);
    $config_stmt->bind_param("i", $comentario['usuario_id']);
    $config_stmt->execute();
    $config_result = $config_stmt->get_result();
    $config = $config_result->fetch_assoc();
    
    if ($config && $config['notificar_respostas'] === 'N') {
        return 0;
    }
    
    // Buscar dados do vídeo
    $video_query = "SELECT titulo FROM videos WHERE id = ?";
    $video_stmt = $conexao->prepare($video_query);
    $video_stmt->bind_param("i", $video_id);
    $video_stmt->execute();
    $video_data = $video_stmt->get_result()->fetch_assoc();
    
    $titulo = "Nova resposta em: " . $video_data['titulo'];
    $mensagem = "Alguém respondeu seu comentário";
    $link = "video_detalhes.php?id=" . $video_id;
    
    if (criarNotificacao($conexao, $comentario['usuario_id'], 'resposta', $titulo, $mensagem, $link)) {
        return 1;
    }
    
    return 0;
}

function criarNotificacaoLive($conexao, $live_titulo, $live_url) {
    // Notificar todos os usuários que querem receber notificações de lives
    $query = "SELECT u.id 
              FROM usuarios u
              LEFT JOIN usuario_notificacoes_config unc ON u.id = unc.usuario_id
              WHERE (unc.notificar_lives = 'S' OR unc.notificar_lives IS NULL)";
    
    $result = $conexao->query($query);
    
    $notificados = 0;
    while ($user = $result->fetch_assoc()) {
        $titulo = "🔴 Live ao Vivo: " . $live_titulo;
        $mensagem = "Uma transmissão ao vivo começou!";
        $link = "transmissao_ao_vivo.php";
        
        if (criarNotificacao($conexao, $user['id'], 'live', $titulo, $mensagem, $link)) {
            $notificados++;
        }
    }
    
    return $notificados;
}
?>

