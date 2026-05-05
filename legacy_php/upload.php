<?php
$diretorioDestino = "uploads/";

if (!is_dir($diretorioDestino)) {
    mkdir($diretorioDestino, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];
    if ($arquivo['error'] !== 0) {
        die("Erro ao enviar o arquivo.");
    }

    $nomeArquivo = time() . "_" . basename($arquivo['name']);
    $caminhoArquivo = $diretorioDestino . $nomeArquivo;

    $tiposPermitidos = ['application/pdf', 'image/png', 'image/jpeg'];

    if (in_array($arquivo['type'], $tiposPermitidos)) {
        if (move_uploaded_file($arquivo['tmp_name'], $caminhoArquivo)) {
            echo "<script>alert('Diploma enviado com sucesso!'); window.location.href='index.php';</script>";
        } else {
            die("Erro ao mover o arquivo.");
        }
    } else {
        die("Formato inválido.");
    }
}
?>
