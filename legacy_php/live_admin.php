<?php
include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $streamKey = bin2hex(random_bytes(10)); // Gera uma chave aleatória para a live
    $query = "UPDATE live_status SET is_live = 1, stream_key = '$streamKey' WHERE id = 1";
    mysqli_query($conexao, $query);
    echo json_encode(["success" => true, "stream_key" => $streamKey]);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>🎥 Iniciar Transmissão</h2>
    <button id="startLiveBtn">Iniciar Live</button>
    <button id="stopLiveBtn" style="display:none;">⛔ Encerrar Live</button>
    <button id="shareScreenBtn" style="display:none;">📺 Compartilhar Tela</button>
    <button id="muteAudioBtn" style="display:none;">🔇 Mutar Áudio</button>

    <video id="localVideo" autoplay muted playsinline></video>

    <script src="script.js"></script>
</body>
</html>
