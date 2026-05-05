<?php
session_start();
if (!isset($_SESSION['user_adm']) || $_SESSION['user_adm'] != 1) {
    die("Acesso negado!");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmissão Ao Vivo</title>
</head>
<body>
    <h1>Transmissão Ao Vivo</h1>
    <p>Sua transmissão está ativa!</p>

    <!-- Embed de YouTube Live (Exemplo) -->
    <iframe width="560" height="315" src="https://www.youtube.com/embed/live_stream?channel=SEU_CANAL_ID" frameborder="0" allowfullscreen></iframe>

    <!-- Botão para finalizar a live -->
    <button onclick="window.location.href='index.php'" style="background: red; color: white; padding: 10px; border: none; cursor: pointer;">Encerrar Transmissão</button>
</body>
</html>
