<?php
/**
 * Script V2 - Correção INTELIGENTE de encoding
 * Detecta e corrige corrupção dupla/tripla de encoding
 */

session_start();
require_once 'db/conexao.php';

mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Correção de Encoding V2</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .test { margin: 10px 0; padding: 10px; border-left: 3px solid #007bff; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Correção de Encoding V2 - Método Inteligente</h1>";

// Função para corrigir encoding de forma inteligente
function corrigirEncodingInteligente($texto) {
    if (empty($texto)) return $texto;
    
    $original = $texto;
    
    // Padrões de corrupção comuns e suas correções
    $correcoes = [
        // Corrupção simples
        'MÃ©dico' => 'Médico',
        'MÃ©' => 'é',
        'Ã©' => 'é',
        'Ã§' => 'ç',
        'Ã£' => 'ã',
        'Ã³' => 'ó',
        'Ã¡' => 'á',
        'Ã­' => 'í',
        'Ãº' => 'ú',
        'Ã' => 'à',
        'Ãª' => 'ê',
        'Ã´' => 'ô',
        'Ãµ' => 'õ',
        'Ã' => 'À',
        'Ã‰' => 'É',
        'Ã' => 'Í',
        'Ã"' => 'Ó',
        'Ãš' => 'Ú',
        'Ã‡' => 'Ç',
        
        // Corrupção dupla/tripla (mais comum)
        'MÃ Ã©dico' => 'Médico',
        'MÃ Ã Ã Â©dico' => 'Médico',
        'apresentaÃ§Ã£o' => 'apresentação',
        'apresentaÃ¤ÂÃ Â§Ã¤Ã¤Ã¤Â£o' => 'apresentação',
        'consultÃ³rio' => 'consultório',
        'consultÃ Ã Ã Â³rio' => 'consultório',
        
        // Padrões específicos comuns
        'Ã§Ã£o' => 'ção',
        'Ã£o' => 'ão',
        'Ã§' => 'ç',
    ];
    
    // Primeiro, tenta correções diretas de padrões conhecidos
    foreach ($correcoes as $errado => $correto) {
        $texto = str_replace($errado, $correto, $texto);
    }
    
    // Se ainda tem "Ã", tenta métodos de conversão
    if (strpos($texto, 'Ã') !== false) {
        // Método 1: Tenta interpretar como latin1 e converter para utf8
        $tentativa1 = @mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        if ($tentativa1 && strpos($tentativa1, 'Ã') === false) {
            return $tentativa1;
        }
        
        // Método 2: Remove caracteres corrompidos e reconstrói
        // Se tem "MÃ©dico", tenta extrair "M" + "édico"
        if (preg_match('/MÃ[^\s]*dico/', $texto)) {
            $texto = str_replace(['MÃ©dico', 'MÃ Ã©dico', 'MÃ Ã Ã Â©dico'], 'Médico', $texto);
        }
        
        // Método 3: Para "apresentação" corrompido
        if (preg_match('/apresenta[Ã]+[^o]*o/', $texto)) {
            $texto = preg_replace('/apresenta[Ã]+[^o]*o/', 'apresentação', $texto);
        }
        
        // Método 4: Para "consultório" corrompido
        if (preg_match('/consult[Ã]+[^o]*rio/', $texto)) {
            $texto = preg_replace('/consult[Ã]+[^o]*rio/', 'consultório', $texto);
        }
        
        // Método 5: Tenta utf8_decode (se estava em utf8 mas interpretado como latin1)
        $tentativa2 = @utf8_decode($texto);
        if ($tentativa2 && mb_check_encoding($tentativa2, 'UTF-8')) {
            $tentativa2_utf8 = utf8_encode($tentativa2);
            if (strpos($tentativa2_utf8, 'Ã') === false) {
                return $tentativa2_utf8;
            }
        }
    }
    
    return $texto;
}

// Função para testar antes de aplicar
function testarCorrecao($original, $corrigido) {
    // Se o corrigido não tem mais "Ã", provavelmente está certo
    if (strpos($corrigido, 'Ã') === false && strpos($original, 'Ã') !== false) {
        return true;
    }
    // Se o original não tinha "Ã" e o corrigido também não, está ok
    if (strpos($original, 'Ã') === false && strpos($corrigido, 'Ã') === false) {
        return true;
    }
    return false;
}

$total_corrigidos = 0;
$erros = [];
$testes = [];

try {
    // 1. Corrige videos
    echo "<div class='info'><strong>1. Corrigindo tabela videos...</strong></div>";
    $query = "SELECT id, titulo, descricao FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $videos_corrigidos = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $titulo_original = $row['titulo'];
        $descricao_original = $row['descricao'];
        
        $titulo_corrigido = corrigirEncodingInteligente($titulo_original);
        $descricao_corrigida = corrigirEncodingInteligente($descricao_original);
        
        // Testa se a correção é válida
        $titulo_ok = testarCorrecao($titulo_original, $titulo_corrigido);
        $descricao_ok = testarCorrecao($descricao_original, $descricao_corrigida);
        
        if ($titulo_ok || $descricao_ok) {
            $update = "UPDATE videos SET titulo = ?, descricao = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $update);
            mysqli_stmt_bind_param($stmt, "ssi", $titulo_corrigido, $descricao_corrigida, $row['id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $videos_corrigidos++;
                echo "<div class='test'>";
                echo "<strong>Vídeo ID {$row['id']}:</strong><br>";
                echo "ANTES: <code>{$titulo_original}</code><br>";
                echo "DEPOIS: <code>{$titulo_corrigido}</code><br>";
                if ($descricao_original !== $descricao_corrigida) {
                    echo "DESC ANTES: <code>{$descricao_original}</code><br>";
                    echo "DESC DEPOIS: <code>{$descricao_corrigida}</code><br>";
                }
                echo "</div>";
            } else {
                $erros[] = "Erro ao corrigir vídeo ID {$row['id']}: " . mysqli_error($conexao);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "<div class='warning'>⚠ Vídeo ID {$row['id']}: Não foi possível corrigir automaticamente. Requer correção manual.</div>";
        }
    }
    $total_corrigidos += $videos_corrigidos;
    echo "<div class='info'>Total de vídeos corrigidos: {$videos_corrigidos}</div>";
    
    // 2. Corrige setores
    echo "<div class='info'><strong>2. Corrigindo tabela setores...</strong></div>";
    $query = "SELECT id, nome FROM setores WHERE nome LIKE '%Ã%'";
    $result = mysqli_query($conexao, $query);
    
    $setores_corrigidos = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $nome_original = $row['nome'];
        $nome_corrigido = corrigirEncodingInteligente($nome_original);
        
        if (testarCorrecao($nome_original, $nome_corrigido)) {
            $update = "UPDATE setores SET nome = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $update);
            mysqli_stmt_bind_param($stmt, "si", $nome_corrigido, $row['id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $setores_corrigidos++;
            } else {
                $erros[] = "Erro ao corrigir setor ID {$row['id']}: " . mysqli_error($conexao);
            }
            mysqli_stmt_close($stmt);
        }
    }
    $total_corrigidos += $setores_corrigidos;
    echo "<div class='info'>Total de setores corrigidos: {$setores_corrigidos}</div>";
    
    // Resumo
    echo "<div class='success'><strong>✅ Correção concluída!</strong></div>";
    echo "<div class='info'><strong>Total de registros corrigidos: {$total_corrigidos}</strong></div>";
    
    if (!empty($erros)) {
        echo "<div class='error'><strong>Erros encontrados:</strong><pre>" . implode("\n", $erros) . "</pre></div>";
    }
    
    // Verificação final
    echo "<div class='info'><strong>3. Verificação final:</strong></div>";
    $check_videos = mysqli_query($conexao, "SELECT COUNT(*) as total FROM videos WHERE titulo LIKE '%Ã%' OR descricao LIKE '%Ã%'");
    $videos_restantes = mysqli_fetch_assoc($check_videos)['total'];
    
    if ($videos_restantes > 0) {
        echo "<div class='warning'>⚠ Ainda existem {$videos_restantes} vídeos com encoding incorreto. Pode ser necessário correção manual.</div>";
    } else {
        echo "<div class='success'>✅ Todos os vídeos foram corrigidos!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Erro:</strong> " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>

