<?php
include 'db/conexao.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT capa FROM transmissao_agendada WHERE id = $id";
    $result = mysqli_query($conexao, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row && !empty($row['capa'])) {
        header("Content-Type: image/jpeg");
        echo $row['capa'];
        exit;
    }
}

// Caso não tenha imagem, exibir uma padrão
header("Content-Type: image/jpeg");
readfile("img/default_live.jpg");
?>
