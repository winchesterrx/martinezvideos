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

// Verifica se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
$usuario_nome = $_SESSION['user_nome'] ?? null;
$usuario_adm = isset($_SESSION['user_adm']) ?? false;
$usuario_id = $_SESSION['user_id'] ?? null;

// Configuração de busca e filtro
$filtro_setor = isset($_GET['filtroSetor']) ? intval($_GET['filtroSetor']) : 0;
$filtro_modulo = isset($_GET['filtroModulo']) ? intval($_GET['filtroModulo']) : 0;
$busca_titulo = isset($_GET['pesquisaTitulo']) ? trim($_GET['pesquisaTitulo']) : '';

// Configuração de paginação
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$videos_por_pagina = 6;
$offset = ($pagina_atual - 1) * $videos_por_pagina;

// Construção da query dinâmica usando prepared statements
$where_conditions = [];
$params = [];
$types = "";

if ($filtro_setor > 0) {
    $where_conditions[] = "videos.setor_id = ?";
    $params[] = $filtro_setor;
    $types .= "i";
}

if ($filtro_modulo > 0) {
    $where_conditions[] = "videos.modulo_id = ?";
    $params[] = $filtro_modulo;
    $types .= "i";
}

if (!empty($busca_titulo)) {
    $where_conditions[] = "videos.titulo LIKE ?";
    $params[] = "%" . $busca_titulo . "%";
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Busca os vídeos
// Verificar se tabela sequencias existe
$check_sequencias = $conexao->query("SHOW TABLES LIKE 'sequencias'");
$sequencias_exists = $check_sequencias && $check_sequencias->num_rows > 0;

if ($sequencias_exists) {
    $videos_query = "SELECT videos.*, setores.nome AS setor_nome, 
                            modulos.nome AS modulo_nome, modulos.icone AS modulo_icone, modulos.cor AS modulo_cor,
                            sequencias.titulo AS sequencia_nome,
                            (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id) AS curtidas,
                            (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = videos.id) AS total_comentarios,
                            videos.visualizacoes AS visualizacoes,
                            (SELECT COUNT(*) FROM videos v2 WHERE v2.sequencia_id = videos.sequencia_id) AS total_sequencia
                     FROM videos 
                     JOIN setores ON videos.setor_id = setores.id 
                     LEFT JOIN modulos ON videos.modulo_id = modulos.id
                     LEFT JOIN sequencias ON videos.sequencia_id = sequencias.id
                     $where_clause
                     ORDER BY videos.data_upload DESC 
                     LIMIT ? OFFSET ?";
} else {
    $videos_query = "SELECT videos.*, setores.nome AS setor_nome, 
                            modulos.nome AS modulo_nome, modulos.icone AS modulo_icone, modulos.cor AS modulo_cor,
                            NULL AS sequencia_nome,
                            (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id) AS curtidas,
                            (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = videos.id) AS total_comentarios,
                            videos.visualizacoes AS visualizacoes,
                            0 AS total_sequencia
                     FROM videos 
                     JOIN setores ON videos.setor_id = setores.id 
                     LEFT JOIN modulos ON videos.modulo_id = modulos.id
                     $where_clause
                     ORDER BY videos.data_upload DESC 
                     LIMIT ? OFFSET ?";
}

$params[] = $videos_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt_videos = $conexao->prepare($videos_query);
if (!$stmt_videos) {
    die(json_encode(['error' => 'Erro ao preparar consulta']));
}

if (!empty($types)) {
    $stmt_videos->bind_param($types, ...$params);
}
$stmt_videos->execute();
$videos_result = $stmt_videos->get_result();

// Total de vídeos
$count_params = [];
$count_types = "";
if ($filtro_setor > 0) {
    $count_params[] = $filtro_setor;
    $count_types .= "i";
}
if ($filtro_modulo > 0) {
    $count_params[] = $filtro_modulo;
    $count_types .= "i";
}
if (!empty($busca_titulo)) {
    $count_params[] = "%" . $busca_titulo . "%";
    $count_types .= "s";
}

$count_query = "SELECT COUNT(*) as total 
                FROM videos 
                JOIN setores ON videos.setor_id = setores.id 
                LEFT JOIN modulos ON videos.modulo_id = modulos.id
                $where_clause";
$stmt_count = $conexao->prepare($count_query);
if (!$stmt_count) {
    die(json_encode(['error' => 'Erro ao preparar contagem']));
}

if (!empty($count_types)) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_videos = $count_result->fetch_assoc()['total'];
$total_paginas = ceil($total_videos / $videos_por_pagina);

// Busca nome do setor e módulo para breadcrumb
$setor_nome = 'Todos os Setores';
$modulo_nome = '';
if ($filtro_setor > 0) {
    $setor_query = "SELECT nome FROM setores WHERE id = ?";
    $stmt_setor = $conexao->prepare($setor_query);
    $stmt_setor->bind_param("i", $filtro_setor);
    $stmt_setor->execute();
    $setor_result = $stmt_setor->get_result();
    if ($setor_row = $setor_result->fetch_assoc()) {
        $setor_nome = $setor_row['nome'];
    }
    $stmt_setor->close();
    
    if ($filtro_modulo > 0) {
        $modulo_query = "SELECT nome FROM modulos WHERE id = ?";
        $stmt_modulo = $conexao->prepare($modulo_query);
        $stmt_modulo->bind_param("i", $filtro_modulo);
        $stmt_modulo->execute();
        $modulo_result = $stmt_modulo->get_result();
        if ($modulo_row = $modulo_result->fetch_assoc()) {
            $modulo_nome = $modulo_row['nome'];
        }
        $stmt_modulo->close();
    }
}

// Retorna JSON com HTML dos vídeos
header('Content-Type: application/json; charset=utf-8');

$videos_html = '';
$pode_editar = false;
if ($is_logged_in && $usuario_id) {
    if ($usuario_adm) {
        $pode_editar = true;
    } else {
        $setores_permitidos = get_setores_usuario($conexao, $usuario_id);
        $pode_editar = !empty($setores_permitidos);
    }
}

if ($videos_result && mysqli_num_rows($videos_result) > 0) {
    mysqli_data_seek($videos_result, 0);
    while ($video = mysqli_fetch_assoc($videos_result)) {
        if (!isset($video['id']) || empty($video['id'])) {
            continue;
        }
        
        $visualizacoes_formatadas = number_format($video['visualizacoes'], 0, ',', '.');
        
        // Verificar se faz parte de sequência
        $is_sequencia = isset($video['is_sequencia']) && ($video['is_sequencia'] == 1 || $video['is_sequencia'] === '1');
        $sequencia_ordem = isset($video['sequencia_ordem']) ? intval($video['sequencia_ordem']) : 0;
        $total_sequencia = isset($video['total_sequencia']) ? intval($video['total_sequencia']) : 0;
        $sequencia_nome = isset($video['sequencia_nome']) ? htmlspecialchars($video['sequencia_nome']) : '';
        
        $videos_html .= '<div class="video-card' . ($is_sequencia ? ' video-card-sequencia' : '') . '" data-video-id="' . $video['id'] . '" data-sequencia="' . ($is_sequencia ? '1' : '0') . '">';
        $videos_html .= '<div class="video-card-thumbnail">';
        
        // Thumbnail/preview do vídeo
        $video_url = htmlspecialchars($video['url_video'] ?? '');
        if (!empty($video_url)) {
            $videos_html .= '<video class="video-thumbnail-preview" preload="metadata" muted playsinline data-video-src="' . $video_url . '">';
            $videos_html .= '<source src="' . $video_url . '" type="video/mp4">';
            $videos_html .= '</video>';
        } else {
            // Fallback com ícone se não houver vídeo
            $videos_html .= '<div class="video-thumbnail-fallback">';
            $videos_html .= '<i class="fas fa-video"></i>';
            $videos_html .= '</div>';
        }
        
        // Badge de sequência no thumbnail
        if ($is_sequencia && $sequencia_ordem > 0) {
            $videos_html .= '<div class="video-sequencia-badge-card">';
            $videos_html .= '<span class="sequencia-badge-number">' . $sequencia_ordem . '</span>';
            if ($total_sequencia > 0) {
                $videos_html .= '<span class="sequencia-badge-total">/' . $total_sequencia . '</span>';
            }
            $videos_html .= '</div>';
        }
        
        $videos_html .= '<a href="video_detalhes.php?id=' . $video['id'] . '" class="video-play-overlay" style="text-decoration: none; color: inherit;"><i class="fas fa-play"></i></a>';
        $videos_html .= '</div>';
        $videos_html .= '<div class="video-card-content">';
        $videos_html .= '<div class="video-card-tags">';
        $videos_html .= '<div class="video-card-setor"><i class="fas fa-tag"></i><span>' . htmlspecialchars($video['setor_nome']) . '</span></div>';
        
        if (!empty($video['modulo_nome'])) {
            $modulo_cor = htmlspecialchars($video['modulo_cor'] ?? '#6366f1');
            $modulo_icone = htmlspecialchars($video['modulo_icone'] ?? 'fas fa-cube');
            $videos_html .= '<div class="video-card-modulo" style="--modulo-color: ' . $modulo_cor . ';"><i class="' . $modulo_icone . '"></i><span>' . htmlspecialchars($video['modulo_nome']) . '</span></div>';
        }
        $videos_html .= '</div>';
        
        $videos_html .= '<h3 class="video-card-title">' . htmlspecialchars($video['titulo']) . '</h3>';
        $videos_html .= '<p class="video-card-description">' . htmlspecialchars($video['descricao']) . '</p>';
        $videos_html .= '<div class="video-card-stats">';
        $videos_html .= '<span class="stat-item stat-likes"><i class="fas fa-heart"></i> <span class="curtidas-count" id="curtidas-' . $video['id'] . '">' . $video['curtidas'] . '</span></span>';
        $videos_html .= '<span class="stat-item stat-comments"><i class="fas fa-comments"></i> <span class="comentarios-count" id="comentarios-' . $video['id'] . '">' . $video['total_comentarios'] . '</span></span>';
        $videos_html .= '<span class="stat-item stat-views"><i class="fas fa-eye"></i> <span id="views-' . $video['id'] . '">' . $visualizacoes_formatadas . '</span></span>';
        $videos_html .= '<span class="stat-item stat-date"><i class="fas fa-calendar"></i> ' . date('d/m/Y', strtotime($video['data_upload'])) . '</span>';
        $videos_html .= '</div>';
        $videos_html .= '<div class="video-card-actions">';
        $videos_html .= '<button type="button" class="video-card-btn btn-like" data-video-id="' . $video['id'] . '" data-tooltip="Curtir" title="Curtir"><i class="fas fa-heart"></i></button>';
        $videos_html .= '<a href="video_detalhes.php?id=' . $video['id'] . '" class="video-card-btn btn-play" data-tooltip="Assistir" title="Assistir"><i class="fas fa-play"></i></a>';
        $videos_html .= '<button type="button" class="video-card-btn btn-share" data-video-id="' . $video['id'] . '" data-tooltip="Compartilhar" title="Compartilhar"><i class="fas fa-share-alt"></i></button>';
        
        if ($pode_editar) {
            $videos_html .= '<button type="button" class="video-card-btn btn-edit" data-video-id="' . $video['id'] . '" data-video-title="' . htmlspecialchars($video['titulo'], ENT_QUOTES) . '" data-video-desc="' . htmlspecialchars($video['descricao'], ENT_QUOTES) . '" data-setor-id="' . $video['setor_id'] . '" data-tooltip="Editar" title="Editar"><i class="fas fa-edit"></i></button>';
        }
        
        $videos_html .= '</div>';
        $videos_html .= '</div>';
        $videos_html .= '</div>';
    }
} else {
    $videos_html = '<div class="no-videos"><i class="fas fa-video-slash"></i><p>Nenhum vídeo encontrado.</p><p>Tente ajustar os filtros ou verifique outros setores na barra lateral.</p></div>';
}

// Breadcrumb HTML
$breadcrumb_html = '';
if ($filtro_setor > 0) {
    $breadcrumb_html .= '<i class="fas fa-folder-open" style="color: #ff6f00;"></i> ' . htmlspecialchars($setor_nome);
    if (!empty($modulo_nome)) {
        $breadcrumb_html .= ' <span style="margin: 0 8px; color: #999;">/</span> <i class="fas fa-cube" style="color: #6366f1;"></i> ' . htmlspecialchars($modulo_nome);
    }
} else {
    $breadcrumb_html .= '<i class="fas fa-th" style="color: #ff6f00;"></i> Todos os Vídeos';
}

// Paginação HTML
$pagination_html = '';
if ($total_paginas > 1) {
    $pagination_html .= '<div class="pagination-modern">';
    
    // Primeira página
    if ($pagina_atual > 1) {
        $pagination_html .= '<a href="?pagina=1&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn pagination-first"><i class="fas fa-angle-double-left"></i></a>';
        $pagination_html .= '<a href="?pagina=' . ($pagina_atual - 1) . '&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn pagination-prev"><i class="fas fa-angle-left"></i> Anterior</a>';
    }
    
    // Números das páginas
    $start_page = max(1, $pagina_atual - 2);
    $end_page = min($total_paginas, $pagina_atual + 2);
    
    if ($start_page > 1) {
        $pagination_html .= '<a href="?pagina=1&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn">1</a>';
        if ($start_page > 2) {
            $pagination_html .= '<span class="pagination-dots">...</span>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $pagina_atual) {
            $pagination_html .= '<span class="pagination-btn pagination-active">' . $i . '</span>';
        } else {
            $pagination_html .= '<a href="?pagina=' . $i . '&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn">' . $i . '</a>';
        }
    }
    
    if ($end_page < $total_paginas) {
        if ($end_page < $total_paginas - 1) {
            $pagination_html .= '<span class="pagination-dots">...</span>';
        }
        $pagination_html .= '<a href="?pagina=' . $total_paginas . '&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn">' . $total_paginas . '</a>';
    }
    
    // Próxima página
    if ($pagina_atual < $total_paginas) {
        $pagination_html .= '<a href="?pagina=' . ($pagina_atual + 1) . '&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn pagination-next">Próxima <i class="fas fa-angle-right"></i></a>';
        $pagination_html .= '<a href="?pagina=' . $total_paginas . '&filtroSetor=' . $filtro_setor . '&filtroModulo=' . $filtro_modulo . '&pesquisaTitulo=' . urlencode($busca_titulo) . '" class="pagination-btn pagination-last"><i class="fas fa-angle-double-right"></i></a>';
    }
    
    $pagination_html .= '</div>';
}

echo json_encode([
    'success' => true,
    'videos_html' => $videos_html,
    'breadcrumb_html' => $breadcrumb_html,
    'pagination_html' => $pagination_html,
    'videos_info_html' => '<div class="videos-info-modern"><div class="videos-count-modern"><i class="fas fa-video"></i><span class="videos-number">' . $total_videos . '</span><span class="videos-label">' . ($total_videos == 1 ? 'vídeo' : 'vídeos') . '</span></div>' . ($total_paginas > 1 ? '<div class="videos-pages-modern"><i class="fas fa-file-alt"></i><span>Página <strong>' . $pagina_atual . '</strong> de <strong>' . $total_paginas . '</strong></span></div>' : '') . '</div>',
    'total_videos' => $total_videos,
    'total_paginas' => $total_paginas,
    'pagina_atual' => $pagina_atual
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

