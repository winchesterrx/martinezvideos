<?php
include 'db/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['video'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $setor_id = $_POST['setor_id']; // Recebendo o setor_id selecionado
    $target_dir = "uploads/";
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $video_name = basename($_FILES["video"]["name"]);
    $target_file = $target_dir . $video_name;

    if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO videos (titulo, descricao, url_video, setor_id) VALUES ('$titulo', '$descricao', '$target_file', $setor_id)";
        if (mysqli_query($conexao, $sql)) {
            $id = mysqli_insert_id($conexao); // ID do novo vídeo
            echo json_encode([
                "success" => true,
                "id" => $id,
                "titulo" => $titulo,
                "descricao" => $descricao,
                "url_video" => $target_file,
                "setor_id" => $setor_id,
                "data_upload" => date("Y-m-d H:i:s")
            ]);
        } else {
            echo json_encode(["success" => false, "error" => mysqli_error($conexao)]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Erro ao mover o arquivo de vídeo."]);
    }
}
?>
