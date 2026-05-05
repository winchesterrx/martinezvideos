<?php
session_start();
require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$setor_id = isset($_GET['setor_id']) ? intval($_GET['setor_id']) : 0;
$modulo_id = isset($_GET['modulo_id']) ? intval($_GET['modulo_id']) : 0;

if ($setor_id <= 0) {
    echo json_encode(['success' => false, 'count' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = "SELECT COUNT(*) as total FROM videos WHERE setor_id = ?";
$params = [$setor_id];
$types = "i";

if ($modulo_id > 0) {
    $query .= " AND modulo_id = ?";
    $params[] = $modulo_id;
    $types .= "i";
}

$stmt = mysqli_prepare($conexao, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'count' => intval($row['total'])], JSON_UNESCAPED_UNICODE);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'count' => 0], JSON_UNESCAPED_UNICODE);
}

