<?php
include 'db/conexao.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $live_id = intval($_POST['live_id'] ?? 0);
    
    if (!$live_id) {
        echo json_encode(['success' => false, 'message' => 'ID da live inválido.']);
        exit;
    }

    // Verifica se o usuário já marcou interesse usando prepared statement
    $check_query = "SELECT interesse FROM transmissao_agendada WHERE id = ?";
    $stmt_check = $conexao->prepare($check_query);
    
    if (!$stmt_check) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta.']);
        exit;
    }
    
    $stmt_check->bind_param("i", $live_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $live = $result->fetch_assoc();
    $stmt_check->close();

    if (!$live) {
        echo json_encode(['success' => false, 'message' => 'Live não encontrada.']);
        exit;
    }

    if (isset($_POST['remove']) && $live['interesse'] > 0) {
        // Remover interesse usando prepared statement
        $update_query = "UPDATE transmissao_agendada SET interesse = interesse - 1 WHERE id = ?";
        $new_status = false;
    } else {
        // Adicionar interesse usando prepared statement
        $update_query = "UPDATE transmissao_agendada SET interesse = interesse + 1 WHERE id = ?";
        $new_status = true;
    }

    $stmt_update = $conexao->prepare($update_query);
    if (!$stmt_update) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar atualização.']);
        exit;
    }
    
    $stmt_update->bind_param("i", $live_id);
    
    if ($stmt_update->execute()) {
        // Busca o novo valor usando prepared statement
        $stmt_select = $conexao->prepare("SELECT interesse FROM transmissao_agendada WHERE id = ?");
        $stmt_select->bind_param("i", $live_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $data = $result->fetch_assoc();
        $stmt_select->close();
        
        echo json_encode(['success' => true, 'interesse' => $data['interesse'], 'status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar interesse: ' . $stmt_update->error]);
    }
    
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>
