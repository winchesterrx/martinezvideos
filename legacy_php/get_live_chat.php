<?php
header('Content-Type: application/json');
include 'db/conexao.php';

// 🔹 Configuração da API do YouTube
$api_key = "AIzaSyCtZmp0aOFxre-L58fkGn2oHdnPrMGfHvo";

// 🔹 Busca o ID do chat ao vivo no banco de dados
$live_query = "SELECT url FROM transmissao_ao_vivo WHERE ativo = 1 ORDER BY created_at DESC LIMIT 1";
$live_result = mysqli_query($conexao, $live_query);
$live_data = mysqli_fetch_assoc($live_result);
$live_url = $live_data['url'] ?? null;

// 🔹 Extrai o ID do vídeo do YouTube
function getYouTubeVideoID($url) {
    preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/|.+\?v=))([^"&?\/\s]{11})/', $url, $matches);
    return $matches[1] ?? null;
}

$video_id = $live_url ? getYouTubeVideoID($live_url) : null;
if (!$video_id) {
    echo json_encode(["error" => "ID do vídeo não encontrado"]);
    exit;
}

// 🔹 Busca o ID do chat ao vivo
$api_url = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id=$video_id&key=$api_key";
$response = file_get_contents($api_url);
$data = json_decode($response, true);

$chat_id = $data['items'][0]['liveStreamingDetails']['activeLiveChatId'] ?? null;
if (!$chat_id) {
    echo json_encode(["error" => "Chat ao vivo não encontrado"]);
    exit;
}

// 🔹 Busca as mensagens do chat ao vivo
$chat_api_url = "https://www.googleapis.com/youtube/v3/liveChat/messages?liveChatId=$chat_id&part=snippet,authorDetails&key=$api_key";
$chat_response = file_get_contents($chat_api_url);
$chat_data = json_decode($chat_response, true);

if (!isset($chat_data['items'])) {
    echo json_encode(["error" => "Nenhuma mensagem encontrada"]);
    exit;
}

// 🔹 Processa as mensagens do chat
$messages = [];
foreach ($chat_data['items'] as $item) {
    $messages[] = [
        "author" => $item['authorDetails']['displayName'],
        "profilePic" => $item['authorDetails']['profileImageUrl'],
        "message" => $item['snippet']['displayMessage']
    ];
}

echo json_encode(["messages" => $messages]);
?>
