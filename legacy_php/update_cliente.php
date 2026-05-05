<?php
session_start();
include 'db/conexao.php';
$conexao->set_charset("utf8");

header('Content-Type: application/json');

// VERIFICAÇÃO DE SEGURANÇA: Apenas administradores podem editar clientes
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $estado_id = intval($_POST['estado_id'] ?? 0);
    $municipio_id = intval($_POST['municipio_id'] ?? 0);
    $ativo = trim($_POST['ativo'] ?? 'S');

    if ($id <= 0 || empty($nome)) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
        exit;
    }

    // Prepara valores NULL para estado e municipio se não informados
    $estado_val = $estado_id > 0 ? $estado_id : null;
    $municipio_val = $municipio_id > 0 ? $municipio_id : null;
    
    // Query dinâmica para lidar com NULLs
    $sql = "UPDATE clientes SET nome = ?, email = ?, telefone = ?, cpf_cnpj = ?, endereco = ?, observacoes = ?, ativo = ?";
    $params = [$nome, $email, $telefone, $cpf_cnpj, $endereco, $observacoes, $ativo];
    $types = 'sssssss';
    
    if ($estado_val !== null) {
        $sql .= ", estado_id = ?";
        $params[] = $estado_val;
        $types .= 'i';
    } else {
        $sql .= ", estado_id = NULL";
    }
    
    if ($municipio_val !== null) {
        $sql .= ", municipio_id = ?";
        $params[] = $municipio_val;
        $types .= 'i';
    } else {
        $sql .= ", municipio_id = NULL";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Erro ao preparar consulta: ' . $conexao->error]);
        exit;
    }
    
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar cliente: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
}

$conexao->close();
?>

