<?php
include 'db/conexao.php';

// 🔹 Verifica se há uma live ativa no banco
$query = "SELECT * FROM transmissao_ao_vivo WHERE ativo = 1 ORDER BY created_at DESC LIMIT 1";
$result = mysqli_query($conexao, $query);
$live = mysqli_fetch_assoc($result);

$response = [
    "live_ativa" => $live ? true : false,
    "titulo" => $live ? $live["titulo"] : ""
];

header("Content-Type: application/json");
echo json_encode($response);
?>
