<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você precisa estar logado.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $estado_id = intval($_POST['estado_id'] ?? 0);
    $municipio_id = intval($_POST['municipio_id'] ?? 0);
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Nome e email são obrigatórios.']);
        exit;
    }

    // Verificar se o email já está em uso por outro usuário
    $check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $stmt_check = $conexao->prepare($check_email);
    $stmt_check->bind_param('si', $email, $usuario_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        echo json_encode(['success' => false, 'error' => 'Este email já está em uso por outro usuário.']);
        exit;
    }
    $stmt_check->close();

    // Se tentou alterar senha, verificar senha atual
    if (!empty($nova_senha)) {
        if (empty($senha_atual)) {
            echo json_encode(['success' => false, 'error' => 'Para alterar a senha, informe a senha atual.']);
            exit;
        }

        if ($nova_senha !== $confirmar_senha) {
            echo json_encode(['success' => false, 'error' => 'As senhas não coincidem.']);
            exit;
        }

        // Verificar senha atual
        $check_senha = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt_senha = $conexao->prepare($check_senha);
        $stmt_senha->bind_param('i', $usuario_id);
        $stmt_senha->execute();
        $result_senha = $stmt_senha->get_result();
        $user_data = $result_senha->fetch_assoc();
        $stmt_senha->close();

        if (!password_verify($senha_atual, $user_data['senha'])) {
            echo json_encode(['success' => false, 'error' => 'Senha atual incorreta.']);
            exit;
        }

        // Atualizar com nova senha
        $senha_hash = password_hash($nova_senha, PASSWORD_BCRYPT);
        
        // Verificar se a coluna telefone existe
        $check_telefone = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'telefone'");
        $has_telefone = $check_telefone && $check_telefone->num_rows > 0;
        
        if ($has_telefone) {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, estado_id = ?, municipio_id = ?, senha = ? WHERE id = ?";
            $stmt = $conexao->prepare($sql);
            
            $estado_val = $estado_id > 0 ? $estado_id : null;
            $municipio_val = $municipio_id > 0 ? $municipio_id : null;
            $telefone_val = !empty($telefone) ? $telefone : null;
            
            $stmt->bind_param('sssiisi', $nome, $email, $telefone_val, $estado_val, $municipio_val, $senha_hash, $usuario_id);
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, estado_id = ?, municipio_id = ?, senha = ? WHERE id = ?";
            $stmt = $conexao->prepare($sql);
            
            $estado_val = $estado_id > 0 ? $estado_id : null;
            $municipio_val = $municipio_id > 0 ? $municipio_id : null;
            
            $stmt->bind_param('ssiisi', $nome, $email, $estado_val, $municipio_val, $senha_hash, $usuario_id);
        }
    } else {
        // Atualizar sem alterar senha
        // Verificar se a coluna telefone existe
        $check_telefone = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'telefone'");
        $has_telefone = $check_telefone && $check_telefone->num_rows > 0;
        
        if ($has_telefone) {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, estado_id = ?, municipio_id = ? WHERE id = ?";
            $stmt = $conexao->prepare($sql);
            
            $estado_val = $estado_id > 0 ? $estado_id : null;
            $municipio_val = $municipio_id > 0 ? $municipio_id : null;
            $telefone_val = !empty($telefone) ? $telefone : null;
            
            $stmt->bind_param('sssiii', $nome, $email, $telefone_val, $estado_val, $municipio_val, $usuario_id);
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, estado_id = ?, municipio_id = ? WHERE id = ?";
            $stmt = $conexao->prepare($sql);
            
            $estado_val = $estado_id > 0 ? $estado_id : null;
            $municipio_val = $municipio_id > 0 ? $municipio_id : null;
            
            $stmt->bind_param('ssiii', $nome, $email, $estado_val, $municipio_val, $usuario_id);
        }
    }

    if ($stmt->execute()) {
        // Atualizar sessão
        $_SESSION['user_nome'] = $nome;
        
        echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar perfil: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
}

$conexao->close();
?>

