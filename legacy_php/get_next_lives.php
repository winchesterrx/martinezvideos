<?php
include 'db/conexao.php';

$query = "SELECT id, titulo, descricao, data_transmissao, imagem_capa, url 
          FROM proximas_lives 
          WHERE data_transmissao > NOW() 
          ORDER BY data_transmissao ASC 
          LIMIT 10";

$result = mysqli_query($conexao, $query);
$lives = [];

while ($row = mysqli_fetch_assoc($result)) {
    $lives[] = $row;
}

echo json_encode($lives);
?>
