<?php
// Limpa qualquer output
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
require_once 'db/conexao.php';

// Configura charset
mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$video_id = isset($data['video_id']) ? intval($data['video_id']) : 0;
$tempo_assistido = isset($data['tempo_assistido']) ? intval($data['tempo_assistido']) : 0;
$completou = isset($data['completou']) ? ($data['completou'] ? 1 : 0) : 0;

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if ($video_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de vídeo inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Buscar informações do vídeo
    $query_video = "SELECT setor_id, modulo_id FROM videos WHERE id = ?";
    $stmt_video = $conexao->prepare($query_video);
    $stmt_video->bind_param("i", $video_id);
    $stmt_video->execute();
    $result_video = $stmt_video->get_result();
    
    if ($result_video->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Vídeo não encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $video_data = $result_video->fetch_assoc();
    $setor_id = $video_data['setor_id'];
    $modulo_id = $video_data['modulo_id'];
    $stmt_video->close();
    
    // Verificar se já existe registro de histórico para este vídeo e usuário
    if ($usuario_id) {
        $query_check = "SELECT id FROM usuario_historico WHERE usuario_id = ? AND video_id = ?";
        $stmt_check = $conexao->prepare($query_check);
        $stmt_check->bind_param("ii", $usuario_id, $video_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Atualiza registro existente
            $query_update = "UPDATE usuario_historico 
                            SET tempo_assistido = GREATEST(tempo_assistido, ?), 
                                completou = ?,
                                visualizado_em = NOW()
                            WHERE usuario_id = ? AND video_id = ?";
            $stmt_update = $conexao->prepare($query_update);
            $stmt_update->bind_param("iiii", $tempo_assistido, $completou, $usuario_id, $video_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Insere novo registro
            $query_insert = "INSERT INTO usuario_historico (usuario_id, video_id, setor_id, modulo_id, tempo_assistido, completou) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conexao->prepare($query_insert);
            $stmt_insert->bind_param("iiiiii", $usuario_id, $video_id, $setor_id, $modulo_id, $tempo_assistido, $completou);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Visualização registrada'], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

