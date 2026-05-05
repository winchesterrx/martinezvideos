<?php
session_start();
include 'db/conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método inválido.']);
    exit;
}

// Verifica se o usuário é administrador
if (empty($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

// Verifica parâmetros recebidos
$tipo = $_POST['tipo'] ?? null;
$ids = $_POST['ids'] ?? [];

if (!$tipo || !is_array($ids) || count($ids) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum item selecionado ou tipo inválido.']);
    exit;
}

// Limpeza de IDs
$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
    $conexao->begin_transaction();

    if ($tipo === 'respostas') {
        $query = "DELETE FROM respostas WHERE id IN ($placeholders)";
    } elseif ($tipo === 'comentarios') {
        // Exclui respostas associadas primeiro
        $query_respostas = "DELETE FROM respostas WHERE comentario_id IN ($placeholders)";
        $stmt_respostas = $conexao->prepare($query_respostas);
        $stmt_respostas->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt_respostas->execute();

        $query = "DELETE FROM comentarios WHERE id IN ($placeholders)";
    } else {
        throw new Exception("Tipo de exclusão inválido.");
    }

    $stmt = $conexao->prepare($query);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();

    $conexao->commit();
    echo json_encode(['success' => true, 'message' => ucfirst($tipo) . ' excluídos com sucesso.']);
} catch (Exception $e) {
    $conexao->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
