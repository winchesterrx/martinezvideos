<?php
// Teste simples para verificar se get_modulos.php está funcionando
session_start();
require_once 'db/conexao.php';

$setor_id = 7; // ID do setor "Saúde" para teste

echo "<h2>Teste de Módulos - Setor ID: $setor_id</h2>";

// Verifica conexão
if (!isset($conexao) || !$conexao) {
    die("Erro: Conexão não estabelecida");
}

echo "<p>✓ Conexão OK</p>";

// Query
$query = "SELECT id, nome, icone, cor FROM modulos WHERE setor_id = ? AND ativo = 'S' ORDER BY nome ASC";
$stmt = mysqli_prepare($conexao, $query);

if (!$stmt) {
    die("Erro ao preparar: " . mysqli_error($conexao));
}

echo "<p>✓ Query preparada</p>";

mysqli_stmt_bind_param($stmt, "i", $setor_id);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao executar: " . mysqli_stmt_error($stmt));
}

echo "<p>✓ Query executada</p>";

$result = mysqli_stmt_get_result($stmt);
$modulos = [];

while ($row = mysqli_fetch_assoc($result)) {
    $modulos[] = $row;
}

mysqli_stmt_close($stmt);

echo "<h3>Módulos encontrados: " . count($modulos) . "</h3>";

if (count($modulos) > 0) {
    echo "<ul>";
    foreach ($modulos as $modulo) {
        echo "<li>ID: {$modulo['id']} - Nome: {$modulo['nome']} - Ícone: {$modulo['icone']} - Cor: {$modulo['cor']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Nenhum módulo encontrado para este setor!</p>";
}

echo "<hr>";
echo "<h3>Teste JSON:</h3>";
$json = json_encode(['success' => true, 'modulos' => $modulos], JSON_UNESCAPED_UNICODE);
echo "<pre>" . htmlspecialchars($json) . "</pre>";
?>

