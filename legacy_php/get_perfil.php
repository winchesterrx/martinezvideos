<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Você precisa estar logado.']);
    exit;
}

$usuario_id = $_SESSION['user_id'];

// Verificar se a coluna telefone existe
$check_telefone = $conexao->query("SHOW COLUMNS FROM usuarios LIKE 'telefone'");
$has_telefone = $check_telefone && $check_telefone->num_rows > 0;

if ($has_telefone) {
    $query = "SELECT u.*, m.nome AS cidade_nome, e.sigla AS estado_sigla
              FROM usuarios u
              LEFT JOIN municipio m ON u.municipio_id = m.id
              LEFT JOIN UF e ON u.estado_id = e.id
              WHERE u.id = ?";
} else {
    $query = "SELECT u.id, u.nome, u.email, u.estado_id, u.municipio_id, u.ADM, m.nome AS cidade_nome, e.sigla AS estado_sigla
              FROM usuarios u
              LEFT JOIN municipio m ON u.municipio_id = m.id
              LEFT JOIN UF e ON u.estado_id = e.id
              WHERE u.id = ?";
}
$stmt = $conexao->prepare($query);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    
    // Remove a senha do retorno por segurança
    unset($usuario['senha']);
    
    echo json_encode($usuario);
} else {
    echo json_encode(['error' => 'Usuário não encontrado.']);
}

$stmt->close();
$conexao->close();
?>

