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

$playlist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($playlist_id <= 0) {
    header('Location: gerenciar_playlists.php');
    exit;
}

// Busca informações da playlist
$playlist_query = "SELECT p.*, u.nome AS usuario_nome 
                   FROM playlists p 
                   LEFT JOIN usuarios u ON p.usuario_id = u.id 
                   WHERE p.id = ?";
$stmt = $conexao->prepare($playlist_query);
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$playlist_result = $stmt->get_result();
$playlist = $playlist_result->fetch_assoc();
$stmt->close();

if (!$playlist) {
    header('Location: gerenciar_playlists.php');
    exit;
}

// Verifica se o usuário tem permissão (dono ou admin)
if ($playlist['usuario_id'] != $usuario_id && !$usuario_adm) {
    header('Location: gerenciar_playlists.php?erro=acesso_negado');
    exit;
}

// Adicionar vídeo à playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_video'])) {
    $video_id = intval($_POST['video_id'] ?? 0);
    
    if ($video_id > 0) {
        // Busca a próxima ordem
        $ordem_query = "SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima_ordem FROM playlist_videos WHERE playlist_id = ?";
        $stmt = $conexao->prepare($ordem_query);
        $stmt->bind_param("i", $playlist_id);
        $stmt->execute();
        $ordem_result = $stmt->get_result();
        $ordem_data = $ordem_result->fetch_assoc();
        $proxima_ordem = $ordem_data['proxima_ordem'];
        $stmt->close();
        
        // Verifica se o vídeo já está na playlist
        $check_query = "SELECT id FROM playlist_videos WHERE playlist_id = ? AND video_id = ?";
        $stmt = $conexao->prepare($check_query);
        $stmt->bind_param("ii", $playlist_id, $video_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            $insert_query = "INSERT INTO playlist_videos (playlist_id, video_id, ordem) VALUES (?, ?, ?)";
            $stmt = $conexao->prepare($insert_query);
            $stmt->bind_param("iii", $playlist_id, $video_id, $proxima_ordem);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: playlist_detalhes.php?id=$playlist_id");
    exit;
}

// Remover vídeo da playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_video'])) {
    $playlist_video_id = intval($_POST['playlist_video_id']);
    $delete_query = "DELETE FROM playlist_videos WHERE id = ? AND playlist_id = ?";
    $stmt = $conexao->prepare($delete_query);
    $stmt->bind_param("ii", $playlist_video_id, $playlist_id);
    $stmt->execute();
    $stmt->close();
    
    // Reordenar os vídeos restantes
    $reorder_query = "SELECT id FROM playlist_videos WHERE playlist_id = ? ORDER BY ordem ASC";
    $stmt = $conexao->prepare($reorder_query);
    $stmt->bind_param("i", $playlist_id);
    $stmt->execute();
    $reorder_result = $stmt->get_result();
    $ordem = 1;
    while ($row = $reorder_result->fetch_assoc()) {
        $update_ordem = "UPDATE playlist_videos SET ordem = ? WHERE id = ?";
        $stmt_update = $conexao->prepare($update_ordem);
        $stmt_update->bind_param("ii", $ordem, $row['id']);
        $stmt_update->execute();
        $stmt_update->close();
        $ordem++;
    }
    $stmt->close();
    
    header("Location: playlist_detalhes.php?id=$playlist_id");
    exit;
}

