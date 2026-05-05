<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'notificacoes' => [], 'nao_lidas' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 20;
$apenas_nao_lidas = isset($_GET['apenas_nao_lidas']) ? $_GET['apenas_nao_lidas'] === 'true' : false;

try {
    $where = "usuario_id = ?";
    $params = [$usuario_id];
    $types = "i";
    
    if ($apenas_nao_lidas) {
        $where .= " AND lida = 'N'";
    }
    
    $query = "SELECT * FROM notificacoes 
              WHERE $where
              ORDER BY created_at DESC
              LIMIT ?";
    
    $params[] = $limite;
    $types .= "i";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notificacoes = [];
    while ($row = $result->fetch_assoc()) {
        $notificacoes[] = $row;
    }
    
    // Contar não lidas
    $count_query = "SELECT COUNT(*) as total FROM notificacoes WHERE usuario_id = ? AND lida = 'N'";
    $count_stmt = $conexao->prepare($count_query);
    $count_stmt->bind_param("i", $usuario_id);
    $count_stmt->execute();
    $nao_lidas = $count_stmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'notificacoes' => $notificacoes,
        'nao_lidas' => intval($nao_lidas)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'notificacoes' => [],
        'nao_lidas' => 0,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

