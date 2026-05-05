<?php
/**
 * Endpoint AJAX para buscar municípios por estado
 * Retorna JSON com lista de municípios do estado selecionado
 */

include 'db/conexao.php';
header('Content-Type: application/json; charset=utf-8');

// Verificar se foi enviado o ID do estado
if (!isset($_GET['estado_id']) || empty($_GET['estado_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID do estado não fornecido']);
    exit;
}

$estado_id = intval($_GET['estado_id']);

if ($estado_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID do estado inválido']);
    exit;
}

// Verificar estrutura da tabela municipio
$queryCheck = $conexao->query("SHOW COLUMNS FROM municipio");
$estadoColumn = null;

if ($queryCheck) {
    while ($column = $queryCheck->fetch_assoc()) {
        $columnName = strtolower($column['Field']);
        // Verificar se a coluna contém 'estado' ou 'uf' no nome
        if (strpos($columnName, 'estado') !== false || strpos($columnName, 'uf') !== false) {
            // Verificar se não é a coluna 'id' que pode conter 'estado' no nome
            if ($columnName !== 'id') {
                $estadoColumn = $column['Field'];
                break;
            }
        }
    }
}

// Se não encontrou, tenta os nomes mais comuns
if (!$estadoColumn) {
    $testColumns = ['estado_id', 'uf_id', 'id_estado', 'id_uf'];
    foreach ($testColumns as $col) {
        $testQuery = $conexao->query("SELECT $col FROM municipio LIMIT 1");
        if ($testQuery) {
            $estadoColumn = $col;
            break;
        }
    }
}

// Se ainda não encontrou, usa estado_id como padrão
if (!$estadoColumn) {
    $estadoColumn = 'estado_id';
}

// Buscar municípios do estado usando prepared statement
$query = "SELECT id, nome FROM municipio WHERE $estadoColumn = ? ORDER BY nome ASC";
$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar consulta: ' . $conexao->error]);
    exit;
}

$stmt->bind_param("i", $estado_id);
$stmt->execute();
$result = $stmt->get_result();

$municipios = [];
while ($row = $result->fetch_assoc()) {
    $municipios[] = [
        'id' => intval($row['id']),
        'nome' => $row['nome']
    ];
}

$stmt->close();

// Retorna array de municípios
echo json_encode($municipios);

