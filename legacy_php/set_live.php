<?php
session_start();
include 'db/conexao.php'; // Conexão com o banco de dados
header('Content-Type: application/json');

// VERIFICAÇÃO DE SEGURANÇA: Apenas administradores podem iniciar/encerrar lives
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(["success" => false, "error" => "Acesso negado. Apenas administradores podem gerenciar transmissões ao vivo."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $live_url = isset($_POST['live_url']) ? trim($_POST['live_url']) : null;
    $titulo = isset($_POST['titulo']) && !empty($_POST['titulo']) ? trim($_POST['titulo']) : 'Transmissão Especial';
    $descricao = isset($_POST['descricao']) && !empty($_POST['descricao']) ? trim($_POST['descricao']) : 'Acompanhe ao vivo nossa transmissão especial com conteúdos exclusivos.';
    $subtexto = isset($_POST['subtexto']) ? trim($_POST['subtexto']) : '';
    $stop_live = isset($_POST['stop_live']) ? $_POST['stop_live'] : null;

    if ($stop_live) {
        // Desativa a transmissão ao vivo (Live OFF) usando prepared statement
        $query = "UPDATE transmissao_ao_vivo SET ativo = 0 WHERE ativo = 1";
        $stmt = $conexao->prepare($query);
        
        if ($stmt && $stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Transmissão encerrada."]);
        } else {
            echo json_encode(["success" => false, "error" => "Erro ao encerrar a transmissão."]);
        }
        
        if ($stmt) $stmt->close();
        exit;
    }

    if ($live_url) {
        // Corrige a URL para formato embed do YouTube
        if (strpos($live_url, "youtube.com/live/") !== false) {
            $live_url = preg_replace('/youtube\.com\/live\/([a-zA-Z0-9_-]+)/', 'youtube.com/embed/$1', $live_url);
        } elseif (strpos($live_url, "watch?v=") !== false) {
            $live_url = preg_replace('/watch\?v=([a-zA-Z0-9_-]+)/', 'embed/$1', $live_url);
        }

        // Verifica se já existe uma live ativa
        $query_check = "SELECT * FROM transmissao_ao_vivo WHERE ativo = 1";
        $result = mysqli_query($conexao, $query_check);

        // Verifica se a coluna subtexto existe
        $check_subtexto = mysqli_query($conexao, "SHOW COLUMNS FROM transmissao_ao_vivo LIKE 'subtexto'");
        $has_subtexto = mysqli_num_rows($check_subtexto) > 0;

        $is_nova_live = mysqli_num_rows($result) == 0;
        
        if (!$is_nova_live) {
            // Atualiza a live existente
            if ($has_subtexto) {
                $query = "UPDATE transmissao_ao_vivo SET url = ?, titulo = ?, descricao = ?, subtexto = ?, created_at = NOW() WHERE ativo = 1";
                $stmt = mysqli_prepare($conexao, $query);
                mysqli_stmt_bind_param($stmt, "ssss", $live_url, $titulo, $descricao, $subtexto);
            } else {
                $query = "UPDATE transmissao_ao_vivo SET url = ?, titulo = ?, descricao = ?, created_at = NOW() WHERE ativo = 1";
                $stmt = mysqli_prepare($conexao, $query);
                mysqli_stmt_bind_param($stmt, "sss", $live_url, $titulo, $descricao);
            }
        } else {
            // Insere uma nova live
            if ($has_subtexto) {
                $query = "INSERT INTO transmissao_ao_vivo (url, titulo, descricao, subtexto, ativo) VALUES (?, ?, ?, ?, 1)";
                $stmt = mysqli_prepare($conexao, $query);
                mysqli_stmt_bind_param($stmt, "ssss", $live_url, $titulo, $descricao, $subtexto);
            } else {
                $query = "INSERT INTO transmissao_ao_vivo (url, titulo, descricao, ativo) VALUES (?, ?, ?, 1)";
                $stmt = mysqli_prepare($conexao, $query);
                mysqli_stmt_bind_param($stmt, "sss", $live_url, $titulo, $descricao);
            }
        }

        if (mysqli_stmt_execute($stmt)) {
            // Criar notificações se for uma nova live
            if ($is_nova_live) {
                require_once 'criar_notificacao.php';
                criarNotificacaoLive($conexao, $titulo, $live_url);
            }
            
            echo json_encode(["success" => true, "message" => "Live atualizada com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "error" => "Erro ao atualizar a live"]);
        }
        exit;
    }
}

echo json_encode(["success" => false, "error" => "Requisição inválida"]);
