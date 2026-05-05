<?php
include 'db/conexao.php';


// Verifica se o ID do vídeo foi fornecido na URL
if (!isset($_GET['id'])) {
    echo "ID do vídeo não especificado.";
    exit;
}

$video_id = (int)$_GET['id'];
$usuario_id = 1; // Substitua com o ID do usuário logado se aplicável

// Consulta para buscar o vídeo específico e o nome do setor
$sql = "SELECT videos.*, setores.nome AS setor_nome 
        FROM videos 
        JOIN setores ON videos.setor_id = setores.id 
        WHERE videos.id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Vídeo não encontrado.";
    exit;
}

$video = $result->fetch_assoc();

// Verifica se o usuário já curtiu o vídeo
$sql_curtida = "SELECT * FROM curtidas WHERE video_id = ? AND usuario_id = ?";
$stmt = $conexao->prepare($sql_curtida);
$stmt->bind_param("ii", $video_id, $usuario_id);
$stmt->execute();
$curtida_result = $stmt->get_result();
$curtida_ativa = $curtida_result->num_rows > 0;

// Consulta para buscar todos os comentários do vídeo
$comentarios_stmt = $conexao->prepare("SELECT * FROM comentarios WHERE video_id = ? ORDER BY data DESC");
$comentarios_stmt->bind_param("i", $video_id);
$comentarios_stmt->execute();
$comentarios_result = $comentarios_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['titulo']) ?> - Detalhes do Vídeo</title>
    <!-- Importa o Bootstrap CSS e os ícones Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .header {
            width: 100%;
            background-color: #FF6F00;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .logo {
            width: 45px;
            object-fit: contain;
        }

        .main-container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }

        .video-container {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .video-container video {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .like-btn {
            font-size: 24px;
            color: <?= $curtida_ativa ? '#FF6F00' : '#ccc' ?>;
            cursor: pointer;
            transition: color 0.3s;
        }

        .like-btn.active {
            color: #FF6F00;
        }

        .comentarios-section {
            width: 100%;
            margin-top: 30px;
        }

        .comentarios-section h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }

        .comentario {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .comentario p {
            margin: 0;
            color: #555;
        }

        .comentario .data {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }

        .comentario-form {
            width: 100%;
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .comentario-form textarea {
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ddd;
            resize: vertical;
            min-height: 60px;
        }

        .comentario-form button {
            align-self: flex-end;
            background-color: #FF6F00;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .comentario-form button:hover {
            background-color: #e65c00;
        }

    </style>
</head>
<body>

<div class="header">
    <div class="logo-container">
        <img src="img/martinez.png" alt="Logo da Empresa" class="logo">
        <h1>Detalhes do Vídeo</h1>
    </div>
</div>

<div class="main-container">
    <div class="video-container">
        <video controls>
            <source src="<?= htmlspecialchars($video['url_video']) ?>" type="video/mp4">
            Seu navegador não suporta este vídeo.
        </video>
    </div>

    <div class="video-details">
        <h1><?= htmlspecialchars($video['titulo']) ?></h1>
        <p><strong>Setor:</strong> <?= htmlspecialchars($video['setor_nome']) ?></p>
        <p><?= htmlspecialchars($video['descricao']) ?></p>
        <p class="info"><em>Adicionado em: <?= $video['data_upload'] ?></em></p>
        <p><strong>Curtidas:</strong> <span id="curtidas-count"><?= $video['curtidas'] ?></span></p>
    </div>

    <!-- Ícone de Curtida -->
    <div class="action-buttons">
        <i id="like-btn" class="fas fa-thumbs-up like-btn <?= $curtida_ativa ? 'active' : '' ?>" onclick="toggleCurtida(<?= $video_id ?>, <?= $usuario_id ?>)"></i>
    </div>

    <div class="comentarios-section">
        <h2>Comentários</h2>

        <div id="comentarios-list">
            <?php while ($comentario = $comentarios_result->fetch_assoc()): ?>
                <div class="comentario">
                    <p><?= htmlspecialchars($comentario['conteudo']) ?></p>
                    <span class="data"><?= $comentario['data'] ?></span>
                </div>
            <?php endwhile; ?>
        </div>

        <form class="comentario-form" onsubmit="return adicionarComentario(<?= $video_id ?>, <?= $usuario_id ?>);">
            <textarea id="conteudo-comentario" placeholder="Deixe um comentário..." required></textarea>
            <button type="submit">Enviar Comentário</button>
        </form>
    </div>
</div>

<!-- Importa o Bootstrap JS e o jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    function toggleCurtida(video_id, usuario_id) {
    const likeBtn = document.getElementById('like-btn');
    const isLiked = likeBtn.classList.contains('active');
    const action = isLiked ? "remove_curtida.php" : "add_curtida.php";
    
    const xhr = new XMLHttpRequest();
    xhr.open("POST", action, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        console.log(xhr.responseText); // Mostra a resposta do servidor no console
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.curtidas !== undefined) {
                document.getElementById("curtidas-count").innerText = response.curtidas;
                likeBtn.classList.toggle('active');
            } else {
                alert("Erro: " + response.error);
            }
        } else {
            alert("Erro ao atualizar curtida.");
        }
    };
    xhr.send("video_id=" + video_id + "&usuario_id=" + usuario_id);
}

    function toggleCurtida(video_id, usuario_id) {
        const likeBtn = document.getElementById('like-btn');
        const isLiked = likeBtn.classList.contains('active');
        const action = isLiked ? "remove_curtida.php" : "add_curtida.php";
        
        const xhr = new XMLHttpRequest();
        xhr.open("POST", action, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                document.getElementById("curtidas-count").innerText = response.curtidas;
                likeBtn.classList.toggle('active');
            } else {
                alert("Erro ao atualizar curtida.");
            }
        };
        xhr.send("video_id=" + video_id + "&usuario_id=" + usuario_id);
    }

    function adicionarComentario(video_id, usuario_id) {
        const conteudo = document.getElementById("conteudo-comentario").value;
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "add_comentario.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const comentarioDiv = document.createElement("div");
                comentarioDiv.classList.add("comentario");
                comentarioDiv.innerHTML = `<p>${response.conteudo}</p><span class="data">${response.data}</span>`;
                document.getElementById("comentarios-list").prepend(comentarioDiv);
                document.getElementById("conteudo-comentario").value = "";
            } else {
                alert("Erro ao adicionar comentário.");
            }
        };
        xhr.send("video_id=" + video_id + "&usuario_id=" + usuario_id + "&conteudo=" + encodeURIComponent(conteudo));
        return false;
    }
</script>

</body>
</html>

<?php
$conexao->close();
?>
