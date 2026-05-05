<?php
include 'db/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $live_id = intval($_POST['live_id']);

    // 🔹 Verifica se o ID é válido
    if (!$live_id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido.']);
        exit;
    }

    // 🔹 Verifica se o usuário já registrou interesse (pelo IP)
    $ip_usuario = $_SERVER['REMOTE_ADDR'];

    $verificar_query = "SELECT COUNT(*) AS total FROM transmissao_agendada 
                        WHERE id = $live_id AND FIND_IN_SET('$ip_usuario', usuarios_interessados)";
    $verificar_result = mysqli_query($conexao, $verificar_query);
    $verificar = mysqli_fetch_assoc($verificar_result)['total'];

    if ($verificar > 0) {
        echo json_encode(['success' => false, 'message' => 'Interesse já registrado.']);
        exit;
