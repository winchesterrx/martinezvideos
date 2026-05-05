<?php
include 'db/conexao.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $live_id = intval($_POST['live_id'] ?? 0);
    
    if (!$live_id) {
        echo json_encode(['success' => false, 'message' => 'ID da live inválido.']);
        exit;
    }

    // Atualiza o número de interessados usando prepared statement
    $query = "UPDATE transmissao_agendada SET interesse = interesse + 1 WHERE id = ?";
    $stmt_update = $conexao->prepare($query);
    
    if (!$stmt_update) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar atualização.']);
        exit;
    }
    
    $stmt_update->bind_param("i", $live_id);
    
    if ($stmt_update->execute()) {
        // Obtém o novo número de interessados usando prepared statement
        $stmt_select = $conexao->prepare("SELECT interesse FROM transmissao_agendada WHERE id = ?");
        $stmt_select->bind_param("i", $live_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $data = $result->fetch_assoc();
        $stmt_select->close();
        
        echo json_encode(['success' => true, 'interesse' => $data['interesse']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao registrar interesse: ' . $stmt_update->error]);
    }
    
    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>
