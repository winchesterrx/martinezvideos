<?php
session_start();
include 'db/conexao.php';
include 'db/funcoes_permissoes.php';
// Configura charset para UTF-8 (utf8mb4) para suportar caracteres especiais e emojis
$conexao->set_charset("utf8mb4");
$conexao->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
$conexao->query("SET CHARACTER SET utf8mb4");
$conexao->query("SET character_set_connection=utf8mb4");
$conexao->query("SET character_set_client=utf8mb4");
$conexao->query("SET character_set_results=utf8mb4");
// Desabilitar exibição de erros em produção
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Verifica se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
$usuario_id = $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['user_nome'] ?? 'Usuário';
$usuario_adm = $_SESSION['user_adm'] ?? false;
$user_type = $_SESSION['user_type'] ?? null;

// Captura o ID do vídeo pela URL
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($video_id <= 0) {
    die('ID de vídeo inválido.');
}

// Configuração da paginação de comentários
$comentarios_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $comentarios_por_pagina;

// Busca as informações do vídeo
$usuario_id_query = $usuario_id ?? 0;

// Query simples sem JOIN em sequencias (vamos buscar depois se necessário)
$query = "SELECT videos.*, setores.nome AS setor_nome, modulos.nome AS modulo_nome,
                 (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id) AS curtidas, 
                 (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id AND curtidas.usuario_id = ?) AS usuario_curtiu 
          FROM videos 
          JOIN setores ON videos.setor_id = setores.id 
          LEFT JOIN modulos ON videos.modulo_id = modulos.id
          WHERE videos.id = ?";

$stmt = $conexao->prepare($query);
if (!$stmt) {
    die('Erro ao preparar consulta: ' . $conexao->error);
}

