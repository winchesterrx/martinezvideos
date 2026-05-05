<?php
function conectarBanco() {
    $host = 'localhost';
    $usuario = 'u578036672_estoque';
    $senha = 'Xofome23@';
    $db = 'u578036672_estoque';

    $conexao = new mysqli($host, $usuario, $senha, $db);
    if ($conexao->connect_error) {
        die("Erro de conexão: " . $conexao->connect_error);
    }
    return $conexao;
}

function executarConsultaPreparada($conexao, $sql, $tipos, ...$parametros) {
    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        die("Erro ao preparar consulta: " . $conexao->error);
    }
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();
    return $stmt;
}

function validarEntrada($entrada) {
    return htmlspecialchars(trim($entrada), ENT_QUOTES, 'UTF-8');
}

function verificarSessao() {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>