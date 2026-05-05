<?php
session_start();
include 'db/conexao.php';

header('Content-Type: application/json');

// Função para capturar o IP do cliente
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Função para obter localização com base no IP
function getGeoLocation($ip) {
    $url = "http://www.geoplugin.net/json.gp?ip=" . $ip;

    $response = file_get_contents($url); // Faz a requisição à API GeoPlugin
    $data = json_decode($response, true); // Decodifica o JSON retornado

    if (!empty($data) && $data['geoplugin_status'] == 200) {
        return [
            'cidade' => $data['geoplugin_city'] ?? 'Desconhecida',
            'regiao' => $data['geoplugin_region'] ?? 'Desconhecida',
            'pais' => $data['geoplugin_countryName'] ?? 'Desconhecido',
            'latitude' => $data['geoplugin_latitude'] ?? null,
            'longitude' => $data['geoplugin_longitude'] ?? null
        ];
    }

    return [
        'cidade' => 'Desconhecida',
        'regiao' => 'Desconhecida',
        'pais' => 'Desconhecido',
        'latitude' => null,
        'longitude' => null
    ];
}

// Captura os dados do frontend e o IP do cliente
$data = json_decode(file_get_contents('php://input'), true);
$video_id = intval($data['video_id'] ?? 0);
$ip_address = getClientIP(); // Captura o IP do cliente

// Recupera o user_id e o nome do usuário da sessão
$user_id = $_SESSION['user_id'] ?? null; // ID do usuário logado
$nome_usuario = $_SESSION['nome_usuario'] ?? null; // Nome do usuário logado

// Se o nome do usuário não estiver na sessão, busque no banco de dados
if ($user_id && !$nome_usuario) {
    $stmt = $conexao->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nome_usuario = $row['nome'] ?? null;
}

// Obtém a localização com base no IP
$geo_data = getGeoLocation($ip_address);

if ($video_id > 0) {
    // Verifica se o IP/usuário já registrou visualização para este vídeo
    $stmt = $conexao->prepare("
        SELECT id 
        FROM video_visualizacoes 
        WHERE video_id = ? AND ip_address = ? AND (user_id = ? OR user_id IS NULL)");
    $stmt->bind_param('isi', $video_id, $ip_address, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        // IP ou usuário ainda não registrado para este vídeo
        $stmt = $conexao->prepare("
            INSERT INTO video_visualizacoes (video_id, user_id, nome_usuario, ip_address, cidade, regiao, pais, latitude, longitude) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'iisssssdd',
            $video_id,
            $user_id,
            $nome_usuario,
            $ip_address,
            $geo_data['cidade'],
            $geo_data['regiao'],
            $geo_data['pais'],
            $geo_data['latitude'],
            $geo_data['longitude']
        );

        if ($stmt->execute()) {
            // Incrementa o contador de visualizações no banco de dados
            $stmt = $conexao->prepare("
                UPDATE videos 
                SET visualizacoes = visualizacoes + 1 
                WHERE id = ?");
            $stmt->bind_param('i', $video_id);
            $stmt->execute();
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao registrar visualização.']);
            exit;
        }
    }

    // Retorna a contagem de visualizações atualizada
    $stmt = $conexao->prepare("SELECT visualizacoes FROM videos WHERE id = ?");
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode(['success' => true, 'visualizacoes' => $row['visualizacoes']]);
} else {
    echo json_encode(['success' => false, 'error' => 'ID inválido ou ausente.']);
}
?>
