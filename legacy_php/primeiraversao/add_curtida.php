<?php
include 'db/conexao.php';

if (isset($_POST['video_id']) && isset($_POST['usuario_id'])) {
    $video_id = (int)$_POST['video_id'];
    $usuario_id = (int)$_POST['usuario_id'];

    // Adiciona a curtida se não existir
    $sql = "INSERT IGNORE INTO curtidas (video_id, usuario_id) VALUES (?, ?)";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ii", $video_id, $usuario_id);
    $stmt->execute();

    // Atualiza a contagem de curtidas na tabela de vídeos
    $sql_update = "UPDATE videos SET curtidas = (SELECT COUNT(*) FROM curtidas WHERE video_id = ?) WHERE id = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("ii", $video_id, $video_id);
    $stmt_update->execute();

    // Obtém a nova contagem de curtidas para retornar
    $sql_count = "SELECT curtidas FROM videos WHERE id = ?";
    $stmt_count = $conexao->prepare($sql_count);
    $stmt_count->bind_param("i", $video_id);
    $stmt_count->execute();
    $result = $stmt_count->get_result();
    $curtidas = $result->fetch_assoc()['curtidas'];

    echo json_encode(['curtidas' => $curtidas]);
} else {
    echo json_encode(['error' => 'Parâmetros inválidos']);
}

$conexao->close();
?>