$stmt->bind_param('ii', $usuario_id_query, $video_id);
if (!$stmt->execute()) {
    die('Erro ao executar consulta: ' . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Vídeo não encontrado.');
}

$video = $result->fetch_assoc();
$total_curtidas = $video['curtidas'] ?? 0;
$usuario_curtiu = $video['usuario_curtiu'] > 0;

// Buscar vídeos relacionados (da mesma sequência ou mesmo setor/módulo)
$videos_sequencia = [];
$videos_relacionados = [];
$sequencia_info = null;
$total_videos_sequencia = 0;
$video_atual_ordem = null;
$video_faz_parte_sequencia = false;

// Verificar se o vídeo faz parte de uma sequência (verificação simples)
if (isset($video['is_sequencia']) && 
    ($video['is_sequencia'] == 1 || $video['is_sequencia'] === '1' || $video['is_sequencia'] === true) && 
    isset($video['sequencia_id']) && 
    !empty($video['sequencia_id']) && 
    intval($video['sequencia_id']) > 0) {
    $video_faz_parte_sequencia = true;
}

// Buscar sequência apenas se o vídeo faz parte de uma
if ($video_faz_parte_sequencia) {
    try {
        $sequencia_id_valor = intval($video['sequencia_id']);
        
        // Tentar buscar informações da sequência (pode não existir a tabela)
        $query_seq_info = "SELECT titulo, descricao FROM sequencias WHERE id = ?";
        $stmt_seq_info = $conexao->prepare($query_seq_info);
        if ($stmt_seq_info) {
            $stmt_seq_info->bind_param("i", $sequencia_id_valor);
            if ($stmt_seq_info->execute()) {
                $result_seq_info = $stmt_seq_info->get_result();
                if ($result_seq_info && $row_seq_info = $result_seq_info->fetch_assoc()) {
                    $sequencia_info = $row_seq_info;
                }
            }
            $stmt_seq_info->close();
        }
        
        // Contar total de vídeos na sequência
        $query_total = "SELECT COUNT(*) as total FROM videos WHERE sequencia_id = ?";
        $stmt_total = $conexao->prepare($query_total);
        if ($stmt_total) {
            $stmt_total->bind_param("i", $sequencia_id_valor);
            if ($stmt_total->execute()) {
                $result_total = $stmt_total->get_result();
                if ($result_total && $row_total = $result_total->fetch_assoc()) {
                    $total_videos_sequencia = intval($row_total['total']);
                }
            }
            $stmt_total->close();
        }
        
        // Buscar ordem do vídeo atual
        $video_atual_ordem = isset($video['sequencia_ordem']) ? intval($video['sequencia_ordem']) : null;
        
        // Buscar TODOS os vídeos da mesma sequência (incluindo o atual)
        $query_relacionados = "SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome,
                              (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                              (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                              FROM videos v
                              JOIN setores s ON v.setor_id = s.id
                              LEFT JOIN modulos m ON v.modulo_id = m.id
                              WHERE v.sequencia_id = ? AND v.is_sequencia = 1
                              ORDER BY COALESCE(v.sequencia_ordem, 999999) ASC, v.id ASC";
        
        $stmt_rel = $conexao->prepare($query_relacionados);
        if ($stmt_rel) {
            $stmt_rel->bind_param("i", $sequencia_id_valor);
            if ($stmt_rel->execute()) {
                $result_rel = $stmt_rel->get_result();
                if ($result_rel) {
                    while ($row = $result_rel->fetch_assoc()) {
                        $videos_sequencia[] = $row;
                    }
                }
            }
            $stmt_rel->close();
        }
    } catch (Exception $e) {
        // Se houver erro, não carrega sequência
        $videos_sequencia = [];
        $sequencia_info = null;
    }
}

// Buscar vídeos relacionados do mesmo setor/módulo (mas não da sequência)
if (isset($video['setor_id']) && intval($video['setor_id']) > 0) {
    $setor_id_valor = intval($video['setor_id']);
    $where_conditions = ["v.setor_id = ?", "v.id != ?"];
    $params_rel = [$setor_id_valor, $video_id];
    $types_rel = "ii";
    
    // Se o vídeo faz parte de uma sequência, excluir vídeos da mesma sequência
    if ($video_faz_parte_sequencia && isset($video['sequencia_id']) && intval($video['sequencia_id']) > 0) {
        $where_conditions[] = "(v.sequencia_id IS NULL OR v.sequencia_id != ?)";
        $params_rel[] = intval($video['sequencia_id']);
        $types_rel .= "i";
    }
    
    if (!empty($video['modulo_id']) && intval($video['modulo_id']) > 0) {
        $where_conditions[] = "v.modulo_id = ?";
        $params_rel[] = intval($video['modulo_id']);
        $types_rel .= "i";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $query_relacionados_geral = "SELECT v.*, s.nome as setor_nome, m.nome as modulo_nome,
                            (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                            (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                            FROM videos v
                            JOIN setores s ON v.setor_id = s.id
                            LEFT JOIN modulos m ON v.modulo_id = m.id
                            WHERE $where_clause ORDER BY v.data_upload DESC LIMIT 6";

    $stmt_rel_geral = $conexao->prepare($query_relacionados_geral);
    if ($stmt_rel_geral) {
        $stmt_rel_geral->bind_param($types_rel, ...$params_rel);
        if ($stmt_rel_geral->execute()) {
            $result_rel_geral = $stmt_rel_geral->get_result();
            if ($result_rel_geral) {
                while ($row = $result_rel_geral->fetch_assoc()) {
                    $videos_relacionados[] = $row;
                }
            }
        }
        $stmt_rel_geral->close();
    }
}

// Verificar permissões
$pode_editar = false;
if ($is_logged_in && $usuario_id) {
    if ($usuario_adm) {
        $pode_editar = true;
    } else {
        if (!usuario_eh_cliente($conexao, $usuario_id)) {
            $pode_editar = usuario_pode_editar_video($conexao, $usuario_id, $video_id);
        }
    }
}

// Busca os comentários do vídeo
$query_comentarios = "SELECT comentarios.*, usuarios.nome AS usuario_nome 
                      FROM comentarios 
                      JOIN usuarios ON comentarios.usuario_id = usuarios.id 
                      WHERE comentarios.video_id = ? 
                      ORDER BY comentarios.data DESC";
$stmt_comentarios = $conexao->prepare($query_comentarios);
$stmt_comentarios->bind_param('i', $video_id);
$stmt_comentarios->execute();
$comentarios_result = $stmt_comentarios->get_result();
//respota
$respostas_query = "SELECT respostas.*, usuarios.nome AS usuario_nome 
                    FROM respostas 
                    JOIN usuarios ON respostas.usuario_id = usuarios.id 
                    WHERE respostas.comentario_id = ? 
                    ORDER BY respostas.data ASC";
$stmt_respostas = $conexao->prepare($respostas_query);
$stmt_respostas->bind_param('i', $comentario['id']);
$stmt_respostas->execute();
$respostas_result = $stmt_respostas->get_result();

// Contagem total de comentários
$total_comentarios_query = "SELECT COUNT(*) AS total FROM comentarios WHERE video_id = ?";
$stmt_total = $conexao->prepare($total_comentarios_query);
$stmt_total->bind_param('i', $video_id);
$stmt_total->execute();
$total_comentarios = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_comentarios / $comentarios_por_pagina);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['titulo']) ?> - Detalhes</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        // Aplicar tema imediatamente antes do CSS carregar (evita flash)
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            color: #262626;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        @media (min-width: 769px) {
            body {
                overflow-y: hidden;
                height: 100vh;
            }
        }

        /* ===== DARK MODE ===== */
        [data-theme="dark"] body {
            background-color: #1a1a1a !important;
            color: #e0e0e0;
        }

        [data-theme="dark"] .top-header {
            background: #2d2d2d !important;
            color: #e0e0e0;
        }

        [data-theme="dark"] .main-content {
            background: #1a1a1a !important;
        }

        [data-theme="dark"] .sidebar {
            background: #1e293b !important;
        }

        [data-theme="dark"] .video-card-modern {
            background: #1a1a1a !important;
            border-color: #363636;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .video-card-modern:hover {
            border-color: #404040;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .comments-container-modern {
            background: #1a1a1a !important;
            border-color: #363636;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .comments-container-modern:hover {
            border-color: #404040;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .comment-modern:hover {
            background: #2a2a2a;
            border-color: #404040;
        }

        [data-theme="dark"] .reply-modern {
            background: #1f1f1f;
            border-color: #363636;
            border-left-color: #404040;
        }

        [data-theme="dark"] .reply-modern:hover {
            background: #252525;
            border-color: #404040;
            border-left-color: #525252;
        }

        [data-theme="dark"] .video-info-section {
            background: transparent;
        }

        [data-theme="dark"] .video-title-modern {
            background: linear-gradient(135deg, #e0e0e0 0%, #b0b0b0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        [data-theme="dark"] .video-meta-modern .meta-item {
            background: #333 !important;
            border-color: #404040;
            color: #e0e0e0;
        }

        [data-theme="dark"] .meta-icon-wrapper {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a) !important;
        }

        [data-theme="dark"] .meta-label {
            color: rgba(255, 255, 255, 0.6);
        }

        [data-theme="dark"] .meta-value {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .video-description-modern {
            color: #c0c0c0 !important;
            background: #333 !important;
        }

        [data-theme="dark"] .setor-badge-modern {
            background: #3a2a1a;
            color: #fcd34d;
            border-color: #4a3a2a;
        }

        [data-theme="dark"] .setor-badge-modern:hover {
            background: #4a3a2a;
            border-color: #5a4a3a;
            color: #fde68a;
        }

        [data-theme="dark"] .setor-badge-modern i {
            color: #fcd34d;
        }

        [data-theme="dark"] .comments-container-modern {
            background: transparent !important;
            border: none;
        }

        [data-theme="dark"] .comment-modern {
            background: transparent !important;
            border-bottom-color: #363636;
        }

        [data-theme="dark"] .comment-author-name {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .comment-content-modern {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .comment-date-modern {
            color: #a5b4fc;
        }

        [data-theme="dark"] .meta-item:nth-child(2) .meta-value {
            color: #7dd3fc;
        }

        [data-theme="dark"] .meta-item:nth-child(2) .meta-value i {
            color: #7dd3fc;
        }

        [data-theme="dark"] .meta-item:nth-child(3) .meta-value {
            color: #a5b4fc;
        }

        [data-theme="dark"] .meta-item:nth-child(3) .meta-value i {
            color: #a5b4fc;
        }

        [data-theme="dark"] .comment-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            border-color: #404040;
        }

        [data-theme="dark"] .comment-modern:hover .comment-avatar {
            border-color: #525252;
        }

        [data-theme="dark"] .reply-modern {
            border-left-color: #363636;
        }

        [data-theme="dark"] .reply-modern:hover {
            border-left-color: #404040;
        }

        [data-theme="dark"] .reply-modern .comment-avatar {
            border-color: #404040;
        }

        [data-theme="dark"] .comment-form-modern textarea {
            border-bottom-color: #363636;
            color: #e0e0e0;
        }

        [data-theme="dark"] .comment-form-modern textarea:focus {
            border-bottom-color: #e0e0e0;
        }

        [data-theme="dark"] .btn-reply-modern {
            color: #7dd3fc;
            border-color: #1a3a4a;
        }

        [data-theme="dark"] .btn-reply-modern:hover {
            background: #1a2a3a;
            color: #7dd3fc;
            border-color: #2a4a6a;
        }

        [data-theme="dark"] .btn-delete-modern {
            color: #fca5a5;
            border-color: #5a2a2a;
        }

        [data-theme="dark"] .btn-delete-modern:hover {
            background: #3a1f1f;
            color: #fca5a5;
            border-color: #6a3a3a;
        }

        [data-theme="dark"] .comment-form-modern button {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }

        [data-theme="dark"] .comment-form-modern button:hover:not(:disabled) {
            background: #0369a1;
            border-color: #0369a1;
            color: #ffffff;
        }

        [data-theme="dark"] .btn-like-modern {
            border-color: #5a2a2a;
            color: #fca5a5;
        }

        [data-theme="dark"] .btn-like-modern:hover {
            background: #3a1f1f;
            border-color: #6a3a3a;
            color: #fca5a5;
        }

        [data-theme="dark"] .btn-like-modern.active {
            background: #4a2a2a;
            border-color: #7a4a4a;
            color: #fca5a5;
        }

        [data-theme="dark"] .btn-share-modern {
            border-color: #1a3a4a;
            color: #7dd3fc;
        }

        [data-theme="dark"] .btn-share-modern:hover {
            background: #1a2a3a;
            border-color: #2a4a6a;
            color: #7dd3fc;
        }

        [data-theme="dark"] .btn-cinema-modern {
            border-color: #2a1a4a;
            color: #a5b4fc;
        }

        [data-theme="dark"] .btn-cinema-modern:hover {
            background: #2a1a3a;
            border-color: #3a2a6a;
            color: #a5b4fc;
        }

        [data-theme="dark"] .btn-edit-modern {
            border-color: #4a3a1a;
            color: #fcd34d;
        }

        [data-theme="dark"] .btn-edit-modern:hover {
            background: #3a2a1a;
            border-color: #5a4a2a;
            color: #fcd34d;
        }

        [data-theme="dark"] .curtidas-display {
            background: #3a1f1f;
            color: #fca5a5;
            border-color: #5a2a2a;
        }

        [data-theme="dark"] .curtidas-display:hover {
            background: #4a2a2a;
            border-color: #6a3a3a;
            color: #fca5a5;
        }

        [data-theme="dark"] .video-card-modern {
            border-bottom-color: #363636;
        }

        [data-theme="dark"] .video-card-modern:hover {
            border-bottom-color: #404040;
        }

        [data-theme="dark"] .curtidas-display {
            color: #e0e0e0;
        }

        [data-theme="dark"] .curtidas-display i {
            color: #e0e0e0;
        }

        [data-theme="dark"] .btn-action-modern {
            color: #e0e0e0;
            opacity: 0.7;
        }

        [data-theme="dark"] .btn-action-modern:hover {
            opacity: 1;
        }

        [data-theme="dark"] .btn-like-modern.active i {
            color: #ed4956;
        }

        [data-theme="dark"] .meta-value {
            color: #e0e0e0 !important;
        }

        [data-theme="dark"] .meta-value i {
            color: #a8a8a8;
        }

        [data-theme="dark"] .video-header-modern {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .video-date-modern {
            color: rgba(255, 255, 255, 0.6);
        }


        [data-theme="dark"] .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            color: #ff6f00;
        }

        [data-theme="dark"] .theme-toggle:hover {
            background: rgba(255, 111, 0, 0.2);
        }

        /* ===== LAYOUT: VÍDEO + RECOMENDAÇÕES AO LADO ===== */
        .video-content-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 28px;
            align-items: start;
            margin-bottom: 32px;
        }

        .video-main-content {
            min-width: 0;
        }

        /* Garantir que comentários fiquem centralizados e fora do grid */
        .comments-wrapper {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Badge de Sequência no Vídeo Principal */
        .sequencia-badge-video {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #ef4444;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #fecaca;
        }

        .sequencia-badge-video i {
            font-size: 13px;
        }

        .sequencia-ordem-video {
            background: #ef4444;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 6px;
        }

        [data-theme="dark"] .sequencia-badge-video {
            background: linear-gradient(135deg, #3a1f1f 0%, #4a2a2a 100%);
            color: #fca5a5;
            border-color: #5a2a2a;
        }

        [data-theme="dark"] .sequencia-ordem-video {
            background: #ef4444;
            color: #ffffff;
        }

        /* ===== RECOMENDAÇÕES SIDEBAR ===== */
        .videos-relacionados-sidebar {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .videos-relacionados-sidebar:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .sequencia-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }

        .videos-relacionados-section {
            margin-top: 24px;
        }

        /* ===== HEADER DA SEQUÊNCIA ===== */
        .sequencia-header-info {
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }

        .sequencia-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .sequencia-header-badge i {
            font-size: 12px;
        }

        .sequencia-titulo-principal {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 8px 0;
            line-height: 1.4;
        }

        .sequencia-descricao {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 16px 0;
            line-height: 1.5;
        }

        .sequencia-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sequencia-stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }

        .sequencia-stat-item i {
            color: #ef4444;
            font-size: 14px;
        }

        .sequencia-stat-item strong {
            color: #111827;
            font-weight: 700;
        }

        .sequencia-stat-item.sequencia-atual {
            background: #fef2f2;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #fecaca;
        }

        .sequencia-stat-item.sequencia-atual i {
            color: #ef4444;
        }

        .sequencia-stat-item.sequencia-atual strong {
            color: #ef4444;
        }

        .videos-relacionados-header-sidebar {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }

        .videos-relacionados-header-sidebar h3 {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .videos-relacionados-header-sidebar i {
            color: #ef4444;
            font-size: 20px;
        }

        .videos-relacionados-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .video-relacionado-item {
            background: #f9fafb;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .video-relacionado-item.video-atual {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .video-relacionado-item:hover {
            transform: translateX(6px) translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
            background: #ffffff;
        }

        .video-relacionado-item.video-atual:hover {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }

        .video-relacionado-link-sidebar {
            text-decoration: none;
            color: inherit;
            display: flex;
            gap: 14px;
            padding: 14px;
        }

        .video-relacionado-thumbnail-sidebar {
            position: relative;
            width: 160px;
            height: 90px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .video-sequencia-badge-sidebar {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 2;
            background: rgba(239, 68, 68, 0.95);
            color: #ffffff;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            min-width: 32px;
            text-align: center;
        }

        .video-sequencia-badge-sidebar.badge-atual {
            background: rgba(34, 197, 94, 0.95);
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(34, 197, 94, 0.5);
            }
        }

        .badge-numero {
            display: inline-block;
        }

        .video-atual-indicator {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 3;
            background: rgba(34, 197, 94, 0.95);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
        }

        .video-atual-indicator i {
            font-size: 12px;
        }

        .video-play-overlay-sidebar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .video-relacionado-item:hover .video-play-overlay-sidebar {
            opacity: 1;
        }

        .video-play-overlay-sidebar i {
            color: #ffffff;
            font-size: 24px;
        }

        .video-relacionado-info-sidebar {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .video-sequencia-info {
            margin-bottom: 10px;
        }

        .sequencia-ordem-badge-grande {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #ef4444;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid #fecaca;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.15);
        }

        .sequencia-ordem-badge-grande i {
            font-size: 13px;
        }

        .video-relacionado-titulo-sidebar {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 8px 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-relacionado-meta-sidebar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
            color: #6b7280;
            margin-top: auto;
        }

        .video-relacionado-meta-sidebar i {
            color: #ef4444;
            font-size: 13px;
            margin-right: 4px;
        }

        .video-relacionado-meta-sidebar span {
            display: flex;
            align-items: center;
        }

        [data-theme="dark"] .videos-relacionados-sidebar {
            background: #1f2937;
            border-color: #374151;
        }

        [data-theme="dark"] .videos-relacionados-header-sidebar h3 {
            color: #f3f4f6;
        }

        [data-theme="dark"] .videos-relacionados-header-sidebar {
            border-bottom-color: #374151;
        }

        [data-theme="dark"] .video-relacionado-item {
            background: #1a1a1a;
            border-color: #374151;
        }

        [data-theme="dark"] .video-relacionado-item:hover {
            background: #252525;
            border-color: #ef4444;
        }

        [data-theme="dark"] .video-relacionado-titulo-sidebar {
            color: #f3f4f6;
        }

        [data-theme="dark"] .video-relacionado-meta-sidebar {
            color: #9ca3af;
        }

        [data-theme="dark"] .sequencia-header-info {
            border-bottom-color: #374151;
        }

        [data-theme="dark"] .sequencia-titulo-principal {
            color: #f3f4f6;
        }

        [data-theme="dark"] .sequencia-descricao {
            color: #9ca3af;
        }

        [data-theme="dark"] .sequencia-stat-item {
            color: #9ca3af;
        }

        [data-theme="dark"] .sequencia-stat-item strong {
            color: #f3f4f6;
        }

        [data-theme="dark"] .sequencia-stat-item.sequencia-atual {
            background: #3a1f1f;
            border-color: #5a2a2a;
        }

        [data-theme="dark"] .sequencia-stat-item.sequencia-atual strong {
            color: #fca5a5;
        }

        [data-theme="dark"] .video-relacionado-item.video-atual {
            background: linear-gradient(135deg, #3a1f1f 0%, #4a2a2a 100%);
            border-color: #ef4444;
        }

        [data-theme="dark"] .video-relacionado-item.video-atual:hover {
            background: linear-gradient(135deg, #4a2a2a 0%, #5a3a3a 100%);
        }

        [data-theme="dark"] .sequencia-ordem-badge-grande {
            background: linear-gradient(135deg, #3a1f1f 0%, #4a2a2a 100%);
            color: #fca5a5;
            border-color: #5a2a2a;
        }

        [data-theme="dark"] .sequencia-section {
            border-bottom-color: #374151;
        }

        [data-theme="dark"] .videos-relacionados-section {
            border-top-color: #374151;
        }

        @media (max-width: 1200px) {
            .video-content-layout {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .videos-relacionados-sidebar {
                max-width: 900px;
                margin: 0 auto;
            }

            .videos-relacionados-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 16px;
            }

            .video-relacionado-link-sidebar {
                flex-direction: column;
            }

            .video-relacionado-thumbnail-sidebar {
                width: 100%;
                height: 0;
                padding-bottom: 56.25%;
            }

            .comments-container-modern {
                grid-column: 1;
                margin: 32px auto;
            }
        }

        @media (max-width: 768px) {
            .videos-relacionados-sidebar {
                padding: 16px;
            }

            .videos-relacionados-list {
                grid-template-columns: 1fr;
            }
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: #1e293b;
            color: rgba(255, 255, 255, 0.9);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: transparent;
        }

        .sidebar-logo {
            height: 45px;
            filter: none;
        }

        .sidebar-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            display: none;
            width: 35px;
            height: 35px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
            background: transparent;
        }

        .sidebar-content::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .user-info {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            background: transparent;
            margin: 0;
            border-radius: 0;
            position: relative;
            transition: all 0.2s ease;
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.2);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
            color: rgba(255, 255, 255, 0.95);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            display: block;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .user-email {
            display: block;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 400;
        }

        .btn-edit-profile {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.08);
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 11px;
        }

        .btn-edit-profile:hover {
            background: rgba(255, 111, 0, 0.8);
            color: white;
            transform: rotate(90deg);
        }

        .sidebar-actions {
            padding: 10px 15px;
            margin-bottom: 10px;
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: white;
        }

        .sidebar-btn i {
            width: 18px;
            text-align: center;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.2s ease;
        }

        .sidebar-btn:hover i {
            color: rgba(255, 255, 255, 0.9);
        }

        .btn-upload {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            color: white;
            border: none;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.2);
        }

        .btn-upload:hover {
            background: linear-gradient(135deg, #ff8c1a 0%, #ff6f00 100%);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }

        .btn-upload i {
            color: white;
        }

        .btn-live {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-live:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
        }

        .btn-live i {
            color: inherit;
        }

        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
            margin: 15px;
        }

        .sidebar-section {
            padding: 0 15px 10px 15px;
            background: transparent;
            margin-bottom: 0;
        }

        .sidebar-section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            padding: 10px 0 8px 0;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            border-bottom: none;
        }

        .sidebar-section-title i {
            color: rgba(255, 255, 255, 0.4);
            font-size: 11px;
        }

        .setores-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .setor-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            position: relative;
            background: transparent;
            border: none;
            font-weight: 500;
            font-size: 13px;
        }

        .setor-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
        }

        .setor-item.active {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.2);
        }

        .setor-item.active:hover {
            background: linear-gradient(135deg, #ff8c1a 0%, #ff6f00 100%);
        }

        .setor-item i {
            width: 18px;
            text-align: center;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.2s ease;
        }

        .setor-item.active i {
            color: white;
        }

        .setor-item:hover i {
            color: rgba(255, 255, 255, 0.85);
        }

        .setor-item.active:hover i {
            color: white;
        }

        .setor-count {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            min-width: 22px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .setor-item.active .setor-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .setor-item:hover .setor-count {
            background: rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background: transparent;
            margin-top: auto;
        }

        .btn-logout, .btn-login {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.75);
            transition: all 0.2s ease;
        }

        .btn-logout:hover, .btn-login:hover {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
        }

        .btn-logout i, .btn-login i {
            color: inherit;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ===== TOP HEADER ===== */
        .top-header {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 70px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            padding: 0 40px;
            z-index: 999;
            gap: 30px;
        }

        .menu-toggle {
            display: none;
            background: rgba(0, 0, 0, 0.05);
            border: none;
            border-radius: 8px;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            padding: 8px;
            transition: background 0.2s ease;
        }

        .menu-toggle:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .page-title {
            flex: 1;
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .page-title .highlight {
            color: #ff6f00;
        }

        .page-title a {
            text-decoration: none;
            color: inherit;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* ===== THEME TOGGLE ===== */
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.05);
            border: none;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .theme-toggle:hover {
            background: rgba(255, 111, 0, 0.1);
            color: #ff6f00;
            transform: scale(1.1);
        }

        .user-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 15px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 20px;
            transition: all 0.2s ease;
        }

        .user-header-info:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .user-header-name {
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-header-login {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.2);
        }

        .btn-header-login:hover {
            background: linear-gradient(135deg, #ff8c1a, #ff6f00);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
            color: white;
        }

        .btn-header-login i {
            font-size: 14px;
        }

        .btn-header-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            text-decoration: none;
            border-radius: 50%;
            transition: all 0.2s ease;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-header-logout:hover {
            background: #ef4444;
            color: white;
            transform: scale(1.1);
            border-color: #ef4444;
        }

        .btn-header-logout i {
            font-size: 14px;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            padding: 0;
            height: calc(100vh - 70px);
            background: #ffffff;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .main-content::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        /* ===== VIDEO CARD MODERNO - MINIMALISTA ===== */
        .video-card-modern {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #efefef;
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }

        .video-card-modern:hover {
            border-color: #dbdbdb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .video-player-wrapper {
            position: relative;
            width: 100%;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            margin-bottom: 20px;
        }

        .video-player-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .video-info-section {
            padding: 0;
            position: relative;
        }

        .video-header-modern {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 20px;
            padding-bottom: 0;
            border-bottom: none;
        }

        .video-title-modern {
            flex: 1;
            font-size: 24px;
            font-weight: 600;
            color: #262626;
            line-height: 1.4;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .video-title-wrapper {
            flex: 1;
        }

        .video-date-modern {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            color: #8e8e8e;
            font-size: 13px;
            font-weight: 400;
        }

        .video-date-modern i {
            color: #8e8e8e;
            font-size: 12px;
        }

        .video-meta-modern {
            display: flex;
            gap: 24px;
            margin-bottom: 20px;
            padding: 0;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0;
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .meta-item:hover {
            transform: none;
        }

        .meta-icon-wrapper {
            display: none;
        }

        .meta-content {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 6px;
            flex: 1;
        }

        .meta-label {
            display: none;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 500;
            color: #262626;
        }

        .meta-value i {
            margin-right: 6px;
            font-size: 14px;
        }

        .meta-item:nth-child(2) .meta-value {
            color: #0284c7;
        }

        .meta-item:nth-child(2) .meta-value i {
            color: #0ea5e9;
        }

        .meta-item:nth-child(3) .meta-value {
            color: #7c3aed;
        }

        .meta-item:nth-child(3) .meta-value i {
            color: #8b5cf6;
        }

        .meta-setor .meta-content {
            gap: 8px;
        }

        .setor-badge-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #fef3c7;
            color: #d97706;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
            box-shadow: none;
            border: 1px solid #fde68a;
            transition: all 0.2s ease;
        }

        .setor-badge-modern:hover {
            background: #fde68a;
            border-color: #fcd34d;
            color: #b45309;
        }

        .setor-badge-modern i {
            font-size: 12px;
            color: #f59e0b;
        }

        .video-description-modern {
            font-size: 14px;
            line-height: 1.6;
            color: #262626;
            padding: 0;
            background: transparent;
            border-radius: 0;
            border: none;
            margin-bottom: 20px;
            box-shadow: none;
        }

        .video-actions-modern {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            padding-top: 16px;
            border-top: none;
            align-items: center;
            justify-content: flex-start;
        }

        /* Contador de curtidas minimalista */
        .curtidas-display {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #fef2f2;
            border-radius: 12px;
            font-weight: 600;
            color: #dc2626;
            font-size: 14px;
            border: 1px solid #fecaca;
            transition: all 0.2s ease;
        }

        .curtidas-display:hover {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        .curtidas-display i {
            display: none;
        }

        /* ===== BOTÕES DE AÇÃO MINIMALISTAS ===== */
        .btn-action-modern {
            padding: 8px;
            width: auto;
            height: auto;
            border: 1.5px solid transparent;
            border-radius: 8px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: transparent;
            box-shadow: none;
            color: #262626;
            opacity: 0.8;
        }

        .btn-action-modern:hover {
            opacity: 1;
            transform: translateY(-1px);
        }

        .btn-action-modern:active {
            transform: translateY(0) scale(0.96);
        }

        .btn-action-modern i {
            font-size: 20px;
        }

        .btn-like-modern {
            border-color: #fecaca;
            color: #dc2626;
        }

        .btn-like-modern:hover {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #b91c1c;
        }

        .btn-like-modern.active {
            color: #dc2626;
            border-color: #f87171;
            background: #fee2e2;
        }

        .btn-like-modern.active:hover {
            background: #fecaca;
            border-color: #f87171;
            color: #991b1b;
        }

        .btn-share-modern {
            border-color: #bae6fd;
            color: #0284c7;
        }

        .btn-share-modern:hover {
            color: #0369a1;
            background: #e0f2fe;
            border-color: #7dd3fc;
        }

        .btn-cinema-modern {
            border-color: #c7d2fe;
            color: #6366f1;
        }

        .btn-cinema-modern:hover {
            color: #4f46e5;
            background: #eef2ff;
            border-color: #a5b4fc;
        }

        .btn-edit-modern {
            border-color: #fde68a;
            color: #f59e0b;
        }

        .btn-edit-modern:hover {
            color: #d97706;
            background: #fef3c7;
            border-color: #fcd34d;
        }

        .btn-action-modern span {
            display: none; /* Esconde o texto, apenas ícone */
        }

        /* Tooltip para os botões */
        .btn-action-modern::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-5px);
            padding: 6px 12px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            font-size: 11px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
            margin-bottom: 8px;
        }

        .btn-action-modern:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .btn-action-modern::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.8);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            margin-bottom: -5px;
        }

        .btn-action-modern:hover::before {
            opacity: 1;
        }


        /* ===== COMMENTS MODERNOS ===== */
        .comments-wrapper {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
            clear: both;
        }

        .comments-container-modern {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 24px;
            border: 1px solid #efefef;
            position: relative;
            transition: all 0.2s ease;
            clear: both;
        }

        .comments-container-modern:hover {
            border-color: #dbdbdb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .comments-container-modern::before {
            display: none;
        }

        .comments-header-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 24px;
            padding-bottom: 0;
            border-bottom: none;
            position: relative;
        }

        .comments-header-modern::after {
            display: none;
        }

        .comments-header-modern h4 {
            font-size: 16px;
            font-weight: 600;
            color: #262626;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .comments-header-modern i {
            display: none;
        }

        .comment-form-modern {
            margin-bottom: 32px;
            padding: 0;
            background: transparent;
            border-radius: 0;
            border: none;
            box-shadow: none;
        }

        .comment-form-modern:focus-within {
            border: none;
            box-shadow: none;
        }

        .comment-form-modern textarea {
            width: 100%;
            padding: 12px 0;
            border: none;
            border-bottom: 1px solid #dbdbdb;
            border-radius: 0;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            resize: none;
            min-height: 80px;
            transition: border-color 0.2s ease;
            background: transparent;
            color: #262626;
        }

        .comment-form-modern textarea:focus {
            outline: none;
            border-bottom-width: 2px;
            border-bottom-color: #262626;
        }

        .comment-form-modern textarea::placeholder {
            color: #8e8e8e;
        }

        .comment-form-modern button {
            margin-top: 12px;
            padding: 8px 20px;
            background: #0095f6;
            color: #ffffff;
            border: 1px solid #0095f6;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0;
            box-shadow: 0 1px 3px rgba(0, 149, 246, 0.2);
            align-self: flex-end;
        }

        .comment-form-modern button:hover:not(:disabled) {
            background: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 149, 246, 0.3);
            transform: translateY(-1px);
        }

        .comment-form-modern button:active:not(:disabled) {
            background: #bae6fd;
            transform: scale(0.98);
        }

        .comment-form-modern button:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .comment-modern {
            background: transparent;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            position: relative;
            box-shadow: none;
        }

        .comment-modern:hover {
            background: #fafafa;
            border-color: #efefef;
        }

        .comment-modern:last-child {
            margin-bottom: 0;
        }

        .comment-modern::before {
            display: none;
        }

        .comment-modern:hover {
            background: transparent;
            box-shadow: none;
            transform: none;
        }

        .comment-header-modern {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .comment-author-modern {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
            border: 2px solid #ffffff;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .comment-modern:hover .comment-avatar {
            transform: scale(1.05);
            box-shadow: 0 3px 6px rgba(102, 126, 234, 0.3);
        }

        .comment-modern:hover .comment-avatar {
            transform: none;
            box-shadow: none;
        }

        .comment-author-info {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .comment-author-name {
            font-weight: 600;
            color: #262626;
            font-size: 14px;
        }

        .comment-date-modern {
            font-size: 12px;
            color: #6366f1;
            margin-top: 0;
            font-weight: 500;
        }

        .comment-content-modern {
            font-size: 14px;
            color: #262626;
            line-height: 1.5;
            margin-bottom: 12px;
            margin-left: 44px;
        }

        .comment-actions-modern {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-left: 44px;
        }

        .btn-reply-modern {
            background: transparent;
            color: #0284c7;
            border: 1px solid #bae6fd;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-reply-modern:hover {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #7dd3fc;
        }

        .btn-delete-modern {
            background: transparent;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-delete-modern:hover {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        .reply-modern {
            margin-left: 44px;
            margin-top: 12px;
            background: #fafafa;
            border: 1px solid #efefef;
            border-radius: 8px;
            padding: 12px;
            border-left: 3px solid #dbdbdb;
            transition: all 0.2s ease;
        }

        .reply-modern:hover {
            border-left-color: #c7c7c7;
            background: #f5f5f5;
            border-color: #e5e5e5;
        }

        .reply-modern .comment-avatar {
            width: 24px;
            height: 24px;
            font-size: 12px;
            border: 1px solid #dbdbdb;
        }

        .reply-modern .comment-author-name {
            font-size: 13px;
        }

        .reply-modern .comment-content-modern {
            font-size: 13px;
            margin-left: 36px;
        }

        .reply-modern .comment-actions-modern {
            margin-left: 36px;
        }

        .alert-modern {
            padding: 16px 0;
            background: transparent;
            border: none;
            border-radius: 0;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: none;
            position: relative;
        }

        .alert-modern::before {
            display: none;
        }

        .alert-modern i {
            display: none;
        }

        .alert-modern p {
            flex: 1;
            margin: 0;
            color: #8e8e8e;
            font-weight: 400;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn-login-modern {
            padding: 0;
            background: transparent;
            color: #0095f6;
            text-decoration: none;
            border-radius: 0;
            font-size: 14px;
            font-weight: 600;
            transition: opacity 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0;
            box-shadow: none;
            border: none;
            cursor: pointer;
        }

        .btn-login-modern::before {
            display: none;
        }

        .btn-login-modern:hover {
            background: transparent;
            transform: none;
            box-shadow: none;
            color: #0095f6;
            opacity: 0.7;
        }

        .btn-login-modern i {
            display: none;
        }

        .btn-login-modern span {
            display: inline;
        }

        /* ===== CINEMA MODE ===== */
        .cinema-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .cinema-overlay.active {
            display: flex;
        }

        .cinema-video-container {
            max-width: 95%;
            max-height: 95%;
            position: relative;
        }

        .cinema-video-container video {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .cinema-title {
            color: white;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            margin-top: 20px;
            padding: 0 20px;
        }

        /* ===== NOTIFICATIONS MODERNAS ===== */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 90%;
            max-width: 420px;
        }

        .notification {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 18px 22px;
            border-radius: 16px;
            background: white;
            color: #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            opacity: 0;
            transform: translateX(400px);
            animation: slideInNotification 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards, slideOutNotification 0.4s 3.6s forwards;
            border-left: 5px solid;
            position: relative;
            overflow: hidden;
        }

        .notification::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: inherit;
        }

        .notification.success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9f4 100%);
        }

        .notification.success::before {
            background: linear-gradient(180deg, #28a745, #20c997);
        }

        .notification.error {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
        }

        .notification.error::before {
            background: linear-gradient(180deg, #dc3545, #f56565);
        }

        .notification.info {
            border-left-color: #17a2b8;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
        }

        .notification.info::before {
            background: linear-gradient(180deg, #17a2b8, #3b82f6);
        }

        .notification-icon {
            font-size: 24px;
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-top: 2px;
        }

        .notification.success .notification-icon {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .notification.error .notification-icon {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .notification.info .notification-icon {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .notification-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .notification-title {
            font-weight: 600;
            font-size: 15px;
            color: #2c3e50;
        }

        .notification-message {
            color: #555;
            line-height: 1.5;
        }

        .notification .btn-close {
            background: rgba(0, 0, 0, 0.05);
            border: none;
            font-size: 16px;
            color: #666;
            cursor: pointer;
            padding: 6px;
            line-height: 1;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notification .btn-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
            transform: rotate(90deg);
        }

        @keyframes slideInNotification {
            0% {
                opacity: 0;
                transform: translateX(400px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutNotification {
            0% {
                opacity: 1;
                transform: translateX(0);
            }
            100% {
                opacity: 0;
                transform: translateX(400px);
            }
        }

        /* ===== RESPONSIVIDADE MOBILE ===== */
        @media (max-width: 768px) {
            body {
                overflow-y: auto !important;
                height: auto !important;
            }

            .main-content {
                margin-left: 0 !important;
                margin-top: 60px !important;
                padding: 16px !important;
                height: auto !important;
                min-height: calc(100vh - 60px);
            }

            .content-wrapper-inner {
                padding: 0 !important;
            }

            .top-header {
                left: 0 !important;
                padding: 0 12px;
                height: 60px;
                z-index: 1001;
            }

            .page-title {
                font-size: 16px !important;
            }

            .header-actions {
                gap: 8px;
            }

            .theme-toggle,
            .btn-header-login,
            .btn-header-logout {
                width: 40px !important;
                height: 40px !important;
                font-size: 16px !important;
            }

            .user-header-name {
                display: none;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 1003;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1002;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .menu-toggle {
                display: flex !important;
                width: 44px;
                height: 44px;
                font-size: 20px;
            }

            /* Video Card */
            .video-card-modern {
                padding: 16px !important;
                margin-bottom: 16px;
                border-radius: 8px;
            }

            .video-player-wrapper {
                margin: -16px -16px 16px -16px !important;
                border-radius: 8px 8px 0 0 !important;
            }

            .video-title-modern {
                font-size: 18px !important;
                line-height: 1.3;
            }

            .video-date-modern {
                font-size: 12px !important;
            }

            .video-meta-modern {
                gap: 16px !important;
                flex-wrap: wrap;
            }

            .meta-value {
                font-size: 13px !important;
            }

            .setor-badge-modern {
                font-size: 12px !important;
                padding: 4px 10px !important;
            }

            .video-description-modern {
                font-size: 13px !important;
            }

            /* Botões de Ação - Touch Friendly */
            .video-actions-modern {
                gap: 12px !important;
                flex-wrap: wrap;
                padding-top: 12px;
            }

            .btn-action-modern {
                padding: 12px !important;
                min-width: 44px !important;
                min-height: 44px !important;
                font-size: 22px !important;
                border-width: 2px !important;
            }

            .btn-action-modern i {
                font-size: 22px !important;
            }

            .curtidas-display {
                padding: 8px 12px !important;
                font-size: 13px !important;
                min-height: 44px !important;
                display: flex !important;
                align-items: center !important;
            }

            /* Comments Container */
            .comments-container-modern {
                padding: 16px !important;
                margin-bottom: 16px;
                border-radius: 8px;
            }

            .comments-header-modern h4 {
                font-size: 15px !important;
            }

            .comment-form-modern textarea {
                font-size: 16px !important;
                min-height: 100px !important;
                padding: 12px 0 !important;
            }

            .comment-form-modern button {
                padding: 10px 20px !important;
                font-size: 15px !important;
                min-height: 44px !important;
                width: 100% !important;
                align-self: stretch !important;
            }

            /* Comentários */
            .comment-modern {
                padding: 12px !important;
                margin-bottom: 12px;
            }

            .comment-avatar {
                width: 36px !important;
                height: 36px !important;
                font-size: 16px !important;
            }

            .comment-author-name {
                font-size: 13px !important;
            }

            .comment-date-modern {
                font-size: 11px !important;
            }

            .comment-content-modern {
                font-size: 13px !important;
                margin-left: 48px !important;
                margin-bottom: 10px;
            }

            .comment-actions-modern {
                margin-left: 48px !important;
                gap: 12px !important;
            }

            .btn-reply-modern,
            .btn-delete-modern {
                padding: 8px 12px !important;
                font-size: 13px !important;
                min-height: 36px !important;
            }

            /* Respostas */
            .reply-modern {
                margin-left: 48px !important;
                padding: 12px !important;
            }

            .reply-modern .comment-avatar {
                width: 28px !important;
                height: 28px !important;
                font-size: 13px !important;
            }

            .reply-modern .comment-content-modern {
                margin-left: 40px !important;
            }

            .reply-modern .comment-actions-modern {
                margin-left: 40px !important;
            }

            .reply-modern .comment-form-modern {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 12px !important;
            }

            .video-card-modern {
                padding: 12px !important;
            }

            .video-player-wrapper {
                margin: -12px -12px 12px -12px !important;
            }

            .video-title-modern {
                font-size: 16px !important;
            }

            .video-actions-modern {
                gap: 8px !important;
            }

            .btn-action-modern {
                padding: 10px !important;
                min-width: 40px !important;
                min-height: 40px !important;
                font-size: 20px !important;
            }

            .comments-container-modern {
                padding: 12px !important;
            }

            .comment-content-modern {
                margin-left: 44px !important;
            }

            .comment-actions-modern {
                margin-left: 44px !important;
            }

            .reply-modern {
                margin-left: 44px !important;
            }
        }

</style>

    </style>
</head>
<body>
    <div id="notification-container"></div>

<!-- Sidebar Lateral -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="img/martinez.png" alt="Logo" class="sidebar-logo">
        <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="sidebar-content">
        <?php if ($is_logged_in): ?>
            <div class="user-info">
                <div class="user-avatar" onclick="openProfileModal()">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($usuario_nome) ?></span>
                    <span class="user-role"><?= $usuario_adm ? 'Administrador' : 'Usuário' ?></span>
                    <?php
                    $check_telefone = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'telefone'");
                    $has_telefone = $check_telefone && $check_telefone->num_rows > 0;
                    
                    if ($has_telefone) {
                        $user_email_query = "SELECT email, telefone FROM usuarios WHERE id = ?";
                    } else {
                        $user_email_query = "SELECT email FROM usuarios WHERE id = ?";
                    }
                    $user_email_stmt = $conexao->prepare($user_email_query);
                    $user_email_stmt->bind_param('i', $usuario_id);
                    $user_email_stmt->execute();
                    $user_email_result = $user_email_stmt->get_result();
                    $user_data = $user_email_result->fetch_assoc();
                    $user_email_stmt->close();
                    $user_email = $user_data['email'] ?? '';
                    ?>
                    <span class="user-email"><?= htmlspecialchars($user_email) ?></span>
                </div>
                <button class="btn-edit-profile" onclick="openProfileModal()" title="Editar Perfil">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
            
            <div class="sidebar-actions">
                <?php
                // Verificar se pode fazer upload
                $pode_fazer_upload = false;
                if ($usuario_adm) {
                    $pode_fazer_upload = true;
                } else if ($is_logged_in && $usuario_id) {
                    if (!usuario_eh_cliente($conexao, $usuario_id)) {
                        $setores_permitidos = get_setores_usuario($conexao, $usuario_id);
                        $pode_fazer_upload = !empty($setores_permitidos);
                    }
                }
                ?>
                <?php if ($pode_fazer_upload): ?>
                    <a href="index.php" class="sidebar-btn">
                        <i class="fas fa-home"></i>
                        <span>Início</span>
                    </a>
                <?php endif; ?>
                <?php if ($usuario_adm): ?>
                    <div class="sidebar-divider"></div>
                    <a href="listar_usuarios.php" class="sidebar-btn">
                        <i class="fas fa-user-shield"></i>
                        <span>Usuários do Sistema</span>
                    </a>
                    <a href="listar_clientes.php" class="sidebar-btn">
                        <i class="fas fa-users"></i>
                        <span>Clientes</span>
                    </a>
                    <a href="cadastro_setores.php" class="sidebar-btn">
                        <i class="fas fa-building"></i>
                        <span>Cadastro de Setores</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="sidebar-divider"></div>
        
        <!-- Navegação por Setores -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">
                <i class="fas fa-folder"></i> SETORES
            </div>
            <div class="setores-list">
                <a href="index.php" class="setor-item">
                    <i class="fas fa-th"></i>
                    <span>Todos os Setores</span>
                </a>
                <?php
                $setores_sidebar_query = "SELECT s.id, s.nome, COUNT(v.id) AS total_videos 
                                         FROM setores s 
                                         LEFT JOIN videos v ON s.id = v.setor_id 
                                         WHERE s.ativo = 'S' 
                                         GROUP BY s.id, s.nome 
                                         ORDER BY s.nome ASC";
                $setores_sidebar_result = mysqli_query($conexao, $setores_sidebar_query);
                while ($setor_sidebar = mysqli_fetch_assoc($setores_sidebar_result)):
                ?>
                    <a href="index.php?filtroSetor=<?= $setor_sidebar['id'] ?>" class="setor-item">
                        <i class="fas fa-folder"></i>
                        <span><?= htmlspecialchars($setor_sidebar['nome']) ?></span>
                        <span class="setor-count"><?= $setor_sidebar['total_videos'] ?></span>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <div class="sidebar-footer">
        <?php if ($is_logged_in): ?>
            <a href="logout.php" class="sidebar-btn btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="sidebar-btn btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Overlay para mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Header Top (Barra Superior) -->
<div class="top-header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1 class="page-title"><a href="index.php" style="text-decoration: none; color: inherit;">Plataforma de <span class="highlight">Treinamentos</span></a></h1>
    <div class="header-actions">
        <button class="theme-toggle" id="themeToggle" title="Alternar tema">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <?php if ($is_logged_in): ?>
            <div class="user-header-info">
                <span class="user-header-name"><?= htmlspecialchars($usuario_nome) ?></span>
                <a href="logout.php" class="btn-header-logout" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-header-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h2>Upload de Vídeo</h2>
        <form id="uploadForm">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" required>
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="setor" class="form-label">Setor</label>
                <select class="form-control" id="setor" name="setor_id" required>
                    <option value="">Selecione um setor</option>
                    <?php
                    $setores_query = "SELECT id, nome FROM setores WHERE ativo = 'S'";
                    $setores_result = mysqli_query($conexao, $setores_query);

                    if ($setores_result && mysqli_num_rows($setores_result) > 0) {
                        while ($setor = mysqli_fetch_assoc($setores_result)) {
                            echo '<option value="' . htmlspecialchars($setor['id']) . '">' . htmlspecialchars($setor['nome']) . '</option>';
                        }
                    } else {
                        echo '<option value="">Erro ao carregar setores</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="video" class="form-label">Arquivo de Vídeo</label>
                <input type="file" class="form-control" id="video" name="video" accept="video/*" required>
            </div>
            <button type="button" class="btn btn-primary" onclick="uploadVideo()">Enviar</button>
            <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Fechar</button>
        </form>
        <div id="uploadNotification" class="notification hidden"></div>
        <div class="progress hidden">
            <div class="progress-bar" id="uploadProgressBar"></div>
        </div>
    </div>
</div>

<script>
    // Toggle Sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            // Previne scroll do body quando sidebar está aberta no mobile
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        }
    }

    function toggleCinemaMode() {
        const body = document.body;
        const videoContainer = document.querySelector('.video-container');

        if (!body.classList.contains('cinema-mode')) {
            body.classList.add('cinema-mode');
            if (videoContainer) videoContainer.style.zIndex = '9999';
        } else {
            body.classList.remove('cinema-mode');
            if (videoContainer) videoContainer.style.zIndex = '';
        }
    }

</script>



        

<!-- Conteúdo Principal -->
<div class="main-content" style="padding: 24px 0;">
    <div class="content-wrapper-inner" style="max-width: 1400px; margin: 0 auto; padding: 0 24px;">
    
    <!-- Container Principal: Vídeo + Recomendações ao Lado -->
    <div class="video-content-layout">
        <!-- Vídeo Principal (esquerda) -->
        <div class="video-main-content">
            <!-- Card Moderno do Vídeo -->
            <div class="video-card-modern">
                <div class="video-player-wrapper" style="margin: -24px -24px 20px -24px; border-radius: 12px 12px 0 0;">
                    <video controls poster="<?= htmlspecialchars($video['poster_url'] ?? 'img/default-thumbnail.jpg') ?>" id="video-player">
                        <source src="<?= htmlspecialchars($video['url_video']) ?>" type="video/mp4">
                        Seu navegador não suporta vídeos HTML5. Por favor, atualize para um navegador moderno.
                    </video>
                </div>
                
                <div class="video-info-section">
                    <div class="video-header-modern">
                        <div class="video-title-wrapper">
                            <h1 class="video-title-modern"><?= htmlspecialchars($video['titulo']) ?></h1>
                            <div class="video-date-modern">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Publicado em <?= date('d/m/Y', strtotime($video['data_upload'])) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="video-meta-modern">
                        <div class="meta-item">
                            <span class="setor-badge-modern">
                                <?= htmlspecialchars($video['setor_nome']) ?>
                            </span>
                        </div>
                        <?php 
                        $sequencia_nome_display = '';
                        if ($video_faz_parte_sequencia && $sequencia_info && !empty($sequencia_info['titulo'])) {
                            $sequencia_nome_display = $sequencia_info['titulo'];
                        } elseif ($video_faz_parte_sequencia && isset($video['sequencia_nome']) && !empty($video['sequencia_nome'])) {
                            $sequencia_nome_display = $video['sequencia_nome'];
                        }
                        if ($video_faz_parte_sequencia && !empty($sequencia_nome_display)): 
                        ?>
                        <div class="meta-item">
                            <span class="sequencia-badge-video">
                                <i class="fas fa-list-ol"></i>
                                <?= htmlspecialchars($sequencia_nome_display) ?>
                                <?php if (!empty($video['sequencia_ordem'])): ?>
                                    <span class="sequencia-ordem-video">Parte <?= $video['sequencia_ordem'] ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <span class="meta-value">
                                <i class="far fa-eye"></i>
                                <span id="view-count"><?= htmlspecialchars($video['visualizacoes']) ?></span>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-value">
                                <i class="far fa-comment"></i>
                                <span><?= $total_comentarios ?></span>
                            </span>
                        </div>
                    </div>
                    
                    <p class="video-description-modern"><?= htmlspecialchars($video['descricao']) ?></p>
                    
                    <div class="video-actions-modern">
                        <button id="btn-like" class="btn-action-modern btn-like-modern" onclick="curtirOuDescurtir(<?= htmlspecialchars($video_id) ?>)" title="Curtir">
                            <i class="far fa-heart" id="like-icon"></i>
                        </button>
                        <span class="curtidas-display">
                            <span id="curtidas-count"><?= htmlspecialchars($total_curtidas) ?></span>
                        </span>
                        <button class="btn-action-modern btn-share-modern" onclick="shareVideo()" title="Compartilhar" data-tooltip="Compartilhar">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button class="btn-action-modern btn-cinema-modern" onclick="toggleCinemaMode()" title="Tela Cheia" data-tooltip="Tela Cheia">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <?php if ($pode_editar): ?>
                            <button class="btn-action-modern btn-edit-modern" onclick="window.location.href='edit_video.php?id=<?= $video_id ?>'" title="Editar Vídeo" data-tooltip="Editar Vídeo">
                                <i class="fas fa-edit"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recomendações ao Lado (direita) -->
        <?php if (!empty($videos_sequencia) || !empty($videos_relacionados)): ?>
        <div class="videos-relacionados-sidebar">
            <!-- Seção de Sequência -->
            <?php if (!empty($videos_sequencia) && $sequencia_info): ?>
            <div class="sequencia-section">
                <div class="sequencia-header-info">
                    <div class="sequencia-header-badge">
                        <i class="fas fa-list-ol"></i>
                        <span>SEQUÊNCIA</span>
                    </div>
                    <h3 class="sequencia-titulo-principal">
                        <?= htmlspecialchars($sequencia_info['titulo']) ?>
                    </h3>
                    <?php if (!empty($sequencia_info['descricao'])): ?>
                    <p class="sequencia-descricao"><?= htmlspecialchars($sequencia_info['descricao']) ?></p>
                    <?php endif; ?>
                    <div class="sequencia-stats">
                        <span class="sequencia-stat-item">
                            <i class="fas fa-video"></i>
                            <strong><?= $total_videos_sequencia ?></strong> vídeo<?= $total_videos_sequencia != 1 ? 's' : '' ?>
                        </span>
                        <?php if ($video_atual_ordem): ?>
                        <span class="sequencia-stat-item sequencia-atual">
                            <i class="fas fa-play-circle"></i>
                            Você está na <strong>Parte <?= $video_atual_ordem ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="videos-relacionados-list">
                    <?php foreach ($videos_sequencia as $index => $video_seq): ?>
                    <?php
                    $is_video_atual = ($video_seq['id'] == $video_id);
                    $ordem_video = $video_seq['sequencia_ordem'] ?? ($index + 1);
                    ?>
                    <div class="video-relacionado-item <?= $is_video_atual ? 'video-atual' : '' ?>">
                        <a href="video_detalhes.php?id=<?= $video_seq['id'] ?>" class="video-relacionado-link-sidebar">
                            <div class="video-relacionado-thumbnail-sidebar">
                                <div class="video-sequencia-badge-sidebar <?= $is_video_atual ? 'badge-atual' : '' ?>">
                                    <span class="badge-numero"><?= $ordem_video ?></span>
                                </div>
                                <?php if ($is_video_atual): ?>
                                <div class="video-atual-indicator">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Assistindo</span>
                                </div>
                                <?php endif; ?>
                                <div class="video-play-overlay-sidebar">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="video-relacionado-info-sidebar">
                                <div class="video-sequencia-info">
                                    <span class="sequencia-ordem-badge-grande">
                                        <i class="fas fa-sort-numeric-up"></i>
                                        Parte <?= $ordem_video ?> de <?= $total_videos_sequencia ?>
                                    </span>
                                </div>
                                <h4 class="video-relacionado-titulo-sidebar"><?= htmlspecialchars($video_seq['titulo']) ?></h4>
                                <div class="video-relacionado-meta-sidebar">
                                    <span><i class="fas fa-eye"></i> <?= number_format($video_seq['visualizacoes'] ?? 0, 0, ',', '.') ?></span>
                                    <span><i class="fas fa-heart"></i> <?= $video_seq['curtidas'] ?? 0 ?></span>
                                    <span><i class="fas fa-comment"></i> <?= $video_seq['total_comentarios'] ?? 0 ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Seção de Vídeos Relacionados (separada da sequência) -->
            <?php if (!empty($videos_relacionados)): ?>
            <div class="videos-relacionados-section">
                <div class="videos-relacionados-header-sidebar">
                    <h3>
                        <i class="fas fa-play-circle"></i>
                        Vídeos Relacionados
                    </h3>
                </div>
                
                <div class="videos-relacionados-list">
                    <?php foreach ($videos_relacionados as $video_rel): ?>
                    <div class="video-relacionado-item">
                        <a href="video_detalhes.php?id=<?= $video_rel['id'] ?>" class="video-relacionado-link-sidebar">
                            <div class="video-relacionado-thumbnail-sidebar">
                                <div class="video-play-overlay-sidebar">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="video-relacionado-info-sidebar">
                                <h4 class="video-relacionado-titulo-sidebar"><?= htmlspecialchars($video_rel['titulo']) ?></h4>
                                <div class="video-relacionado-meta-sidebar">
                                    <span><i class="fas fa-eye"></i> <?= number_format($video_rel['visualizacoes'] ?? 0, 0, ',', '.') ?></span>
                                    <span><i class="fas fa-heart"></i> <?= $video_rel['curtidas'] ?? 0 ?></span>
                                    <span><i class="fas fa-comment"></i> <?= $video_rel['total_comentarios'] ?? 0 ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<div class="cinema-overlay" id="cinema-overlay" onclick="closeCinemaMode()">
    <div class="cinema-video-container" onclick="event.stopPropagation()">
        <video controls id="cinema-video"></video>
        <div class="cinema-title"><?= htmlspecialchars($video['titulo']) ?></div>
    </div>
</div>

<style>
/* Configurações principais do contêiner do vídeo */
.video-container {
    max-width: 1000px;
    margin: 0 auto 30px auto;
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 1;
    display: block;
    visibility: visible;
    opacity: 1;
}

/* Configuração do vídeo */
.video-wrapper {
    position: relative;
    width: 100%;
    margin-bottom: 25px;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
}

.video-wrapper video {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 12px;
}

/* Barra de progresso personalizada */
.custom-progress-container {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
}

.custom-progress-bar {
    position: absolute;
    width: 0%;
    height: 100%;
    background: linear-gradient(to right, #ff6f00, #ff8c1a);
    transition: width 0.1s ease;
    border-radius: 4px;
}

/* Detalhes do vídeo */
.video-details {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative;
    z-index: 1;
    width: 100%;
    clear: both;
    overflow: visible;
    box-sizing: border-box;
}

.video-details h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 12px;
    line-height: 1.4;
    display: block !important;
    visibility: visible !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal !important;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.video-details .video-description {
    font-size: 15px;
    color: #7f8c8d;
    line-height: 1.6;
    margin-bottom: 15px;
    display: block !important;
    visibility: visible !important;
}

.video-details p {
    font-size: 14px;
    color: #95a5a6;
    margin-bottom: 8px;
    display: block !important;
    visibility: visible !important;
}

.video-details strong {
    color: #2c3e50;
    font-weight: 600;
}

/* Botões de ação */
.video-actions {
    display: flex !important;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
    visibility: visible !important;
    opacity: 1 !important;
    width: 100%;
    clear: both;
}

.btn-like {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    background: <?= $usuario_curtiu ? 'linear-gradient(135deg, #ff6f00, #ff8c1a)' : '#f0f0f0' ?>;
    color: <?= $usuario_curtiu ? 'white' : '#333' ?>;
}

.btn-like:hover {
    background: linear-gradient(135deg, #ff8c1a, #ff6f00);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
}

.btn-cinema {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #e3f2fd;
    color: #1976d2;
}

.btn-cinema:hover {
    background: #1976d2;
    color: white;
    transform: translateY(-2px);
}

.btn-share {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f3e5f5;
    color: #7b1fa2;
}

.btn-share:hover {
    background: #7b1fa2;
    color: white;
    transform: translateY(-2px);
}


/* Modo Cinema */
.cinema-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.cinema-overlay.active {
    display: flex;
}

.cinema-video-container {
    max-width: 90%;
    max-height: 90%;
    position: relative;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
}

.cinema-video-container video {
    width: 100%;
    height: auto;
    object-fit: contain;
}

/* Responsividade */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 20px 15px;
    }

    .top-header {
        left: 0;
    }

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .sidebar-overlay.active {
        display: block;
    }

    .menu-toggle {
        display: block;
    }

    .video-container, .comments-container {
        padding: 20px;
    }

    .video-details h3 {
        font-size: 20px;
    }

    .video-wrapper {
        padding-top: 56.25%;
    }
}
.custom-progress-container:hover::after {
    content: attr(data-time);
    position: absolute;
    top: -20px;
    left: calc(var(--mouse-x) - 20px);
    background: #333;
    color: #fff;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 0.8rem;
}
</style>


<script>
// Função para criar notificações modernas
function showNotification(type, message, title = '') {
    const container = document.getElementById('notification-container');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    // Ícones por tipo
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle'
    };

    const titles = {
        success: title || 'Sucesso!',
        error: title || 'Erro!',
        info: title || 'Informação'
    };

    notification.innerHTML = `
        <div class="notification-icon">
            <i class="${icons[type]}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${titles[type]}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="btn-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(notification);

    // Remove automaticamente após 4 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutNotification 0.4s forwards';
        setTimeout(() => notification.remove(), 400);
    }, 4000);
}

// Função para ativar o modo cinema
function toggleCinemaMode() {
    const overlay = document.getElementById('cinema-overlay');
    const cinemaVideo = document.getElementById('cinema-video');
    const mainVideo = document.getElementById('video-player');

    // Configura o vídeo para o modo cinema
    cinemaVideo.src = mainVideo.querySelector('source').src;
    cinemaVideo.poster = mainVideo.poster;
    cinemaVideo.currentTime = mainVideo.currentTime;

    cinemaVideo.load();
    overlay.classList.add('active');
    
    // Pausa o vídeo original do contêiner
    mainVideo.pause();

    // Reproduz o vídeo no modo cinema
    cinemaVideo.play().catch(error => console.error('Erro ao reproduzir o vídeo no modo cinema:', error));
}

// Função para fechar o modo cinema
function closeCinemaMode() {
    const overlay = document.getElementById('cinema-overlay');
    const cinemaVideo = document.getElementById('cinema-video');
    const mainVideo = document.getElementById('video-player');

    // Sincroniza o tempo do vídeo principal com o vídeo do modo cinema
    mainVideo.currentTime = cinemaVideo.currentTime;

    // Pausa o vídeo do modo cinema
    cinemaVideo.pause();

    // Fecha o modo cinema
    overlay.classList.remove('active');
}

// Atualizar a barra de progresso conforme o vídeo toca
const video = document.getElementById('video-player');
const progressBarContainer = document.querySelector('.custom-progress-container');
const progressBar = document.getElementById('custom-progress-bar');

video.addEventListener('timeupdate', () => {
    const percentage = (video.currentTime / video.duration) * 100;
    progressBar.style.width = `${percentage}%`;
});

// Permitir que o usuário clique na barra para mudar o tempo do vídeo
progressBarContainer.addEventListener('click', (e) => {
    const rect = progressBarContainer.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const percentage = clickX / rect.width;
    video.currentTime = percentage * video.duration;
});

// Adiciona interação para destacar a barra ao passar o mouse
progressBarContainer.addEventListener('mouseenter', () => {
    progressBarContainer.style.background = '#ccc';
});
progressBarContainer.addEventListener('mouseleave', () => {
    progressBarContainer.style.background = '#e0e0e0';
});
progressBarContainer.addEventListener('mousemove', (e) => {
    const rect = progressBarContainer.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const percentage = mouseX / rect.width;
    const time = video.duration * percentage;
    progressBarContainer.setAttribute('data-time', formatTime(time));
    progressBarContainer.style.setProperty('--mouse-x', `${mouseX}px`);
});

function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
    return `${minutes}:${secs}`;
}
// Função para criar notificações modernas
function showNotification(type, message, title = '') {
    const container = document.getElementById('notification-container');
    if (!container) return;

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    // Ícones por tipo
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle'
    };

    const titles = {
        success: title || 'Sucesso!',
        error: title || 'Erro!',
        info: title || 'Informação'
    };

    notification.innerHTML = `
        <div class="notification-icon">
            <i class="${icons[type]}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${titles[type]}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="btn-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(notification);

    // Remove automaticamente após 4 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutNotification 0.4s forwards';
        setTimeout(() => notification.remove(), 400);
    }, 4000);
}

// Função para compartilhar o link do vídeo
function shareVideo() {
    const videoUrl = window.location.href;
    const videoTitle = document.querySelector('.video-title-modern')?.textContent.trim() || 'Vídeo';
    const shareData = {
        title: videoTitle,
        text: `Confira este vídeo: ${videoTitle}`,
        url: videoUrl,
    };

    if (navigator.share) {
        navigator.share(shareData)
            .then(() => showNotification('success', 'Vídeo compartilhado com sucesso!'))
            .catch(err => {
                if (err.name !== 'AbortError') {
                    console.error('Erro ao compartilhar:', err);
                }
            });
    } else {
        navigator.clipboard.writeText(videoUrl)
            .then(() => showNotification('success', 'Link do vídeo copiado para a área de transferência!'))
            .catch(err => {
                console.error('Erro ao copiar o link: ', err);
                showNotification('error', 'Não foi possível copiar o link. Tente novamente.');
            });
    }
}

// ===== DARK MODE TOGGLE =====
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        if (theme === 'dark') {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    }
}

// Inicializar tema ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
});
</script>





   
   
    </div> <!-- Fim do content-wrapper-inner -->
    
    <!-- Seção de Comentários Moderna (fora do grid) -->
    <div class="comments-wrapper">
        <div class="comments-container-modern">
        <div class="comments-header-modern">
            <h4>Comentários</h4>
            <span style="color: #8e8e8e; font-size: 14px; font-weight: 400;"><?= $total_comentarios ?></span>
        </div>
        
        <?php if ($is_logged_in): ?>
            <div class="comment-form-modern">
                <textarea id="commentText" placeholder="Adicione um comentário..." rows="3"></textarea>
                <button onclick="adicionarComentario(<?= htmlspecialchars($video_id) ?>)">
                    Publicar
                </button>
            </div>
        <?php else: ?>
            <div class="alert-modern">
                <i class="fas fa-exclamation-circle"></i>
                <p>Você precisa estar logado para comentar.</p>
                <a href="login.php" class="btn-login-modern">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Fazer Login</span>
                </a>
            </div>
        <?php endif; ?>

        <div id="comentarios-list">
            <?php while ($comentario = $comentarios_result->fetch_assoc()): ?>
                <div class="comment-modern" id="comment-<?= htmlspecialchars($comentario['id']) ?>">
                    <div class="comment-header-modern">
                        <div class="comment-author-modern">
                            <div class="comment-avatar">
                                <?= strtoupper(substr($comentario['usuario_nome'], 0, 1)) ?>
                            </div>
                            <div class="comment-author-info">
                                <span class="comment-author-name"><?= htmlspecialchars($comentario['usuario_nome']) ?></span>
                                <span class="comment-date-modern"><?= date('d/m/Y H:i', strtotime($comentario['data'])) ?></span>
                            </div>
                        </div>
                        <?php if ($usuario_adm || $comentario['usuario_id'] == $usuario_id): ?>
                            <button class="btn-delete-modern" onclick="excluirComentario(<?= htmlspecialchars($comentario['id']) ?>)">
                                Excluir
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="comment-content-modern"><?= htmlspecialchars($comentario['conteudo']) ?></p>
                    
                    <div class="comment-actions-modern">
                        <?php if ($is_logged_in): ?>
                            <button class="btn-reply-modern" onclick="abrirRespostaForm(<?= htmlspecialchars($comentario['id']) ?>)">
                                Responder
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Formulário de Resposta -->
                    <?php if ($is_logged_in): ?>
                        <div class="comment-form-modern" id="resposta-form-<?= htmlspecialchars($comentario['id']) ?>" style="display: none; margin-top: 12px; margin-left: 44px;">
                            <textarea id="resposta-conteudo-<?= htmlspecialchars($comentario['id']) ?>" placeholder="Adicione uma resposta..." rows="2"></textarea>
                            <button onclick="enviarResposta(<?= htmlspecialchars($comentario['id']) ?>)">
                                Publicar
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Respostas -->
                    <?php
                    $respostas_query = "SELECT respostas.*, usuarios.nome AS usuario_nome, usuarios.adm AS is_adm 
                                        FROM respostas 
                                        JOIN usuarios ON respostas.usuario_id = usuarios.id 
                                        WHERE respostas.comentario_id = ? 
                                        ORDER BY respostas.data ASC";
                    $stmt_respostas = $conexao->prepare($respostas_query);
                    $stmt_respostas->bind_param('i', $comentario['id']);
                    $stmt_respostas->execute();
                    $respostas_result = $stmt_respostas->get_result();
                    ?>
                    <?php while ($resposta = $respostas_result->fetch_assoc()): ?>
                        <div class="reply-modern">
                            <div class="comment-header-modern">
                                <div class="comment-author-modern">
                                    <div class="comment-avatar">
                                        <?= strtoupper(substr($resposta['usuario_nome'], 0, 1)) ?>
                                    </div>
                                    <div class="comment-author-info">
                                        <span class="comment-author-name">
                                            <?= htmlspecialchars($resposta['usuario_nome']) ?>
                                            <?php if ($resposta['is_adm']): ?>
                                                <i class="fas fa-shield-alt" style="color: #ff6f00; margin-left: 5px;"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="comment-date-modern"><?= date('d/m/Y H:i', strtotime($resposta['data'])) ?></span>
                                    </div>
                                </div>
                                <?php if ($usuario_adm || $resposta['usuario_id'] == $usuario_id): ?>
                                    <button class="btn-delete-modern" onclick="excluirResposta(<?= htmlspecialchars($resposta['id']) ?>)">
                                        Excluir
                                    </button>
                                <?php endif; ?>
                            </div>
                            <p class="comment-content-modern"><?= htmlspecialchars($resposta['conteudo']) ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div> <!-- Fim do comments-container-modern -->
    </div> <!-- Fim do comments-wrapper -->
    </div> <!-- Fim do content-wrapper-inner -->
</div>
<!-- Fim do main-content -->

<style>
/* Estilos adicionais para comentários */
.comments-container h4 {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Contêiner de Comentários */
.comment-box {
    background: #f9f9f9;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.comment-box:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.comment-author {
    display: flex;
    align-items: center;
    font-size: 14px;
    gap: 8px;
}

.comment-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}

.comment-meta .btn-delete {
    margin-left: 10px;
    color: #ff5b5b;
    background: transparent;
    border: none;
    font-size: 16px;
    cursor: pointer;
    transition: color 0.3s ease;
}

.comment-meta .btn-delete:hover {
    color: #cc4444;
}

.comment-date {
    font-size: 12px;
    color: #888;
}

.reply-box {
    background: #e9f4ff;
    border-radius: 12px;
    padding: 15px;
    margin: 10px 0 10px 20px;
    border-left: 4px solid #007bff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.reply-box-adm {
    background: #fff8e1;
    border-left: 4px solid #ff6f00;
}

textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    resize: none;
    margin-bottom: 10px;
    font-family: 'Roboto', sans-serif;
    transition: all 0.3s ease;
}

textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 6px rgba(0, 123, 255, 0.4);
}

.btn {
    border: none;
    cursor: pointer;
    padding: 8px 15px;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.btn-submit, .btn-reply {
    background: #007bff;
    color: white;
}

.btn-submit:hover, .btn-reply:hover {
    background: #0056b3;
}

.btn-delete {
    color: #ff5b5b;
}

.btn-delete:hover {
    color: #cc4444;
}
</style>



<!-- Footer -->
<div class="footer" id="footer">
    <div class="footer-content">
        <p>&copy; 2024 Gabriel Silva. Todos os direitos reservados.</p>
        <p>
            <a href="#" class="open-modal" data-modal="privacy-modal">Política de Privacidade</a>
            |
            <a href="#" class="open-modal" data-modal="terms-modal">Termos de Uso</a>
        </p>
    </div>
</div>

<style>
.footer {
    background: linear-gradient(45deg, #2d2d2d, #1e1e1e);
    color: #fff;
    padding: 20px 10px;
    text-align: center;
    font-family: Arial, sans-serif;
    border-top: 2px solid #ff6f00;
    box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.2);
}

.footer a {
    color: #ff6f00;
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

.footer a:hover {
    color: #ff8c1a;
    text-decoration: underline;
}

.footer-content {
    max-width: 800px;
    margin: 0 auto;
}

.footer-content p {
    margin: 5px 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .footer {
        padding: 15px;
    }

    .footer-content p {
        font-size: 0.8rem;
    }
}
</style>


<!-- Modais -->
<div id="privacy-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('privacy-modal')">&times;</span>
        <h2>Política de Privacidade</h2>
        <p>A sua privacidade é importante para nós. É política do Biblioteca de Treinamento respeitar a sua privacidade em relação a qualquer informação sua que possamos coletar no site <a href="Biblioteca de Treinamento">Biblioteca de Treinamento</a>, e outros sites que possuímos e operamos.</p>
        <p>Solicitamos informações pessoais apenas quando realmente precisamos delas para lhe fornecer um serviço. Fazemo-lo por meios justos e legais, com o seu conhecimento e consentimento. Também informamos por que estamos coletando e como será usado.</p>
        <p>Apenas retemos as informações coletadas pelo tempo necessário para fornecer o serviço solicitado. Quando armazenamos dados, protegemos dentro de meios comercialmente aceitáveis ​​para evitar perdas e roubos, bem como acesso, divulgação, cópia, uso ou modificação não autorizados.</p>
        <p>Não compartilhamos informações de identificação pessoal publicamente ou com terceiros, exceto quando exigido por lei.</p>
        <p>O nosso site pode ter links para sites externos que não são operados por nós. Esteja ciente de que não temos controle sobre o conteúdo e práticas desses sites e não podemos aceitar responsabilidade por suas respectivas <a href="https://politicaprivacidade.com/" rel="noopener noreferrer" target="_blank">políticas de privacidade</a>.</p>
        <p>Você é livre para recusar a nossa solicitação de informações pessoais, entendendo que talvez não possamos fornecer alguns dos serviços desejados.</p>
        <p>O uso continuado de nosso site será considerado como aceitação de nossas práticas em torno de privacidade e informações pessoais.</p>
    </div>
</div>

<div id="terms-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('terms-modal')">&times;</span>
        <h2>Termos de Uso</h2>
        <h2>1. Termos</h2>
        <p>Ao acessar ao site <a href='Biblioteca De Treinamento'>Biblioteca De Treinamento</a>, concorda em cumprir estes <a href=https://privacidade.me/ target='_BLANK'>termos de uso</a>, todas as leis e regulamentos aplicáveis ​​e concorda que é responsável pelo cumprimento de todas as leis locais aplicáveis.</p>
        <h2>2. Uso de Licença</h2>
        <p>É concedida permissão para baixar temporariamente uma cópia dos materiais (informações ou software) no site Biblioteca De Treinamento, apenas para visualização transitória pessoal e não comercial.</p>
        <ol>
            <li>Modificar ou copiar os materiais;</li>
            <li>Usar os materiais para qualquer finalidade comercial ou para exibição pública;</li>
            <li>Tentar descompilar ou fazer engenharia reversa de qualquer software;</li>
        </ol>
        <p>Esta licença será automaticamente rescindida se você violar alguma dessas restrições.</p>
    </div>
</div>

<style>
/* Estilo Geral */
body { font-family: Arial, sans-serif; margin: 0; padding: 0; position: relative; }



/* Modais */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-content {
    background: #fff;
    padding: 20px;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
    animation: fadeIn 0.5s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}
.close {
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.querySelectorAll('.open-modal').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = link.dataset.modal;
        document.getElementById(modalId).style.display = 'flex';
    });
});

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = (event) => {
    document.querySelectorAll('.modal').forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};
</script>


<div id="toast-container"></div>

<style>
/* Fundo atualizado */
body {
    background: linear-gradient(to bottom, #f3f3f3, #ffe8d6);
    color: #333;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
}

/* Container de comentários */
.comments-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

/* Títulos */
h4 {
    color: #ff6f00;
    font-weight: bold;
    font-size: 1.6rem;
    text-align: center;
    margin-bottom: 20px;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
}

/* Comentários e respostas */
.comment, .reply {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    border: 1px solid #ddd;
    transition: box-shadow 0.3s ease;
}

.comment:hover, .reply:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.comment .content, .reply .content {
    flex: 1;
}

strong {
    color: #333;
    font-size: 14px;
    font-weight: bold;
}

.comment p, .reply p {
    font-size: 14px;
    color: #555;
    margin: 5px 0;
}

small {
    font-size: 12px;
    color: #888;
}

/* Botões */
.btn {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: bold;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn-comment {
    background: #ff6f00;
    color: white;
}

.btn-login {
    background: #28a745;
    color: white;
}

.btn-excluir {
    background: #e63946;
    color: white;
}

.btn-reply, .btn-send {
    background: #28a745;
    color: white;
}

textarea {
    width: 100%;
    background: #ffffff;
    border: 1px solid #ddd;
    color: #333;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
}

/* Notificações flutuantes */
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: fadeInOut 4s;
}

.toast.success {
    border-left: 5px solid #28a745;
}

.toast.error {
    border-left: 5px solid #e63946;
}

.toast button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
    margin-left: 10px;
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-20px); }
    10%, 90% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
}
</style>

<script>
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast', type);
    toast.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()">✖</button>`;
    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4000);
}

function abrirRespostaForm(comentarioId) {
    const respostaForm = document.getElementById(`resposta-form-${comentarioId}`);
    respostaForm.style.display = respostaForm.style.display === 'none' ? 'block' : 'none';
}

function enviarResposta(comentarioId) {
    const conteudo = document.getElementById(`resposta-conteudo-${comentarioId}`).value.trim();

    if (!conteudo) {
        showToast('A resposta não pode estar vazia.', 'error');
        return false;
    }

    // Simulação de envio
    showToast('Resposta enviada com sucesso!', 'success');
}
</script>


        

    <script>
        function curtirOuDescurtir(videoId) {
            const btnLike = document.getElementById('btn-like');
            const curtidasCount = document.getElementById('curtidas-count');

            fetch('add_curtida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    curtidasCount.textContent = data.curtidas;
                    btnLike.classList.toggle('liked', data.action === 'added');
                    btnLike.style.backgroundColor = data.action === 'added' ? '#ff6f00' : '#f1f1f1';
                    btnLike.style.color = data.action === 'added' ? '#fff' : '#333';
                } else {
                    alert(data.error || 'Erro ao processar.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }

        function adicionarComentario(videoId) {
            const comentarioInput = document.getElementById('commentText');
            const comentario = comentarioInput.value.trim();

            if (comentario === '') {
                alert('O comentário não pode estar vazio.');
                return;
            }

            fetch('add_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, conteudo: comentario })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao adicionar o comentário.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }

        function adicionarResposta(comentarioId) {
            const respostaInput = document.getElementById(`resposta-text-${comentarioId}`);
            const resposta = respostaInput.value.trim();

            if (resposta === '') {
                alert('A resposta não pode estar vazia.');
                return;
            }

            fetch('add_resposta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comentario_id: comentarioId, conteudo: resposta })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao adicionar a resposta.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }

        function abrirRespostaForm(comentarioId) {
            const respostaForm = document.getElementById(`resposta-form-${comentarioId}`);
            respostaForm.style.display = respostaForm.style.display === 'none' ? 'block' : 'none';
        }

        function excluirComentario(comentarioId) {
            if (!confirm("Tem certeza de que deseja excluir este comentário?")) {
                return;
            }

            fetch('delete_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comentario_id: comentarioId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Comentário excluído com sucesso!');
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao excluir o comentário.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }
        
    function excluirSelecionados() {
    const checkboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    const tipoExclusao = document.getElementById('tipoExclusao').value;

    if (checkboxes.length === 0) {
        showToast('Por favor, selecione pelo menos um comentário ou resposta para excluir.', 'error');
        return;
    }

    const ids = Array.from(checkboxes).map(checkbox => checkbox.value);

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));
    formData.append('tipo', tipoExclusao);

    fetch('admin_bulk_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Itens excluídos com sucesso!', 'success');
            location.reload();
        } else {
            showToast(data.error || 'Erro ao processar a exclusão.', 'error');
        }
    })
    .catch(() => showToast('Erro ao processar a solicitação.', 'error'));
}


    function abrirRespostaForm(comentarioId) {
        const respostaForm = document.getElementById(`resposta-form-${comentarioId}`);
        respostaForm.style.display = respostaForm.style.display === 'none' ? 'block' : 'none';
    }

    function enviarResposta(comentarioId) {
        const respostaConteudo = document.getElementById(`resposta-conteudo-${comentarioId}`).value.trim();

        if (!respostaConteudo) {
            alert('A resposta não pode estar vazia.');
            return;
        }

        fetch('add_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comentario_id: comentarioId, conteudo: respostaConteudo })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Resposta adicionada com sucesso!');
                location.reload();
            } else {
                alert(data.error || 'Erro ao adicionar a resposta.');
            }
        })
        .catch(() => alert('Erro ao processar a solicitação.'));
    }

document.addEventListener('DOMContentLoaded', () => {
    const videoPlayer = document.getElementById('video-player');
    const videoId = <?= $video_id ?>; // ID do vídeo vindo do backend
    let viewRecorded = false;

    let tempoAssistido = 0;
    let completou = false;
    
    videoPlayer.addEventListener('timeupdate', () => {
        tempoAssistido = Math.floor(videoPlayer.currentTime);
        completou = videoPlayer.currentTime >= (videoPlayer.duration - 5); // Considera completo se faltam menos de 5 segundos
        
        // Registra visualização após 5 segundos de reprodução
        if (!viewRecorded && videoPlayer.currentTime > 5) {
            fetch('update_visualizacoes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('view-count').textContent = data.visualizacoes;
                    viewRecorded = true; // Evita múltiplas requisições
                    
                    // Registra no histórico para recomendações
                    fetch('registrar_visualizacao.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            video_id: videoId,
                            tempo_assistido: tempoAssistido,
                            completou: completou
                        })
                    }).catch(err => console.error('Erro ao registrar histórico:', err));
                } else {
                    console.error('Erro do backend:', data.error);
                }
            })
            .catch(err => console.error('Erro ao conectar ao backend:', err));
        }
    });
    
    // Atualiza histórico periodicamente enquanto assiste
    setInterval(() => {
        if (viewRecorded && tempoAssistido > 0) {
            fetch('registrar_visualizacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    video_id: videoId,
                    tempo_assistido: tempoAssistido,
                    completou: completou
                })
            }).catch(err => console.error('Erro ao atualizar histórico:', err));
        }
    }, 30000); // A cada 30 segundos
});


    </script>
</body>
</html>
