<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stream_url = $_POST['stream_url'];
    
    // Atualiza o banco de dados com a URL da live
    $query = "INSERT INTO live_stream (is_live, stream_url) VALUES (TRUE, ?)";
    $stmt = mysqli_prepare($conexao, $query);
    mysqli_stmt_bind_param($stmt, "s", $stream_url);
    mysqli_stmt_execute($stmt);
    
    echo json_encode(["success" => true]);
    exit();
}
?>
