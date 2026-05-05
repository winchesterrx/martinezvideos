<?php
include 'db/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = mysqli_real_escape_string($conexao, $_POST['titulo']);
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $url = mysqli_real_escape_string($conexao, $_POST['url']);
    $data_transmissao = $_POST['data_transmissao'];

    // Diretório correto onde a imagem será salva
    $upload_dir = "img/";

    // Definir imagem padrão caso o usuário não envie uma imagem
    $imagem_capa = "img/default_live.jpg";

    if (isset($_FILES['capa']) && $_FILES['capa']['error'] == 0) {
        $extensao = pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . "." . $extensao;
        $caminho_destino = $upload_dir . $novo_nome;

        // Move o arquivo para o diretório correto
        if (move_uploaded_file($_FILES['capa']['tmp_name'], $caminho_destino)) {
            $imagem_capa = $caminho_destino;
        }
    }

    // Inserir no banco de dados usando prepared statement
    $query = "INSERT INTO transmissao_agendada (titulo, descricao, url, data_transmissao, imagem_capa) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexao->prepare($query);
    
    if (!$stmt) {
        echo "<script>alert('Erro ao preparar a consulta.'); window.history.back();</script>";
        exit;
    }
    
    $stmt->bind_param("sssss", $titulo, $descricao, $url, $data_transmissao, $imagem_capa);
    
    if ($stmt->execute()) {
        echo "<script>alert('Transmissão agendada com sucesso!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Erro ao agendar a transmissão: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agendar Transmissão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>📅 Agendar Transmissão</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Título</label>
            <input type="text" class="form-control" name="titulo" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">URL da Live (YouTube)</label>
            <input type="text" class="form-control" name="url" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Imagem da Capa</label>
            <input type="file" class="form-control" name="capa" accept="image/*">
        </div>
        <div class="mb-3">
            <label class="form-label">Data e Hora da Transmissão</label>
            <input type="datetime-local" class="form-control" name="data_transmissao" required>
        </div>
        <button type="submit" class="btn btn-success">📌 Agendar Live</button>
    </form>
</body>
</html>
