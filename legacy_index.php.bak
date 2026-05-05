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
$usuario_adm = $_SESSION['user_adm'] ?? false;
$usuario_id = $_SESSION['user_id'] ?? null;

// Verifica se o usuário pode fazer upload
$pode_fazer_upload = false;
if ($is_logged_in && $usuario_id) {
    if ($usuario_adm) {
        // Admin pode fazer upload
        $pode_fazer_upload = true;
    } else {
        // Usuário do sistema (não cliente) pode fazer upload se tiver setores vinculados
        if (!usuario_eh_cliente($conexao, $usuario_id)) {
            $setores_permitidos = get_setores_usuario($conexao, $usuario_id);
            $pode_fazer_upload = !empty($setores_permitidos);
        }
    }
}

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

// Verificar se a tabela sequencias existe antes de fazer JOIN
$check_sequencias = $conexao->query("SHOW TABLES LIKE 'sequencias'");
$sequencias_exists = $check_sequencias && $check_sequencias->num_rows > 0;

// Busca os vídeos com limite e offset usando prepared statement
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
    error_log("Erro ao preparar consulta de vídeos: " . $conexao->error);
    $videos_result = null;
} else {
if (!empty($types)) {
    $stmt_videos->bind_param($types, ...$params);
}
    if (!$stmt_videos->execute()) {
        error_log("Erro ao executar consulta de vídeos: " . $stmt_videos->error);
        $videos_result = null;
    } else {
$videos_result = $stmt_videos->get_result();
    }
}

// Total de vídeos para paginação usando prepared statement
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

// Query de contagem - precisa incluir os JOINs também
$count_query = "SELECT COUNT(*) as total 
                FROM videos 
                JOIN setores ON videos.setor_id = setores.id 
                LEFT JOIN modulos ON videos.modulo_id = modulos.id
                $where_clause";
$stmt_count = $conexao->prepare($count_query);
if (!$stmt_count) {
    die("Erro ao preparar consulta de contagem: " . $conexao->error);
}

if (!empty($count_types)) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_videos = $count_result->fetch_assoc()['total'];
$total_paginas = ceil($total_videos / $videos_por_pagina);

// Busca os setores para filtro e modal de upload usando prepared statement
$setores_query = "SELECT id, nome FROM setores WHERE ativo = ?";
$stmt_setores = $conexao->prepare($setores_query);
if (!$stmt_setores) {
    die("Erro ao preparar consulta de setores: " . $conexao->error);
}

$ativo = 'S';
$stmt_setores->bind_param("s", $ativo);
$stmt_setores->execute();
$setores_result = $stmt_setores->get_result();

