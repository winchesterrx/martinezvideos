<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário está logado E é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido.']);
    exit;
}

$query = "SELECT c.*, m.nome AS cidade_nome, e.sigla AS estado_sigla
          FROM clientes c
          LEFT JOIN municipio m ON c.municipio_id = m.id
          LEFT JOIN UF e ON c.estado_id = e.id
          WHERE c.id = ?";
$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode(['error' => 'Erro ao preparar consulta.']);
    exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    
    // Busca setores do cliente
    $query_setores = "SELECT s.id, s.nome 
                      FROM setores s
                      INNER JOIN cliente_setores cs ON s.id = cs.setor_id
                      WHERE cs.cliente_id = ?";
    $stmt_setores = $conexao->prepare($query_setores);
    $stmt_setores->bind_param('i', $id);
    $stmt_setores->execute();
    $result_setores = $stmt_setores->get_result();
    
    $setores = [];
    while ($row = $result_setores->fetch_assoc()) {
        $setores[] = $row;
    }
    $stmt_setores->close();
    
    $cliente['setores'] = $setores;
    
    // Remove a senha do retorno por segurança
    unset($cliente['senha']);
    
    echo json_encode($cliente);
} else {
    echo json_encode(['error' => 'Cliente não encontrado.']);
}

$stmt->close();
$conexao->close();
?>

