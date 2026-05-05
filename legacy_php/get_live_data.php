<?php
header('Content-Type: application/json');
include 'db/conexao.php';
include 'extract_video_id.php';

// 🔹 Obtém a URL da live no banco de dados
$live_query = "SELECT url FROM transmissao_ao_vivo WHERE ativo = 1 ORDER BY id DESC LIMIT 1";
$live_result = mysqli_query($conexao, $live_query);
$live_data = mysqli_fetch_assoc($live_result);
$live_url = $live_data['url'] ?? null;

if (!$live_url) {
    echo json_encode(['error' => 'Nenhuma live ativa']);
    exit;
}

// 🔹 Extrai o ID do vídeo do YouTube
$video_id = getYouTubeVideoID($live_url);
if (!$video_id) {
    echo json_encode(['error' => 'ID do vídeo inválido']);
    exit;
}

// 🔹 Chave da API do YouTube
$api_key = "AIzaSyCtZmp0aOFxre-L58fkGn2oHdnPrMGfHvo";

// 🔹 URL da API do YouTube para buscar as estatísticas
$api_url = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails,statistics&id=$video_id&key=$api_key";

// 🔹 Fazendo a requisição para a API
$response = file_get_contents($api_url);
$data = json_decode($response, true);

// 🔹 Verifica se a API retornou informações válidas
if (!$data || !isset($data['items'][0])) {
    echo json_encode(['error' => 'Erro ao obter dados da API']);
    exit;
}

// 🔹 Obtém o número de espectadores simultâneos e curtidas
$viewers = $data['items'][0]['liveStreamingDetails']['concurrentViewers'] ?? "0";
$likes = $data['items'][0]['statistics']['likeCount'] ?? "0";

// 🔹 Obtém o ID do Chat ao Vivo
$chat_id = $data['items'][0]['liveStreamingDetails']['activeLiveChatId'] ?? null;

// 🔹 Retorna os dados em JSON
echo json_encode([
    'views' => $viewers,
    'likes' => $likes,
    'chat_id' => $chat_id
]);
?>
