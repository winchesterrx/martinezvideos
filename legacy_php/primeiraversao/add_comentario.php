<?php
include 'db/conexao.php';

if (isset($_POST['video_id']) && isset($_POST['conteudo'])) {
    $video_id = (int)$_POST['video_id'];
    $conteudo = mysqli_real_escape_string($conexao, $_POST['conteudo']);

    // Insere o novo comentário
    $sql = "INSERT INTO comentarios (video_id, conteudo) VALUES ($video_id, '$conteudo')";
    mysqli_query($conexao, $sql);

    // Retorna o comentário com o formato de data atual
    echo json_encode([
        'conteudo' => $conteudo,
        'data' => date("Y-m-d H:i:s")
    ]);
}

mysqli_close($conexao);
?>