// Busca módulos do setor selecionado (se houver)
$modulos_result = null;
if ($filtro_setor > 0) {
    $modulos_query = "SELECT id, nome, icone, cor, 
                     (SELECT COUNT(*) FROM videos WHERE videos.modulo_id = modulos.id AND videos.setor_id = ?) AS total_videos
                     FROM modulos 
                     WHERE setor_id = ? AND ativo = 'S' 
                     ORDER BY nome ASC";
    $stmt_modulos = $conexao->prepare($modulos_query);
    if ($stmt_modulos) {
        $stmt_modulos->bind_param("ii", $filtro_setor, $filtro_setor);
        if ($stmt_modulos->execute()) {
            $modulos_result = $stmt_modulos->get_result();
        } else {
            error_log("Erro ao buscar módulos: " . $stmt_modulos->error);
            $modulos_result = null;
        }
    } else {
        error_log("Erro ao preparar query de módulos: " . $conexao->error);
        $modulos_result = null;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Treinamentos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Popper.js (necessário para Dropdown do Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Previne seleção acidental de múltiplos elementos */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Permite seleção apenas em inputs e textareas */
        input, textarea, [contenteditable] {
            -webkit-user-select: auto;
            -moz-user-select: auto;
            -ms-user-select: auto;
            user-select: auto;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto;
            min-height: 100vh;
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
            z-index: 1002;
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
            background: rgba(255, 255, 255, 0.2);
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
            background: rgba(255, 255, 255, 0.3);
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

        .setor-item span:first-of-type {
            flex: 1;
        }

        /* Wrapper para setor e seus módulos */
        .setor-wrapper {
            display: flex;
            flex-direction: column;
        }
        
        .setor-toggle {
            cursor: pointer;
            user-select: none;
        }
        
        .setor-chevron {
            transition: transform 0.3s ease;
        }
        
        .setor-wrapper.active .setor-chevron {
            transform: rotate(90deg);
        }

        /* Estilos para Módulos - aparecem como subpastas */
        .modulos-list {
            display: none;
            flex-direction: column;
            gap: 3px;
            margin-left: 24px;
            margin-top: 4px;
            margin-bottom: 8px;
            padding-left: 16px;
            border-left: 2px solid rgba(255, 111, 0, 0.3);
            padding-top: 4px;
            padding-bottom: 4px;
            overflow: hidden;
        }
        
        .modulos-list.expanded {
            display: flex;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
        }
        
        .modulos-loading {
            font-size: 11px;
        }

        .modulo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 12px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            position: relative;
            background: transparent;
            border: none;
            font-weight: 500;
            font-size: 12px;
            margin-left: 8px;
        }
        
        .modulo-item::before {
            content: '└';
            color: rgba(255, 111, 0, 0.4);
            font-size: 14px;
            margin-right: -4px;
            font-weight: bold;
        }
        
        .modulo-empty {
            padding: 8px 12px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 11px;
            text-align: left;
            margin-left: 8px;
            font-style: italic;
        }
        
        .modulo-empty i {
            margin-right: 6px;
        }

        .modulo-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.9);
            transform: translateX(4px);
        }

        .modulo-item.active {
            background: var(--modulo-color, #6366f1);
            color: white;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .modulo-item.active:hover {
            opacity: 0.9;
        }

        .modulo-item i {
            width: 16px;
            text-align: center;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            transition: all 0.2s ease;
        }

        .modulo-item.active i {
            color: white;
        }

        .modulo-item:hover i {
            color: rgba(255, 255, 255, 0.8);
        }

        .modulo-item.active:hover i {
            color: white;
        }

        .modulo-item span:first-of-type {
            flex: 1;
        }

        .modulo-count {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            min-width: 24px;
            text-align: center;
        }

        .modulo-item.active .modulo-count {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .modulo-item:hover .modulo-count {
            background: rgba(255, 255, 255, 0.15);
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
            z-index: 1001;
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
            background: none;
            border: none;
            font-size: 24px;
            color: #333;
            cursor: pointer;
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

        .search-box {
            flex: 0 0 400px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #ff6f00;
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
        }

        .search-btn {
            width: 45px;
            height: 45px;
            border: none;
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(255, 111, 0, 0.4);
        }

        /* ===== HEADER ACTIONS ===== */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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

        /* ===== DARK MODE ===== */
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-sidebar: #0f172a;
            --bg-header: #1e293b;
            --text-primary: rgba(255, 255, 255, 0.95);
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] body {
            background-color: #0f172a;
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .top-header {
            background: #1e293b;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .page-title {
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .menu-toggle {
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .search-input {
            background: #0f172a;
            color: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .main-content {
            background: #0f172a;
        }

        [data-theme="dark"] .videos-section {
            background: transparent;
        }

        [data-theme="dark"] .video-card {
            background: #1e293b;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .video-card-title {
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .video-card-description {
            color: rgba(255, 255, 255, 0.7);
        }

        [data-theme="dark"] .video-card-setor {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 50%, #ff6f00 100%);
            box-shadow: 0 3px 10px rgba(255, 111, 0, 0.4);
        }

        [data-theme="dark"] .video-card-setor:hover {
            box-shadow: 0 5px 15px rgba(255, 111, 0, 0.5);
        }

        [data-theme="dark"] .content-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .content-header h2 {
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .content-header h2 .breadcrumb-path {
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .content-header p {
            color: rgba(255, 255, 255, 0.7);
        }

        [data-theme="dark"] .video-duration {
            background: rgba(0, 0, 0, 0.9);
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .video-progress {
            background: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .video-card-stats {
            border-top-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .stat-likes {
            color: #ff6b9d;
        }

        [data-theme="dark"] .stat-comments {
            color: #64b5f6;
        }

        [data-theme="dark"] .stat-views {
            color: #4caf50;
        }

        [data-theme="dark"] .stat-date {
            color: rgba(255, 255, 255, 0.6);
        }

        [data-theme="dark"] .content-header p {
            color: rgba(255, 255, 255, 0.7);
        }

        [data-theme="dark"] .user-header-info {
            background: rgba(255, 255, 255, 0.05);
        }

        [data-theme="dark"] .user-header-info:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        [data-theme="dark"] .user-header-name {
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .theme-toggle:hover {
            background: rgba(255, 111, 0, 0.2);
            color: #ff8c1a;
        }

        [data-theme="dark"] .empty-state {
            background: #1e293b;
            color: rgba(255, 255, 255, 0.7);
        }

        [data-theme="dark"] .empty-state h3 {
            color: rgba(255, 255, 255, 0.9);
        }

        /* ===== FOOTER COMPACTO E MODERNO ===== */
        .footer {
            background: #1e293b;
            color: rgba(255, 255, 255, 0.9);
            padding: 30px 0 20px 0;
            margin-top: 60px;
            border-top: 2px solid #ff6f00;
            position: relative;
            z-index: 1;
        }
        
        [data-theme="light"] .footer {
            background: #1e293b;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }
        
        .footer-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer-brand i {
            font-size: 24px;
            color: #ff6f00;
        }
        
        .footer-brand span {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
        }
        
        .footer-social {
            display: flex;
            gap: 10px;
        }
        
        .social-link {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 111, 0, 0.2);
            font-size: 14px;
        }
        
        .social-link:hover {
            background: #ff6f00;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
            border-color: #ff6f00;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .footer-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .footer-links a i {
            color: #ff6f00;
            font-size: 12px;
        }
        
        .footer-links a:hover {
            color: #ff6f00;
        }
        
        .footer-copyright {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            margin: 0;
            font-weight: 500;
        }
        
        .footer-highlight {
            color: #ff6f00;
            font-weight: 600;
        }
        
        [data-theme="dark"] .footer {
            background: #0f172a;
        }
        
        @media (max-width: 768px) {
            .footer {
                padding: 25px 0 15px 0;
            }
            
            .footer-container {
                padding: 0 20px;
            }
            
            .footer-top {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
            }
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            padding: 40px 40px 150px 40px;
            min-height: calc(100vh - 70px);
            background: #f5f5f5;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: none;
            display: flex;
            flex-direction: column;
        }

        /* Esconde a barra de rolagem do conteúdo principal, mas mantém a funcionalidade */
        .main-content::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }

        /* ===== HEADER DE SEÇÃO ELEGANTE ===== */
        .content-header {
            margin-bottom: 24px;
            padding: 0;
            border: none;
        }

        .content-header h2 {
            font-size: 22px;
            font-weight: 500;
            color: #2c3e50;
            margin: 0 0 16px 0;
            letter-spacing: 0.2px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .content-header h2 .breadcrumb-path {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2c3e50;
        }

        .content-header p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }

        /* ===== SEÇÕES MODERNAS COM DIVISORES SUTIS ===== */
        .section-modern {
            margin-bottom: 48px;
            position: relative;
        }

        .section-modern:not(:last-child)::after {
            content: '';
            position: absolute;
            bottom: -24px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1) 20%, rgba(255, 255, 255, 0.1) 80%, transparent);
        }

        /* ===== INFORMAÇÕES DE VÍDEOS MODERNAS ===== */
        .videos-info-modern {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .videos-count-modern,
        .videos-pages-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 13px;
            color: #6c757d;
            transition: all 0.2s ease;
        }

        .videos-count-modern:hover,
        .videos-pages-modern:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }

        .videos-count-modern i,
        .videos-pages-modern i {
            font-size: 12px;
            color: #ff6f00;
        }

        .videos-number {
            font-weight: 700;
            color: #2c3e50;
            font-size: 14px;
        }

        .videos-label {
            color: #6c757d;
        }

        .videos-pages-modern strong {
            color: #2c3e50;
            font-weight: 700;
        }

        [data-theme="dark"] .videos-count-modern,
        [data-theme="dark"] .videos-pages-modern {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        [data-theme="dark"] .videos-count-modern:hover,
        [data-theme="dark"] .videos-pages-modern:hover {
            background: #334155;
            border-color: #475569;
        }

        [data-theme="dark"] .videos-number,
        [data-theme="dark"] .videos-pages-modern strong {
            color: #f1f5f9;
        }

        /* ===== PAGINAÇÃO MODERNA ===== */
        .pagination-modern {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            min-width: 40px;
            height: 40px;
        }

        .pagination-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
            transform: translateY(-1px);
        }

        .pagination-btn.pagination-active {
            background: #ff6f00;
            border-color: #ff6f00;
            color: #ffffff;
            font-weight: 700;
            cursor: default;
        }

        .pagination-btn.pagination-active:hover {
            transform: none;
        }

        .pagination-dots {
            padding: 8px 4px;
            color: #9ca3af;
            font-weight: 700;
        }

        .pagination-first,
        .pagination-last {
            padding: 8px 12px;
        }

        .pagination-prev,
        .pagination-next {
            padding: 8px 16px;
        }

        [data-theme="dark"] .pagination-btn {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        [data-theme="dark"] .pagination-btn:hover {
            background: #334155;
            border-color: #475569;
            color: #f1f5f9;
        }

        [data-theme="dark"] .pagination-btn.pagination-active {
            background: #ff6f00;
            border-color: #ff6f00;
            color: #ffffff;
        }

        [data-theme="dark"] .pagination-dots {
            color: #64748b;
        }

        /* ===== CONTAINER DE SEÇÕES SECUNDÁRIAS ===== */
        .secondary-sections-container {
            margin: 0 0 24px 0;
            padding: 0;
        }

        /* ===== SEÇÃO DE RECOMENDAÇÕES - CARD MINIMALISTA ELEGANTE ===== */
        .carousel-section {
            margin: 0 0 48px 0;
            padding: 0;
        }

        .carousel-header {
            margin-bottom: 16px;
            padding: 0;
            border: none;
        }

        .carousel-title {
            font-size: 18px;
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.2px;
        }

        .carousel-title i {
            color: #ff6f00;
            font-size: 14px;
            opacity: 0.9;
        }

        .carousel-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .carousel-container {
            flex: 1;
            overflow-x: auto;
            overflow-y: hidden;
            position: relative;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            padding: 2px 0;
        }

        .carousel-container::-webkit-scrollbar {
            display: none;
        }

        .carousel-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .carousel-track {
            display: flex;
            gap: 10px;
            padding: 0;
        }

        .carousel-item {
            flex: 0 0 240px;
            min-width: 240px;
            max-width: 240px;
        }

        .carousel-item .video-card {
            margin: 0;
            height: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f1f5f9;
            background: #ffffff;
        }

        .carousel-item .video-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
            border-color: #e2e8f0;
        }

        .carousel-item .video-card-thumbnail {
            padding-top: 56.25%;
        }

        .carousel-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            z-index: 2;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .carousel-btn:hover:not(:disabled) {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #334155;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .carousel-btn:active:not(:disabled) {
            transform: scale(0.95);
        }

        .carousel-btn i {
            font-size: 11px;
        }

        .carousel-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        /* ===== BADGE RECOMENDADO MINIMALISTA ===== */
        .badge-recomendado {
            position: absolute;
            top: 6px;
            left: 6px;
            background: rgba(255, 111, 0, 0.95);
            backdrop-filter: blur(8px);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 3px;
            box-shadow: 0 1px 3px rgba(255, 111, 0, 0.3);
        }

        .badge-recomendado i {
            font-size: 8px;
        }

        /* ===== ESTILOS MINIMALISTAS PARA CARDS DE RECOMENDAÇÃO ===== */
        .carousel-item .video-card-content {
            padding: 12px;
        }

        .carousel-item .video-card-title {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
            margin: 0 0 8px 0;
            color: #1e293b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 36px;
        }

        .carousel-item .video-card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
        }

        .carousel-item .video-card-setor-minimal,
        .carousel-item .video-card-views-minimal {
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
        }

        .carousel-item .video-card-setor-minimal i,
        .carousel-item .video-card-views-minimal i {
            font-size: 10px;
            color: #94a3b8;
        }

        [data-theme="dark"] .carousel-item .video-card-title {
            color: #f1f5f9;
        }

        [data-theme="dark"] .carousel-item .video-card-setor-minimal,
        [data-theme="dark"] .carousel-item .video-card-views-minimal {
            color: #94a3b8;
        }

        [data-theme="dark"] .carousel-item .video-card-meta {
            border-top-color: #334155;
        }

        /* ===== DIVISOR ENTRE SEÇÕES (SUTIL E ELEGANTE) ===== */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb 20%, #e5e7eb 80%, transparent);
            margin: 32px 0;
            display: block;
            border: none;
        }

        [data-theme="dark"] .section-divider {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1) 20%, rgba(255, 255, 255, 0.1) 80%, transparent);
        }

        /* ===== SEÇÃO PRINCIPAL DE VÍDEOS ===== */
        .videos-main-section {
            margin-top: 0;
            padding: 0;
            position: relative;
        }

        /* ===== DARK MODE PARA RECOMENDAÇÕES ===== */
        [data-theme="dark"] .carousel-title {
            color: rgba(255, 255, 255, 0.95);
        }

        [data-theme="dark"] .carousel-title i {
            color: #ff6f00;
        }

        [data-theme="dark"] .carousel-item .video-card {
            background: #1e293b;
            border-color: #334155;
        }

        [data-theme="dark"] .carousel-item .video-card:hover {
            border-color: #475569;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        [data-theme="dark"] .carousel-btn {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        [data-theme="dark"] .carousel-btn:hover:not(:disabled) {
            background: #334155;
            border-color: #475569;
            color: #cbd5e1;
        }

        /* ===== RESPONSIVIDADE MOBILE ===== */
        @media (max-width: 768px) {
            .carousel-section {
                margin: 0 0 20px 0;
            }

            .carousel-item {
                flex: 0 0 200px;
                min-width: 200px;
                max-width: 200px;
            }

            .carousel-btn {
                width: 28px;
                height: 28px;
            }

            .carousel-btn i {
                font-size: 10px;
            }

            .carousel-title {
                font-size: 12px;
            }
        }

        .videos-section {
            position: relative;
            z-index: 1;
            margin-bottom: 48px;
        }

        .content-wrapper {
            flex: 1; /* Ocupa o espaço disponível */
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1; /* Garante que o conteúdo fique acima do footer */
            isolation: isolate; /* Isola o conteúdo */
        }

        /* ===== VIDEO CARDS ELEGANTES (ESTILO STREAMING) ===== */
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 2;
            padding: 0;
            background: transparent;
            isolation: isolate;
        }

        @media (max-width: 1200px) {
            .videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
            }
        }

        /* ===== RESPONSIVIDADE MOBILE ===== */
        @media (max-width: 768px) {
            body {
                overflow-y: auto !important;
                height: auto !important;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 260px;
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
                z-index: 1002 !important;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .top-header {
                left: 0 !important;
                padding: 0 15px;
                height: 60px;
                z-index: 1001;
            }

            .sidebar {
                z-index: 1003 !important;
            }

            .sidebar-overlay {
                z-index: 1002 !important;
            }

            .menu-toggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                font-size: 20px;
                cursor: pointer;
                background: rgba(0, 0, 0, 0.05);
                border-radius: 8px;
                transition: background 0.2s ease;
            }

            .menu-toggle:hover {
                background: rgba(0, 0, 0, 0.1);
            }

            .page-title {
                font-size: 18px;
            }

            .main-content {
                margin-left: 0 !important;
                margin-top: 60px;
                padding: 15px;
                min-height: calc(100vh - 60px);
                overflow-y: auto;
            }

            .videos-grid {
                grid-template-columns: 1fr !important;
                gap: 15px;
            }

            .video-card {
                min-height: auto;
                width: 100%;
            }

            .video-card-content {
                padding: 15px;
            }

            .video-card-title {
                font-size: 15px;
                min-height: auto;
                line-height: 1.3;
            }

            .video-card-description {
                font-size: 13px;
                margin-bottom: 12px;
                -webkit-line-clamp: 3;
            }

            .video-card-stats {
                font-size: 12px;
                gap: 12px;
                flex-wrap: wrap;
            }

            .video-card-stats .stat-item {
                font-size: 12px;
            }

            .video-card-actions {
                gap: 10px;
                padding-top: 12px;
                justify-content: center;
            }

            .video-card-btn {
                width: 48px !important;
                height: 48px !important;
                font-size: 18px;
                min-width: 48px;
                min-height: 48px;
            }

            .content-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }

            .content-header h2 {
                font-size: 22px;
            }

            .footer {
                position: relative !important;
                margin-top: 30px;
                padding: 15px;
                font-size: 11px;
            }

            .search-box {
                display: none;
            }

            .search-form {
                width: 100%;
            }

            .search-input {
                font-size: 14px;
                padding: 12px 15px;
                min-height: 44px;
            }

            .video-badge {
                font-size: 9px;
                padding: 4px 8px;
                top: 6px;
                left: 6px;
            }

            .video-duration {
                font-size: 10px;
                padding: 3px 6px;
                bottom: 6px;
                right: 6px;
            }

            .video-card-setor {
                font-size: 11px;
                padding: 6px 12px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }

            .video-card-content {
                padding: 12px;
            }

            .video-card-title {
                font-size: 14px;
            }

            .video-card-description {
                font-size: 12px;
            }

            .video-card-stats {
                font-size: 11px;
                gap: 10px;
            }

            .video-card-stats .stat-item {
                font-size: 11px;
            }

            .video-card-btn {
                width: 44px !important;
                height: 44px !important;
                font-size: 16px;
                min-width: 44px;
                min-height: 44px;
            }

            .video-badge {
                font-size: 8px;
                padding: 3px 6px;
            }

            .video-duration {
                font-size: 9px;
                padding: 2px 5px;
            }

            .content-header h2 {
                font-size: 20px;
            }

            .top-header {
                height: 55px;
            }

            .main-content {
                margin-top: 55px;
            }

            .page-title {
                font-size: 16px;
            }

            .menu-toggle {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .header-actions {
                gap: 8px;
            }

            .user-header-info {
                display: none;
            }

            .btn-header-login,
            .btn-header-logout {
                min-width: 44px;
                min-height: 44px;
                padding: 10px;
            }

            .theme-toggle {
                min-width: 44px;
                min-height: 44px;
            }
        }

        .video-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            isolation: isolate;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            margin: 0;
            border: 1px solid rgba(255, 111, 0, 0.1);
            z-index: 1;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 111, 0, 0.05);
        }

        .video-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff6f00, #ff8c1a, #ff6f00);
            background-size: 200% 100%;
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 2;
        }

        .video-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 12px 40px rgba(255, 111, 0, 0.2), 0 0 0 1px rgba(255, 111, 0, 0.15);
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(255, 250, 240, 0.95) 100%);
            border-color: rgba(255, 111, 0, 0.2);
        }

        .video-card:hover::before {
            opacity: 1;
            animation: gradientShift 2s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        [data-theme="dark"] .video-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.85) 100%);
            border-color: rgba(255, 111, 0, 0.15);
        }
        
        [data-theme="dark"] .video-card:hover {
            background: linear-gradient(135deg, rgba(30, 41, 59, 1) 0%, rgba(20, 30, 50, 0.95) 100%);
            border-color: rgba(255, 111, 0, 0.25);
        }

        /* ===== BADGE DE SEQUÊNCIA - PADRONIZADO E ELEGANTE ===== */
        .video-sequencia-badge-card {
            position: absolute;
            bottom: 8px;
            left: 8px;
            z-index: 5;
            background: linear-gradient(135deg, rgba(255, 111, 0, 0.9) 0%, rgba(255, 140, 26, 0.85) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
            font-size: 11px;
            line-height: 1.2;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .sequencia-badge-number {
            font-size: 12px;
            font-weight: 700;
        }
        
        .sequencia-badge-total {
            font-size: 11px;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Removido preview hover - design limpo e padronizado */
        .video-card-preview-hover {
            display: none !important;
        }

        .preview-sequencia-titulo {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 16px 0;
            line-height: 1.3;
        }

        .preview-sequencia-progresso {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 16px;
        }

        .preview-progresso-bar {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            overflow: hidden;
        }

        .preview-progresso-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffffff 0%, #f0f0f0 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        .preview-progresso-texto {
            font-size: 13px;
            font-weight: 600;
            opacity: 0.95;
        }

        /* Header de sequência no card */
        .video-card-sequencia-header {
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(239, 68, 68, 0.2);
        }

        .sequencia-header-badge-card {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #ef4444;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid #fecaca;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15);
        }

        .sequencia-header-badge-card i {
            font-size: 14px;
        }

        .sequencia-header-badge-card span:first-of-type {
            flex: 1;
        }

        .sequencia-header-ordem {
            background: #ef4444;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
        }

        [data-theme="dark"] .video-card-sequencia {
            background: linear-gradient(135deg, #1e293b 0%, #3a1f1f 100%);
            border-color: #ef4444;
        }

        [data-theme="dark"] .sequencia-header-badge-card {
            background: linear-gradient(135deg, #3a1f1f 0%, #4a2a2a 100%);
            color: #fca5a5;
            border-color: #5a2a2a;
        }

        [data-theme="dark"] .sequencia-header-ordem {
            background: #ef4444;
            color: #ffffff;
        }

        /* Previne seleção de texto em todos os elementos do card */
        .video-card * {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            pointer-events: auto; /* Garante que os elementos do card sejam clicáveis */
        }

        /* Permite seleção apenas nos botões de ação */
        .video-card-actions button {
            user-select: auto;
            -webkit-user-select: auto;
            -moz-user-select: auto;
            -ms-user-select: auto;
        }

        /* Garante que o footer não interfira com os cards */
        .footer * {
            pointer-events: auto; /* Links e elementos do footer são clicáveis */
        }

        /* Previne que eventos do footer afetem os cards */
        .footer {
            pointer-events: auto;
        }

        /* Garante isolamento completo entre footer e cards */
        .videos-grid::before,
        .videos-grid::after {
            content: '';
            display: none;
        }

        .video-card-thumbnail {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 aspect ratio */
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            background-size: 200% 200%;
            overflow: hidden;
            border-radius: 0;
            margin-bottom: 0;
            animation: thumbnailGradient 8s ease infinite;
        }

        @keyframes thumbnailGradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .video-card:hover .video-card-thumbnail {
            animation-duration: 4s;
        }

        .video-thumbnail-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #000;
            object-fit: cover;
            background: #000;
            z-index: 1;
            display: block;
            opacity: 1;
            visibility: visible;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.4s ease;
            filter: brightness(0.95) contrast(1.05);
        }
        
        .video-card:hover .video-thumbnail-preview {
            transform: scale(1.08);
            filter: brightness(1.05) contrast(1.1);
        }
        
        .video-card-thumbnail video {
            object-position: center;
        }
        
        .video-thumbnail-fallback {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0f0f;
            color: rgba(255, 255, 255, 0.2);
        }
        
        .video-thumbnail-fallback i {
            font-size: 48px;
        }
        
        /* Garantir que badges e overlays fiquem acima do vídeo */
        .video-badge,
        .video-sequencia-badge-card,
        .video-card-preview-hover {
            z-index: 3;
            position: relative;
        }
        
        .video-duration,
        .video-progress {
            z-index: 2;
            position: relative;
        }
        
        .video-play-overlay,
        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 4;
            cursor: pointer;
            pointer-events: auto;
            text-decoration: none;
        }
        
        .video-card-thumbnail:hover .video-play-overlay,
        .video-card-thumbnail:hover .play-overlay {
            opacity: 1;
        }
        
        .video-play-overlay i,
        .play-overlay i {
            font-size: 48px;
            color: white;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }
        
        .video-card-thumbnail {
            cursor: pointer;
        }
        
        .video-card-thumbnail a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 5;
        }

        .video-thumbnail-fallback {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: rgba(255, 255, 255, 0.3);
        }

        .video-thumbnail-fallback i {
            font-size: 48px;
        }

        /* ===== BADGES (NOVO, POPULAR, RECENTE) ===== */
        .video-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            animation: badgePulse 2s infinite;
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .video-badge-new {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .video-badge-popular {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
        }

        .video-badge-recente {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        /* ===== DURAÇÃO DO VÍDEO - PADRONIZADO ===== */
        .video-duration {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: white;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            z-index: 5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        /* Badge de sequência sempre à esquerda, duração sempre à direita */
        .video-card-thumbnail:has(.video-sequencia-badge-card) .video-duration {
            right: 8px;
            left: auto;
        }

        /* ===== INDICADOR DE PROGRESSO ===== */
        .video-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(0, 0, 0, 0.3);
            z-index: 2;
        }

        .video-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            transition: width 0.3s ease;
            box-shadow: 0 0 8px rgba(255, 111, 0, 0.6);
        }

        .video-progress-complete {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .video-progress-complete::after {
            content: '✓';
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 10px;
            font-weight: bold;
        }

        .video-card-thumbnail video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            pointer-events: none; /* Previne que o vídeo capture cliques */
        }

        .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 111, 0, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 2;
        }

        .video-card:hover .play-overlay {
            opacity: 1;
        }

        .video-card-thumbnail::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .video-card-thumbnail::before {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-45%, -50%);
            font-size: 24px;
            color: #ff6f00;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 1;
        }

        .video-card:hover .video-card-thumbnail::after,
        .video-card:hover .video-card-thumbnail::before {
            opacity: 1;
        }

        .video-card-content {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-height: 0;
        }

        .video-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .video-card-title {
            font-size: 15px;
            font-weight: 600;
            background: linear-gradient(135deg, #0f0f0f 0%, #2c3e50 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 42px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            letter-spacing: -0.01em;
            transition: all 0.3s ease;
        }

        .video-card:hover .video-card-title {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        [data-theme="dark"] .video-card-title {
            background: linear-gradient(135deg, #f1f1f1 0%, #e0e0e0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        [data-theme="dark"] .video-card:hover .video-card-title {
            background: linear-gradient(135deg, #ff8c1a 0%, #ffb84d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ===== TAGS VIBRANTES ===== */
        .video-card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .video-card-setor {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, rgba(255, 111, 0, 0.15) 0%, rgba(255, 140, 26, 0.12) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #ff6f00;
            border: 1px solid rgba(255, 111, 0, 0.2);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0.2px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.1);
        }

        .video-card-setor:hover {
            background: linear-gradient(135deg, rgba(255, 111, 0, 0.25) 0%, rgba(255, 140, 26, 0.2) 100%);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.2);
            border-color: rgba(255, 111, 0, 0.3);
        }

        .video-card-setor i {
            font-size: 10px;
            opacity: 0.9;
        }

        .video-card-modulo {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.12) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: var(--modulo-color, #6366f1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0.2px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.1);
        }

        .video-card-modulo:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.2) 100%);
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .video-card-modulo i {
            font-size: 10px;
            opacity: 0.9;
        }
        
        [data-theme="dark"] .video-card-setor {
            background: linear-gradient(135deg, rgba(255, 111, 0, 0.2) 0%, rgba(255, 140, 26, 0.15) 100%);
            color: #ff8c1a;
            border-color: rgba(255, 111, 0, 0.3);
        }
        
        [data-theme="dark"] .video-card-modulo {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.15) 100%);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .video-card-description {
            font-size: 12px;
            color: #606060;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        [data-theme="dark"] .video-card-description {
            color: rgba(255, 255, 255, 0.7);
        }

        .video-card-stats {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11px;
            color: #606060;
            padding-top: 6px;
            border-top: none;
            flex-wrap: wrap;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        [data-theme="dark"] .video-card-stats {
            color: rgba(255, 255, 255, 0.6);
        }

        .video-card-stats i {
            margin-right: 5px;
        }

        .video-card-stats .stat-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-likes {
            color: #e91e63;
        }

        .stat-comments {
            color: #2196f3;
        }

        .stat-views {
            color: #10b981;
            font-weight: 600;
        }

        .stat-date {
            color: #95a5a6;
        }

        .video-card-actions {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding-top: 8px;
            flex-wrap: wrap;
        }

        .video-card-btn {
            padding: 0;
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 18px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.05) 0%, rgba(0, 0, 0, 0.03) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #606060;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .video-card-btn:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .video-card-btn:active {
            transform: translateY(-1px) scale(1.05);
        }
        
        [data-theme="dark"] .video-card-btn {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            color: #aaaaaa;
        }
        
        [data-theme="dark"] .video-card-btn:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.1) 100%);
        }

        .btn-like.active {
            background: linear-gradient(135deg, rgba(255, 0, 0, 0.2) 0%, rgba(233, 30, 99, 0.15) 100%);
            color: #ff0000;
            box-shadow: 0 4px 12px rgba(255, 0, 0, 0.2);
        }

        .btn-like.active i {
            color: #ff0000;
            animation: heartBeat 0.6s ease;
        }

        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.3); }
            50% { transform: scale(1.1); }
        }

        .btn-share:hover {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.2) 0%, rgba(33, 150, 243, 0.15) 100%);
            color: #2196f3;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, rgba(255, 111, 0, 0.2) 0%, rgba(255, 140, 26, 0.15) 100%);
            color: #ff6f00;
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.2);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, rgba(239, 83, 80, 0.2) 0%, rgba(239, 83, 80, 0.15) 100%);
            color: #ef5350;
            box-shadow: 0 4px 12px rgba(239, 83, 80, 0.2);
        }

        .btn-recomendado {
            color: #94a3b8;
        }

        .btn-recomendado[data-recomendado="1"] {
            color: #ffd700;
        }

        .btn-recomendado:hover {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 215, 0, 0.15) 100%);
            color: #ffd700;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
        }

        .btn-recomendado[data-recomendado="1"]:hover {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 215, 0, 0.25) 100%);
            color: #ffed4e;
        }

        /* Tooltip para os botões */
        .video-card-btn::after {
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

        .video-card-btn:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .video-card-btn::before {
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

        .video-card-btn:hover::before {
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #95a5a6;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 25px;
            opacity: 0.3;
            color: #bdc3c7;
        }

        .empty-state h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: #7f8c8d;
            font-weight: 600;
        }

        .empty-state p {
            font-size: 16px;
            color: #95a5a6;
        }

        /* ===== PAGINAÇÃO MODERNA ===== */
        .pagination-modern {
            margin: 50px 0 30px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .pagination-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .pagination-text {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .pagination-total {
            padding-left: 20px;
            border-left: 1px solid var(--border-color);
        }

        .pagination-info strong {
            color: var(--primary-color);
            font-weight: 700;
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 10px;
            background: white;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid var(--border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            min-width: 44px;
            height: 44px;
        }

        .pagination-btn:hover:not(.disabled) {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }

        .pagination-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .pagination-btn-text {
            display: inline;
        }

        @media (max-width: 768px) {
            .pagination-btn-text {
                display: none;
            }

            .pagination-btn {
                min-width: 44px;
                min-height: 44px;
                padding: 10px;
            }

            .pagination-number {
                min-width: 44px;
                min-height: 44px;
            }
        }

        .pagination-numbers {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pagination-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: white;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            border: 2px solid var(--border-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .pagination-number:hover {
            background: rgba(255, 111, 0, 0.1);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 111, 0, 0.2);
        }

        .pagination-number.active {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.4);
            transform: scale(1.1);
        }

        .pagination-ellipsis {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            color: var(--text-muted);
            font-weight: 600;
        }

        [data-theme="dark"] .pagination-info {
            color: rgba(255, 255, 255, 0.7);
        }

        [data-theme="dark"] .pagination-btn {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .pagination-btn.disabled {
            background: #0f172a;
            opacity: 0.3;
        }

        [data-theme="dark"] .pagination-number {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .pagination-number:hover {
            background: rgba(255, 111, 0, 0.2);
            border-color: var(--primary-color);
        }

        [data-theme="dark"] .pagination-ellipsis {
            color: rgba(255, 255, 255, 0.5);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
            }

            .top-header {
                left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 40px 20px 100px 20px; /* Ajuste para mobile */
            }

            .footer {
                left: 0;
            }

            .videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .search-box {
                flex: 0 0 250px;
            }
        }

        @media (max-width: 768px) {
            .top-header {
                padding: 0 15px;
            }

            .page-title {
                font-size: 18px;
            }

            .search-box {
                flex: 0 0 200px;
            }

            .main-content {
                padding: 20px 15px;
            }

            .videos-grid {
                grid-template-columns: 1fr;
            }
        }

        .header {
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
   
        .header .logo {
            height: 50px;
        }

        .header .btn {
            font-size: 14px;
            padding: 0px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            color: white;
            border: none;
            transition: all 0.3s ease;
            justify-content: flex-start;
        }

        .header .btn:hover {
            text-decoration: underline;
        }

        /* Estilo Principal do Título */
.main-title {
    text-align: center;
    font-size: 2.8rem;
    font-weight: 700;
    color: #2c3e50; /* Azul escuro neutro */
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin: 20px 0;
    font-family: 'Poppins', sans-serif;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2); /* Sombra leve */
    position: relative;
}

/* Texto com Destaque */
.highlight {
    color: #ff6f00; /* Cor laranja vibrante */
    font-weight: 800;
    position: relative;
    padding: 0 5px;
    border-bottom: 3px solid #ff6f00; /* Barra de destaque */
    transition: all 0.3s ease-in-out;
}

/* Efeito Hover Suave */
.highlight:hover {
    color: #d35400; /* Laranja mais escuro no hover */
    border-bottom: 3px solid #d35400;
    text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3); /* Sombra mais intensa */
}

/* Linha Decorativa Simples */
.main-title::after {
    content: "";
    width: 50%;
    height: 4px;
    background: #ff6f00;
    display: block;
    margin: 12px auto 0;
    border-radius: 2px;
}


        /* Contêiner Principal */
.filters {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px; /* Espaçamento entre os elementos */
    padding: 15px 20px;
    margin: 20px auto;
    max-width: 800px;
    background: linear-gradient(145deg, #ffffff, #f9f9f9); /* Fundo sutil */
    border-radius: 20px; /* Bordas arredondadas */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); /* Sombra leve */
    position: relative;
    overflow: hidden;
}

/* Elemento Decorativo no Fundo */
.filters::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: linear-gradient(90deg, #ff6f00, #ffa726);
    opacity: 0.1;
    border-radius: 50%;
    z-index: 0;
    animation: rotateBlob 8s infinite linear;
}

/* Animação do Blob */
@keyframes rotateBlob {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Formulário Interno */
.filters form {
    display: flex;
    align-items: center;
    gap: 10px; /* Espaçamento entre os campos */
    width: 100%;
    position: relative;
    z-index: 1;
}

/* Campos de Input e Select */
.filters .form-select,
.filters .form-control {
    padding: 10px 15px;
    border: 1px solid #ddd; /* Borda discreta */
    border-radius: 8px;
    font-size: 0.9rem;
    color: #2c3e50;
    background: #ffffff;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); /* Sombra inicial */
    outline: none;
    flex: 1; /* Campos ocupam o mesmo espaço */
}

/* Hover e Foco nos Campos */
.filters .form-select:hover,
.filters .form-control:hover {
    border-color: #ff6f00; /* Destaque em laranja */
    background: #fff8ec; /* Fundo levemente laranja */
    box-shadow: 0 4px 8px rgba(255, 111, 0, 0.1); /* Sombra no hover */
}

.filters .form-select:focus,
.filters .form-control:focus {
    border-color: #ff6f00;
    box-shadow: 0 4px 10px rgba(255, 111, 0, 0.2); /* Destaque no foco */
}

/* Placeholder */
.filters .form-control::placeholder {
    color: #bbb;
    font-style: italic;
}

/* Botão de Filtrar */
.filters .btn-primary {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: bold;
    text-transform: uppercase;
    color: white;
    background: linear-gradient(90deg, #ff6f00, #ffa726); /* Gradiente elegante */
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
    position: relative;
    z-index: 1;
}

/* Hover no Botão */
.filters .btn-primary:hover {
    background: linear-gradient(90deg, #ffa726, #ffd54f); /* Gradiente mais claro */
    transform: translateY(-1px); /* Leve elevação */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra aprimorada */
}

/* Animação ao Carregar */
.filters .form-select,
.filters .form-control,
.filters .btn-primary {
    opacity: 0;
    transform: translateY(10px);
    animation: fadeIn 0.8s ease forwards;
}

.filters .form-select {
    animation-delay: 0.2s;
}

.filters .form-control {
    animation-delay: 0.4s;
}

.filters .btn-primary {
    animation-delay: 0.6s;
}

/* Animação de Fade-In */
@keyframes fadeIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsividade */
@media (max-width: 768px) {
    .filters {
        flex-direction: column; /* Empilha os elementos no mobile */
        padding: 20px;
    }

    .filters form {
        flex-direction: column; /* Reorganiza os elementos verticalmente */
        gap: 15px;
    }

    .filters .form-select,
    .filters .form-control,
    .filters .btn-primary {
        width: 100%; /* Campos ocupam toda a largura */
    }
}

        .video-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .video-item:hover {
            transform: scale(1.05);
        }

        .video-item video {
            width: 100%;
            height: 180px;
            border-radius: 10px;
        }

        .btn-group {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-custom {
            padding: 8px 12px;
            font-size: 14px;
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn-custom.green { color: #28a745; }
        .btn-custom.blue { color: #17a2b8; }
        .btn-custom.orange { color: #ffc107; }
        .btn-custom.red { color: #dc3545; }

        .btn-custom:hover {
            transform: scale(1.1);
        }

        /* Notificações Flutuantes */
        .notification-container {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #333;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-size: 14px;
            opacity: 0;
            transform: translateY(-20px);
            animation: fade-in-out 4s forwards;
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        @keyframes fade-in-out {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            10% {
                opacity: 1;
                transform: translateY(0);
            }
            90% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
        }

        .footer a {
            color: #ff6f00;
        }

        .footer a:hover {
            color: #ff8c1a;
        }
        /* Modal Container */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Fundo escuro semitransparente */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* Modal Content */
.modal-content {
    background: white;
    padding: 30px;
    border-radius: 15px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.3s ease-in-out;
}

/* Modal Title */
.modal-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

/* Input Fields */
.form-control {
    border: 1px solid #ddd;
    border-radius: 22px;
    padding: 10px;
    font-size: 14px;
    margin-top: -2px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #ff6f00;
    outline: none;
    box-shadow: 0 0 5px rgba(255, 111, 0, 0.5);
}

/* Buttons */
.modal-buttons {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    flex: 1;
    padding: 10px 15px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
}

/* Minimalist Primary Button */
.btn-minimal-primary {
    background-color: #ff6f00; /* Laranja forte */
    color: white;
    border: none;
}

.btn-minimal-primary:hover {
    background-color: #ff8c1a; /* Laranja mais claro no hover */
    color: white;
    box-shadow: 0 4px 10px rgba(255, 111, 0, 0.3);
}

/* Minimalist Secondary Button */
.btn-minimal-secondary {
    background-color: transparent; /* Fundo transparente */
    color: #ff6f00; /* Texto laranja */
    border: 1px solid #ff6f00;
}

.btn-minimal-secondary:hover {
    background-color: #ff6f00; /* Laranja no hover */
    color: white; /* Texto branco */
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

        .notification {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
        }
        .notification.error {
            background: #dc3545;
        }
        #notification-container {
    position: fixed;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 90%;
    max-width: 400px;
}

.notification {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-radius: 8px;
    background-color: #fff;
    color: #333;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    opacity: 0;
    transform: translateY(-20px);
    animation: fadeIn 0.4s forwards, fadeOut 0.4s 3.6s forwards;
    transition: transform 0.3s ease-in-out;
}

.notification.success {
    border-left: 4px solid #28a745;
}

.notification.error {
    border-left: 4px solid #dc3545;
}

.notification.info {
    border-left: 4px solid #17a2b8;
}

.notification .btn-close {
    background: none;
    border: none;
    font-size: 16px;
    color: #666;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.2s ease;
}

.notification .btn-close:hover {
    color: #333;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeOut {
    0% {
        opacity: 1;
        transform: translateY(0);
    }
    100% {
        opacity: 0;
        transform: translateY(-20px);
    }
}


.progress {
    margin-top: 10px;
    width: 100%;
    height: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    overflow: hidden;
    display: none;
}

.progress-bar {
    height: 100%;
    width: 0;
    background-color: #28a745;
    transition: width 0.4s ease;
}
.header {
    background: linear-gradient(90deg, #ff6f00, #ff8c1a);
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 10px; /* Espaço entre os botões */
}

.btn {
    font-size: 14px;
    padding: 8px 15px;
    border-radius: 5px;
    background: transparent;
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.btn:hover {
    text-decoration: underline;
}

/* Modal editar */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease-in-out;
    z-index: 1000;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    position: relative;
    animation: slideIn 0.3s ease-in-out forwards;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.modal-header .modal-title {
    font-size: 20px;
    font-weight: bold;
    color: #ff6f00;
}

.modal-header .btn-close {
    background: transparent;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    transition: color 0.3s ease;
}

.modal-header .btn-close:hover {
    color: #ff6f00;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.modal-actions .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    padding: 10px 20px;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.modal-actions .btn-primary {
    background: #ff6f00;
    color: white;
}

.modal-actions .btn-primary:hover {
    background: #ff8c1a;
}

.modal-actions .btn-danger {
    background: transparent;
    color: #dc3545;
    border: 2px solid #dc3545;
}

.modal-actions .btn-danger:hover {
    background: #dc3545;
    color: white;
}

/* Animações */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        transform: scale(0.9);
    }
    to {
        transform: scale(1);
    }
}
/* Estilo do Dropdown */
.dropdown-menu {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
    padding: 10px 15px;
    font-size: 14px;
    color: #333;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.dropdown-item:hover {
    background: #ff6f00;
    color: white;
}

.dropdown-toggle {
    background: transparent;
    color: white;
    border: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
}

.dropdown-toggle:hover {
    text-decoration: underline;
}
.filters select {
    width: auto; /* Ajusta a largura automaticamente ao conteúdo */
    min-width: 200px; /* Define uma largura mínima */
    padding: 10px; /* Espaçamento interno para melhor visualização */
    white-space: nowrap; /* Evita quebra de texto */
    overflow: hidden; /* Esconde texto que ultrapassar */
    text-overflow: ellipsis; /* Adiciona "..." para texto muito longo */
}
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8); /* Fundo semitransparente */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.4s ease;
}

.modal-header {
    display: flex;
    justify-content: center;
    align-items: center;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 22px;
    font-weight: bold;
    color: #ff6f00;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-size: 14px;
    font-weight: bold;
    color: #333;
    display: block;
    margin-bottom: 8px;
}

.form-label span {
    color: #ff6f00;
}

.form-control {
    width: 100%;
    padding: 12px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s ease;
    outline: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.form-control:focus {
    border-color: #ff6f00;
    box-shadow: 0 0 5px rgba(255, 111, 0, 0.5);
}

.btn {
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(90deg, #ff6f00, #ffa726);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #ffa726, #ffd54f);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
    background: transparent;
    color: #666;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #f9f9f9;
    color: #333;
}

.progress {
    display: none;
    width: 100%;
    height: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background-color: #ff6f00;
    transition: width 0.4s ease;
}

/* Animação de entrada */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
 .header {
        position: fixed;
        top: 0;
        width: 100%;
        background: linear-gradient(to right, #ff7f00, #ffae42);
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 100;
        transition: transform 0.5s ease, opacity 0.5s ease;
    }

    .hidden {
        transform: translateY(-100%);
        opacity: 0;
    }
    .click-instruction {
    font-size: 0.75rem;       /* Tamanho menor da fonte */
    color: rgba(0, 0, 0, 0.3); /* Cor mais transparente */
    margin-top: 0.5rem;       /* Espaçamento superior sutil */
    font-style: italic;       /* Estilo itálico minimalista */
    line-height: 1.2;         /* Linha um pouco mais compacta */
}
.video-title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin: 0.5rem 0;   /* Espaçamento sutil acima e abaixo */
    line-height: 1.2;   /* Linha mais compacta */
    /* Remova ou comente as linhas abaixo se estiverem presentes:
       text-overflow: ellipsis;
       white-space: nowrap;
       overflow: hidden; */
}
/* ESTILO DO PLAYER AO VIVO */
/* Estilização do Player de Transmissão Ao Vivo */
.live-container {
    background: white;
    padding: 20px;
    text-align: center;
    border-radius: 16px;
    max-width: 800px;
    margin: 30px auto;
    box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0,0,0,0.05);
}

/* Cabeçalho com Título e Botões */
.live-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(255, 0, 0, 0.1);
    padding: 5px;
    border-radius: 8px;
}

.live-title {
    font-size: 1.2rem;
    font-weight: bold;
    color: red;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Botões de Controle */
.live-controls {
    display: flex;
    gap: 8px;
}

.btn-live {
    background: transparent;
    border: none;
    color: red;
    font-size: 16px;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.btn-live:hover {
    transform: scale(1.1);
}

/* Player */
.live-player {
    border-radius: 10px;
    overflow: hidden;
}

/* Estilização do Texto da Live */
.live-description {
    font-size: 14px;
    color: #333;
    background: #f9f9f9;
    padding: 8px;
    border-radius: 8px;
    margin-top: 8px;
    font-family: 'Poppins', sans-serif;
}


/* RESPONSIVIDADE */
@media (max-width: 768px) {
    .live-container {
        max-width: 100%;
    }
    .live-title {
        font-size: 1rem;
    }
}



/* =========================================
   PREMIUM UI UPGRADE (INJECTED & FIXED V3)
   ========================================= */

:root {
    --primary-color: #ff6f00;
    --primary-glow: rgba(255, 111, 0, 0.3);
    --bg-body-premium: #f8f9fa;
    --glass-surface: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.5);
    --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.08);
}

body {
    background-color: var(--bg-body-premium);
    overflow-x: hidden; /* Prevent horizontal scroll */
}

/* --- SIDEBAR REFINED --- */
.sidebar {
    background: #1e293b;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
    z-index: 2001; /* Sidebar above header */
    /* Ensure sidebar doesn't overlay unexpectedly */
    width: 280px; 
}

.setor-item {
    border-radius: 8px;
    margin-bottom: 2px;
}

.setor-item.active {
    background: linear-gradient(90deg, rgba(255, 111, 0, 0.15), transparent) !important;
    border-left: 3px solid #ff6f00;
    color: #ff8c1a !important;
    box-shadow: none !important;
}

.setor-item.active i {
    color: #ff6f00 !important;
}

/* --- CARD PREMIUM LOOK --- */
.video-card {
    border: 1px solid rgba(0,0,0,0.04) !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
    border-radius: 16px !important;
    background: #ffffff !important;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative;
    z-index: 10; /* Base z-index */
    overflow: visible !important; /* Allow hover effects */
}

.video-card:hover {
    transform: translateY(-8px) !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    z-index: 20; /* Higher on hover */
}

.video-card-title {
    font-weight: 600 !important;
    letter-spacing: -0.01em;
}

/* --- INTERACTION FIXES --- */
/* Ensure Overlay does not block actions */
.video-play-overlay {
    z-index: 5 !important;
    pointer-events: auto; /* Clickable link */
}

.video-card-actions {
    position: relative;
    z-index: 30 !important; /* Actions must be TOP */
    pointer-events: auto !important;
}

.video-card-btn {
    position: relative;
    z-index: 31 !important;
    cursor: pointer !important;
}

/* --- HEADER GLASSMORPHISM --- */
.top-header {
    height: 70px;
    background: #ffffff !important; /* Solid background backup */
    background: rgba(255, 255, 255, 0.98) !important; /* High opacity */
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03) !important;
    position: sticky !important;
    top: 0;
    z-index: 2000 !important; /* High Z-index */
    pointer-events: auto !important;
}

/* --- FOOTER PREMIUM (ISOLATED) --- */
.footer-premium {
    position: relative !important; /* Force flow */
    bottom: auto !important;
    left: auto !important;
    right: auto !important;
    width: auto !important;
    
    background: #1e293b !important;
    margin-top: 60px !important;
    margin-left: 280px !important; /* Desktop offset */
    padding: 40px 0 !important;
    border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
    z-index: 50 !important;
    pointer-events: auto !important;
}

/* Hide old footer styles just in case */
.footer {
    display: none !important;
}

.footer-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
}

.footer-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    margin-bottom: 0 !important;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding-bottom: 20px;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.footer-brand {
    font-size: 1.2rem;
    font-weight: 600;
    color: white;
}

.footer-social {
    gap: 15px;
    display: flex;
}

.social-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,0.1) !important;
    color: white !important;
    transition: all 0.3s ease;
    text-decoration: none !important;
}

.social-link:hover {
    background: #ff6f00 !important;
    transform: translateY(-2px);
}

.footer-links {
    display: flex;
    gap: 20px;
}

.footer-links a {
    color: #94a3b8 !important;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s;
}

.footer-links a:hover {
    color: white !important;
}

.footer-copyright {
    color: #64748b;
    font-size: 0.85rem;
}

/* --- DARK MODE ADJUSTMENTS --- */
[data-theme="dark"] body {
    background-color: #0f172a;
}

[data-theme="dark"] .top-header {
    background: rgba(15, 23, 42, 0.98) !important;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

[data-theme="dark"] .video-card {
    background: #1e293b !important;
    border: 1px solid rgba(255,255,255,0.05) !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
}

/* --- RESPONSIVE FOOTER --- */
@media (max-width: 992px) {
    .footer-premium {
        margin-left: 0 !important;
    }
    
    .sidebar {
        width: 100%; /* If mobile sidebar logic uses this */
        /* But usually mobile sidebar is off-canvas. Keep default logic */
    }
}

@media (max-width: 768px) {
    .footer-top, .footer-bottom {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    .footer-social {
        justify-content: center;
    }
    .footer-links {
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
}

    </style>
    <link href="premium_ui.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

    <div id="notification-container"></div>
    <!-- O resto do conteúdo da página -->


    

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
                    // Buscar email do usuário
                    // Verificar se a coluna telefone existe
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
                <?php if ($pode_fazer_upload): ?>
                    <button class="sidebar-btn btn-upload" onclick="openUploadModal()">
                        <i class="fas fa-upload"></i>
                        <span>Upload de Vídeo</span>
    </button>
                <?php endif; ?>
            <?php if ($usuario_adm): ?>
                    <button class="sidebar-btn btn-live" onclick="openLiveModal()">
                        <i class="fas fa-broadcast-tower"></i>
                        <span>Transmissão ao Vivo</span>
                    </button>
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
                    <a href="cadastro_modulos.php" class="sidebar-btn">
                        <i class="fas fa-cubes"></i>
                        <span>Cadastro de Módulos</span>
                    </a>
                <?php endif; ?>
                <?php if ($is_logged_in): ?>
                    <div class="sidebar-divider"></div>
                    <a href="gerenciar_playlists.php" class="sidebar-btn">
                        <i class="fas fa-list"></i>
                        <span>Minhas Playlists</span>
                    </a>
                <?php endif; ?>
                </div>
            <?php endif; ?>
        
        <div class="sidebar-divider"></div>
        
        <!-- Navegação por Setores -->
        <div class="sidebar-section">
            <h3 class="sidebar-section-title">
                <i class="fas fa-folder"></i>
                <span>Setores</span>
            </h3>
            <div class="setores-list">
                <a href="?filtroSetor=0&filtroModulo=0" class="setor-item <?= $filtro_setor == 0 ? 'active' : '' ?>">
                    <i class="fas fa-th"></i>
                    <span>Todos os Setores</span>
                    <span class="setor-count"><?= $total_videos ?></span>
                </a>
                <?php
                mysqli_data_seek($setores_result, 0);
                while ($setor = mysqli_fetch_assoc($setores_result)):
                    // Conta vídeos por setor
                    $count_query = "SELECT COUNT(*) as total FROM videos WHERE setor_id = ?";
                    $count_stmt = $conexao->prepare($count_query);
                    $count_stmt->bind_param('i', $setor['id']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_data = $count_result->fetch_assoc();
                    $video_count = $count_data['total'];
                    $count_stmt->close();
                    
                    $is_setor_ativo = ($filtro_setor == $setor['id']);
                ?>
                    <div class="setor-wrapper" data-setor-id="<?= $setor['id'] ?>">
                        <div class="setor-item <?= $is_setor_ativo ? 'active' : '' ?> setor-toggle" 
                             data-setor-id="<?= $setor['id'] ?>"
                             data-setor-nome="<?= htmlspecialchars($setor['nome']) ?>">
                            <i class="fas fa-folder<?= $is_setor_ativo ? '-open' : '' ?>"></i>
                            <i class="fas fa-chevron-<?= $is_setor_ativo ? 'down' : 'right' ?> setor-chevron" style="font-size: 10px; margin-left: 4px;"></i>
                        <span><?= htmlspecialchars($setor['nome']) ?></span>
                        <span class="setor-count"><?= $video_count ?></span>
                        </div>
                        
                        <!-- Container para módulos (carregado via AJAX) -->
                        <div class="modulos-list <?= $is_setor_ativo ? 'expanded' : '' ?>" 
                             id="modulos-<?= $setor['id'] ?>" 
                             style="display: <?= $is_setor_ativo ? 'flex' : 'none' ?>;">
                            <?php if ($is_setor_ativo): ?>
                                <div class="modulos-loading" style="padding: 12px; text-align: center; color: rgba(255,255,255,0.5);">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando módulos...
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
    <h1 class="page-title">Plataforma de <span class="highlight">Treinamentos</span></h1>
    <div class="search-box">
        <form method="GET" action="index.php" class="search-form">
            <input type="hidden" name="filtroSetor" value="<?= $filtro_setor ?>">
            <input type="hidden" name="filtroModulo" value="<?= $filtro_modulo ?>">
            <input type="text" name="pesquisaTitulo" placeholder="Pesquisar vídeos..." value="<?= htmlspecialchars($busca_titulo) ?>" class="search-input">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="header-actions">
        <button class="theme-toggle" id="themeToggle" title="Alternar tema">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <?php if ($is_logged_in): ?>
            <div class="notifications-wrapper">
                <button class="btn-notifications" id="btn-notifications" title="Notificações">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                </button>
                <div class="notifications-dropdown" id="notifications-dropdown">
                    <div class="notifications-header">
                        <h3>Notificações</h3>
                        <button class="btn-mark-all-read" onclick="marcarTodasLidas()">Marcar todas como lidas</button>
                    </div>
                    <div class="notifications-list" id="notifications-list">
                        <div class="notifications-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Carregando...</p>
                        </div>
                    </div>
                    <div class="notifications-footer">
                        <a href="config_notificacoes.php" class="btn-view-all-notifications">Configurar notificações</a>
                    </div>
                </div>
            </div>
            <?php if ($is_logged_in): ?>
                <a href="historico.php" class="btn-header-history" title="Histórico">
                    <i class="fas fa-history"></i>
                    <span>Histórico</span>
                </a>
            <?php endif; ?>
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
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-video"></i> Upload de Vídeo</h2>
        </div>
        <form id="uploadForm" enctype="multipart/form-data" accept-charset="UTF-8">
            <div class="form-group">
                <label for="titulo" class="form-label">
                    <i class="fas fa-heading"></i> Título <span>*</span>
                </label>
                <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Digite o título do vídeo" required>
            </div>
            <div class="form-group">
                <label for="descricao" class="form-label">
                    <i class="fas fa-align-left"></i> Descrição <span>*</span>
                </label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descreva brevemente o vídeo" required></textarea>
            </div>
            <div class="form-group">
                <label for="setor" class="form-label">
                    <i class="fas fa-briefcase"></i> Setor <span>*</span>
                </label>
                <select class="form-control" id="setor" name="setor_id" required>
                    <option value="">Selecione um setor</option>
                    <?php
                    // Se for admin, mostra todos os setores ativos
                    // Se for usuário, mostra apenas os setores permitidos
                    if ($usuario_adm) {
                        $setores_query = "SELECT id, nome FROM setores WHERE ativo = 'S' ORDER BY nome ASC";
                    } else {
                        // Filtra apenas setores permitidos
                        if (!empty($setores_permitidos)) {
                            $setores_ids = implode(',', array_map('intval', $setores_permitidos));
                            $setores_query = "SELECT id, nome FROM setores WHERE ativo = 'S' AND id IN ($setores_ids) ORDER BY nome ASC";
                        } else {
                            $setores_query = "SELECT id, nome FROM setores WHERE 1=0"; // Nenhum setor se não tiver permissão
                        }
                    }
                    $setores_result = mysqli_query($conexao, $setores_query);

                    if ($setores_result && mysqli_num_rows($setores_result) > 0) {
                        while ($setor = mysqli_fetch_assoc($setores_result)) {
                            echo '<option value="' . htmlspecialchars($setor['id']) . '">' . htmlspecialchars($setor['nome']) . '</option>';
                        }
                    } else {
                        echo '<option value="">Nenhum setor disponível</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="modulo" class="form-label">
                    <i class="fas fa-cube"></i> Módulo
                </label>
                <select class="form-control" id="modulo" name="modulo_id">
                    <option value="">Selecione um módulo (opcional)</option>
                    <?php
                    // Carrega módulos do setor selecionado via JavaScript
                    // O select será populado dinamicamente quando o setor for selecionado
                    ?>
                </select>
                <small class="text-muted" style="font-size: 12px; margin-top: 4px; display: block;">
                    <i class="fas fa-info-circle"></i> Selecione um setor primeiro para ver os módulos disponíveis
                </small>
            </div>
            <div class="form-group">
                <label for="video" class="form-label">
                    <i class="fas fa-file-video"></i> Arquivo de Vídeo <span>*</span>
                </label>
                <input type="file" class="form-control" id="video" name="video" accept="video/*" required>
            </div>
            
            <!-- Campos de Sequência -->
            <div class="form-group" style="border-top: 1px solid #e5e7eb; padding-top: 16px; margin-top: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="isSequencia" name="is_sequencia" onchange="toggleSequenciaFields()" style="width: auto; margin: 0;">
                    <i class="fas fa-list-ol"></i>
                    <span>Este vídeo faz parte de uma sequência</span>
                </label>
            </div>
            
            <div id="sequenciaFields" style="display: none; background: linear-gradient(135deg, #f9fafb 0%, #f0f4ff 100%); padding: 20px; border-radius: 12px; margin-top: 16px; border: 2px solid #e0e7ff;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #d1d5db;">
                    <i class="fas fa-list-ol" style="color: #6366f1; font-size: 18px;"></i>
                    <h4 style="margin: 0; color: #374151; font-size: 16px; font-weight: 700;">Gerenciar Sequência</h4>
                </div>
                
                <div class="form-group">
                    <label for="sequenciaSelect" class="form-label">
                        <i class="fas fa-list"></i> Escolher Sequência
                    </label>
                    <select class="form-control" id="sequenciaSelect" name="sequencia_id" onchange="toggleNovaSequenciaFields()" style="font-size: 14px;">
                        <option value="">-- Criar nova sequência --</option>
                    </select>
                    <small class="text-muted" style="font-size: 12px; margin-top: 6px; display: block; color: #6b7280;">
                        <i class="fas fa-info-circle"></i> <strong>Dica:</strong> Selecione uma sequência existente para adicionar este vídeo, ou crie uma nova sequência
                    </small>
                </div>
                
                <div id="novaSequenciaFields" style="display: block; background: #ffffff; padding: 16px; border-radius: 8px; border: 1px solid #d1d5db; margin-top: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <i class="fas fa-plus-circle" style="color: #10b981; font-size: 16px;"></i>
                        <span style="font-weight: 600; color: #374151; font-size: 14px;">Criar Nova Sequência</span>
                    </div>
                    <div class="form-group">
                        <label for="sequenciaTitulo" class="form-label">
                            <i class="fas fa-heading"></i> Título da Sequência <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" class="form-control" id="sequenciaTitulo" name="sequencia_titulo" 
                               placeholder="Ex: Curso de Consultório Médico" style="font-size: 14px;">
                        <small class="text-muted" style="font-size: 12px; margin-top: 6px; display: block; color: #6b7280;">
                            <i class="fas fa-lightbulb"></i> Este será o nome da sequência completa. Ex: "Curso de Consultório - Parte 1, 2, 3..."
                        </small>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 16px;">
                    <label for="sequenciaOrdem" class="form-label">
                        <i class="fas fa-sort-numeric-up"></i> Ordem na Sequência
                    </label>
                    <input type="number" class="form-control" id="sequenciaOrdem" name="sequencia_ordem" 
                           min="1" placeholder="Ex: 1, 2, 3..." style="font-size: 14px;">
                    <small class="text-muted" style="font-size: 12px; margin-top: 6px; display: block; color: #6b7280;">
                        <i class="fas fa-magic"></i> <strong>Automático:</strong> Se deixar vazio, será automaticamente o próximo número da sequência
                    </small>
                </div>
                
                <div style="background: #eff6ff; padding: 12px; border-radius: 8px; margin-top: 16px; border-left: 4px solid #3b82f6;">
                    <div style="display: flex; align-items: start; gap: 8px;">
                        <i class="fas fa-info-circle" style="color: #3b82f6; margin-top: 2px;"></i>
                        <div>
                            <strong style="color: #1e40af; font-size: 13px;">Como funciona:</strong>
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #1e3a8a; line-height: 1.5;">
                                1. Marque "Este vídeo faz parte de uma sequência"<br>
                                2. Escolha uma sequência existente ou crie uma nova<br>
                                3. Defina a ordem (ou deixe automático)<br>
                                4. No próximo upload, a sequência criada estará disponível!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-primary" onclick="uploadVideo()">
                    <i class="fas fa-cloud-upload-alt"></i> Enviar
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
            <div class="progress mt-3">
                <div class="progress-bar" id="uploadProgressBar" role="progressbar" style="width: 0;"></div>
            </div>
        </form>
        <div id="uploadNotification" class="notification hidden"></div>
    </div>
</div>

<!-- Modal de Transmissão ao Vivo - Redesenhado -->
<div id="liveModal" class="modal-live">
    <div class="modal-live-content">
        <div class="modal-live-header">
            <div class="modal-live-title-wrapper">
                <i class="fas fa-broadcast-tower"></i>
                <h2>Transmissão ao Vivo</h2>
        </div>
            <button class="modal-live-close" onclick="closeLiveModal()">
                <i class="fas fa-times"></i>
                </button>
            </div>
        <div class="modal-live-body">
            <div class="form-group-live">
                <label for="liveURL" class="form-label-live">
                    <i class="fab fa-youtube"></i>
                    <span>URL do YouTube</span>
                </label>
                <input type="text" id="liveURL" class="form-input-live" placeholder="https://www.youtube.com/watch?v=..." required>
                <small class="form-hint">Cole o link completo da transmissão ao vivo</small>
            </div>
            
            <div class="form-group-live">
                <label for="liveTitulo" class="form-label-live">
                    <i class="fas fa-heading"></i>
                    <span>Título</span>
                </label>
                <input type="text" id="liveTitulo" class="form-input-live" placeholder="Ex: Transmissão Especial #001" value="Transmissão Especial">
                <small class="form-hint">Título que aparecerá no card da transmissão</small>
            </div>
            
            <div class="form-group-live">
                <label for="liveDescricao" class="form-label-live">
                    <i class="fas fa-align-left"></i>
                    <span>Descrição</span>
                </label>
                <textarea id="liveDescricao" class="form-textarea-live" rows="3" placeholder="Descreva brevemente a transmissão...">Acompanhe ao vivo nossa transmissão especial com conteúdos exclusivos.</textarea>
                <small class="form-hint">Descrição principal que aparecerá abaixo do título</small>
            </div>
            
            <div class="form-group-live">
                <label for="liveSubtexto" class="form-label-live">
                    <i class="fas fa-info-circle"></i>
                    <span>Subtexto (Opcional)</span>
                </label>
                <input type="text" id="liveSubtexto" class="form-input-live" placeholder="Ex: Participe e faça suas perguntas!">
                <small class="form-hint">Texto adicional que aparecerá de forma discreta</small>
            </div>
        </div>
        <div class="modal-live-footer">
            <button class="btn-live-secondary" onclick="closeLiveModal()">
                <i class="fas fa-times"></i> Cancelar
                </button>
            <button class="btn-live-primary" onclick="startLive()">
                <i class="fas fa-play"></i> Iniciar Transmissão
                </button>
            </div>
    </div>
</div>

<!-- Modal de Confirmação de Encerramento -->
<div id="stopLiveModal" class="modal-live">
    <div class="modal-live-content modal-live-small">
        <div class="modal-live-header">
            <div class="modal-live-title-wrapper">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Encerrar Transmissão</h2>
            </div>
            <button class="modal-live-close" onclick="closeStopLiveModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-live-body">
            <p class="stop-live-message">Tem certeza que deseja encerrar a transmissão ao vivo?</p>
            <p class="stop-live-warning">Esta ação não pode ser desfeita.</p>
        </div>
        <div class="modal-live-footer">
            <button class="btn-live-secondary" onclick="closeStopLiveModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn-live-danger" onclick="confirmStopLive()">
                <i class="fas fa-stop"></i> Encerrar
            </button>
        </div>
    </div>
</div>

        


<!-- Conteúdo Principal -->
<div class="main-content">
    <div class="content-wrapper">
    <!-- Header do Conteúdo -->
    <div class="content-header videos-header">
        <h2>
            <span class="breadcrumb-path">
            <?php if ($filtro_setor > 0): ?>
                <?php
                mysqli_data_seek($setores_result, 0);
                $setor_nome_filtro = 'Todos os Setores';
                while ($setor = mysqli_fetch_assoc($setores_result)) {
                    if ($setor['id'] == $filtro_setor) {
                        $setor_nome_filtro = $setor['nome'];
                        break;
                    }
                }
                    
                    // Busca nome do módulo se houver filtro
                    $modulo_nome_filtro = '';
                    if ($filtro_modulo > 0 && $modulos_result) {
                        mysqli_data_seek($modulos_result, 0);
                        while ($modulo = mysqli_fetch_assoc($modulos_result)) {
                            if ($modulo['id'] == $filtro_modulo) {
                                $modulo_nome_filtro = $modulo['nome'];
                                break;
                            }
                    }
                }
                ?>
                <i class="fas fa-folder-open" style="color: #ff6f00;"></i> <?= htmlspecialchars($setor_nome_filtro) ?>
                    <?php if (!empty($modulo_nome_filtro)): ?>
                        <span style="margin: 0 8px; color: #999;">/</span>
                        <i class="fas fa-cube" style="color: #6366f1;"></i> <?= htmlspecialchars($modulo_nome_filtro) ?>
                    <?php endif; ?>
            <?php else: ?>
                <i class="fas fa-th" style="color: #ff6f00;"></i> Todos os Vídeos
            <?php endif; ?>
            </span>
        </h2>
        <div class="videos-info-modern">
            <div class="videos-count-modern">
                <i class="fas fa-video"></i>
                <span class="videos-number"><?= $total_videos ?></span>
                <span class="videos-label"><?= $total_videos == 1 ? 'vídeo' : 'vídeos' ?></span>
            </div>
            <?php if ($total_paginas > 1): ?>
            <div class="videos-pages-modern">
                <i class="fas fa-file-alt"></i>
                <span>Página <strong><?= $pagina_atual ?></strong> de <strong><?= $total_paginas ?></strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Container Separado para Seções Secundárias (Recomendações e Continuar Assistindo) -->
    <div class="secondary-sections-container">
        <!-- Seção Continuar Assistindo - CARROSSEL -->
        <div class="carousel-section" id="continuar-assistindo-section" style="display: none;">
            <div class="carousel-header">
                <h3 class="carousel-title">
                    <i class="fas fa-play-circle"></i>
                    Continuar Assistindo
                </h3>
            </div>
            <div class="carousel-wrapper">
                <button class="carousel-btn carousel-btn-prev" onclick="scrollCarousel('continuar', -1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="carousel-container" id="continuar-carousel">
                    <div class="carousel-track" id="continuar-track">
                        <!-- Carregado via JavaScript -->
                    </div>
                </div>
                <button class="carousel-btn carousel-btn-next" onclick="scrollCarousel('continuar', 1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Seção de Recomendações - CARD MINIMALISTA -->
        <div class="carousel-section" id="recomendacoesSection" style="display: none;">
            <div class="carousel-header">
                <h3 class="carousel-title">
                    <i class="fas fa-sparkles"></i>
                    Recomendado para Você
                </h3>
            </div>
            <div class="carousel-wrapper">
                <button class="carousel-btn carousel-btn-prev" onclick="scrollCarousel('recomendacoes', -1)" aria-label="Anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="carousel-container" id="recomendacoes-carousel">
                    <div class="carousel-track" id="recomendacoes-track">
                        <!-- Carregado via AJAX -->
                    </div>
                </div>
                <button class="carousel-btn carousel-btn-next" onclick="scrollCarousel('recomendacoes', 1)" aria-label="Próximo">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Divisor Visual entre Seções Secundárias e Vídeos Principais -->
    <div class="section-divider" id="sectionDivider" style="display: none;"></div>

   <!--começo da live-->    
    <?php
include 'db/conexao.php';

// 🔹 Busca a live ativa no banco de dados
$live_query = "SELECT url, titulo, descricao, subtexto FROM transmissao_ao_vivo WHERE ativo = 1 ORDER BY created_at DESC LIMIT 1";
$live_result = mysqli_query($conexao, $live_query);
$live_data = mysqli_fetch_assoc($live_result);
$live_url = $live_data['url'] ?? null;
$live_titulo = $live_data['titulo'] ?? "🔴 AO VIVO";
$live_descricao = $live_data['descricao'] ?? "Estamos transmitindo ao vivo!";
$live_subtexto = $live_data['subtexto'] ?? '';

// 🔹 Função para extrair o ID do vídeo do YouTube
function getYouTubeVideoID($url) {
    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/|.+\?v=))([^"&?\/\s]{11})/', $url, $matches);
    return $matches[1] ?? null;
}

$video_id = $live_url ? getYouTubeVideoID($live_url) : null;

if ($live_url && $video_id):
?>
<!-- Transmissão Ao Vivo - Design Refinado e Elegante -->
<div class="live-container-refined" id="liveStreamContainer">
    <!-- Header Refinado -->
    <div class="live-header-refined">
        <div class="live-header-content-refined">
            <div class="live-header-left-refined">
                <div class="live-indicator-refined">
                    <span class="live-dot-refined"></span>
                </div>
                <div class="live-header-text-refined">
                    <span class="live-label-refined">Transmissão ao Vivo</span>
                    <h2 class="live-title-refined"><?= htmlspecialchars($live_titulo) ?></h2>
                </div>
            </div>
            <div class="live-header-actions-refined">
                <button class="live-btn-action-refined" onclick="toggleLiveCard()" title="Minimizar">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <?php if ($usuario_adm): ?>
                <button class="live-btn-action-refined live-btn-end-refined" onclick="openStopLiveModal()" title="Encerrar transmissão">
                    <i class="fas fa-stop-circle"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        </div>

    <!-- Card Principal Refinado -->
    <div class="live-card-refined" id="liveCardModern">
        <!-- Video e Chat -->
        <div class="live-content-grid-refined">
            <div class="live-video-refined">
                <div class="live-video-wrapper-refined">
                    <div class="live-badge-refined">
                        <span class="live-dot-badge"></span>
                        <span>AO VIVO</span>
                    </div>
                    <iframe id="livePlayer" 
                        src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>?enablejsapi=1&autoplay=1&mute=0" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
                </div>
            </div>

            <div class="live-chat-refined">
                <div class="live-chat-header-refined">
                    <div class="live-chat-title-wrapper">
                        <i class="fas fa-comments"></i>
                        <span class="live-chat-title-refined">Chat ao Vivo</span>
                    </div>
                    <div class="live-chat-status-refined">
                        <span class="live-status-dot-refined"></span>
                        <span>Ativo</span>
                    </div>
                </div>
                <div class="live-chat-iframe-refined">
                    <iframe id="chatFrame" 
                    src="https://www.youtube.com/live_chat?v=<?= htmlspecialchars($video_id) ?>&embed_domain=<?= $_SERVER['HTTP_HOST'] ?>" 
                    frameborder="0">
                </iframe>
                </div>
            </div>
        </div>

        <!-- Divisor -->
        <div class="live-divider-refined"></div>

        <!-- Informações Refinadas -->
        <div class="live-info-refined">
            <div class="live-info-content-refined">
                <?php if (!empty($live_subtexto)): ?>
                <div class="live-subtitle-wrapper-refined">
                    <i class="fas fa-info-circle"></i>
                    <p class="live-subtitle-refined"><?= htmlspecialchars($live_subtexto) ?></p>
            </div>
                <?php endif; ?>
                <div class="live-description-wrapper-refined">
                    <i class="fas fa-align-left"></i>
                    <p class="live-description-refined"><?= htmlspecialchars($live_descricao) ?></p>
        </div>
    </div>
            <div class="live-stats-refined">
                <div class="live-stat-refined">
                    <div class="live-stat-icon-refined">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="live-stat-info-refined">
                        <span class="live-stat-value-refined" id="liveViews">0</span>
                        <span class="live-stat-label-refined">Assistindo</span>
                    </div>
                </div>
                <div class="live-stat-divider-refined"></div>
                <div class="live-stat-refined">
                    <div class="live-stat-icon-refined">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="live-stat-info-refined">
                        <span class="live-stat-value-refined" id="liveLikes">0</span>
                        <span class="live-stat-label-refined">Curtidas</span>
                    </div>
                </div>
                <div class="live-stat-divider-refined"></div>
                <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($video_id) ?>" target="_blank" class="live-link-yt-refined">
                    <i class="fab fa-youtube"></i>
                    <span>Assistir no YouTube</span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Botão Flutuante Refinado (quando minimizado) -->
    <button class="live-float-refined" id="liveFloatButton" onclick="toggleLiveCard()" style="display: none;">
        <div class="live-float-indicator-refined">
            <span class="live-float-dot-refined"></span>
        </div>
        <div class="live-float-content-refined">
            <i class="fas fa-video"></i>
            <span>Transmissão ao Vivo</span>
        </div>
    </button>
</div>

<?php endif; ?>

<style>
/* ============================================
   TRANSMISSÃO AO VIVO - DESIGN REFINADO E ELEGANTE
   ============================================ */

.live-container-refined {
    margin: 30px auto;
    max-width: 1320px;
    padding: 0 20px;
    position: relative;
}

/* Header Refinado */
.live-header-refined {
    background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
    border: 1px solid #e5e7eb;
    border-bottom: 2px solid #ef4444;
    border-radius: 12px 12px 0 0;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.live-header-content-refined {
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.live-header-left-refined {
    display: flex;
    align-items: center;
    gap: 16px;
}

.live-indicator-refined {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    border-radius: 10px;
}

.live-dot-refined {
    width: 12px;
    height: 12px;
    background: #ef4444;
    border-radius: 50%;
    position: absolute;
    animation: liveDotRefined 2s infinite;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
}

@keyframes liveDotRefined {
    0% {
        opacity: 1;
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1);
        box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
    }
    100% {
        opacity: 1;
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}

.live-header-text-refined {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.live-label-refined {
    font-size: 11px;
    font-weight: 700;
    color: #ef4444;
    text-transform: uppercase;
    letter-spacing: 1.2px;
}

.live-title-refined {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1.3;
}

.live-header-actions-refined {
    display: flex;
    gap: 8px;
    align-items: center;
}

.live-btn-action-refined {
    background: transparent;
    border: 1.5px solid #e5e7eb;
    color: #6b7280;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 15px;
}

.live-btn-action-refined:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #374151;
    transform: translateY(-1px);
}

.live-btn-end-refined {
    color: #ef4444;
    border-color: #fee2e2;
}

.live-btn-end-refined:hover {
    background: #fef2f2;
    border-color: #fecaca;
    color: #dc2626;
}

/* Card Principal Refinado */
.live-card-refined {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transition: all 0.3s ease;
}

.live-card-refined.minimized {
    display: none;
}

/* Grid de Conteúdo */
.live-content-grid-refined {
    display: grid;
    grid-template-columns: 2.2fr 1fr;
    gap: 0;
    min-height: 540px;
}

/* Video Refinado */
.live-video-refined {
    background: #000;
    position: relative;
}

.live-video-wrapper-refined {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%;
    overflow: hidden;
}

.live-badge-refined {
    position: absolute;
    top: 14px;
    left: 14px;
    z-index: 10;
    background: rgba(239, 68, 68, 0.98);
    padding: 6px 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 7px;
    color: #ffffff;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
}

.live-dot-badge {
    width: 7px;
    height: 7px;
    background: #ffffff;
    border-radius: 50%;
    animation: liveDotRefined 1.5s infinite;
}

.live-video-wrapper-refined iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

/* Chat Refinado */
.live-chat-refined {
    background: #f9fafb;
    border-left: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    height: 540px;
}

.live-chat-header-refined {
    background: #ffffff;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.live-chat-title-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.live-chat-title-wrapper i {
    color: #ef4444;
    font-size: 16px;
}

.live-chat-title-refined {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
}

.live-chat-status-refined {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
}

.live-status-dot-refined {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
    animation: liveDotRefined 2s infinite;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
}

.live-chat-iframe-refined {
    flex: 1;
    overflow: hidden;
    background: #ffffff;
}

.live-chat-iframe-refined iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Divisor */
.live-divider-refined {
    height: 1px;
    background: linear-gradient(to right, transparent, #e5e7eb, transparent);
    margin: 0;
}

/* Informações Refinadas */
.live-info-refined {
    background: #f9fafb;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 32px;
}

.live-info-content-refined {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.live-subtitle-wrapper-refined,
.live-description-wrapper-refined {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.live-subtitle-wrapper-refined i,
.live-description-wrapper-refined i {
    color: #ef4444;
    font-size: 16px;
    margin-top: 2px;
    flex-shrink: 0;
}

.live-subtitle-refined {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
    font-weight: 600;
    line-height: 1.5;
}

.live-description-refined {
    font-size: 14px;
    color: #4b5563;
    margin: 0;
    line-height: 1.7;
}

.live-stats-refined {
    display: flex;
    align-items: center;
    gap: 0;
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.live-stat-refined {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 20px;
}

.live-stat-icon-refined {
    width: 40px;
    height: 40px;
    background: #fef2f2;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
    font-size: 18px;
}

.live-stat-info-refined {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.live-stat-value-refined {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.live-stat-label-refined {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.live-stat-divider-refined {
    width: 1px;
    height: 40px;
    background: #e5e7eb;
}

.live-link-yt-refined {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ef4444;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 10px;
    transition: all 0.2s ease;
    margin-left: 8px;
}

.live-link-yt-refined:hover {
    background: #fef2f2;
    color: #dc2626;
    transform: translateY(-1px);
}

.live-link-yt-refined i:first-child {
    font-size: 18px;
}

.live-link-yt-refined i:last-child {
    font-size: 12px;
    opacity: 0.7;
}

/* Botão Flutuante Refinado */
.live-float-refined {
    position: fixed;
    bottom: 28px;
    right: 28px;
    background: #ffffff;
    border: 2px solid #ef4444;
    color: #ef4444;
    padding: 14px 22px;
    border-radius: 30px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.25), 0 0 0 0 rgba(239, 68, 68, 0.4);
    z-index: 9999;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 14px;
    animation: liveFloatPulseRefined 2s infinite;
}

@keyframes liveFloatPulseRefined {
    0%, 100% {
        box-shadow: 0 4px 16px rgba(239, 68, 68, 0.25), 0 0 0 0 rgba(239, 68, 68, 0.4);
    }
    50% {
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35), 0 0 0 8px rgba(239, 68, 68, 0);
    }
}

.live-float-refined:hover {
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
    transform: translateY(-3px);
    background: #fef2f2;
}

.live-float-indicator-refined {
    position: relative;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    border-radius: 50%;
}

.live-float-dot-refined {
    width: 10px;
    height: 10px;
    background: #ef4444;
    border-radius: 50%;
    animation: liveDotRefined 2s infinite;
}

.live-float-content-refined {
    display: flex;
    align-items: center;
    gap: 10px;
}

.live-float-content-refined i {
    font-size: 18px;
}

/* Responsividade */
@media (max-width: 1024px) {
    .live-content-grid-refined {
        grid-template-columns: 1fr;
    }
    
    .live-chat-refined {
        border-left: none;
        border-top: 1px solid #e5e7eb;
        height: 380px;
    }
    
    .live-info-refined {
        flex-direction: column;
        gap: 20px;
    }
    
    .live-stats-refined {
        width: 100%;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .live-stat-divider-refined {
        display: none;
    }
}

@media (max-width: 768px) {
    .live-container-refined {
        margin: 15px 0;
        padding: 0 10px;
    }
    
    .live-title-refined {
        font-size: 16px;
    }
    
    .live-header-actions-refined {
        gap: 6px;
    }
    
    .live-btn-action-refined {
        width: 34px;
        height: 34px;
        font-size: 13px;
    }
    
    .live-stats-refined {
        flex-direction: column;
        gap: 12px;
    }
    
    .live-stat-refined {
        width: 100%;
        padding: 0;
        justify-content: center;
    }
    
    .live-stat-divider-refined {
        display: none;
    }
    
    .live-float-refined {
        bottom: 20px;
        right: 20px;
        padding: 12px 18px;
        font-size: 13px;
    }
}

/* Dark Mode */
[data-theme="dark"] .live-header-refined {
    background: linear-gradient(to bottom, #1f2937 0%, #111827 100%);
    border-color: #374151;
}

[data-theme="dark"] .live-title-refined {
    color: #f3f4f6;
}

[data-theme="dark"] .live-card-refined {
    background: #1f2937;
    border-color: #374151;
}

[data-theme="dark"] .live-chat-refined {
    background: #111827;
    border-color: #374151;
}

[data-theme="dark"] .live-chat-header-refined {
    background: #1f2937;
    border-color: #374151;
}

[data-theme="dark"] .live-chat-title-refined {
    color: #f3f4f6;
}

[data-theme="dark"] .live-info-refined {
    background: #111827;
}

[data-theme="dark"] .live-stats-refined {
    background: #1f2937;
    border-color: #374151;
}

[data-theme="dark"] .live-stat-value-refined {
    color: #f3f4f6;
}

[data-theme="dark"] .live-description-refined {
    color: #d1d5db;
}

[data-theme="dark"] .live-float-refined {
    background: #1f2937;
    border-color: #ef4444;
    color: #ef4444;
}

[data-theme="dark"] .live-float-refined:hover {
    background: #374151;
}

/* Modal de Live - Premium */
.modal-live {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.2s ease;
}

.modal-live.active {
    display: flex;
}

.modal-live-content {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

.modal-live-small {
    max-width: 400px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-live-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.modal-live-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-live-title-wrapper i {
    color: #ff6f00;
    font-size: 20px;
}

.modal-live-title-wrapper h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1a1a1a;
}

.modal-live-close {
    background: transparent;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #999;
    transition: all 0.2s ease;
    font-size: 18px;
}

.modal-live-close:hover {
    background: #f5f5f5;
    color: #333;
}

.modal-live-body {
    padding: 24px;
}

.form-group-live {
    margin-bottom: 20px;
}

.form-label-live {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-label-live i {
    color: #ff6f00;
    font-size: 16px;
}

.form-input-live,
.form-textarea-live {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: #ffffff;
    color: #333;
    box-sizing: border-box;
}

.form-input-live:focus,
.form-textarea-live:focus {
    outline: none;
    border-color: #ff6f00;
    box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
}

.form-textarea-live {
    resize: vertical;
    min-height: 80px;
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #999;
}

.modal-live-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    background: #fafafa;
}

.btn-live-primary,
.btn-live-secondary,
.btn-live-danger {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-live-primary {
    background: #ff6f00;
    color: #ffffff;
}

.btn-live-primary:hover {
    background: #ff8c1a;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
}

.btn-live-secondary {
    background: #f5f5f5;
    color: #666;
}

.btn-live-secondary:hover {
    background: #e8e8e8;
}

.btn-live-danger {
    background: #ef4444;
    color: #ffffff;
}

.btn-live-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.stop-live-message {
    font-size: 16px;
    color: #333;
    margin: 0 0 8px 0;
}

.stop-live-warning {
    font-size: 14px;
    color: #ef4444;
    margin: 0;
}

/* Responsividade */
@media (max-width: 968px) {
    .live-card-content {
        grid-template-columns: 1fr;
    }
    
    .live-card-chat {
        border-left: none;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
        height: 300px;
    }
    
    .live-card-stats {
        flex-direction: column;
        gap: 12px;
    }
}

@media (max-width: 768px) {
    .live-card-wrapper {
        margin: 15px 0;
        padding: 0 10px;
    }
    
    .live-card-title {
        font-size: 20px;
    }
    
    .modal-live-content {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    .modal-live-body {
        padding: 20px;
    }
}

/* Dark Mode Support */
[data-theme="dark"] .live-card {
    background: #1a1a1a;
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .live-card-title {
    color: #e0e0e0;
}

[data-theme="dark"] .live-card-subtext {
    color: #999;
}

[data-theme="dark"] .live-card-chat {
    background: #2a2a2a;
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .live-card-footer {
    background: #222;
    border-color: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .live-card-description {
    color: #ccc;
}

[data-theme="dark"] .modal-live-content {
    background: #1a1a1a;
    color: #e0e0e0;
}

[data-theme="dark"] .form-input-live,
[data-theme="dark"] .form-textarea-live {
    background: #2a2a2a;
    border-color: rgba(255, 255, 255, 0.1);
    color: #e0e0e0;
}

[data-theme="dark"] .modal-live-footer {
    background: #222;
    border-color: rgba(255, 255, 255, 0.1);
}
</style>

<script>
    // Modal de Perfil
    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        if (modal) {
            modal.style.display = 'flex';
            
            // Carregar dados do perfil
            fetch('get_perfil.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('profileNome').value = data.nome || '';
                    document.getElementById('profileEmail').value = data.email || '';
                    document.getElementById('profileTelefone').value = data.telefone || '';
                    document.getElementById('profileEstado').value = data.estado_id || '';
                    
                    // Carregar municípios se houver estado
                    if (data.estado_id) {
                        document.getElementById('profileEstado').dispatchEvent(new Event('change'));
                        setTimeout(() => {
                            document.getElementById('profileMunicipio').value = data.municipio_id || '';
                        }, 500);
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar perfil:', error);
                    alert('Erro ao carregar dados do perfil.');
                });
        }
    }

    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        if (modal) {
            modal.style.display = 'none';
            const form = document.getElementById('profileForm');
            if (form) form.reset();
        }
    }

    // Carregar municípios quando estado mudar no modal de perfil
    // ===== FUNÇÕES PARA DURAÇÃO E PROGRESSO DOS VÍDEOS =====
    function formatDuration(seconds) {
        if (!seconds || isNaN(seconds)) return '--:--';
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
        return `${minutes}:${String(secs).padStart(2, '0')}`;
    }

    function loadVideoDurations() {
        document.querySelectorAll('.video-card-thumbnail video').forEach(video => {
            const videoId = video.closest('.video-card-thumbnail').dataset.videoId;
            const durationElement = document.getElementById(`duration-${videoId}`);
            
            if (!durationElement) return;
            
            video.addEventListener('loadedmetadata', function() {
                const duration = this.duration;
                if (duration && !isNaN(duration)) {
                    durationElement.textContent = formatDuration(duration);
                }
            });
            
            // Tentar carregar se já estiver disponível
            if (video.readyState >= 1) {
                const duration = video.duration;
                if (duration && !isNaN(duration)) {
                    durationElement.textContent = formatDuration(duration);
                }
            }
        });
    }

    function loadVideoProgress() {
        // Verificar se há histórico de visualização
        // Por enquanto, apenas preparar a estrutura
        // Futuramente, buscar do banco de dados via AJAX
        document.querySelectorAll('.video-progress').forEach(progress => {
            const videoId = progress.id.replace('progress-', '');
            const progressBar = document.getElementById(`progress-bar-${videoId}`);
            
            // Exemplo: se houver progresso salvo no localStorage
            const savedProgress = localStorage.getItem(`video_progress_${videoId}`);
            if (savedProgress) {
                const progressPercent = parseFloat(savedProgress);
                if (progressPercent > 0 && progressPercent < 100) {
                    progress.style.display = 'block';
                    progressBar.style.width = progressPercent + '%';
                } else if (progressPercent >= 100) {
                    progress.style.display = 'block';
                    progressBar.style.width = '100%';
                    progressBar.classList.add('video-progress-complete');
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const profileEstado = document.getElementById('profileEstado');
        if (profileEstado) {
            profileEstado.addEventListener('change', function() {
                const estadoId = this.value;
                const municipioSelect = document.getElementById('profileMunicipio');
                if (!municipioSelect) return;
                
                municipioSelect.innerHTML = '<option value="">Carregando...</option>';
                
                if (estadoId) {
                    fetch(`get_municipios.php?estado_id=${estadoId}`)
                        .then(response => response.json())
                        .then(municipios => {
                            municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                            if (Array.isArray(municipios)) {
                                municipios.forEach(m => {
                                    municipioSelect.innerHTML += `<option value="${m.id}">${m.nome}</option>`;
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao carregar municípios:', error);
                            municipioSelect.innerHTML = '<option value="">Erro ao carregar</option>';
                        });
                } else {
                    municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                }
            });
        }

        // Salvar perfil
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('update_perfil.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Perfil atualizado com sucesso!');
                        closeProfileModal();
                        location.reload();
                    } else {
                        alert('Erro: ' + (data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao atualizar perfil.');
                });
            });
        }
    });

    // Toggle Sidebar
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
    
    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
    
    // Fecha sidebar ao clicar na área principal (apenas no mobile)
    document.addEventListener('DOMContentLoaded', function() {
        const mainContent = document.querySelector('.main-content');
        const contentWrapper = document.querySelector('.content-wrapper');
        const videosGrid = document.querySelector('.videos-grid');
        
        function handleMainContentClick(e) {
            // Verifica se está no mobile (largura <= 768px)
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                // Só fecha se o sidebar estiver aberto
                if (sidebar && sidebar.classList.contains('active')) {
                    // Não fecha se clicou dentro do sidebar ou no botão de toggle
                    const clickedInSidebar = e.target.closest('.sidebar');
                    const clickedInToggle = e.target.closest('.menu-toggle') || e.target.closest('.sidebar-toggle');
                    
                    if (!clickedInSidebar && !clickedInToggle) {
                        closeSidebar();
                    }
                }
            }
        }
        
        // Fecha ao clicar na área principal
        if (mainContent) {
            mainContent.addEventListener('click', handleMainContentClick);
        }
        
        // Fecha ao clicar na grid de vídeos
        if (videosGrid) {
            videosGrid.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    if (sidebar && sidebar.classList.contains('active')) {
                        // Não fecha se clicou em um botão de ação do card
                        const clickedInAction = e.target.closest('.video-card-actions') || 
                                                 e.target.closest('.video-card-btn') ||
                                                 e.target.closest('a');
                        if (!clickedInAction) {
                            closeSidebar();
                        }
                    }
                }
            });
        }
    });

    // Fechar sidebar ao clicar no overlay
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }
    });

    // Fechar sidebar ao redimensionar (se for desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        }
    });

    function openLivePopup() {
        const liveURL = document.getElementById('livePlayer').src;
        window.open(liveURL, '_blank', 'width=800,height=500');
    }

    function openChatPopup() {
        const chatURL = document.getElementById('chatFrame').src;
        window.open(chatURL, '_blank', 'width=400,height=600');
    }

    function toggleMute() {
        const player = document.getElementById("livePlayer").contentWindow;
        const muteIcon = document.getElementById("muteIcon");

        if (muteIcon.classList.contains("bi-volume-up")) {
            player.postMessage('{"event":"command","func":"mute","args":""}', '*');
            muteIcon.classList.replace("bi-volume-up", "bi-volume-mute");
        } else {
            player.postMessage('{"event":"command","func":"unMute","args":""}', '*');
            muteIcon.classList.replace("bi-volume-mute", "bi-volume-up");
        }
    }

    function formatNumber(num) {
        if (!num || num === "0") return "0";
        const n = parseInt(num);
        if (n >= 1000000) {
            return (n / 1000000).toFixed(1) + "M";
        } else if (n >= 1000) {
            return (n / 1000).toFixed(1) + "K";
        }
        return n.toString();
    }

    // Atualiza estatísticas da live apenas se houver uma live ativa
    function updateLiveData() {
        const liveViewsEl = document.getElementById("liveViews");
        const liveLikesEl = document.getElementById("liveLikes");
        
        if (!liveViewsEl || !liveLikesEl) {
            return; // Não há live ativa
        }
        
        fetch('get_live_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.log("Live não ativa ou erro:", data.error);
                    return;
                }
                if (liveViewsEl) {
                    const views = data.views || "0";
                    liveViewsEl.textContent = formatNumber(views);
                    liveViewsEl.setAttribute('title', views + ' visualizações');
                }
                if (liveLikesEl) {
                    const likes = data.likes || "0";
                    liveLikesEl.textContent = formatNumber(likes);
                    liveLikesEl.setAttribute('title', likes + ' curtidas');
                }
            })
            .catch(error => {
                // Silenciosamente ignora erros (live pode não estar ativa)
                console.log("Live não disponível");
            });
    }

    // Só atualiza se houver elementos de live na página
    const liveCard = document.getElementById('liveCardModern');
    if (liveCard) {
    setInterval(updateLiveData, 5000);
    updateLiveData();
    }
</script>

   
   <!-- 🔴 Notificação de Live Clean e Minimalista -->
<div id="liveAlert" class="live-alert" style="display: none;">
    <i class="bi bi-camera-video-fill live-icon"></i>
    <span id="liveMessage">Nova transmissão ao vivo!</span>
    <button class="close-alert" onclick="closeLiveAlert()">&times;</button>
</div>



<style>
  /* 🔴 Notificação de Live Clean */
.live-alert {
    position: fixed;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95); /* Branco com leve transparência */
    color: #333;
    font-size: 14px;
    font-weight: 500;
    padding: 10px 18px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.08);
    animation: slideDown 0.5s ease-in-out;
    z-index: 1000;
    transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
    border-left: 4px solid #ff6f00; /* Destaque sutil na lateral */
}

/* 🎥 Ícone de Câmera do Bootstrap */
.live-icon {
    font-size: 18px;
    color: #ff6f00;
}

/* ❌ Botão de Fechar Minimalista */
.close-alert {
    background: none;
    border: none;
    color: #999;
    font-size: 16px;
    cursor: pointer;
    font-weight: bold;
    transition: color 0.2s ease;
}

.close-alert:hover {
    color: #333;
}

/* 📌 Efeito de Entrada */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

/* 📌 Efeito de Saída ao Fechar */
.live-alert.fade-out {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
}

        /* ===== SISTEMA DE NOTIFICAÇÕES ===== */
        .notifications-wrapper {
            position: relative;
        }

        .btn-notifications {
            position: relative;
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

        .btn-notifications:hover {
            background: rgba(255, 111, 0, 0.1);
            color: #ff6f00;
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .notifications-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .notifications-dropdown.active {
            display: flex;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notifications-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .notifications-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .btn-mark-all-read {
            background: none;
            border: none;
            color: #6366f1;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-mark-all-read:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        .notifications-list {
            overflow-y: auto;
            max-height: 400px;
        }

        .notification-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            gap: 12px;
            position: relative;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #fef3c7;
            border-left: 3px solid #ff6f00;
        }

        .notification-item.unread:hover {
            background: #fde68a;
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .notification-icon.video {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
        }

        .notification-icon.comentario {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .notification-icon.resposta {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .notification-icon.live {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            animation: livePulse 2s infinite;
        }

        @keyframes livePulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 4px 0;
            line-height: 1.4;
        }

        .notification-message {
            font-size: 12px;
            color: #64748b;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .notification-time {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .notifications-footer {
            padding: 12px 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .btn-view-all-notifications {
            color: #6366f1;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-view-all-notifications:hover {
            color: #4f46e5;
            text-decoration: underline;
        }

        .notifications-empty {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
        }

        .notifications-empty i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* ===== SEÇÃO CONTINUAR ASSISTINDO ===== */
        /* Mantido para compatibilidade, mas não usado mais */
        .continuar-assistindo-section {
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border-radius: 16px;
            border: 1px solid rgba(255, 111, 0, 0.2);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.1);
            display: none;
        }

        .continuar-assistindo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .continuar-assistindo-title-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .continuar-assistindo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }

        .continuar-assistindo-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .continuar-assistindo-subtitle {
            font-size: 14px;
            color: #64748b;
            margin: 4px 0 0 0;
        }

        .btn-ver-todo-historico {
            padding: 10px 20px;
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.2);
        }

        .btn-ver-todo-historico:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }

        .continuar-assistindo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .video-card-continuar {
            position: relative;
        }

        .video-progress-continuar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(0, 0, 0, 0.2);
            z-index: 2;
            border-radius: 0 0 12px 12px;
        }

        .video-progress-bar-continuar {
            height: 100%;
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            transition: width 0.3s ease;
            border-radius: 0 0 12px 12px;
        }

        .video-progress-complete-continuar {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .continuar-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(135deg, rgba(255, 111, 0, 0.95), rgba(255, 140, 26, 0.95));
            color: white;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            z-index: 3;
            backdrop-filter: blur(8px);
            box-shadow: 0 2px 8px rgba(255, 111, 0, 0.4);
        }

        [data-theme="dark"] .continuar-assistindo-section {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.8));
            border-color: rgba(255, 111, 0, 0.3);
        }

        [data-theme="dark"] .notifications-dropdown {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .notification-item {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .notification-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        [data-theme="dark"] .notification-item.unread {
            background: rgba(255, 111, 0, 0.15);
        }

        [data-theme="dark"] .notification-title {
            color: #f1f5f1;
        }

        [data-theme="dark"] .notification-message {
            color: #cbd5e1;
        }

</style>
<script>

let lastLiveStatus = false;

function checkLiveStatus() {
    fetch("verificar_live.php")
        .then(response => response.json())
        .then(data => {
            if (data.live_ativa && !lastLiveStatus) {
                showLiveAlert(`${data.titulo} está ao vivo agora!`);
                playLiveSound();
                lastLiveStatus = true;
            } else if (!data.live_ativa) {
                lastLiveStatus = false;
            }
        })
        .catch(error => console.error("Erro ao verificar live:", error));
}

function showLiveAlert(message) {
    let alertBox = document.getElementById("liveAlert");
    let alertMessage = document.getElementById("liveMessage");

    alertMessage.textContent = message;
    alertBox.style.display = "flex";

    // Remove automaticamente após 6 segundos
    setTimeout(() => {
        closeLiveAlert();
    }, 6000);
}

function closeLiveAlert() {
    let alertBox = document.getElementById("liveAlert");
    alertBox.classList.add("fade-out");
    setTimeout(() => {
        alertBox.style.display = "none";
        alertBox.classList.remove("fade-out");
    }, 300);
}

function playLiveSound() {
    let audio = new Audio("notificacao.mp3");
    audio.volume = 0.1; // Som muito suave
    audio.play().catch(error => console.log("Som bloqueado pelo navegador."));
}

setInterval(checkLiveStatus, 10000); // Checa a cada 10 segundos
</script>

 

<?php
// 🔹 Verifica se há uma live ativa antes de exibir o título e a linha divisória
if ($live_url): ?>
    <div class="gallery-divider">
        <span class="divider-line"></span>
        <h2 class="gallery-title">
            <i class="bi bi-collection-play"></i> Galeria de Vídeos
        </h2>
        <span class="divider-line"></span>
    </div>
<?php endif; ?>




<style>
 /* 🔹 Container da Galeria */
.gallery-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 40px 0;
    position: relative;
    width: 100%;
}

/* 🔹 Linha Divisória */
.divider-line {
    flex-grow: 1;
    height: 2px;
    background: linear-gradient(90deg, #ff6f00, #ff8c1a);
    border-radius: 4px;
}

/* 🔹 Estilo do Título */
.gallery-title {
    font-size: 1.8rem;
    font-weight: 700;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    color: #333;
    transition: all 0.3s ease-in-out;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* 🔹 Ícone do Título */
.gallery-title i {
    font-size: 2rem;
    color: #ff6f00;
    transition: transform 0.3s ease-in-out;
}

/* 🔹 Animação ao passar o mouse */
.gallery-title:hover i {
    transform: scale(1.1);
}

/* 🔹 Responsividade */
@media (max-width: 768px) {
    .gallery-title {
        font-size: 1.4rem;
    }

    .gallery-title i {
        font-size: 1.6rem;
    }
}
/* 🔹 Animação para um efeito de luz na linha */
.divider-line {
    flex-grow: 1;
    height: 2px;
    background: linear-gradient(90deg, #ff6f00, #ff8c1a);
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.divider-line::before {
    content: "";
    position: absolute;
    top: 0;
    left: -50%;
    width: 50%;
    height: 100%;
    background: rgba(255, 255, 255, 0.4);
    animation: slideLight 3s infinite linear;
}

/* 🔹 Efeito de deslizar */
@keyframes slideLight {
    from {
        left: -50%;
    }
    to {
        left: 100%;
    }
}

</style>


<?php
include 'db/conexao.php';

// 🔹 Buscar a próxima live agendada
$query = "SELECT * FROM transmissao_agendada WHERE data_transmissao > NOW() ORDER BY data_transmissao ASC LIMIT 1";
$result = mysqli_query($conexao, $query);
$live = mysqli_fetch_assoc($result);

if ($live):
?>
<div id="liveReminder" class="live-reminder shadow-lg">
    <div class="live-header">
        <strong><i class="bi bi-calendar2-event-fill me-1"></i> Próxima Live</strong>
        <button onclick="closeReminder()" class="btn-close"></button>
    </div>
    <h5 class="live-title"><?= htmlspecialchars($live['titulo']) ?></h5>
    <p class="live-desc"><?= htmlspecialchars($live['descricao']) ?></p>
    <p><strong><i class="bi bi-clock-fill me-1"></i> Faltam:</strong> <span id="countdown">Carregando...</span></p>

    <!-- Botão de Interesse -->
    <div class="interest-section">
        <button id="interestButton" class="btn-interest" onclick="toggleInterest(<?= $live['id'] ?>)">
            <i id="interestIcon" class="bi bi-heart"></i> Tenho Interesse
        </button>
        <p id="interesse-count" class="text-muted mt-2"><i class="bi bi-person-fill"></i> <span><?= htmlspecialchars($live['interesse']) ?></span> interessados</p>
    </div>
    
    <a href="<?= htmlspecialchars($live['url']) ?>" class="btn btn-live" target="_blank">
        <i class="bi bi-play-circle-fill me-1"></i> Assistir Live
    </a>
</div>

<script>
function startCountdown(targetDate) {
    function updateCountdown() {
        let now = new Date().getTime();
        let distance = targetDate - now;

        if (distance <= 0) {
            document.getElementById("countdown").innerHTML = "🚀 Live já começou!";
            return;
        }

        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById("countdown").innerHTML = 
            `${days}d ${hours}h ${minutes}m ${seconds}s`;
    }

    setInterval(updateCountdown, 1000);
}

function closeReminder() {
    document.getElementById("liveReminder").style.display = "none";
}

function toggleInterest(liveId) {
    let button = document.getElementById("interestButton");
    let icon = document.getElementById("interestIcon");
    let isInterested = button.classList.contains("interested");

    fetch('toggle_interesse.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `live_id=${liveId}&remove=${isInterested}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('interesse-count').innerHTML = 
                `<i class="bi bi-person-fill"></i> ${data.interesse} interessados`;

            if (data.status) {
                button.classList.add("interested");
                button.innerHTML = `<i class="bi bi-heart-fill"></i> Remover Interesse`;
            } else {
                button.classList.remove("interested");
                button.innerHTML = `<i class="bi bi-heart"></i> Tenho Interesse`;
            }
        } else {
            alert(data.message || 'Erro ao atualizar interesse.');
        }
    })
    .catch(error => console.error('Erro:', error));
}

let targetDate = new Date("<?= $live ? $live['data_transmissao'] : '' ?>").getTime();
if (targetDate) startCountdown(targetDate);
</script>

<style>
/* 🔹 Estilização do lembrete */
.live-reminder {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    padding: 12px;
    width: 270px;
    border-radius: 10px;
    box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.2);
    z-index: 999;
    border-left: 5px solid #ff6f00;
}

/* 🔹 Botão de Interesse */
.btn-interest {
    background: linear-gradient(90deg, #ff6f00, #ff8c1a);
    color: white;
    padding: 8px;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    width: 100%;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.btn-interest:hover {
    background: linear-gradient(90deg, #ff8c1a, #ffd54f);
}

/* 🔹 Botão de assistir */
.btn-live {
    display: block;
    text-align: center;
    background: #333;
    color: white;
    padding: 8px;
    border-radius: 6px;
    font-weight: bold;
    transition: all 0.3s ease-in-out;
}

.btn-live:hover {
    background: #222;
}

/* 🔹 Contagem de interessados */
.interest-section {
    text-align: center;
    font-size: 13px;
    margin-top: 8px;
}
</style>

<?php endif; ?>

    <!-- Galeria de Vídeos Principais - Foco Principal -->
    <div class="videos-main-section">
    <div class="videos-grid" id="videos-grid">
        <?php 
        // Garante que o resultado não foi consumido antes
        if ($videos_result && mysqli_num_rows($videos_result) > 0): 
            // Reset do ponteiro do resultado para garantir que começamos do início
            mysqli_data_seek($videos_result, 0);
        ?>
            <?php while ($video = mysqli_fetch_assoc($videos_result)): 
                // Debug: verifica se o ID está presente
                if (!isset($video['id']) || empty($video['id'])) {
                    continue; // Pula vídeos sem ID
                }
            ?>
                <?php 
                // Verifica se o usuário pode editar este vídeo
                $pode_editar = false;
                if ($is_logged_in && $usuario_id) {
                    if ($usuario_adm) {
                        $pode_editar = true;
                    } else {
                        if (!usuario_eh_cliente($conexao, $usuario_id)) {
                            $pode_editar = usuario_pode_editar_video($conexao, $usuario_id, $video['id']);
                        }
                    }
                }
                ?>
                <?php
                // Calcular badges
                $data_upload = strtotime($video['data_upload']);
                $dias_desde_upload = floor((time() - $data_upload) / (60 * 60 * 24));
                $visualizacoes = intval($video['visualizacoes'] ?? 0);
                $curtidas = intval($video['curtidas'] ?? 0);
                
                $badges = [];
                if ($dias_desde_upload <= 7) {
                    $badges[] = ['class' => 'video-badge-new', 'text' => 'NOVO'];
                }
                if ($dias_desde_upload <= 3) {
                    $badges[] = ['class' => 'video-badge-recente', 'text' => 'RECENTE'];
                }
                if ($visualizacoes > 100 || $curtidas > 20) {
                    $badges[] = ['class' => 'video-badge-popular', 'text' => 'POPULAR'];
                }
                
                // Formatar visualizações
                $visualizacoes_formatadas = $visualizacoes;
                if ($visualizacoes >= 1000000) {
                    $visualizacoes_formatadas = number_format($visualizacoes / 1000000, 1) . 'M';
                } elseif ($visualizacoes >= 1000) {
                    $visualizacoes_formatadas = number_format($visualizacoes / 1000, 1) . 'k';
                }
                ?>
                <?php
                // Verificar se faz parte de sequência
                $is_sequencia = isset($video['is_sequencia']) && ($video['is_sequencia'] == 1 || $video['is_sequencia'] === '1');
                $sequencia_ordem = isset($video['sequencia_ordem']) ? intval($video['sequencia_ordem']) : 0;
                $total_sequencia = isset($video['total_sequencia']) ? intval($video['total_sequencia']) : 0;
                $sequencia_nome = isset($video['sequencia_nome']) ? htmlspecialchars($video['sequencia_nome']) : '';
                ?>
                <article class="video-card<?= $is_sequencia ? ' video-card-sequencia' : '' ?>" id="video-card-<?= $video['id'] ?>" data-video-id="<?= $video['id'] ?>" data-sequencia="<?= $is_sequencia ? '1' : '0' ?>" data-upload-date="<?= $data_upload ?>">
                    <div class="video-card-thumbnail" data-video-id="<?= $video['id'] ?>">
                        <?php if (!empty($badges)): ?>
                            <?php foreach ($badges as $badge): ?>
                                <div class="video-badge <?= $badge['class'] ?>"><?= $badge['text'] ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php
                        // Thumbnail/preview do vídeo
                        $video_url = htmlspecialchars($video['url_video'] ?? '');
                        if (!empty($video_url)):
                        ?>
                            <video class="video-thumbnail-preview" preload="metadata" muted playsinline data-video-src="<?= $video_url ?>">
                                <source src="<?= $video_url ?>" type="video/mp4">
    </video>
                        <?php else: ?>
                            <div class="video-thumbnail-fallback">
                                <i class="fas fa-video"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        // Badge de sequência no thumbnail
                        if ($is_sequencia && $sequencia_ordem > 0):
                        ?>
                            <div class="video-sequencia-badge-card">
                                <span class="sequencia-badge-number"><?= $sequencia_ordem ?></span>
                                <?php if ($total_sequencia > 0): ?>
                                    <span class="sequencia-badge-total">/<?= $total_sequencia ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php /* Banner de sequência removido - apenas badge discreto no canto */ ?>
                        
                        <div class="video-duration" id="duration-<?= $video['id'] ?>">--:--</div>
                        <div class="video-progress" id="progress-<?= $video['id'] ?>" style="display: none;">
                            <div class="video-progress-bar" id="progress-bar-<?= $video['id'] ?>" style="width: 0%"></div>
                        </div>
                        <a href="video_detalhes.php?id=<?= $video['id'] ?>" class="video-play-overlay" style="text-decoration: none; color: inherit;">
                            <i class="fas fa-play"></i>
                        </a>
                    </div>
                    <div class="video-card-content">
                        <div class="video-card-tags">
                            <div class="video-card-setor">
                                <i class="fas fa-tag"></i>
                                <span><?= htmlspecialchars($video['setor_nome']) ?></span>
                            </div>
                            <?php if (!empty($video['modulo_nome'])): ?>
                                <div class="video-card-modulo" style="--modulo-color: <?= htmlspecialchars($video['modulo_cor'] ?? '#6366f1') ?>;">
                                    <i class="<?= htmlspecialchars($video['modulo_icone'] ?? 'fas fa-cube') ?>"></i>
                                    <span><?= htmlspecialchars($video['modulo_nome']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="video-card-title"><?= htmlspecialchars($video['titulo']) ?></h3>
                        <p class="video-card-description"><?= htmlspecialchars($video['descricao']) ?></p>
                        <div class="video-card-stats">
                            <span class="stat-item stat-likes"><i class="fas fa-heart"></i> <span class="curtidas-count" id="curtidas-<?= $video['id'] ?>"><?= $video['curtidas'] ?></span></span>
                            <span class="stat-item stat-comments"><i class="fas fa-comments"></i> <span class="comentarios-count" id="comentarios-<?= $video['id'] ?>"><?= $video['total_comentarios'] ?></span></span>
                            <span class="stat-item stat-views"><i class="fas fa-eye"></i> <span id="views-<?= $video['id'] ?>"><?= $visualizacoes_formatadas ?></span></span>
                            <span class="stat-item stat-date"><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($video['data_upload'])) ?></span>
                        </div>
                        <div class="video-card-actions">
                            <button type="button" class="video-card-btn btn-like" data-video-id="<?= $video['id'] ?>" data-tooltip="Curtir" title="Curtir">
                                <i class="fas fa-heart"></i>
        </button>
                            <button type="button" class="video-card-btn btn-share" data-video-id="<?= $video['id'] ?>" data-video-title="<?= htmlspecialchars($video['titulo']) ?>" data-tooltip="Compartilhar" title="Compartilhar">
                                <i class="fas fa-share-nodes"></i>
        </button>
                            <?php if ($pode_editar): ?>
                                <button type="button" class="video-card-btn btn-edit" data-video-id="<?= $video['id'] ?>" data-video-title="<?= htmlspecialchars($video['titulo']) ?>" data-video-desc="<?= htmlspecialchars($video['descricao']) ?>" data-setor-id="<?= $video['setor_id'] ?>" data-tooltip="Editar" title="Editar">
                                    <i class="fas fa-pencil"></i>
            </button>
                                <button type="button" class="video-card-btn btn-delete" data-video-id="<?= $video['id'] ?>" data-tooltip="Excluir" title="Excluir">
                                    <i class="fas fa-trash"></i>
            </button>
        <?php endif; ?>
                            <?php if ($usuario_adm): ?>
                                <button type="button" class="video-card-btn btn-recomendado" data-video-id="<?= $video['id'] ?>" data-recomendado="<?= isset($video['recomendado']) && $video['recomendado'] == 1 ? '1' : '0' ?>" data-tooltip="<?= isset($video['recomendado']) && $video['recomendado'] == 1 ? 'Remover dos Recomendados' : 'Adicionar aos Recomendados' ?>" title="<?= isset($video['recomendado']) && $video['recomendado'] == 1 ? 'Remover dos Recomendados' : 'Adicionar aos Recomendados' ?>">
                                    <i class="fas <?= isset($video['recomendado']) && $video['recomendado'] == 1 ? 'fa-star' : 'fa-star' ?>"></i>
            </button>
        <?php endif; ?>
    </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-video-slash"></i>
                <h3>Nenhum vídeo encontrado</h3>
                <p>Tente ajustar os filtros ou verifique outros setores na barra lateral.</p>
            </div>
        <?php endif; ?>
</div>

    <!-- Paginação Moderna -->
    <?php if ($total_paginas > 1): ?>
    <div class="pagination-modern">
        <div class="pagination-info">
            <span class="pagination-text">Página <strong><?= $pagina_atual ?></strong> de <strong><?= $total_paginas ?></strong></span>
            <span class="pagination-total">Total: <strong><?= $total_videos ?></strong> vídeo<?= $total_videos != 1 ? 's' : '' ?></span>
        </div>
        
        <nav class="pagination-nav">
        <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=1&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                   class="pagination-btn pagination-first" title="Primeira página">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?pagina=<?= $pagina_atual - 1 ?>&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                   class="pagination-btn pagination-prev" title="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                    <span class="pagination-btn-text">Anterior</span>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-first disabled" title="Primeira página">
                    <i class="fas fa-angle-double-left"></i>
                </span>
                <span class="pagination-btn pagination-prev disabled" title="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                    <span class="pagination-btn-text">Anterior</span>
                </span>
        <?php endif; ?>
        
            <div class="pagination-numbers">
                <?php
                $start = max(1, $pagina_atual - 2);
                $end = min($total_paginas, $pagina_atual + 2);
                
                if ($start > 1): ?>
                    <a href="?pagina=1&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                       class="pagination-number">1</a>
                    <?php if ($start > 2): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?pagina=<?= $i ?>&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                       class="pagination-number <?= $i == $pagina_atual ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
        <?php endfor; ?>
                
                <?php if ($end < $total_paginas): ?>
                    <?php if ($end < $total_paginas - 1): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="?pagina=<?= $total_paginas ?>&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                       class="pagination-number"><?= $total_paginas ?></a>
                <?php endif; ?>
            </div>
        
        <?php if ($pagina_atual < $total_paginas): ?>
                <a href="?pagina=<?= $pagina_atual + 1 ?>&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                   class="pagination-btn pagination-next" title="Próxima página">
                    <span class="pagination-btn-text">Próxima</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="?pagina=<?= $total_paginas ?>&filtroSetor=<?= $filtro_setor ?>&filtroModulo=<?= $filtro_modulo ?>&pesquisaTitulo=<?= urlencode($busca_titulo) ?>" 
                   class="pagination-btn pagination-last" title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-next disabled" title="Próxima página">
                    <span class="pagination-btn-text">Próxima</span>
                    <i class="fas fa-chevron-right"></i>
                </span>
                <span class="pagination-btn pagination-last disabled" title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </span>
        <?php endif; ?>
        </nav>
                </div>
        <?php endif; ?>
        </div>
    </div>

<!-- Modal de Editar Perfil -->
<div id="profileModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white;">
            <h2 class="modal-title"><i class="fas fa-user-edit"></i> Editar Meu Perfil</h2>
            <button class="btn-close" onclick="closeProfileModal()" style="color: white;">&times;</button>
        </div>
        <form id="profileForm">
            <div class="modal-body" style="padding: 25px;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="profileNome" class="form-label">
                            <i class="fas fa-user"></i> Nome <span>*</span>
                        </label>
                        <input type="text" class="form-control" id="profileNome" name="nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profileEmail" class="form-label">
                            <i class="fas fa-envelope"></i> Email <span>*</span>
                        </label>
                        <input type="email" class="form-control" id="profileEmail" name="email" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="profileTelefone" class="form-label">
                            <i class="fas fa-phone"></i> Telefone
                        </label>
                        <input type="text" class="form-control" id="profileTelefone" name="telefone" placeholder="(00) 00000-0000">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profileEstado" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Estado
                        </label>
                        <select class="form-control" id="profileEstado" name="estado_id">
                            <option value="">Selecione um estado</option>
                            <?php
                            $query_uf_profile = "SELECT id, nome, sigla FROM UF ORDER BY nome ASC";
                            $result_uf_profile = $conexao->query($query_uf_profile);
                            while ($uf = $result_uf_profile->fetch_assoc()): ?>
                                <option value="<?= $uf['id'] ?>"><?= htmlspecialchars($uf['sigla']) ?> - <?= htmlspecialchars($uf['nome']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="profileMunicipio" class="form-label">
                            <i class="fas fa-city"></i> Município
                        </label>
                        <select class="form-control" id="profileMunicipio" name="municipio_id">
                            <option value="">Selecione um município</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="profileSenhaAtual" class="form-label">
                        <i class="fas fa-lock"></i> Senha Atual (para alterar senha)
                    </label>
                    <input type="password" class="form-control" id="profileSenhaAtual" name="senha_atual" placeholder="Deixe em branco se não quiser alterar">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="profileNovaSenha" class="form-label">
                            <i class="fas fa-key"></i> Nova Senha
                        </label>
                        <input type="password" class="form-control" id="profileNovaSenha" name="nova_senha" placeholder="Deixe em branco se não quiser alterar">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profileConfirmarSenha" class="form-label">
                            <i class="fas fa-key"></i> Confirmar Nova Senha
                        </label>
                        <input type="password" class="form-control" id="profileConfirmarSenha" name="confirmar_senha" placeholder="Confirme a nova senha">
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="padding: 20px 25px; border-top: 1px solid #e0e0e0;">
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6); border: none;">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Editar Vídeo</h5>
            <button class="btn-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm">
            <input type="hidden" id="editVideoId" name="video_id">
            <div class="form-group mb-3">
                <label for="editTitulo" class="form-label">Título</label>
                <input type="text" id="editTitulo" name="titulo" class="form-control" placeholder="Digite o título" required>
            </div>
            <div class="form-group mb-3">
                <label for="editDescricao" class="form-label">Descrição</label>
                <textarea id="editDescricao" name="descricao" class="form-control" rows="3" placeholder="Digite a descrição" required></textarea>
            </div>
            <div class="form-group mb-3">
                <label for="editSetor" class="form-label">Setor</label>
                <select id="editSetor" name="setor_id" class="form-select" required>
                    <?php
                    // Busca setores permitidos para o modal de edição
                    if ($usuario_adm) {
                        $edit_setores_query = "SELECT id, nome FROM setores WHERE ativo = 'S' ORDER BY nome ASC";
                    } else {
                        if (!empty($setores_permitidos)) {
                            $setores_ids = implode(',', array_map('intval', $setores_permitidos));
                            $edit_setores_query = "SELECT id, nome FROM setores WHERE ativo = 'S' AND id IN ($setores_ids) ORDER BY nome ASC";
                        } else {
                            $edit_setores_query = "SELECT id, nome FROM setores WHERE 1=0";
                        }
                    }
                    $edit_setores_result = mysqli_query($conexao, $edit_setores_query);
                    if ($edit_setores_result && mysqli_num_rows($edit_setores_result) > 0) {
                        while ($setor = mysqli_fetch_assoc($edit_setores_result)) {
                            echo '<option value="' . htmlspecialchars($setor['id']) . '">' . htmlspecialchars($setor['nome']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>



    </div>
   <!-- Footer -->
    <footer class="footer-premium" id="footer">
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-brand">
                    <i class="fas fa-video"></i>
                    <span>Biblioteca de Treinamento</span>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-link" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-links">
                    <a href="index.php"><i class="fas fa-home"></i> Início</a>
                    <a href="#"><i class="fas fa-question-circle"></i> Ajuda</a>
                    <a href="#" class="open-modal" data-modal="privacy-modal"><i class="fas fa-shield-alt"></i> Privacidade</a>
                    <a href="#" class="open-modal" data-modal="terms-modal"><i class="fas fa-file-contract"></i> Termos</a>
                </div>
                <div class="footer-copyright">
                    <p>&copy; <?= date('Y') ?> <span class="footer-highlight">Gabriel Silva</span>. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </footer>
</div>
</div>

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

/* Footer Fixo */
.footer {
    position: fixed;
    bottom: 0;
    left: 280px; /* Ajusta para a sidebar */
    right: 0;
    width: auto;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    color: #333;
    text-align: center;
    padding: 15px 20px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    font-size: 12px;
    z-index: 100; /* Fica acima do conteúdo mas abaixo de modais */
    pointer-events: auto;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    transition: left 0.3s ease;
}

/* Ajuste do footer quando sidebar está fechada (mobile) */
@media (max-width: 992px) {
    .footer {
        left: 0;
    }
}

.footer a {
    color: #007bff;
    text-decoration: none;
    margin: 0 8px;
    transition: color 0.3s;
}
.footer a:hover { color: #0056b3; }

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



    <script>
       function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.error('Notification container not found!');
        return;
    }

    // Cria o elemento da notificação
    const notification = document.createElement('div');
    notification.className = `notification ${type}`; // Adiciona a classe de tipo
    notification.innerHTML = `
        <span>${message}</span>
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;

    // Adiciona a notificação ao contêiner
    container.appendChild(notification);

    // Remove a notificação automaticamente após 4 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 4000);
}




        function curtirVideo(event, videoId) {
    event.preventDefault(); // Impede o comportamento padrão do botão/link.

    fetch('toggle_curtida.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: videoId })
    })
    .then(response => response.json())
    .then(data => {
        const curtidasSpan = document.querySelector(`#curtidas-${videoId}`);
        if (data.success) {
            if (data.action === 'added') {
                curtidasSpan.textContent = parseInt(curtidasSpan.textContent) + 1;
                showNotification(data.message, 'success');
            } else if (data.action === 'removed') {
                curtidasSpan.textContent = parseInt(curtidasSpan.textContent) - 1;
                showNotification(data.message, 'info');
            }
        } else {
            showNotification(data.error || 'Erro ao processar a curtida.', 'error');
        }
    })
    .catch(() => {
        showNotification('Erro ao processar a curtida.', 'error');
    });
}

function showLoginMessage(event) {
    event.preventDefault();
    showNotification('Você precisa estar logado para curtir.', 'error');
}


function openUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'flex';
    
    // Se houver um setor selecionado, carrega os módulos automaticamente
    setTimeout(() => {
        const setorSelect = document.getElementById('setor');
        if (setorSelect && setorSelect.value && parseInt(setorSelect.value) > 0) {
            setorSelect.dispatchEvent(new Event('change'));
        }
        
        // Se o checkbox de sequência estiver marcado, carrega sequências
        const isSequenciaCheck = document.getElementById('isSequencia');
        if (isSequenciaCheck && isSequenciaCheck.checked) {
            loadSequencias();
        }
    }, 100);
}

// Funções para gerenciar campos de sequência
function toggleSequenciaFields() {
    const isSequencia = document.getElementById('isSequencia').checked;
    const sequenciaFields = document.getElementById('sequenciaFields');
    sequenciaFields.style.display = isSequencia ? 'block' : 'none';
    
    if (isSequencia) {
        loadSequencias(); // Carregar sequências do setor/modulo
        // Mostrar campo de nova sequência por padrão
        setTimeout(() => {
            const sequenciaSelect = document.getElementById('sequenciaSelect');
            if (sequenciaSelect && sequenciaSelect.value === '') {
                toggleNovaSequenciaFields();
            }
        }, 100);
    } else {
        // Limpar campos quando desmarcar
        const sequenciaSelect = document.getElementById('sequenciaSelect');
        const sequenciaTitulo = document.getElementById('sequenciaTitulo');
        const sequenciaOrdem = document.getElementById('sequenciaOrdem');
        const novaSequenciaFields = document.getElementById('novaSequenciaFields');
        
        if (sequenciaSelect) sequenciaSelect.value = '';
        if (sequenciaTitulo) sequenciaTitulo.value = '';
        if (sequenciaOrdem) sequenciaOrdem.value = '';
        if (novaSequenciaFields) novaSequenciaFields.style.display = 'none';
    }
}

function toggleNovaSequenciaFields() {
    const sequenciaSelect = document.getElementById('sequenciaSelect');
    const novaSequenciaFields = document.getElementById('novaSequenciaFields');
    const sequenciaTitulo = document.getElementById('sequenciaTitulo');
    
    if (!sequenciaSelect || !novaSequenciaFields || !sequenciaTitulo) {
        return;
    }
    
    if (sequenciaSelect.value === '' || sequenciaSelect.value === null || sequenciaSelect.value === '0') {
        // Criar nova sequência
        novaSequenciaFields.style.display = 'block';
        sequenciaTitulo.required = true;
        sequenciaTitulo.disabled = false;
    } else {
        // Usar sequência existente
        novaSequenciaFields.style.display = 'none';
        sequenciaTitulo.required = false;
        sequenciaTitulo.value = '';
        sequenciaTitulo.disabled = true;
    }
}

function loadSequencias() {
    const setorSelect = document.getElementById('setor');
    const moduloSelect = document.getElementById('modulo');
    
    if (!setorSelect) {
        console.warn('Select de setor não encontrado');
        return;
    }
    
    const setorId = setorSelect.value;
    const moduloId = moduloSelect ? moduloSelect.value : '';
    
    if (!setorId || parseInt(setorId) <= 0) {
        // Se não tem setor, limpa o select e mostra campo de nova sequência
        const select = document.getElementById('sequenciaSelect');
        if (select) {
            select.innerHTML = '<option value="">-- Criar nova sequência --</option>';
        }
        const novaSequenciaFields = document.getElementById('novaSequenciaFields');
        if (novaSequenciaFields) {
            novaSequenciaFields.style.display = 'block';
        }
        return;
    }
    
    // Monta URL com parâmetros corretos
    let url = `get_sequencias.php?setor_id=${encodeURIComponent(setorId)}`;
    if (moduloId && moduloId !== '' && parseInt(moduloId) > 0) {
        url += `&modulo_id=${encodeURIComponent(moduloId)}`;
    }
    
    // Mostra loading
    const select = document.getElementById('sequenciaSelect');
    if (select) {
        select.innerHTML = '<option value="">Carregando sequências...</option>';
        select.disabled = true;
    }
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        cache: 'no-cache'
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 100));
            });
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            const select = document.getElementById('sequenciaSelect');
            if (!select) return;
            
            select.disabled = false;
            select.innerHTML = '<option value="">-- Criar nova sequência --</option>';
            
            if (data.success && data.sequencias && Array.isArray(data.sequencias) && data.sequencias.length > 0) {
                data.sequencias.forEach(seq => {
                    const option = document.createElement('option');
                    option.value = seq.id;
                    const videoText = seq.total_videos > 0 ? ` (${seq.total_videos} vídeo${seq.total_videos !== 1 ? 's' : ''})` : ' (nova)';
                    option.textContent = seq.titulo + videoText;
                    select.appendChild(option);
                });
            }
            
            // Verifica se deve mostrar campo de nova sequência
            toggleNovaSequenciaFields();
        } catch (e) {
            console.error('Erro ao parsear JSON:', e, 'Resposta:', text);
            const select = document.getElementById('sequenciaSelect');
            if (select) {
                select.disabled = false;
                select.innerHTML = '<option value="">-- Criar nova sequência --</option>';
            }
            toggleNovaSequenciaFields();
        }
    })
    .catch(error => {
        console.error('Erro ao carregar sequências:', error);
        const select = document.getElementById('sequenciaSelect');
        if (select) {
            select.disabled = false;
            select.innerHTML = '<option value="">-- Criar nova sequência --</option>';
        }
        toggleNovaSequenciaFields();
    });
}

function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'none';
    
    // Limpa o formulário
    const form = document.getElementById('uploadForm');
    if (form) {
        form.reset();
    }
    
    // Reseta campos de sequência
    const sequenciaFields = document.getElementById('sequenciaFields');
    const isSequenciaCheck = document.getElementById('isSequencia');
    if (sequenciaFields) sequenciaFields.style.display = 'none';
    if (isSequenciaCheck) isSequenciaCheck.checked = false;
}

        function uploadVideo() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);

    const notification = document.getElementById('uploadNotification');
    const progressBar = document.getElementById('uploadProgressBar');
    const progressContainer = document.querySelector('.progress');

    // Reseta as notificações e a barra de progresso
    notification.style.display = 'none';
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';

    fetch('upload_ajax.php', {
        method: 'POST',
        body: formData,
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualiza a barra de progresso para 100%
            progressBar.style.width = '100%';

            // Mostra notificação de sucesso
            notification.textContent = data.message;
            notification.className = 'notification success';
            notification.style.display = 'block';

            // Redireciona ou atualiza a página após um pequeno delay
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Mostra notificação de erro
            notification.textContent = data.error || 'Erro ao realizar o upload.';
            notification.className = 'notification error';
            notification.style.display = 'block';

            // Esconde a barra de progresso
            progressContainer.style.display = 'none';
        }
    }).catch(error => {
        console.error('Erro no upload:', error);

        // Mostra notificação de erro
        notification.textContent = 'Erro inesperado ao realizar o upload.';
        notification.className = 'notification error';
        notification.style.display = 'block';

        // Esconde a barra de progresso
        progressContainer.style.display = 'none';
    });
}


        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }
        document.addEventListener('DOMContentLoaded', () => {
    const dropdownToggle = document.getElementById('cadastrosDropdown');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    dropdownToggle.addEventListener('click', (event) => {
        event.stopPropagation(); // Impede que o clique feche o menu.
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', () => {
        if (dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
        }
    });
});

function abrirDetalhesVideo(videoId) {
    // Garante que o ID do vídeo seja passado corretamente
    if (!videoId || videoId <= 0) {
        console.error('ID de vídeo inválido:', videoId);
        showNotification('Erro ao abrir o vídeo. ID inválido.', 'error');
        return;
    }
    
    // Redireciona para a página de detalhes com o ID específico
    window.location.href = 'video_detalhes.php?id=' + parseInt(videoId);
}

// Inicializar event listeners para os cards de vídeo
document.addEventListener('DOMContentLoaded', function() {
    // Carregar durações e progresso dos vídeos
    loadVideoDurations();
    loadVideoProgress();
    
    // Event listener para clicar no card (exceto nos botões)
    document.querySelectorAll('.video-card').forEach(function(card) {
        const videoId = card.getAttribute('data-video-id');
        
        if (!videoId) {
            console.error('Card sem ID de vídeo:', card);
            return;
        }
        
        // Clique no card inteiro (exceto botões)
        card.addEventListener('click', function(e) {
            // Se clicou em um botão ou link, não faz nada
            if (e.target.closest('.video-card-actions') || 
                e.target.closest('button') || 
                e.target.closest('a')) {
                return;
            }
            
            // Abre os detalhes do vídeo específico
            abrirDetalhesVideo(parseInt(videoId));
        });
        
        // Event listeners para os botões
        const btnLike = card.querySelector('.btn-like');
        if (btnLike) {
            btnLike.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                const vidId = this.getAttribute('data-video-id');
                const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
                if (isLoggedIn) {
                    curtirVideo(e, parseInt(vidId));
                } else {
                    showLoginMessage(e);
                }
            });
        }
        
        const btnShare = card.querySelector('.btn-share');
        if (btnShare) {
            btnShare.addEventListener('click', function(e) {
                e.stopPropagation();
                const vidId = this.getAttribute('data-video-id');
                const vidTitle = this.getAttribute('data-video-title');
                compartilharVideo(vidTitle, parseInt(vidId));
            });
        }
        
        const btnEdit = card.querySelector('.btn-edit');
        if (btnEdit) {
            btnEdit.addEventListener('click', function(e) {
                e.stopPropagation();
                const vidId = this.getAttribute('data-video-id');
                const vidTitle = this.getAttribute('data-video-title');
                const vidDesc = this.getAttribute('data-video-desc');
                const setorId = this.getAttribute('data-setor-id');
                abrirEditarModal(parseInt(vidId), vidTitle, vidDesc, parseInt(setorId));
            });
        }
        
        const btnDelete = card.querySelector('.btn-delete');
        if (btnDelete) {
            btnDelete.addEventListener('click', function(e) {
                e.stopPropagation();
                const vidId = this.getAttribute('data-video-id');
                excluirVideo(parseInt(vidId));
            });
        }
    });
});

function excluirVideo(videoId) {
    if (!confirm('Tem certeza que deseja excluir este vídeo?')) return;

    fetch('delete_video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `video_id=${videoId}`,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Vídeo excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000); // Atualiza a página após 1s
        } else {
            showNotification(data.error || 'Erro ao excluir o vídeo.', 'error');
        }
    })
    .catch(() => {
        showNotification('Erro ao excluir o vídeo.', 'error');
    });
}
function abrirEditarModal(id, titulo, descricao, setorId) {
    document.getElementById('editVideoId').value = id;
    document.getElementById('editTitulo').value = titulo;
    document.getElementById('editDescricao').value = descricao;
    document.getElementById('editSetor').value = setorId;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('edit_video.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Vídeo atualizado com sucesso!', 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000); // Atualiza a página após 1s
        } else {
            showNotification(data.error || 'Erro ao atualizar o vídeo.', 'error');
        }
    })
    .catch(() => {
        showNotification('Erro ao atualizar o vídeo.', 'error');
    });
});
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.innerHTML = `
        <span>${message}</span>
        <button class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(notification);
    setTimeout(() => notification.remove(), 4000);
}
document.addEventListener('click', function (event) {
    const dropdown = document.querySelector('.dropdown-menu');
    if (!event.target.closest('.dropdown')) {
        dropdown.classList.remove('show');
    }
});
function compartilharVideo(titulo, videoId) {
    const url = `${window.location.origin}/video_detalhes.php?id=${videoId}`;
    if (navigator.share) {
        navigator.share({
            title: titulo,
            text: `Confira o video da Martinez & Carvalho: ${titulo}`,
            url: url
        }).catch(err => console.error('Erro ao compartilhar:', err));
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Link copiado para a área de transferência!', 'success');
        }).catch(() => {
            showNotification('Erro ao copiar o link para a área de transferência.', 'error');
        });
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.header');
    if (header) {
        const headerHeight = header.offsetHeight;
        document.body.style.paddingTop = `${headerHeight}px`;
    }
});
function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}

function uploadVideo() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);

    const notification = document.getElementById('uploadNotification');
    const progressBar = document.getElementById('uploadProgressBar');
    const progressContainer = document.querySelector('.progress');

    // Reseta barra de progresso e notificação
    notification.style.display = 'none';
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';

    fetch('upload_ajax.php', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json; charset=utf-8'
        }
    })
    .then(response => {
        // Garante que a resposta é interpretada como UTF-8
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Resposta inválida do servidor');
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Atualiza barra de progresso para 100%
            progressBar.style.width = '100%';

            // Mostra mensagem de sucesso
            notification.textContent = data.message;
            notification.className = 'notification success';
            notification.style.display = 'block';

            // Redireciona ou atualiza a página
            setTimeout(() => window.location.reload(), 2000);
        } else {
            // Mostra mensagem de erro
            notification.textContent = data.error || 'Erro ao realizar o upload.';
            notification.className = 'notification error';
            notification.style.display = 'block';

            // Esconde a barra de progresso
            progressContainer.style.display = 'none';
        }
    }).catch(() => {
        notification.textContent = 'Erro inesperado ao realizar o upload.';
        notification.className = 'notification error';
        notification.style.display = 'block';
        progressContainer.style.display = 'none';
    });

    // Simula progresso durante o upload (se necessário)
    let progress = 0;
    const interval = setInterval(() => {
        if (progress >= 90) {
            clearInterval(interval);
        } else {
            progress += 10;
            progressBar.style.width = `${progress}%`;
        }
    }, 200);
}
let prevScrollPos = window.pageYOffset;

window.onscroll = () => {
    const header = document.querySelector('.header');
    if (!header) return; // Se não existe, não faz nada
    
    const currentScrollPos = window.pageYOffset;

    if (prevScrollPos > currentScrollPos) {
        // Mostra o header ao rolar para cima
        header.style.transform = 'translateY(0)';
        header.style.transition = 'transform 0.3s ease';
    } else {
        // Esconde o header ao rolar para baixo
        header.style.transform = 'translateY(-100%)';
        header.style.transition = 'transform 0.3s ease';
    }

    prevScrollPos = currentScrollPos;
};


    function openLiveModal() {
        document.getElementById('liveModal').style.display = 'flex';
    }

    // Modal de Live
    function openLiveModal() {
        const modal = document.getElementById('liveModal');
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
        }
    }

    function closeLiveModal() {
        const modal = document.getElementById('liveModal');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
    }

    function openStopLiveModal() {
        const modal = document.getElementById('stopLiveModal');
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
        }
    }

    function closeStopLiveModal() {
        const modal = document.getElementById('stopLiveModal');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
    }

    function startLive() {
        const liveURL = document.getElementById('liveURL').value.trim();
        const liveTitulo = document.getElementById('liveTitulo').value.trim() || 'Transmissão Especial';
        const liveDescricao = document.getElementById('liveDescricao').value.trim() || 'Acompanhe ao vivo nossa transmissão especial com conteúdos exclusivos.';
        const liveSubtexto = document.getElementById('liveSubtexto').value.trim() || '';

        if (!liveURL) {
            alert("Por favor, cole o link da transmissão ao vivo!");
            return;
        }

        // Salvar a URL da live usando AJAX
        const formData = new FormData();
        formData.append('live_url', liveURL);
        formData.append('titulo', liveTitulo);
        formData.append('descricao', liveDescricao);
        if (liveSubtexto) {
            formData.append('subtexto', liveSubtexto);
        }

        fetch('set_live.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeLiveModal();
                // Mostra notificação de sucesso
                showNotification('Transmissão iniciada com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(data.error || "Erro ao iniciar a transmissão!");
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert("Erro ao iniciar a transmissão!");
        });
    }

    function confirmStopLive() {
        fetch('set_live.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'stop_live=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeStopLiveModal();
                // Mostra notificação de sucesso
                showNotification('Transmissão encerrada com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(data.error || "Erro ao encerrar a transmissão!");
            }
        })
        .catch(error => {
            console.error('Erro:', error);
                alert("Erro ao encerrar a transmissão!");
        });
    }

    // Fecha modais ao clicar fora
    document.addEventListener('click', function(e) {
        const liveModal = document.getElementById('liveModal');
        const stopLiveModal = document.getElementById('stopLiveModal');
        
        if (liveModal && e.target === liveModal) {
            closeLiveModal();
        }
        if (stopLiveModal && e.target === stopLiveModal) {
            closeStopLiveModal();
        }
    });

    // Fecha modais com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLiveModal();
            closeStopLiveModal();
        }
    });

    // Função para minimizar/expandir o card de live
    function toggleLiveCard() {
        const liveCard = document.getElementById('liveCardModern');
        const floatButton = document.getElementById('liveFloatButton');
        
        if (liveCard && floatButton) {
            if (liveCard.classList.contains('minimized')) {
                // Expandir
                liveCard.classList.remove('minimized');
                liveCard.style.display = 'block';
                floatButton.style.display = 'none';
                localStorage.setItem('liveCardMinimized', 'false');
            } else {
                // Minimizar
                liveCard.classList.add('minimized');
                liveCard.style.display = 'none';
                floatButton.style.display = 'flex';
                localStorage.setItem('liveCardMinimized', 'true');
            }
        }
    }

    // Função para rolar carrossel
    function scrollCarousel(type, direction) {
        const container = document.getElementById(type === 'continuar' ? 'continuar-carousel' : 'recomendacoes-carousel');
        if (!container) return;
        
        const itemWidth = 200 + 12; // largura do item + gap
        const scrollAmount = itemWidth * 3; // rola 3 itens por vez
        
        const currentScroll = container.scrollLeft || 0;
        const newScroll = currentScroll + (scrollAmount * direction);
        
        container.scrollTo({
            left: newScroll,
            behavior: 'smooth'
        });
        
        // Atualiza botões após scroll
        setTimeout(() => {
            if (typeof updateCarouselButtons === 'function') updateCarouselButtons(type);
        }, 400);
    }

    // Função para atualizar estado dos botões do carrossel
    function updateCarouselButtons(type) {
        const container = document.getElementById(type === 'continuar' ? 'continuar-carousel' : 'recomendacoes-carousel');
        if (!container) return;
        
        const carousel = container.closest('.carousel-wrapper');
        if (!carousel) return;
        
        const prevBtn = carousel.querySelector('.carousel-btn-prev');
        const nextBtn = carousel.querySelector('.carousel-btn-next');
        
        const maxScroll = container.scrollWidth - container.clientWidth;
        const currentScroll = container.scrollLeft || 0;
        
        if (prevBtn) {
            prevBtn.disabled = currentScroll <= 0;
        }
        if (nextBtn) {
            nextBtn.disabled = currentScroll >= maxScroll - 10;
        }
    }

    // Função para reinicializar listeners dos cards de vídeo (ESCOPO GLOBAL)
    function initVideoCardListeners() {
        try {
            // Remove listeners antigos para evitar duplicação
            const oldButtons = document.querySelectorAll('.btn-like, .btn-share, .btn-edit, .btn-delete, .btn-recomendado');
            oldButtons.forEach(btn => {
                if (btn && btn.parentNode) {
                    const newBtn = btn.cloneNode(true);
                    btn.parentNode.replaceChild(newBtn, btn);
                }
            });
        } catch (error) {
            console.error('Erro ao remover listeners antigos:', error);
        }
        
        try {
            // Listener para botão de curtir
            document.querySelectorAll('.btn-like').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const videoId = this.getAttribute('data-video-id');
                    if (videoId) {
                        // Tenta usar toggleCurtida primeiro, se não existir usa curtirVideo
                        if (typeof toggleCurtida === 'function') {
                            toggleCurtida(videoId);
                        } else if (typeof curtirVideo === 'function') {
                            curtirVideo(e, parseInt(videoId));
                        } else {
                            console.error('Função de curtir não encontrada');
                        }
                    }
                });
            });
        } catch (error) {
            console.error('Erro ao adicionar listener de curtir:', error);
        }
        
        try {
            // Listener para botão de compartilhar
            document.querySelectorAll('.btn-share').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const videoId = this.getAttribute('data-video-id');
                    const videoTitle = this.getAttribute('data-video-title') || 'Vídeo';
                    if (videoId) {
                        // Tenta usar shareVideo primeiro, se não existir usa compartilharVideo
                        if (typeof shareVideo === 'function') {
                            shareVideo(videoId, videoTitle);
                        } else if (typeof compartilharVideo === 'function') {
                            compartilharVideo(videoTitle, parseInt(videoId));
                        } else {
                            console.error('Função de compartilhar não encontrada');
                        }
                    }
                });
            });
        } catch (error) {
            console.error('Erro ao adicionar listener de compartilhar:', error);
        }
        
        try {
            // Listener para botão de editar
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const videoId = this.getAttribute('data-video-id');
                    if (videoId) {
                        // Tenta usar abrirEditarModal se existir
                        const videoTitle = this.getAttribute('data-video-title');
                        const videoDesc = this.getAttribute('data-video-desc');
                        const setorId = this.getAttribute('data-setor-id');
                        if (typeof abrirEditarModal === 'function' && videoTitle && videoDesc && setorId) {
                            abrirEditarModal(parseInt(videoId), videoTitle, videoDesc, parseInt(setorId));
                        } else {
                            window.location.href = 'edit_video.php?id=' + videoId;
                        }
                    }
                });
            });
        } catch (error) {
            console.error('Erro ao adicionar listener de editar:', error);
        }
        
        try {
            // Listener para botão de deletar (se existir)
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const videoId = this.getAttribute('data-video-id');
                    if (videoId) {
                        // Tenta usar deleteVideo primeiro, se não existir usa excluirVideo
                        if (typeof deleteVideo === 'function') {
                            deleteVideo(videoId);
                        } else if (typeof excluirVideo === 'function') {
                            excluirVideo(parseInt(videoId));
                        } else {
                            console.error('Função de deletar não encontrada');
                        }
                    }
                });
            });
        } catch (error) {
            console.error('Erro ao adicionar listener de deletar:', error);
        }

        try {
            // Botão de recomendar vídeo
            document.querySelectorAll('.btn-recomendado').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const videoId = this.getAttribute('data-video-id');
                    if (videoId && typeof toggleRecomendado === 'function') {
                        toggleRecomendado(videoId, this);
                    }
                });
            });
        } catch (error) {
            console.error('Erro ao adicionar listener de recomendar:', error);
        }
    }

    // Função para marcar/desmarcar vídeo como recomendado (ESCOPO GLOBAL)
    function toggleRecomendado(videoId, buttonElement) {
        console.log('toggleRecomendado chamado para vídeo:', videoId);
        
        const formData = new FormData();
        formData.append('video_id', videoId);

        fetch('toggle_recomendado.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Resposta do servidor:', response.status);
            if (!response.ok) {
                throw new Error('Erro na resposta: ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            console.log('Resposta bruta:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erro ao fazer parse do JSON:', e);
                console.error('Texto recebido:', text);
                throw new Error('Resposta inválida do servidor');
            }
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            if (data.success) {
                // Atualiza o estado do botão
                const isRecomendado = data.recomendado;
                buttonElement.setAttribute('data-recomendado', isRecomendado ? '1' : '0');
                buttonElement.setAttribute('data-tooltip', isRecomendado ? 'Remover dos Recomendados' : 'Adicionar aos Recomendados');
                buttonElement.setAttribute('title', isRecomendado ? 'Remover dos Recomendados' : 'Adicionar aos Recomendados');
                
                // Atualiza o ícone
                const icon = buttonElement.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-star';
                    if (isRecomendado) {
                        buttonElement.style.color = '#ffd700';
                    } else {
                        buttonElement.style.color = '#94a3b8';
                    }
                }

                // Mostra notificação
                if (typeof showNotification === 'function') {
                    showNotification(data.message, 'success');
                } else {
                    alert(data.message);
                }

                // Recarrega as recomendações se estiver na página inicial
                if (typeof loadRecomendacoes === 'function') {
                    console.log('Recarregando recomendações...');
                    setTimeout(() => {
                        loadRecomendacoes();
                    }, 500);
                }
            } else {
                console.error('Erro do servidor:', data.error);
                if (typeof showNotification === 'function') {
                    showNotification(data.error || 'Erro ao atualizar recomendação', 'error');
                } else {
                    alert(data.error || 'Erro ao atualizar recomendação');
                }
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            if (typeof showNotification === 'function') {
                showNotification('Erro ao atualizar recomendação: ' + error.message, 'error');
            } else {
                alert('Erro ao atualizar recomendação: ' + error.message);
            }
        });
    }

    // Restaura estado do card ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        const liveCard = document.getElementById('liveCardModern');
        const floatButton = document.getElementById('liveFloatButton');
        const isMinimized = localStorage.getItem('liveCardMinimized') === 'true';
        
        if (liveCard && floatButton) {
            if (isMinimized) {
                liveCard.classList.add('minimized');
                liveCard.style.display = 'none';
                floatButton.style.display = 'flex';
            } else {
                liveCard.classList.remove('minimized');
                liveCard.style.display = 'block';
                floatButton.style.display = 'none';
            }
        }
        
        // Inicializa listeners dos cards de vídeo
        try {
            if (typeof initVideoCardListeners === 'function') {
                initVideoCardListeners();
            }
        } catch (error) {
            console.error('Erro ao inicializar listeners dos cards:', error);
        }
        
        // Configura preview dos vídeos
        try {
            if (typeof setupVideoPreviews === 'function') {
                setupVideoPreviews();
            }
        } catch (error) {
            console.error('Erro ao configurar previews:', error);
        }
        
        // Inicializa sistema de notificações
        try {
            if (typeof initNotificacoes === 'function') {
                initNotificacoes();
            }
        } catch (error) {
            console.error('Erro ao inicializar notificações:', error);
        }
        
        // Carrega recomendações e continuar assistindo
        setTimeout(() => {
            if (typeof loadRecomendacoes === 'function') {
                console.log('Carregando recomendações...');
                loadRecomendacoes();
            } else {
                console.error('loadRecomendacoes não está definida!');
            }
            
            if (typeof loadContinuarAssistindo === 'function') {
                console.log('Carregando continuar assistindo...');
                loadContinuarAssistindo();
            } else {
                console.error('loadContinuarAssistindo não está definida!');
            }
        }, 500);
        
        // Atualiza divisor inicialmente
        setTimeout(() => {
            if (typeof updateSectionDivider === 'function') updateSectionDivider();
            // Atualiza botões dos carrosséis
            if (typeof updateCarouselButtons === 'function') {
                updateCarouselButtons('continuar');
                updateCarouselButtons('recomendacoes');
            }
        }, 500);
        
        // Adiciona listeners para scroll manual nos carrosséis
        const continuarCarousel = document.getElementById('continuar-carousel');
        const recomendacoesCarousel = document.getElementById('recomendacoes-carousel');
        
        if (continuarCarousel) {
            continuarCarousel.addEventListener('scroll', () => {
                if (typeof updateCarouselButtons === 'function') updateCarouselButtons('continuar');
            });
        }
        
        if (recomendacoesCarousel) {
            recomendacoesCarousel.addEventListener('scroll', () => {
                if (typeof updateCarouselButtons === 'function') updateCarouselButtons('recomendacoes');
            });
        }
    });
    
    // Função para configurar preview dos vídeos
    function setupVideoPreviews() {
        const videoPreviews = document.querySelectorAll('.video-thumbnail-preview');
        console.log('setupVideoPreviews: Encontrados', videoPreviews.length, 'vídeos para preview');
        
        videoPreviews.forEach((video, index) => {
            // Se já tem src, força carregar o primeiro frame
            if (video.src) {
                video.currentTime = 0.1;
                video.load();
                video.style.opacity = '0';
                video.addEventListener('loadedmetadata', function() {
                    this.currentTime = 0.1;
                    this.pause();
                    this.style.opacity = '1';
                }, { once: true });
                return;
            }
            
            // Se tem data-video-src, usa ele
            const videoSrc = video.getAttribute('data-video-src');
            if (videoSrc) {
                video.src = videoSrc;
            } else {
                // Tenta pegar do source dentro do video
                const source = video.querySelector('source');
                if (source && source.src) {
                    video.src = source.src;
                }
            }
            
            // Inicialmente oculto até carregar
            video.style.opacity = '0';
            
            // Tenta carregar o primeiro frame
            video.addEventListener('loadedmetadata', function() {
                console.log('Preview carregado para vídeo', index);
                this.currentTime = 0.1;
                this.pause();
                this.style.opacity = '1';
            }, { once: true });
            
            video.addEventListener('loadeddata', function() {
                this.style.opacity = '1';
            }, { once: true });
            
            // Se não conseguir carregar, mostra fallback
            video.addEventListener('error', function() {
                console.warn('Erro ao carregar preview do vídeo', index);
                this.style.opacity = '0.3';
            });
            
            // Força o carregamento
            if (video.src) {
                video.load();
            }
        });
    }
    
    // Função para carregar recomendações inteligentes
    function loadRecomendacoes() {
        const recomendacoesSection = document.getElementById('recomendacoesSection');
        const recomendacoesTrack = document.getElementById('recomendacoes-track');
        
        if (!recomendacoesSection || !recomendacoesTrack) {
            console.warn('Elementos de recomendações não encontrados');
            return;
        }
        
        // Só mostra recomendações se não houver filtros ativos
        const urlParams = new URLSearchParams(window.location.search);
        const filtroSetor = urlParams.get('filtroSetor');
        const filtroModulo = urlParams.get('filtroModulo');
        const pesquisaTitulo = urlParams.get('pesquisaTitulo');
        
        console.log('Filtros ativos:', { filtroSetor, filtroModulo, pesquisaTitulo });
        
        if ((filtroSetor && filtroSetor !== '0') || (filtroModulo && filtroModulo !== '0') || pesquisaTitulo) {
            console.log('Filtros ativos detectados, ocultando recomendações');
            recomendacoesSection.style.display = 'none';
            if (typeof updateSectionDivider === 'function') updateSectionDivider();
            return;
        }
        
        console.log('Carregando recomendações...');
        recomendacoesSection.style.display = 'block'; // Mostra a seção enquanto carrega
        recomendacoesTrack.innerHTML = '<div class="carousel-item" style="flex: 0 0 100%; text-align: center; padding: 24px;"><i class="fas fa-spinner fa-spin" style="color: #ff6f00; font-size: 16px;"></i><p style="margin-top: 12px; color: #64748b; font-size: 13px;">Carregando...</p></div>';
        
        fetch('get_recomendacoes.php?limite=6')
            .then(response => {
                console.log('Resposta do servidor:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor: ' + response.status);
                }
                return response.text(); // Primeiro pega como texto para debug
            })
            .then(text => {
                console.log('Resposta bruta:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Dados de recomendações parseados:', data);
                    return data;
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Texto recebido:', text);
                    throw new Error('Resposta inválida do servidor');
                }
            })
            .then(data => {
                console.log('Dados completos recebidos:', data);
                console.log('Total de recomendações:', data.recomendacoes ? data.recomendacoes.length : 0);
                if (data.debug) {
                    console.log('Debug:', data.debug);
                }
                
                if (data.success && data.recomendacoes && data.recomendacoes.length > 0) {
                    console.log('Encontradas', data.recomendacoes.length, 'recomendações');
                    // Verifica quantos são recomendados manualmente
                    const recomendados_manual = data.recomendacoes.filter(v => v.recomendado == 1);
                    console.log('Vídeos recomendados manualmente:', recomendados_manual.length);
                    
                    let html = '';
                    data.recomendacoes.forEach(video => {
                        console.log('Processando vídeo:', video.id, 'Recomendado:', video.recomendado);
                        const visualizacoes_formatadas = parseInt(video.visualizacoes || 0).toLocaleString('pt-BR');
                        const titulo_truncado = (video.titulo || '').length > 50 ? (video.titulo || '').substring(0, 50) + '...' : (video.titulo || '');
                        
                        html += '<div class="carousel-item">';
                        html += '<div class="video-card" data-video-id="' + video.id + '">';
                        html += '<div class="video-card-thumbnail">';
                        if (video.url_video) {
                            html += '<video class="video-thumbnail-preview" preload="metadata" muted playsinline data-video-src="' + (video.url_video || '') + '">';
                            html += '<source src="' + (video.url_video || '') + '" type="video/mp4">';
                            html += '</video>';
                        } else {
                            html += '<div class="video-thumbnail-fallback"><i class="fas fa-video"></i></div>';
                        }
                        html += '<div class="badge-recomendado"><i class="fas fa-sparkles"></i> Recomendado</div>';
                        html += '<a href="video_detalhes.php?id=' + video.id + '" class="video-play-overlay"><i class="fas fa-play"></i></a>';
                        html += '</div>';
                        html += '<div class="video-card-content">';
                        html += '<h3 class="video-card-title" title="' + (video.titulo || '').replace(/"/g, '&quot;') + '">' + titulo_truncado + '</h3>';
                        html += '<div class="video-card-meta">';
                        html += '<span class="video-card-setor-minimal"><i class="fas fa-tag"></i> ' + (video.setor_nome || '') + '</span>';
                        html += '<span class="video-card-views-minimal"><i class="fas fa-eye"></i> ' + visualizacoes_formatadas + '</span>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    
                    const recomendacoesTrack = document.getElementById('recomendacoes-track');
                    if (recomendacoesTrack) {
                        recomendacoesTrack.innerHTML = html;
                    }
                    recomendacoesSection.style.display = 'block';
                    
                    // Atualiza divisor
                    if (typeof updateSectionDivider === 'function') updateSectionDivider();
                    
                    // Reinicializa listeners e previews
                    setTimeout(() => {
                        if (typeof initVideoCardListeners === 'function') {
                            initVideoCardListeners();
                        }
                        if (typeof setupVideoPreviews === 'function') {
                            setupVideoPreviews();
                        }
                        if (typeof updateCarouselButtons === 'function') {
                            updateCarouselButtons('recomendacoes');
                        }
                    }, 200);
                } else {
                    console.log('Nenhuma recomendação encontrada ou dados inválidos:', data);
                    // Mesmo sem recomendações, mostra a seção com mensagem amigável
                    if (data.recomendacoes && Array.isArray(data.recomendacoes) && data.recomendacoes.length === 0) {
                        recomendacoesTrack.innerHTML = '<div class="carousel-item" style="flex: 0 0 100%; text-align: center; padding: 24px; color: #64748b;"><i class="fas fa-info-circle" style="margin-bottom: 12px; color: #ff6f00; font-size: 16px;"></i><p style="font-size: 13px; margin: 0; line-height: 1.5;">Assista alguns vídeos para receber recomendações personalizadas</p></div>';
                        setTimeout(() => {
                            if (typeof updateCarouselButtons === 'function') updateCarouselButtons('recomendacoes');
                        }, 100);
                        recomendacoesSection.style.display = 'block';
                        if (typeof updateSectionDivider === 'function') updateSectionDivider();
                    } else {
                        // Se não tem dados válidos, tenta mostrar vídeos populares
                        console.log('Tentando mostrar vídeos populares como fallback...');
                        recomendacoesSection.style.display = 'none';
                        if (typeof updateSectionDivider === 'function') updateSectionDivider();
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao carregar recomendações:', error);
                // Em caso de erro, oculta a seção para não poluir a interface
                recomendacoesSection.style.display = 'none';
                if (typeof updateSectionDivider === 'function') updateSectionDivider();
            });
    }

    function muteLive() {
        const iframe = document.querySelector(".live-container iframe");
        if (iframe) {
            iframe.contentWindow.postMessage('{"event":"command","func":"mute","args":""}', '*');
        }
    }

    function unmuteLive() {
        const iframe = document.querySelector(".live-container iframe");
        if (iframe) {
            iframe.contentWindow.postMessage('{"event":"command","func":"unMute","args":""}', '*');
        }
    }


   
    // ABRIR LIVE EM POPUP
    function openLivePopup() {
        const liveURL = "<?= preg_replace('/youtube\.com\/live\/([a-zA-Z0-9_-]+)/', 'youtube.com/embed/$1', htmlspecialchars($_SESSION['live_url'])) ?>";
        window.open(liveURL, '_blank', 'width=800,height=450');
    }

    // MUTE/DESMUTE O VÍDEO
    function toggleMute() {
        const player = document.getElementById("livePlayer").contentWindow;
        const muteIcon = document.getElementById("muteIcon");

        if (muteIcon.classList.contains("fa-volume-up")) {
            player.postMessage('{"event":"command","func":"mute","args":""}', '*');
            muteIcon.classList.remove("fa-volume-up");
            muteIcon.classList.add("fa-volume-mute");
        } else {
            player.postMessage('{"event":"command","func":"unMute","args":""}', '*');
            muteIcon.classList.remove("fa-volume-mute");
            muteIcon.classList.add("fa-volume-up");
        }
    }

    // TELA CHEIA
    function goFullscreen() {
        const iframe = document.getElementById("livePlayer");
        if (iframe.requestFullscreen) {
            iframe.requestFullscreen();
        } else if (iframe.mozRequestFullScreen) {
            iframe.mozRequestFullScreen();
        } else if (iframe.webkitRequestFullscreen) {
            iframe.webkitRequestFullscreen();
        } else if (iframe.msRequestFullscreen) {
            iframe.msRequestFullscreen();
        }
    }


 // SAIR DA TELA CHEIA
    function exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
    // Atualizar título e descrição em tempo real
    function updateLiveInfo() {
        const titulo = document.getElementById('adminLiveTitle').value.trim();
        const descricao = document.getElementById('adminLiveDescription').value.trim();

        if (!titulo || !descricao) {
            alert("Preencha o título e a descrição corretamente!");
            return;
        }

        fetch('update_live_info.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `titulo=${encodeURIComponent(titulo)}&descricao=${encodeURIComponent(descricao)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('liveTitle').textContent = titulo;
                document.getElementById('liveDescription').textContent = descricao;
                alert("Live atualizada com sucesso!");
            } else {
                alert(data.error);
            }
        })
        .catch(() => alert("Erro ao atualizar a live."));
    }

    // ===== MODO ESCURO =====
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
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // Inicializar tema ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        try {
            initTheme();
            
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
        } catch (error) {
            console.error('Erro ao inicializar tema:', error);
        }

        // Sistema de dropdown dinâmico para setores e módulos
        document.querySelectorAll('.setor-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const setorId = this.getAttribute('data-setor-id');
                const setorNome = this.getAttribute('data-setor-nome');
                const wrapper = this.closest('.setor-wrapper');
                const modulosList = document.getElementById('modulos-' + setorId);
                const chevron = this.querySelector('.setor-chevron');
                const folderIcon = this.querySelector('.fa-folder, .fa-folder-open');
                
                // Fecha outros setores abertos
                document.querySelectorAll('.setor-wrapper').forEach(function(w) {
                    if (w !== wrapper) {
                        w.classList.remove('active');
                        const otherList = w.querySelector('.modulos-list');
                        if (otherList) {
                            otherList.classList.remove('expanded');
                            otherList.style.display = 'none';
                        }
                        const otherToggle = w.querySelector('.setor-toggle');
                        if (otherToggle) {
                            otherToggle.classList.remove('active');
                            const otherChevron = otherToggle.querySelector('.setor-chevron');
                            const otherFolder = otherToggle.querySelector('.fa-folder, .fa-folder-open');
                            if (otherChevron) otherChevron.className = 'fas fa-chevron-right setor-chevron';
                            if (otherFolder) otherFolder.className = 'fas fa-folder';
                        }
                    }
                });
                
                // Toggle do setor atual
                const isExpanded = wrapper.classList.contains('active');
                
                if (isExpanded) {
                    // Fecha
                    wrapper.classList.remove('active');
                    modulosList.classList.remove('expanded');
                    modulosList.style.display = 'none';
                    chevron.className = 'fas fa-chevron-right setor-chevron';
                    folderIcon.className = 'fas fa-folder';
                    this.classList.remove('active');
                    
                    // Remove filtro (atualiza vídeos sem recarregar)
                    updateVideos(0, 0);
                } else {
                    // Abre
                    wrapper.classList.add('active');
                    this.classList.add('active');
                    chevron.className = 'fas fa-chevron-down setor-chevron';
                    folderIcon.className = 'fas fa-folder-open';
                    modulosList.style.display = 'flex';
                    modulosList.classList.add('expanded');
                    
                    // Verifica se já tem conteúdo (não recarrega se já carregou)
                    if (modulosList.querySelector('.modulo-item') || modulosList.querySelector('.modulo-empty')) {
                        console.log('Módulos já carregados, apenas atualiza vídeos');
                        // Apenas atualiza vídeos, mantém módulos abertos
                        updateVideos(setorId, 0);
                    } else {
                        // Carrega módulos via AJAX
                        console.log('Carregando módulos para setor:', setorId);
                        loadModulos(setorId, modulosList, function() {
                            console.log('Módulos carregados com sucesso');
                            // Após carregar módulos, atualiza vídeos
                            updateVideos(setorId, 0);
                        });
                    }
                }
            });
        });
        
        // Função para carregar módulos via AJAX
        function loadModulos(setorId, container, callback) {
            if (!container) {
                console.error('Container não encontrado');
                return;
            }
            
            container.innerHTML = '<div class="modulos-loading"><i class="fas fa-spinner fa-spin"></i> Carregando módulos...</div>';
            
            const url = 'get_modulos.php?setor_id=' + encodeURIComponent(setorId);
            console.log('Carregando módulos de:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-cache'
            })
            .then(response => {
                console.log('Resposta recebida:', response.status, response.statusText);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Erro HTTP:', response.status, text);
                        throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 100));
                    });
                }
                return response.text();
            })
            .then(text => {
                console.log('Texto recebido:', text.substring(0, 200));
                try {
                    const data = JSON.parse(text);
                    console.log('Dados parseados:', data);
                    
                    container.innerHTML = '';
                    
                    // Adiciona "Todos os Vídeos"
                    const todosLink = document.createElement('a');
                    todosLink.href = '#';
                    todosLink.className = 'modulo-item';
                    todosLink.setAttribute('data-setor-id', setorId);
                    todosLink.setAttribute('data-modulo-id', '0');
                    todosLink.innerHTML = '<i class="fas fa-th"></i><span>Todos os Vídeos</span><span class="modulo-count">-</span>';
                        todosLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            // Remove active de todos os módulos
                            document.querySelectorAll('.modulo-item').forEach(m => m.classList.remove('active'));
                            // Marca este como ativo
                            this.classList.add('active');
                            // Atualiza vídeos sem recarregar página
                            updateVideos(setorId, 0);
                            // Mantém o dropdown aberto
                            const wrapper = this.closest('.setor-wrapper');
                            if (wrapper) {
                                wrapper.classList.add('active');
                                const modulosList = wrapper.querySelector('.modulos-list');
                                if (modulosList) {
                                    modulosList.style.display = 'flex';
                                    modulosList.classList.add('expanded');
                                }
                            }
                        });
                    container.appendChild(todosLink);
                    
                    if (data && data.success && Array.isArray(data.modulos) && data.modulos.length > 0) {
                        data.modulos.forEach(function(modulo) {
                            const moduloLink = document.createElement('a');
                            moduloLink.href = '#';
                            moduloLink.className = 'modulo-item';
                            moduloLink.setAttribute('data-setor-id', setorId);
                            moduloLink.setAttribute('data-modulo-id', modulo.id);
                            moduloLink.style.setProperty('--modulo-color', modulo.cor || '#6366f1');
                            moduloLink.innerHTML = '<i class="' + (modulo.icone || 'fas fa-cube') + '"></i><span>' + modulo.nome + '</span><span class="modulo-count">-</span>';
                            
                                moduloLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    // Remove active de todos os módulos
                                    document.querySelectorAll('.modulo-item').forEach(m => m.classList.remove('active'));
                                    // Marca este como ativo
                                    this.classList.add('active');
                                    // Atualiza vídeos sem recarregar página
                                    updateVideos(setorId, modulo.id);
                                    // Mantém o dropdown aberto
                                    const wrapper = this.closest('.setor-wrapper');
                                    if (wrapper) {
                                        wrapper.classList.add('active');
                                        const modulosList = wrapper.querySelector('.modulos-list');
                                        if (modulosList) {
                                            modulosList.style.display = 'flex';
                                            modulosList.classList.add('expanded');
                                        }
                                    }
                                });
                            
                            container.appendChild(moduloLink);
                            
                            // Busca contagem de vídeos do módulo
                            fetch('get_video_count.php?setor_id=' + setorId + '&modulo_id=' + modulo.id)
                                .then(r => r.json())
                                .then(countData => {
                                    const countSpan = moduloLink.querySelector('.modulo-count');
                                    if (countSpan) countSpan.textContent = countData.count || 0;
                                })
                                .catch(() => {
                                    const countSpan = moduloLink.querySelector('.modulo-count');
                                    if (countSpan) countSpan.textContent = '0';
                                });
                        });
                        
                        // Busca contagem total de vídeos do setor
                        fetch('get_video_count.php?setor_id=' + setorId + '&modulo_id=0')
                            .then(r => r.json())
                            .then(countData => {
                                const countSpan = todosLink.querySelector('.modulo-count');
                                if (countSpan) countSpan.textContent = countData.count || 0;
                            })
                            .catch(() => {
                                const countSpan = todosLink.querySelector('.modulo-count');
                                if (countSpan) countSpan.textContent = '0';
                            });
                    } else {
                        container.innerHTML = '<div class="modulo-empty"><i class="fas fa-info-circle"></i> Nenhum módulo cadastrado</div>';
                    }
                    
                    if (callback) callback();
                } catch (e) {
                    console.error('Erro ao parsear JSON:', e, 'Texto:', text);
                    container.innerHTML = '<div class="modulo-empty"><i class="fas fa-exclamation-triangle"></i> Erro ao processar resposta</div>';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                container.innerHTML = '<div class="modulo-empty"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar módulos: ' + error.message + '</div>';
            });
        }
        
        // Função para atualizar vídeos via AJAX (sem recarregar página)
        function updateVideos(setorId, moduloId) {
            const videosGrid = document.querySelector('.videos-grid');
            const videosSection = document.querySelector('.videos-section h2');
            const loadingHtml = '<div style="text-align: center; padding: 60px;"><i class="fas fa-spinner fa-spin fa-2x" style="color: #ff6f00;"></i><p style="margin-top: 20px; color: #666;">Carregando vídeos...</p></div>';
            
            if (videosGrid) {
                videosGrid.style.opacity = '0.5';
                videosGrid.innerHTML = loadingHtml;
            }
            
            // Atualiza URL sem recarregar
            const url = new URL(window.location);
            url.searchParams.set('filtroSetor', setorId);
            url.searchParams.set('filtroModulo', moduloId);
            url.searchParams.set('pagina', '1');
            window.history.pushState({}, '', url);
            
            // Busca vídeos via AJAX
            fetch('get_videos.php?' + url.searchParams.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualiza grid de vídeos
                        if (videosGrid) {
                            videosGrid.innerHTML = data.videos_html;
                            videosGrid.style.opacity = '1';
                            
                            // Reinicializa listeners e previews após atualizar
                            initVideoCardListeners();
                            setupVideoPreviews();
                        }
                        
                        // Atualiza breadcrumb e informações
                        const breadcrumbPath = document.querySelector('.breadcrumb-path');
                        const videosInfo = document.querySelector('.videos-info-modern');
                        const paginationContainer = document.querySelector('.pagination-container');
                        
                        if (breadcrumbPath) {
                            breadcrumbPath.innerHTML = data.breadcrumb_html;
                        }
                        
                        if (videosInfo && data.videos_info_html) {
                            videosInfo.outerHTML = data.videos_info_html;
                        }
                        
                        // Atualiza ou cria container de paginação
                        if (data.pagination_html) {
                            let paginationContainer = document.querySelector('.pagination-container');
                            if (!paginationContainer) {
                                paginationContainer = document.createElement('div');
                                paginationContainer.className = 'pagination-container';
                                if (videosGrid && videosGrid.parentNode) {
                                    videosGrid.parentNode.insertBefore(paginationContainer, videosGrid.nextSibling);
                                } else {
                                    const videosSection = document.querySelector('.videos-section');
                                    if (videosSection) {
                                        videosSection.appendChild(paginationContainer);
                                    }
                                }
                            }
                            paginationContainer.innerHTML = data.pagination_html;
                        } else {
                            const paginationContainer = document.querySelector('.pagination-container');
                            if (paginationContainer) {
                                paginationContainer.innerHTML = '';
                            }
                        }
                        
                        // Scroll suave para o topo do conteúdo (não da página toda)
                        const mainContent = document.querySelector('.main-content') || document.querySelector('.videos-section');
                        if (mainContent) {
                            mainContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                        
                        // Reinicializa event listeners dos botões
                        initVideoCardListeners();
                        
                        // Configura preview dos vídeos
                        setupVideoPreviews();
                        
                        // Carrega recomendações após atualizar vídeos
                        loadRecomendacoes();
                    } else {
                        if (videosGrid) {
                            videosGrid.innerHTML = '<div class="no-videos"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar vídeos</p></div>';
                            videosGrid.style.opacity = '1';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar vídeos:', error);
                    if (videosGrid) {
                        videosGrid.innerHTML = '<div class="no-videos"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar vídeos</p></div>';
                        videosGrid.style.opacity = '1';
                    }
                });
        }
        
        // Função para curtir/descurtir vídeo
        function toggleCurtida(videoId) {
            fetch('toggle_curtida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const curtidasSpan = document.querySelector(`#curtidas-${videoId}`);
                    if (curtidasSpan) {
                        const currentCount = parseInt(curtidasSpan.textContent) || 0;
                        if (data.action === 'added') {
                            curtidasSpan.textContent = currentCount + 1;
                            showNotification('Curtida adicionada!', 'success');
                        } else if (data.action === 'removed') {
                            curtidasSpan.textContent = Math.max(0, currentCount - 1);
                            showNotification('Curtida removida!', 'info');
                        }
                    }
                    
                    // Atualiza visual do botão
                    const likeBtn = document.querySelector(`.btn-like[data-video-id="${videoId}"]`);
                    if (likeBtn) {
                        if (data.action === 'added') {
                            likeBtn.classList.add('active');
                            likeBtn.querySelector('i').classList.remove('far');
                            likeBtn.querySelector('i').classList.add('fas');
                        } else {
                            likeBtn.classList.remove('active');
                            likeBtn.querySelector('i').classList.remove('fas');
                            likeBtn.querySelector('i').classList.add('far');
                        }
                    }
                } else {
                    if (data.error && data.error.includes('logado')) {
                        showNotification('Você precisa estar logado para curtir.', 'error');
                    } else {
                        showNotification(data.error || 'Erro ao processar a curtida.', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao curtir:', error);
                showNotification('Erro ao processar a curtida.', 'error');
            });
        }
        
        // Função para compartilhar vídeo
        function shareVideo(videoId, videoTitle) {
            const url = window.location.origin + window.location.pathname.replace('index.php', '') + 'video_detalhes.php?id=' + videoId;
            
            if (navigator.share) {
                // Usa Web Share API se disponível
                navigator.share({
                    title: videoTitle,
                    text: 'Confira este vídeo: ' + videoTitle,
                    url: url
                }).catch(err => {
                    console.log('Erro ao compartilhar:', err);
                    copyToClipboard(url);
                });
            } else {
                // Fallback: copia para clipboard
                copyToClipboard(url);
            }
        }
        
        // Função auxiliar para copiar para clipboard
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showNotification('Link copiado para a área de transferência!', 'success');
                }).catch(() => {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }
        
        // Fallback para copiar (navegadores antigos)
        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification('Link copiado para a área de transferência!', 'success');
            } catch (err) {
                showNotification('Erro ao copiar link. Tente manualmente: ' + text, 'error');
            }
            document.body.removeChild(textArea);
        }
        
        // Função para deletar vídeo
        function deleteVideo(videoId) {
            if (!confirm('Tem certeza que deseja excluir este vídeo? Esta ação não pode ser desfeita.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('video_id', videoId);
            
            fetch('delete_video.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Vídeo excluído com sucesso!', 'success');
                    // Remove o card do vídeo da tela
                    const videoCard = document.querySelector(`[data-video-id="${videoId}"]`);
                    if (videoCard) {
                        videoCard.style.transition = 'opacity 0.3s';
                        videoCard.style.opacity = '0';
                        setTimeout(() => {
                            videoCard.remove();
                            // Recarrega a lista de vídeos
                            const videosGrid = document.getElementById('videos-grid');
                            if (videosGrid && videosGrid.children.length === 0) {
                                updateVideos();
                            }
                        }, 300);
                    } else {
                        // Se não encontrou o card, recarrega a página
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showNotification(data.error || 'Erro ao excluir o vídeo.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao deletar:', error);
                showNotification('Erro ao processar a exclusão.', 'error');
            });
        }
        
        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            let container = document.getElementById('notification-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notification-container';
                document.body.appendChild(container);
            }
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <span>${message}</span>
                <button class="btn-close" onclick="this.parentElement.remove()">×</button>
            `;
            
            container.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }
            }, 4000);
        }
        
        // Se já houver setor selecionado, expande e carrega módulos
        <?php if ($filtro_setor > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const activeSetor = document.querySelector('[data-setor-id="<?= $filtro_setor ?>"]');
                if (activeSetor) {
                    const toggle = activeSetor.querySelector('.setor-toggle');
                    const modulosList = document.getElementById('modulos-<?= $filtro_setor ?>');
                    const chevron = toggle ? toggle.querySelector('.setor-chevron') : null;
                    const folderIcon = toggle ? toggle.querySelector('.fa-folder, .fa-folder-open') : null;
                    
                    if (toggle && modulosList) {
                        activeSetor.classList.add('active');
                        toggle.classList.add('active');
                        modulosList.style.display = 'flex';
                        modulosList.classList.add('expanded');
                        
                        if (chevron) chevron.className = 'fas fa-chevron-down setor-chevron';
                        if (folderIcon) folderIcon.className = 'fas fa-folder-open';
                        
                        loadModulos(<?= $filtro_setor ?>, modulosList, function() {
                            // Marca módulo ativo se houver
                            <?php if ($filtro_modulo > 0): ?>
                            setTimeout(function() {
                                const activeModulo = document.querySelector('[data-modulo-id="<?= $filtro_modulo ?>"]');
                                if (activeModulo) {
                                    activeModulo.classList.add('active');
                                }
                            }, 100);
                            <?php else: ?>
                            setTimeout(function() {
                                const todosVideos = document.querySelector('[data-modulo-id="0"]');
                                if (todosVideos) todosVideos.classList.add('active');
                            }, 100);
                            <?php endif; ?>
                        });
                    }
                }
            }, 100);
        });
        <?php endif; ?>

        // Carregar módulos quando setor for selecionado (no modal de upload)
        const setorSelect = document.getElementById('setor');
        const moduloSelect = document.getElementById('modulo');
        
        if (setorSelect && moduloSelect) {
            setorSelect.addEventListener('change', function() {
                const setorId = this.value;
                
                // Limpa o select de módulos
                moduloSelect.innerHTML = '<option value="">Carregando módulos...</option>';
                moduloSelect.disabled = true;
                
                // Recarrega sequências se o checkbox estiver marcado
                if (document.getElementById('isSequencia') && document.getElementById('isSequencia').checked) {
                    loadSequencias();
                }
                
                if (setorId && setorId > 0) {
                    // Busca módulos do setor selecionado
                    const url = 'get_modulos.php?setor_id=' + encodeURIComponent(setorId);
                    
                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        cache: 'no-cache'
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 50));
                            });
                        }
                        return response.text();
                    })
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
                            
                            moduloSelect.innerHTML = '<option value="">Selecione um módulo (opcional)</option>';
                            
                            if (data && data.success === true && Array.isArray(data.modulos) && data.modulos.length > 0) {
                                data.modulos.forEach(function(modulo) {
                                    const option = document.createElement('option');
                                    option.value = modulo.id;
                                    option.textContent = modulo.nome;
                                    moduloSelect.appendChild(option);
                                });
                            } else {
                                moduloSelect.innerHTML = '<option value="">Nenhum módulo disponível para este setor</option>';
                            }
                            
                            moduloSelect.disabled = false;
                        } catch (e) {
                            console.error('Erro ao parsear JSON:', e, 'Resposta:', text);
                            moduloSelect.innerHTML = '<option value="">Erro ao processar resposta</option>';
                            moduloSelect.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar módulos:', error);
                        moduloSelect.innerHTML = '<option value="">Erro ao carregar módulos</option>';
                        moduloSelect.disabled = false;
                    });
                } else {
                    moduloSelect.innerHTML = '<option value="">Selecione um setor primeiro</option>';
                    moduloSelect.disabled = false;
                }
            });
            
            // Se já houver um setor selecionado quando o modal abrir, carrega os módulos automaticamente
            if (setorSelect.value && parseInt(setorSelect.value) > 0) {
                setorSelect.dispatchEvent(new Event('change'));
            }
            
            // Recarrega sequências quando setor ou módulo mudar (se checkbox estiver marcado)
            const moduloSelect = document.getElementById('modulo');
            if (moduloSelect) {
                moduloSelect.addEventListener('change', function() {
                    // Sempre recarrega sequências quando módulo muda (se checkbox estiver marcado)
                    const isSequenciaCheck = document.getElementById('isSequencia');
                    if (isSequenciaCheck && isSequenciaCheck.checked) {
                        loadSequencias();
                    }
                });
            }
            
            // Recarrega sequências quando setor mudar (se checkbox estiver marcado)
            if (setorSelect) {
                setorSelect.addEventListener('change', function() {
                    // Sempre recarrega sequências quando setor muda (se checkbox estiver marcado)
                    const isSequenciaCheck = document.getElementById('isSequencia');
                    if (isSequenciaCheck && isSequenciaCheck.checked) {
                        loadSequencias();
                    }
                });
            }
        }
    });

    // START EMERGENCY FIX BUNDLE
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Applying Emergency UI Fixes...");
        
        // 1. Force Initialize Listeners (Was missing on load)
        if (typeof initVideoCardListeners === 'function') {
            initVideoCardListeners();
            console.log("Listeners Initialized manually.");
        }
        
        // 2. Force Footer Layout
        const footer = document.querySelector('.footer-premium') || document.querySelector('.footer');
        if(footer) {
            Object.assign(footer.style, {
                position: 'relative',
                zIndex: '50',
                bottom: 'auto', 
                left: 'auto',
                pointerEvents: 'auto',
                display: 'block'
            });
        }
        
        // 3. Force Header & Interaction
        const header = document.querySelector('.top-header');
        if(header) {
            header.style.zIndex = '2000';
            header.style.pointerEvents = 'auto';
        }
        
        // 4. Force Video Card Interaction
        document.querySelectorAll('.video-card-actions').forEach(el => {
            el.style.zIndex = '30';
            el.style.pointerEvents = 'auto';
        });
        
        console.log("Emergency Fixes Applied.");
    });
    // END EMERGENCY FIX BUNDLE
    </script>
    
    <!-- Sistema de Notificações e Histórico -->
    <script src="js/notificacoes_historico.js"></script>
</body>
</html>

