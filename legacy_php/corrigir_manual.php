<?php
/**
 * Script para correção MANUAL específica
 * Use este para corrigir casos específicos que o script automático não consegue
 */

session_start();
require_once 'db/conexao.php';

mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

header('Content-Type: text/html; charset=utf-8');

// Correções manuais específicas
$correcoes_manuais = [
    // Vídeo ID 103 (exemplo do problema)
    [
        'id' => 103,
        'titulo' => 'Atendimento Médico',
        'descricao' => 'video apresentação do novo consultório Médico'
    ],
    // Adicione mais correções aqui conforme necessário
];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Correção Manual</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Correção Manual de Dados</h1>";

$corrigidos = 0;

foreach ($correcoes_manuais as $correcao) {
    $update = "UPDATE videos SET titulo = ?, descricao = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexao, $update);
    mysqli_stmt_bind_param($stmt, "ssi", $correcao['titulo'], $correcao['descricao'], $correcao['id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $corrigidos++;
        echo "<div class='success'>✓ Vídeo ID {$correcao['id']} corrigido: '{$correcao['titulo']}'</div>";
    } else {
        echo "<div class='error'>✗ Erro ao corrigir vídeo ID {$correcao['id']}: " . mysqli_error($conexao) . "</div>";
    }
    mysqli_stmt_close($stmt);
}

echo "<div class='info'><strong>Total corrigido: {$corrigidos}</strong></div>";
echo "</div></body></html>";
?>

