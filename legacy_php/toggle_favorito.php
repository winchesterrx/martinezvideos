<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$tipo = isset($data['tipo']) ? $data['tipo'] : ''; // 'setor' ou 'modulo'
$item_id = isset($data['item_id']) ? intval($data['item_id']) : 0;

if (!in_array($tipo, ['setor', 'modulo']) || $item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar se já é favorito
    $check_query = "SELECT id FROM usuario_favoritos WHERE usuario_id = ? AND tipo = ? AND item_id = ?";
    $check_stmt = $conexao->prepare($check_query);
    $check_stmt->bind_param("isi", $usuario_id, $tipo, $item_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Remover favorito
        $delete_query = "DELETE FROM usuario_favoritos WHERE usuario_id = ? AND tipo = ? AND item_id = ?";
        $delete_stmt = $conexao->prepare($delete_query);
        $delete_stmt->bind_param("isi", $usuario_id, $tipo, $item_id);
        $delete_stmt->execute();
        
        echo json_encode(['success' => true, 'favorito' => false, 'message' => 'Removido dos favoritos'], JSON_UNESCAPED_UNICODE);
    } else {
        // Adicionar favorito
        $insert_query = "INSERT INTO usuario_favoritos (usuario_id, tipo, item_id) VALUES (?, ?, ?)";
        $insert_stmt = $conexao->prepare($insert_query);
        $insert_stmt->bind_param("isi", $usuario_id, $tipo, $item_id);
        $insert_stmt->execute();
        
        echo json_encode(['success' => true, 'favorito' => true, 'message' => 'Adicionado aos favoritos'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

