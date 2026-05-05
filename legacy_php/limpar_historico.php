<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'todos'; // 'continuar', 'completos', 'todos'

try {
    if ($tipo === 'todos') {
        $query = "DELETE FROM usuario_historico WHERE usuario_id = ?";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $usuario_id);
    } elseif ($tipo === 'continuar') {
        $query = "DELETE FROM usuario_historico WHERE usuario_id = ? AND completou = 0";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $usuario_id);
    } elseif ($tipo === 'completos') {
        $query = "DELETE FROM usuario_historico WHERE usuario_id = ? AND completou = 1";
        $stmt = $conexao->prepare($query);
        $stmt->bind_param("i", $usuario_id);
    }
    
    $stmt->execute();
    $afetados = $stmt->affected_rows;
    
    echo json_encode([
        'success' => true,
        'message' => "$afetados registro(s) removido(s)",
        'removidos' => $afetados
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

