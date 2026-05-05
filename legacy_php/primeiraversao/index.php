<?php
include 'db/conexao.php';

// Consulta para buscar todos os vídeos e o nome do setor associado a cada vídeo
$sql = "SELECT videos.*, setores.nome AS setor_nome 
        FROM videos 
        JOIN setores ON videos.setor_id = setores.id 
        ORDER BY videos.data_upload DESC";
$result = mysqli_query($conexao, $sql);

if (!$result) {
    die("Erro ao consultar o banco de dados: " . mysqli_error($conexao));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Treinamentos</title>
    <style>
        /* Estilos gerais */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f5;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Cabeçalho */
        .header {
            width: 100%;
            background-color: #FF6F00;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header .logo {
            width: 50px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            color: white;
            text-align: center;
        }

        /* Botão de upload */
        .upload-btn {
            background-color: #333;
            color: #FF6F00;
            border: 2px solid #FF6F00;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-btn:hover {
            background-color: #FF6F00;
            color: white;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            color: #FF6F00;
            cursor: pointer;
        }

        /* Barra de filtro e pesquisa */
        .filter-search-bar {
            width: 90%;
            max-width: 600px;
            margin: 20px 0;
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        .search-input, .filter-select {
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 2px solid #FF6F00;
            outline: none;
            transition: border-color 0.3s;
            width: 100%;
            max-width: 300px;
        }

        .filter-select {
            cursor: pointer;
        }

        .search-input:focus, .filter-select:focus {
            border-color: #333;
        }

        /* Galeria de vídeos */
        .video-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            max-width: 1200px;
            padding: 20px;
        }

        .video-item {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 300px;
            text-align: center;
            transition: transform 0.3s;
        }

        .video-item:hover {
            transform: scale(1.05);
        }

        .video-item video {
            width: 100%;
            height: 180px;
        }

        .video-item h2 {
            font-size: 18px;
            margin: 10px 0;
            color: #FF6F00;
        }

        .video-item p {
            padding: 0 10px 10px;
            color: #666;
        }

        .video-item em {
            display: block;
            margin-top: 5px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="logo-container">
        <img src="img/martinez.png" alt="Logo da Empresa" class="logo"> <!-- Caminho da logo -->
        <h1>Biblioteca de Treinamentos</h1>
    </div>
    <button class="upload-btn" onclick="openModal()">
        Upload de Vídeo
    </button>
</div>

<!-- Filtro de Setor e Campo de Pesquisa -->
<div class="filter-search-bar">
    <select id="filtroSetor" class="filter-select" onchange="filterBySetor(this.value)">
        <option value="Todos">Todos os Setores</option>
        <?php
        $setores_result = mysqli_query($conexao, "SELECT id, nome FROM setores");
        if ($setores_result) {
            while ($setor = mysqli_fetch_assoc($setores_result)) {
                echo "<option value='{$setor['id']}'>{$setor['nome']}</option>";
            }
        } else {
            echo "<option value=''>Erro ao carregar setores</option>";
        }
        ?>
    </select>
    <input type="text" id="pesquisaTitulo" class="search-input" placeholder="Pesquisar por título..." oninput="searchByTitle(this.value)">
</div>

<!-- Modal de Upload -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Upload de Novo Vídeo</h2>
        <form id="uploadForm" onsubmit="return uploadVideo();">
            <label for="titulo" style="display: block; text-align: left;">Título:</label>
            <input type="text" id="titulo" name="titulo" required style="width: 100%; padding: 8px; margin-bottom: 15px;">

            <label for="descricao" style="display: block; text-align: left;">Descrição:</label>
            <textarea id="descricao" name="descricao" style="width: 100%; padding: 8px; margin-bottom: 15px;"></textarea>

            <label for="setor_id" style="display: block; text-align: left;">Setor:</label>
            <select id="setor_id" name="setor_id" style="width: 100%; padding: 8px; margin-bottom: 15px;">
                <?php
                mysqli_data_seek($setores_result, 0);
                while ($setor = mysqli_fetch_assoc($setores_result)) {
                    echo "<option value='{$setor['id']}'>{$setor['nome']}</option>";
                }
                ?>
            </select>

            <label for="video" style="display: block; text-align: left;">Escolha o vídeo:</label>
            <input type="file" id="video" name="video" accept="video/*" required style="width: 100%; padding: 8px; margin-bottom: 15px;">

            <div id="progressContainer" style="display: none; width: 100%; margin-bottom: 15px;">
                <div id="progressBar" style="height: 5px; width: 0%; background-color: #FF6F00;"></div>
            </div>

            <button type="submit" class="upload-btn" style="width: 100%;">Enviar Vídeo</button>
        </form>
    </div>
</div>

<!-- Galeria de Vídeos -->
<div class="video-gallery" id="videoGallery">
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($video = mysqli_fetch_assoc($result)): ?>
            <div class="video-item" data-setor-id="<?= $video['setor_id'] ?>" data-title="<?= strtolower($video['titulo']) ?>">
                <a href="video_detalhes.php?id=<?= $video['id'] ?>" style="text-decoration: none; color: inherit;">
                    <video controls>
                        <source src="<?= htmlspecialchars($video['url_video']) ?>" type="video/mp4">
                        Seu navegador não suporta este vídeo.
                    </video>
                    <h2><?= htmlspecialchars($video['titulo']) ?></h2>
                </a>
                <p><strong>Setor:</strong> <?= htmlspecialchars($video['setor_nome']) ?></p>
                <p><?= htmlspecialchars($video['descricao']) ?></p>
                <em>Adicionado em: <?= $video['data_upload'] ?></em>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Nenhum vídeo encontrado.</p>
    <?php endif; ?>
</div>

<script>
    function uploadVideo() {
        document.getElementById('progressContainer').style.display = 'block';
        const progressBar = document.getElementById('progressBar');
        
        const form = document.getElementById('uploadForm');
        const formData = new FormData(form);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload_ajax.php', true);

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                progressBar.style.width = percentComplete + '%';
            }
        };

        xhr.onload = function() {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                alert("Upload concluído com sucesso!");
                location.reload(); // Recarrega a página para exibir o novo vídeo
            } else {
                alert("Erro ao enviar o vídeo: " + response.error);
            }
        };

        xhr.onerror = function() {
            alert("Erro ao enviar o vídeo. Verifique a conexão e tente novamente.");
            progressBar.style.width = '0%';
            document.getElementById('progressContainer').style.display = 'none';
        };

        xhr.send(formData);

        return false;
    }

    function openModal() {
        document.getElementById('uploadModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('uploadModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    function filterBySetor(setor_id) {
        const videos = document.querySelectorAll('.video-item');
        videos.forEach(video => {
            const videoSetorId = video.getAttribute('data-setor-id');
            video.style.display = (setor_id === 'Todos' || videoSetorId === setor_id) ? "block" : "none";
        });
    }

    function searchByTitle(query) {
        query = query.toLowerCase();
        const videos = document.querySelectorAll('.video-item');
        videos.forEach(video => {
            const title = video.getAttribute('data-title');
            video.style.display = title.includes(query) ? "block" : "none";
        });
    }
</script>

</body>
</html>

<?php
mysqli_close($conexao);
?>
