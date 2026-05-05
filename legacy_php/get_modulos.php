<?php
// Limpa qualquer output
while (ob_get_level()) {
    ob_end_clean();
}

// Desabilita exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Inclui conexão
require_once 'db/conexao.php';

// Define header JSON
header('Content-Type: application/json; charset=utf-8');

// Pega setor_id
$setor_id = isset($_GET['setor_id']) ? intval($_GET['setor_id']) : 0;

if ($setor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Setor inválido', 'modulos' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verifica se conexão existe
if (!isset($conexao) || !$conexao) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão', 'modulos' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Define charset para UTF-8 (utf8mb4) para suportar caracteres especiais e emojis
mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
mysqli_query($conexao, "SET CHARACTER SET utf8mb4");
mysqli_query($conexao, "SET character_set_connection=utf8mb4");
mysqli_query($conexao, "SET character_set_client=utf8mb4");
mysqli_query($conexao, "SET character_set_results=utf8mb4");

// Query
$query = "SELECT id, nome, icone, cor FROM modulos WHERE setor_id = ? AND ativo = 'S' ORDER BY nome ASC";
$stmt = mysqli_prepare($conexao, $query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar query', 'modulos' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $setor_id);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Erro ao executar query', 'modulos' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$modulos = [];

while ($row = mysqli_fetch_assoc($result)) {
    $modulos[] = [
        'id' => intval($row['id']),
        'nome' => $row['nome'],
        'icone' => isset($row['icone']) ? $row['icone'] : 'fas fa-cube',
        'cor' => isset($row['cor']) ? $row['cor'] : '#6366f1'
    ];
}

mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'message' => 'OK', 'modulos' => $modulos], JSON_UNESCAPED_UNICODE);
exit;
