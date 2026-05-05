<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json');

// Verifica se o usuário está logado E é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

if ($cliente_id <= 0) {
    echo json_encode(['error' => 'ID do cliente inválido.']);
    exit;
}

// Busca setores do cliente
$query = "SELECT s.id, s.nome, s.ativo 
          FROM setores s
          INNER JOIN cliente_setores cs ON s.id = cs.setor_id
          WHERE cs.cliente_id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param('i', $cliente_id);
$stmt->execute();
$result = $stmt->get_result();

$setores = [];
while ($row = $result->fetch_assoc()) {
    $setores[] = $row;
}

// Busca todos os setores disponíveis
$query_all = "SELECT id, nome, ativo FROM setores WHERE ativo = 'S' ORDER BY nome ASC";
$result_all = $conexao->query($query_all);
$todos_setores = [];
while ($row = $result_all->fetch_assoc()) {
    $todos_setores[] = $row;
}

echo json_encode([
    'setores_cliente' => $setores,
    'todos_setores' => $todos_setores
]);

$stmt->close();
$conexao->close();
?>

