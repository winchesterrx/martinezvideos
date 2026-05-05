<?php
// Limpa qualquer output
while (ob_get_level()) {
    ob_end_clean();
}

// Desabilita exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'db/conexao.php';

// Configura charset para UTF-8 (utf8mb4) para suportar caracteres especiais e emojis
mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
mysqli_query($conexao, "SET CHARACTER SET utf8mb4");
mysqli_query($conexao, "SET character_set_connection=utf8mb4");
mysqli_query($conexao, "SET character_set_client=utf8mb4");
mysqli_query($conexao, "SET character_set_results=utf8mb4");

header('Content-Type: application/json; charset=utf-8');

$setor_id = isset($_GET['setor_id']) ? intval($_GET['setor_id']) : 0;
$modulo_id = isset($_GET['modulo_id']) && $_GET['modulo_id'] !== '' ? intval($_GET['modulo_id']) : null;

if ($setor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Setor inválido', 'sequencias' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verifica se conexão existe
if (!isset($conexao) || !$conexao) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão', 'sequencias' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

// Query para buscar sequências
// Busca sequências do setor (e módulo se especificado)
// Inclui sequências mesmo que não tenham vídeos ainda
$where = "WHERE s.setor_id = ?";
$params = [$setor_id];
$types = "i";

if ($modulo_id && $modulo_id > 0) {
    // Se especificou módulo, busca sequências desse módulo OU sem módulo específico
    $where .= " AND (s.modulo_id = ? OR s.modulo_id IS NULL)";
    $params[] = $modulo_id;
    $types .= "i";
}
// Se não especificou módulo, busca todas as sequências do setor (com ou sem módulo)

$query = "SELECT s.id, s.titulo, 
                 COUNT(DISTINCT v.id) as total_videos 
          FROM sequencias s 
          LEFT JOIN videos v ON v.sequencia_id = s.id AND v.is_sequencia = 1
          $where 
          GROUP BY s.id, s.titulo
          ORDER BY s.titulo ASC";

$stmt = mysqli_prepare($conexao, $query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar query', 'sequencias' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Erro ao executar query', 'sequencias' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = mysqli_stmt_get_result($stmt);
$sequencias = [];

while ($row = mysqli_fetch_assoc($result)) {
    $sequencias[] = [
        'id' => intval($row['id']),
        'titulo' => $row['titulo'],
        'total_videos' => intval($row['total_videos'])
    ];
}

mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'message' => 'OK', 'sequencias' => $sequencias], JSON_UNESCAPED_UNICODE);
exit;
?>