// Reordenar vídeos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reordenar'])) {
    $videos_ordem = json_decode($_POST['videos_ordem'], true);
    if (is_array($videos_ordem)) {
        foreach ($videos_ordem as $ordem => $video_id) {
            $update_query = "UPDATE playlist_videos SET ordem = ? WHERE playlist_id = ? AND video_id = ?";
            $stmt = $conexao->prepare($update_query);
            $nova_ordem = $ordem + 1;
            $stmt->bind_param("iii", $nova_ordem, $playlist_id, $video_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// Busca vídeos da playlist em ordem
$videos_playlist_query = "SELECT pv.id AS playlist_video_id, pv.ordem, v.id, v.titulo, v.descricao, v.url_video, v.setor_id, s.nome AS setor_nome
                          FROM playlist_videos pv
                          JOIN videos v ON pv.video_id = v.id
                          LEFT JOIN setores s ON v.setor_id = s.id
                          WHERE pv.playlist_id = ?
                          ORDER BY pv.ordem ASC";
$stmt = $conexao->prepare($videos_playlist_query);
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$videos_playlist_result = $stmt->get_result();
$stmt->close();

// Busca todos os vídeos disponíveis para adicionar
$videos_disponiveis_query = "SELECT v.id, v.titulo, v.setor_id, s.nome AS setor_nome
                             FROM videos v
                             LEFT JOIN setores s ON v.setor_id = s.id
                             WHERE v.id NOT IN (SELECT video_id FROM playlist_videos WHERE playlist_id = ?)
                             ORDER BY v.titulo ASC";
$stmt = $conexao->prepare($videos_disponiveis_query);
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$videos_disponiveis_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($playlist['titulo']) ?> - Playlist</title>
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

        .playlist-header {
            background: linear-gradient(135deg, <?= htmlspecialchars($playlist['cor']) ?>, <?= htmlspecialchars($playlist['cor']) ?>dd);
            color: white;
            padding: 32px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .playlist-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .playlist-header .playlist-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            opacity: 0.9;
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

        .video-item-playlist {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid #efefef;
            transition: all 0.2s ease;
            cursor: move;
        }

        [data-theme="dark"] .video-item-playlist {
            border-bottom-color: #363636;
        }

        .video-item-playlist:hover {
            background: #fafafa;
        }

        [data-theme="dark"] .video-item-playlist:hover {
            background: #2a2a2a;
        }

        .video-item-playlist:last-child {
            border-bottom: none;
        }

        .video-ordem {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .video-info-playlist {
            flex: 1;
        }

        .video-titulo-playlist {
            font-weight: 600;
            font-size: 16px;
            color: #262626;
            margin-bottom: 4px;
        }

        [data-theme="dark"] .video-titulo-playlist {
            color: #e0e0e0;
        }

        .video-setor-playlist {
            font-size: 13px;
            color: #8e8e8e;
        }

        [data-theme="dark"] .video-setor-playlist {
            color: #a8a8a8;
        }

        .video-actions-playlist {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                margin-top: 60px !important;
                padding: 16px !important;
            }

            .playlist-header {
                padding: 20px;
            }

            .playlist-header h1 {
                font-size: 20px;
            }

            .video-item-playlist {
                flex-direction: column;
                align-items: flex-start;
            }

            .video-actions-playlist {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Sidebar será incluída aqui (mesma estrutura de video_detalhes.php)
    include 'db/funcoes_permissoes.php';
    ?>
    
    <!-- Sidebar e Header (mesma estrutura de video_detalhes.php) -->
    <!-- ... código da sidebar ... -->
    
    <div class="main-content">
        <div class="playlist-header">
            <h1><i class="fas fa-list"></i> <?= htmlspecialchars($playlist['titulo']) ?></h1>
            <?php if (!empty($playlist['descricao'])): ?>
                <p style="margin-top: 8px; opacity: 0.9;"><?= htmlspecialchars($playlist['descricao']) ?></p>
            <?php endif; ?>
            <div class="playlist-meta">
                <span><i class="fas fa-video"></i> <?= $videos_playlist_result->num_rows ?> vídeo(s)</span>
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($playlist['usuario_nome']) ?></span>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Vídeos da Playlist</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarVideo">
                <i class="fas fa-plus"></i> Adicionar Vídeo
            </button>
        </div>

        <!-- Lista de vídeos da playlist -->
        <div class="card">
            <div class="card-body p-0">
                <?php if ($videos_playlist_result && $videos_playlist_result->num_rows > 0): ?>
                    <div id="videos-list" class="sortable-list">
                        <?php while ($video_playlist = $videos_playlist_result->fetch_assoc()): ?>
                            <div class="video-item-playlist" data-video-id="<?= $video_playlist['id'] ?>" data-playlist-video-id="<?= $video_playlist['playlist_video_id'] ?>">
                                <div class="video-ordem"><?= $video_playlist['ordem'] ?></div>
                                <div class="video-info-playlist">
                                    <div class="video-titulo-playlist"><?= htmlspecialchars($video_playlist['titulo']) ?></div>
                                    <div class="video-setor-playlist">
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($video_playlist['setor_nome']) ?>
                                    </div>
                                </div>
                                <div class="video-actions-playlist">
                                    <a href="video_detalhes.php?id=<?= $video_playlist['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-play"></i> Assistir
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Remover este vídeo da playlist?');">
                                        <input type="hidden" name="playlist_video_id" value="<?= $video_playlist['playlist_video_id'] ?>">
                                        <input type="hidden" name="remover_video" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-times"></i> Remover
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Nenhum vídeo adicionado ainda.</p>
                        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#modalAdicionarVideo">
                            <i class="fas fa-plus"></i> Adicionar Primeiro Vídeo
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Vídeo -->
    <div class="modal fade" id="modalAdicionarVideo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Vídeo à Playlist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Selecione o Vídeo</label>
                            <select name="video_id" class="form-select" required>
                                <option value="">Selecione um vídeo</option>
                                <?php while ($video = $videos_disponiveis_result->fetch_assoc()): ?>
                                    <option value="<?= $video['id'] ?>">
                                        <?= htmlspecialchars($video['titulo']) ?> 
                                        (<?= htmlspecialchars($video['setor_nome']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="adicionar_video" class="btn btn-primary">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Tornar a lista de vídeos ordenável
        const videosList = document.getElementById('videos-list');
        if (videosList) {
            new Sortable(videosList, {
                handle: '.video-item-playlist',
                animation: 150,
                onEnd: function(evt) {
                    const items = Array.from(videosList.children);
                    const videos_ordem = items.map(item => item.dataset.videoId);
                    
                    fetch('playlist_detalhes.php?id=<?= $playlist_id ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'reordenar=1&videos_ordem=' + JSON.stringify(videos_ordem)
                    }).then(() => {
                        // Atualiza os números de ordem
                        items.forEach((item, index) => {
                            const ordemDiv = item.querySelector('.video-ordem');
                            if (ordemDiv) {
                                ordemDiv.textContent = index + 1;
                            }
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>

