<?php
include 'db/conexao.php';
include 'functions.php'; // Inclui a função de log

session_start();

header('Content-Type: application/json');

if (isset($_POST['video_id']) && isset($_SESSION['usuario_id'])) {
    $video_id = (int)$_POST['video_id'];
    $usuario_id = (int)$_SESSION['usuario_id'];

    // Remove a curtida
    $sql_delete = "DELETE FROM curtidas WHERE video_id = ? AND usuario_id = ?";
    $stmt_delete = $conexao->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $video_id, $usuario_id);
    $stmt_delete->execute();

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
    echo json_encode(['error' => 'Parâmetros inválidos ou usuário não autenticado']);
}

$conexao->close();
?>
