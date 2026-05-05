<?php
session_start();
include 'db/conexao.php';
$conexao->set_charset("utf8");

// Verifica se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
$usuario_nome = $_SESSION['user_nome'] ?? null;
$usuario_adm = isset($_SESSION['user_adm']) && $_SESSION['user_adm'] === true;
$usuario_id = $_SESSION['user_id'] ?? null;

if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

// Criar nova playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_playlist'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $cor = trim($_POST['cor'] ?? '#6366f1');
    
    if (!empty($titulo)) {
        $insert_query = "INSERT INTO playlists (titulo, descricao, usuario_id, cor, ativo) VALUES (?, ?, ?, ?, 'S')";
        $stmt = $conexao->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("siss", $titulo, $descricao, $usuario_id, $cor);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: gerenciar_playlists.php');
    exit;
}

// Excluir playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_playlist'])) {
    $playlist_id = intval($_POST['playlist_id']);
    // Verifica se o usuário é dono da playlist ou admin
    $check_query = "SELECT usuario_id FROM playlists WHERE id = ?";
    $stmt = $conexao->prepare($check_query);
    $stmt->bind_param("i", $playlist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $playlist = $result->fetch_assoc();
    $stmt->close();
    
    if ($playlist && ($playlist['usuario_id'] == $usuario_id || $usuario_adm)) {
        $delete_query = "DELETE FROM playlists WHERE id = ?";
        $stmt = $conexao->prepare($delete_query);
        $stmt->bind_param("i", $playlist_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: gerenciar_playlists.php');
    exit;
}

// Busca playlists do usuário (ou todas se admin)
if ($usuario_adm) {
    $playlists_query = "SELECT p.*, u.nome AS usuario_nome, 
                       (SELECT COUNT(*) FROM playlist_videos WHERE playlist_id = p.id) AS total_videos
                       FROM playlists p 
                       LEFT JOIN usuarios u ON p.usuario_id = u.id 
                       ORDER BY p.created_at DESC";
} else {
    $playlists_query = "SELECT p.*, u.nome AS usuario_nome,
                       (SELECT COUNT(*) FROM playlist_videos WHERE playlist_id = p.id) AS total_videos
                       FROM playlists p 
                       LEFT JOIN usuarios u ON p.usuario_id = u.id 
                       WHERE p.usuario_id = ? 
                       ORDER BY p.created_at DESC";
}
$stmt = $conexao->prepare($playlists_query);
if (!$usuario_adm) {
    $stmt->bind_param("i", $usuario_id);
}
$stmt->execute();
$playlists_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Playlists</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="js/theme.js"></script>
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
            min-height: 100vh;
        }

        [data-theme="dark"] body {
            background-color: #1a1a1a !important;
            color: #e0e0e0;
        }

        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            padding: 24px;
            min-height: calc(100vh - 70px);
        }

        [data-theme="dark"] .main-content {
            background: #1a1a1a !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #262626;
        }

        [data-theme="dark"] .page-title {
            color: #e0e0e0;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #efefef;
            margin-bottom: 24px;
        }

        [data-theme="dark"] .card {
            background: #1a1a1a !important;
            border-color: #363636;
        }

        .playlist-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid #efefef;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        [data-theme="dark"] .playlist-item {
            border-bottom-color: #363636;
        }

        .playlist-item:hover {
            background: #fafafa;
        }

        [data-theme="dark"] .playlist-item:hover {
            background: #2a2a2a;
        }

        .playlist-item:last-child {
            border-bottom: none;
        }

        .playlist-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .playlist-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .playlist-details {
            flex: 1;
        }

        .playlist-titulo {
            font-weight: 600;
            font-size: 16px;
            color: #262626;
            margin-bottom: 4px;
        }

        [data-theme="dark"] .playlist-titulo {
            color: #e0e0e0;
        }

        .playlist-meta {
            font-size: 13px;
            color: #8e8e8e;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        [data-theme="dark"] .playlist-meta {
            color: #a8a8a8;
        }

        .playlist-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary {
            background: #0095f6;
            border: none;
        }

        .btn-primary:hover {
            background: #0284c7;
        }

        .btn-danger {
            background: #ef4444;
            border: none;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                margin-top: 60px !important;
                padding: 16px !important;
            }

            .playlist-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .playlist-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Incluir sidebar (copiar estrutura de video_detalhes.php)
    include 'db/funcoes_permissoes.php';
    ?>
    
    <!-- Sidebar Lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <img src="img/martinez.png" alt="Logo" class="sidebar-logo">
        </div>
        
        <?php if ($is_logged_in): ?>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($usuario_nome) ?></div>
                    <div class="sidebar-user-role"><?= $usuario_adm ? 'ADMINISTRADOR' : 'USUÁRIO' ?></div>
                </div>
            </div>
            
            <div class="sidebar-nav">
                <a href="index.php" class="sidebar-btn">
                    <i class="fas fa-home"></i>
                    <span>Início</span>
                </a>
                <?php if ($usuario_adm): ?>
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
                <a href="gerenciar_playlists.php" class="sidebar-btn active">
                    <i class="fas fa-list"></i>
                    <span>Minhas Playlists</span>
                </a>
            </div>
        <?php endif; ?>
        
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

    <!-- Header Top -->
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
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-list"></i> Gerenciar Playlists
            </h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCriarPlaylist">
                <i class="fas fa-plus"></i> Nova Playlist
            </button>
        </div>

        <!-- Lista de playlists -->
        <div class="card">
            <div class="card-body p-0">
                <?php if ($playlists_result && $playlists_result->num_rows > 0): ?>
                    <?php while ($playlist = $playlists_result->fetch_assoc()): ?>
                        <div class="playlist-item" onclick="window.location.href='playlist_detalhes.php?id=<?= $playlist['id'] ?>'">
                            <div class="playlist-info">
                                <div class="playlist-icon" style="background: <?= htmlspecialchars($playlist['cor']) ?>;">
                                    <i class="fas fa-list"></i>
                                </div>
                                <div class="playlist-details">
                                    <div class="playlist-titulo"><?= htmlspecialchars($playlist['titulo']) ?></div>
                                    <div class="playlist-meta">
                                        <span><i class="fas fa-video"></i> <?= $playlist['total_videos'] ?> vídeo(s)</span>
                                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($playlist['usuario_nome']) ?></span>
                                        <span><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($playlist['created_at'])) ?></span>
                                    </div>
                                    <?php if (!empty($playlist['descricao'])): ?>
                                        <div style="font-size: 13px; color: #8e8e8e; margin-top: 4px;">
                                            <?= htmlspecialchars($playlist['descricao']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="playlist-actions" onclick="event.stopPropagation()">
                                <a href="playlist_detalhes.php?id=<?= $playlist['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Gerenciar
                                </a>
                                <?php if ($playlist['usuario_id'] == $usuario_id || $usuario_adm): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta playlist?');">
                                        <input type="hidden" name="playlist_id" value="<?= $playlist['id'] ?>">
                                        <input type="hidden" name="excluir_playlist" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Nenhuma playlist criada ainda.</p>
                        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalCriarPlaylist">
                            <i class="fas fa-plus"></i> Criar Primeira Playlist
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Criar Playlist -->
    <div class="modal fade" id="modalCriarPlaylist" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Playlist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Título da Playlist *</label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Ex: Treinamento Completo - Passo a Passo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva o conteúdo desta playlist"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cor</label>
                            <input type="color" name="cor" class="form-control form-control-color" value="#6366f1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="criar_playlist" class="btn btn-primary">Criar Playlist</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
    </script>
</body>
</html>

