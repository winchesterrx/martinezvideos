<?php
/**
 * Script PHP para corrigir dados existentes com encoding incorreto
 * Execute este arquivo uma vez através do navegador ou linha de comando
 * 
 * IMPORTANTE: Faça backup do banco antes de executar!
 */

session_start();
require_once 'db/conexao.php';

// Configura charset
mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Correção de Encoding</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Correção de Encoding de Dados</h1>";

// Função para corrigir encoding
function corrigirEncoding($texto, $conexao) {
    if (empty($texto)) return $texto;
    
    // Se contém caracteres corrompidos (ex: "MÃ©dico", "apresentaÃ§Ã£o")
    if (strpos($texto, 'Ã') !== false) {
        // Tenta converter de latin1/iso-8859-1 para utf8
        // Primeiro, força o texto a ser interpretado como latin1
        $texto_latin1 = mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
        // Depois converte para UTF-8
        $texto_corrigido = mb_convert_encoding($texto_latin1, 'UTF-8', 'ISO-8859-1');
        
        // Se ainda tiver problemas, tenta método alternativo
        if (strpos($texto_corrigido, 'Ã') !== false) {
            // Método alternativo: usar iconv
            $texto_corrigido = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $texto);
            if ($texto_corrigido === false) {
                // Último recurso: usar utf8_encode (para dados latin1)
                $texto_corrigido = utf8_encode($texto);
            }
        }
        
        return $texto_corrigido;
    }
    
    return $texto;
}

$total_corrigidos = 0;
$erros = [];

try {
    // 1. Corrige videos
    echo "<div class='info'><strong>1. Corrigindo tabela videos...</strong></div>";
    $query = "SELECT id, titulo, descricao FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $videos_corrigidos = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $titulo_corrigido = corrigirEncoding($row['titulo'], $conexao);
        $descricao_corrigida = corrigirEncoding($row['descricao'], $conexao);
        
        $update = "UPDATE videos SET titulo = ?, descricao = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $update);
        mysqli_stmt_bind_param($stmt, "ssi", $titulo_corrigido, $descricao_corrigida, $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $videos_corrigidos++;
            echo "<div class='success'>✓ Vídeo ID {$row['id']}: '{$row['titulo']}' → '{$titulo_corrigido}'</div>";
        } else {
            $erros[] = "Erro ao corrigir vídeo ID {$row['id']}: " . mysqli_error($conexao);
        }
        mysqli_stmt_close($stmt);
    }
    $total_corrigidos += $videos_corrigidos;
    echo "<div class='info'>Total de vídeos corrigidos: {$videos_corrigidos}</div>";
    
    // 2. Corrige comentarios
    echo "<div class='info'><strong>2. Corrigindo tabela comentarios...</strong></div>";
    $query = "SELECT id, conteudo FROM comentarios WHERE conteudo LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $comentarios_corrigidos = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $conteudo_corrigido = corrigirEncoding($row['conteudo'], $conexao);
        
        $update = "UPDATE comentarios SET conteudo = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $update);
        mysqli_stmt_bind_param($stmt, "si", $conteudo_corrigido, $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $comentarios_corrigidos++;
        } else {
            $erros[] = "Erro ao corrigir comentário ID {$row['id']}: " . mysqli_error($conexao);
        }
        mysqli_stmt_close($stmt);
    }
    $total_corrigidos += $comentarios_corrigidos;
    echo "<div class='info'>Total de comentários corrigidos: {$comentarios_corrigidos}</div>";
    
    // 3. Corrige respostas
    echo "<div class='info'><strong>3. Corrigindo tabela respostas...</strong></div>";
    $query = "SELECT id, conteudo FROM respostas WHERE conteudo LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $respostas_corrigidas = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $conteudo_corrigido = corrigirEncoding($row['conteudo'], $conexao);
        
        $update = "UPDATE respostas SET conteudo = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $update);
        mysqli_stmt_bind_param($stmt, "si", $conteudo_corrigido, $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $respostas_corrigidas++;
        } else {
            $erros[] = "Erro ao corrigir resposta ID {$row['id']}: " . mysqli_error($conexao);
        }
        mysqli_stmt_close($stmt);
    }
    $total_corrigidos += $respostas_corrigidas;
    echo "<div class='info'>Total de respostas corrigidas: {$respostas_corrigidas}</div>";
    
    // 4. Corrige setores
    echo "<div class='info'><strong>4. Corrigindo tabela setores...</strong></div>";
    $query = "SELECT id, nome FROM setores WHERE nome LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $setores_corrigidos = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $nome_corrigido = corrigirEncoding($row['nome'], $conexao);
        
        $update = "UPDATE setores SET nome = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $update);
        mysqli_stmt_bind_param($stmt, "si", $nome_corrigido, $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $setores_corrigidos++;
            echo "<div class='success'>✓ Setor ID {$row['id']}: '{$row['nome']}' → '{$nome_corrigido}'</div>";
        } else {
            $erros[] = "Erro ao corrigir setor ID {$row['id']}: " . mysqli_error($conexao);
        }
        mysqli_stmt_close($stmt);
    }
    $total_corrigidos += $setores_corrigidos;
    echo "<div class='info'>Total de setores corrigidos: {$setores_corrigidos}</div>";
    
    // 5. Corrige modulos
    echo "<div class='info'><strong>5. Corrigindo tabela modulos...</strong></div>";
    $query = "SELECT id, nome, descricao FROM modulos WHERE nome LIKE '%Ã%' OR descricao LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $modulos_corrigidos = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $nome_corrigido = corrigirEncoding($row['nome'], $conexao);
        $descricao_corrigida = corrigirEncoding($row['descricao'] ?? '', $conexao);
        
        $update = "UPDATE modulos SET nome = ?, descricao = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexao, $update);
        mysqli_stmt_bind_param($stmt, "ssi", $nome_corrigido, $descricao_corrigida, $row['id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $modulos_corrigidos++;
            echo "<div class='success'>✓ Módulo ID {$row['id']}: '{$row['nome']}' → '{$nome_corrigido}'</div>";
        } else {
            $erros[] = "Erro ao corrigir módulo ID {$row['id']}: " . mysqli_error($conexao);
        }
        mysqli_stmt_close($stmt);
    }
    $total_corrigidos += $modulos_corrigidos;
    echo "<div class='info'>Total de módulos corrigidos: {$modulos_corrigidos}</div>";
    
    // Resumo
    echo "<div class='success'><strong>✅ Correção concluída!</strong></div>";
    echo "<div class='info'><strong>Total de registros corrigidos: {$total_corrigidos}</strong></div>";
    
    if (!empty($erros)) {
        echo "<div class='error'><strong>Erros encontrados:</strong><pre>" . implode("\n", $erros) . "</pre></div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>

