<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json');

// VERIFICAÇÃO DE SEGURANÇA: Apenas administradores podem resetar senhas
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    $nova_senha = trim($_POST['nova_senha'] ?? '');
    
    if ($cliente_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID do cliente inválido.']);
        exit;
    }
    
    // Se não forneceu senha, gera uma senha aleatória
    if (empty($nova_senha)) {
        $nova_senha = bin2hex(random_bytes(8)); // Gera senha aleatória de 16 caracteres
    }
    
    // Criptografa a senha
    $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT);
    
    $sql = "UPDATE clientes SET senha = ? WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Erro ao preparar consulta: ' . $conexao->error]);
        exit;
    }
    
    $stmt->bind_param('si', $senha_hash, $cliente_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Senha resetada com sucesso!',
            'nova_senha' => $nova_senha // Retorna a senha gerada para exibir ao admin
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao resetar senha: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
}

$conexao->close();
?>

