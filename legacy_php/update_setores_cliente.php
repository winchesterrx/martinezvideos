<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json');

// VERIFICAÇÃO DE SEGURANÇA: Apenas administradores podem gerenciar setores
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    $setores = isset($_POST['setores']) ? $_POST['setores'] : [];
    
    if ($cliente_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID do cliente inválido.']);
        exit;
    }
    
    // Remove todos os setores atuais do cliente
    $delete_query = "DELETE FROM cliente_setores WHERE cliente_id = ?";
    $stmt_delete = $conexao->prepare($delete_query);
    $stmt_delete->bind_param('i', $cliente_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    // Insere os novos setores
    if (!empty($setores) && is_array($setores)) {
        $insert_query = "INSERT INTO cliente_setores (cliente_id, setor_id) VALUES (?, ?)";
        $stmt_insert = $conexao->prepare($insert_query);
        
        foreach ($setores as $setor_id) {
            $setor_id = intval($setor_id);
            if ($setor_id > 0) {
                $stmt_insert->bind_param('ii', $cliente_id, $setor_id);
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Setores atualizados com sucesso!']);
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
}

$conexao->close();
?>

