<?php
session_start();

// Limpa qualquer output antes de iniciar
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Inicia output buffering para capturar qualquer warning
ob_start();

require_once 'db/conexao.php';

header('Content-Type: application/json; charset=utf-8');

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos'; // 'continuar', 'completos', 'todos'
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

try {
    $where_conditions = ["uh.usuario_id = ?"];
    $params = [$usuario_id];
    $types = "i";
    
    if ($tipo === 'continuar') {
        $where_conditions[] = "uh.completou = 0 AND uh.tempo_assistido > 0";
    } elseif ($tipo === 'completos') {
        $where_conditions[] = "uh.completou = 1";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $query = "SELECT 
                uh.*,
                v.titulo,
                v.descricao,
                v.url_video,
                v.duracao,
                v.visualizacoes,
                s.nome AS setor_nome,
                m.nome AS modulo_nome,
                (SELECT COUNT(*) FROM curtidas WHERE video_id = v.id) AS curtidas,
                (SELECT COUNT(*) FROM comentarios WHERE video_id = v.id) AS total_comentarios,
                CASE 
                    WHEN v.duracao > 0 THEN ROUND((uh.tempo_assistido / v.duracao) * 100, 2)
                    ELSE 0
                END AS porcentagem_assistida
              FROM usuario_historico uh
              JOIN videos v ON uh.video_id = v.id
              JOIN setores s ON uh.setor_id = s.id
              LEFT JOIN modulos m ON uh.modulo_id = m.id
              $where_clause
              ORDER BY uh.visualizado_em DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limite;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historico = [];
    while ($row = $result->fetch_assoc()) {
        $historico[] = $row;
    }
    
    // Total de registros
    $count_query = "SELECT COUNT(*) as total FROM usuario_historico uh $where_clause";
    $count_stmt = $conexao->prepare($count_query);
    $count_params = [$usuario_id];
    $count_types = "i";
    if ($tipo === 'continuar') {
        $count_query = str_replace("uh.usuario_id = ?", "uh.usuario_id = ? AND uh.completou = 0 AND uh.tempo_assistido > 0", $count_query);
    } elseif ($tipo === 'completos') {
        $count_query = str_replace("uh.usuario_id = ?", "uh.usuario_id = ? AND uh.completou = 1", $count_query);
    }
    $count_stmt = $conexao->prepare($count_query);
    $count_stmt->bind_param($count_types, ...$count_params);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'historico' => $historico,
        'total' => $total,
        'tipo' => $tipo
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

