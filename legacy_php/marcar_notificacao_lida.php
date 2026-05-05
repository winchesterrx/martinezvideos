<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$notificacao_id = isset($_POST['notificacao_id']) ? intval($_POST['notificacao_id']) : null;
$marcar_todas = isset($_POST['marcar_todas']) ? $_POST['marcar_todas'] === 'true' : false;

try {
    if ($marcar_todas) {
        $query = "UPDATE notificacoes SET lida = 'S' WHERE usuario_id = ? AND lida = 'N'";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $usuario_id);
    } else {
        if (!$notificacao_id) {
            throw new Exception('ID da notificação não fornecido');
        }
        $query = "UPDATE notificacoes SET lida = 'S' WHERE id = ? AND usuario_id = ?";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("ii", $notificacao_id, $usuario_id);
    }
    
    $stmt->execute();
    $afetadas = $stmt->affected_rows;
    
    echo json_encode([
        'success' => true,
        'message' => $marcar_todas ? "Todas as notificações marcadas como lidas" : "Notificação marcada como lida",
        'afetadas' => $afetadas
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

