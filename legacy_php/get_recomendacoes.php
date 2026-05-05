<?php
session_start();

// Limpa qualquer output antes de iniciar
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Inicia output buffering para capturar qualquer warning
ob_start();

require_once 'db/conexao.php';

// Configura charset
mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

header('Content-Type: application/json; charset=utf-8');

// Verificar e criar tabela usuario_historico se não existir
$check_table = $conexao->query("SHOW TABLES LIKE 'usuario_historico'");
if (!$check_table || $check_table->num_rows == 0) {
    // Criar tabela automaticamente
    $create_table = "CREATE TABLE IF NOT EXISTS usuario_historico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NULL COMMENT 'ID do usuário logado (NULL para anônimos)',
        video_id INT NOT NULL,
        setor_id INT NOT NULL,
        modulo_id INT NULL,
        visualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        tempo_assistido INT DEFAULT 0 COMMENT 'Tempo assistido em segundos',
        completou TINYINT(1) DEFAULT 0 COMMENT '1 = assistiu até o final',
        INDEX idx_usuario (usuario_id),
        INDEX idx_video (video_id),
        INDEX idx_setor (setor_id),
        INDEX idx_modulo (modulo_id),
        INDEX idx_visualizado (visualizado_em),
        FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
        FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
        FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conexao->query($create_table);
}

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 6;

// Algoritmo de Recomendações Inteligentes
$recomendacoes = [];

try {
    // ===== PRIORIDADE MÁXIMA: Vídeos marcados manualmente como recomendados =====
    // Verifica se o campo recomendado existe
    $check_column = $conexao->query("SHOW COLUMNS FROM videos LIKE 'recomendado'");
    if ($check_column && $check_column->num_rows > 0) {
        $query_recomendados_manual = "SELECT v.*, 
                                           s.nome AS setor_nome,
                                           m.nome AS modulo_nome,
                                           m.icone AS modulo_icone,
                                           m.cor AS modulo_cor,
                                           (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                                           (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                                    FROM videos v
                                    JOIN setores s ON v.setor_id = s.id
                                    LEFT JOIN modulos m ON v.modulo_id = m.id
                                    WHERE v.recomendado = 1
                                    ORDER BY v.data_upload DESC
                                    LIMIT ?";
        
        $stmt_manual = $conexao->prepare($query_recomendados_manual);
        if ($stmt_manual) {
            $limite_manual = $limite;
            $stmt_manual->bind_param("i", $limite_manual);
            $stmt_manual->execute();
            $result_manual = $stmt_manual->get_result();
            
            while ($row = $result_manual->fetch_assoc()) {
                $recomendacoes[] = $row;
            }
            $stmt_manual->close();
        }
    }
    
    // Se temos vídeos recomendados manualmente, eles têm prioridade
    // Mas ainda podemos adicionar mais se não atingir o limite
    
    // Verificar se a tabela usuario_historico existe
    $table_exists = false;
    $check_table = $conexao->query("SHOW TABLES LIKE 'usuario_historico'");
    if ($check_table && $check_table->num_rows > 0) {
        $table_exists = true;
    }
    
    // IDs dos vídeos já recomendados manualmente (para não duplicar)
    $ids_ja_recomendados = array_column($recomendacoes, 'id');
    
    if ($usuario_id && $usuario_id > 0 && $table_exists) {
        // ===== RECOMENDAÇÕES BASEADAS EM HISTÓRICO DO USUÁRIO =====
        
        // 1. Buscar setores mais acessados pelo usuário
        $query_setores = "SELECT setor_id, COUNT(*) as total_views 
                         FROM usuario_historico 
                         WHERE usuario_id = ? 
                         GROUP BY setor_id 
                         ORDER BY total_views DESC 
                         LIMIT 3";
        $stmt_setores = $conexao->prepare($query_setores);
        $setores_favoritos = [];
        if ($stmt_setores) {
            $stmt_setores->bind_param("i", $usuario_id);
            $stmt_setores->execute();
            $result_setores = $stmt_setores->get_result();
            
            while ($row = $result_setores->fetch_assoc()) {
                $setores_favoritos[] = $row['setor_id'];
            }
            $stmt_setores->close();
        }
        
        // 2. Buscar módulos mais acessados pelo usuário
        $query_modulos = "SELECT modulo_id, COUNT(*) as total_views 
                         FROM usuario_historico 
                         WHERE usuario_id = ? AND modulo_id IS NOT NULL
                         GROUP BY modulo_id 
                         ORDER BY total_views DESC 
                         LIMIT 3";
        $stmt_modulos = $conexao->prepare($query_modulos);
        $modulos_favoritos = [];
        if ($stmt_modulos) {
            $stmt_modulos->bind_param("i", $usuario_id);
            $stmt_modulos->execute();
            $result_modulos = $stmt_modulos->get_result();
            
            while ($row = $result_modulos->fetch_assoc()) {
                $modulos_favoritos[] = $row['modulo_id'];
            }
            $stmt_modulos->close();
        }
        
        // 3. Buscar vídeos já assistidos pelo usuário (para excluir, mas não todos)
        $query_assistidos = "SELECT DISTINCT video_id 
                            FROM usuario_historico 
                            WHERE usuario_id = ?";
        $stmt_assistidos = $conexao->prepare($query_assistidos);
        $videos_assistidos = [];
        if ($stmt_assistidos) {
            $stmt_assistidos->bind_param("i", $usuario_id);
            $stmt_assistidos->execute();
            $result_assistidos = $stmt_assistidos->get_result();
            
            while ($row = $result_assistidos->fetch_assoc()) {
                $videos_assistidos[] = $row['video_id'];
            }
            $stmt_assistidos->close();
        }
        
        // 4. Construir query de recomendações com scoring
        $where_conditions = [];
        $params = [];
        $types = "";
        
        // Priorizar setores favoritos (mas não obrigatório)
        if (!empty($setores_favoritos)) {
            $placeholders = implode(',', array_fill(0, count($setores_favoritos), '?'));
            $where_conditions[] = "v.setor_id IN ($placeholders)";
            $params = array_merge($params, $setores_favoritos);
            $types .= str_repeat('i', count($setores_favoritos));
        }
        
        // Excluir apenas os últimos 2 vídeos assistidos (para não repetir muito)
        // Mas se houver poucos vídeos no total, não exclui nenhum
        if (!empty($videos_assistidos)) {
            // Conta total de vídeos disponíveis
            $count_total = $conexao->query("SELECT COUNT(*) as total FROM videos")->fetch_assoc()['total'];
            
            if ($count_total > 10) {
                // Se há muitos vídeos, exclui apenas os últimos 2 assistidos
                $videos_excluir = array_slice($videos_assistidos, -2);
                if (!empty($videos_excluir)) {
                    $placeholders = implode(',', array_fill(0, count($videos_excluir), '?'));
                    $where_conditions[] = "v.id NOT IN ($placeholders)";
                    $params = array_merge($params, $videos_excluir);
                    $types .= str_repeat('i', count($videos_excluir));
                }
            }
            // Se há poucos vídeos no total, não exclui nenhum para garantir que sempre há recomendações
        }
        
        // Excluir vídeos já recomendados manualmente
        if (!empty($ids_ja_recomendados)) {
            $placeholders = implode(',', array_fill(0, count($ids_ja_recomendados), '?'));
            $where_conditions[] = "v.id NOT IN ($placeholders)";
            $params = array_merge($params, $ids_ja_recomendados);
            $types .= str_repeat('i', count($ids_ja_recomendados));
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "WHERE 1=1";
        
        // Query com scoring baseado em:
        // - Setores favoritos (peso 3)
        // - Módulos favoritos (peso 2)
        // - Visualizações totais (peso 1)
        // - Data de upload (peso 0.5)
        $scoring_setores = !empty($setores_favoritos) ? 
            "CASE WHEN v.setor_id IN (" . implode(',', $setores_favoritos) . ") THEN 3 ELSE 0 END +" : "";
        
        $scoring_modulos = !empty($modulos_favoritos) ? 
            "CASE WHEN v.modulo_id IN (" . implode(',', $modulos_favoritos) . ") THEN 2 ELSE 0 END +" : "";
        
        $query_recomendacoes = "SELECT v.*, 
                                       s.nome AS setor_nome,
                                       m.nome AS modulo_nome,
                                       m.icone AS modulo_icone,
                                       m.cor AS modulo_cor,
                                       (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                                       (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios,
                                       ($scoring_setores
                                        $scoring_modulos
                                        (v.visualizacoes * 0.1) +
                                        (DATEDIFF(NOW(), v.data_upload) < 30 ? 1 : 0) * 0.5) AS score
                                FROM videos v
                                JOIN setores s ON v.setor_id = s.id
                                LEFT JOIN modulos m ON v.modulo_id = m.id
                                $where_clause
                                ORDER BY score DESC, v.data_upload DESC
                                LIMIT ?";
        
        $params[] = $limite;
        $types .= "i";
        
        $stmt_rec = $conexao->prepare($query_recomendacoes);
        if ($stmt_rec) {
            if (!empty($types)) {
                $stmt_rec->bind_param($types, ...$params);
            }
            $stmt_rec->execute();
            $result_rec = $stmt_rec->get_result();
            
            while ($row = $result_rec->fetch_assoc()) {
                $recomendacoes[] = $row;
            }
            $stmt_rec->close();
        }
        
    }
    
    // Se não encontrou recomendações baseadas em histórico OU usuário não está logado OU não tem histórico
    // OU se a query não retornou resultados suficientes
    // Mostra vídeos populares e recentes (mas exclui os já recomendados manualmente)
    if (count($recomendacoes) < $limite) {
        $ids_ja_recomendados = array_column($recomendacoes, 'id');
        $where_excluir = !empty($ids_ja_recomendados) ? 
            "AND v.id NOT IN (" . implode(',', $ids_ja_recomendados) . ")" : "";
        
        $limite_restante = $limite - count($recomendacoes);
        
        $query_recomendacoes = "SELECT v.*, 
                                       s.nome AS setor_nome,
                                       m.nome AS modulo_nome,
                                       m.icone AS modulo_icone,
                                       m.cor AS modulo_cor,
                                       (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                                       (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios,
                                       (v.visualizacoes * 0.5) + 
                                       (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) * 2 +
                                       (DATEDIFF(NOW(), v.data_upload) < 7 ? 3 : 0) AS score
                                FROM videos v
                                JOIN setores s ON v.setor_id = s.id
                                LEFT JOIN modulos m ON v.modulo_id = m.id
                                WHERE 1=1 $where_excluir
                                ORDER BY score DESC, v.data_upload DESC
                                LIMIT ?";
        
        $stmt_rec = $conexao->prepare($query_recomendacoes);
        if ($stmt_rec) {
            $stmt_rec->bind_param("i", $limite_restante);
            $stmt_rec->execute();
            $result_rec = $stmt_rec->get_result();
            
            while ($row = $result_rec->fetch_assoc()) {
                $recomendacoes[] = $row;
            }
            $stmt_rec->close();
        }
    }
        
        $stmt_rec = $conexao->prepare($query_recomendacoes);
        if ($stmt_rec) {
            $stmt_rec->bind_param("i", $limite);
            $stmt_rec->execute();
            $result_rec = $stmt_rec->get_result();
            
            while ($row = $result_rec->fetch_assoc()) {
                $recomendacoes[] = $row;
            }
            $stmt_rec->close();
        }
    }
    
    // Se não encontrou recomendações suficientes, completa com vídeos populares
    if (count($recomendacoes) < $limite) {
        $ids_recomendados = array_column($recomendacoes, 'id');
        $where_extra = !empty($ids_recomendados) ? 
            "WHERE v.id NOT IN (" . implode(',', $ids_recomendados) . ")" : 
            "WHERE 1=1";
        
        $limite_extra = $limite - count($recomendacoes);
        
        $query_extra = "SELECT v.*, 
                               s.nome AS setor_nome,
                               m.nome AS modulo_nome,
                               m.icone AS modulo_icone,
                               m.cor AS modulo_cor,
                               (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                               (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                        FROM videos v
                        JOIN setores s ON v.setor_id = s.id
                        LEFT JOIN modulos m ON v.modulo_id = m.id
                        $where_extra
                        ORDER BY v.visualizacoes DESC, v.data_upload DESC
                        LIMIT ?";
        
        $stmt_extra = $conexao->prepare($query_extra);
        if ($stmt_extra) {
            $stmt_extra->bind_param("i", $limite_extra);
            $stmt_extra->execute();
            $result_extra = $stmt_extra->get_result();
            
            while ($row = $result_extra->fetch_assoc()) {
                $recomendacoes[] = $row;
            }
            $stmt_extra->close();
        }
    }
    
    // Se não encontrou nenhuma recomendação, mostra vídeos populares
    if (empty($recomendacoes)) {
        $query_fallback = "SELECT v.*, 
                                  s.nome AS setor_nome,
                                  m.nome AS modulo_nome,
                                  m.icone AS modulo_icone,
                                  m.cor AS modulo_cor,
                                  (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                                  (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                           FROM videos v
                           JOIN setores s ON v.setor_id = s.id
                           LEFT JOIN modulos m ON v.modulo_id = m.id
                           ORDER BY v.visualizacoes DESC, v.data_upload DESC
                           LIMIT ?";
        
        $stmt_fallback = $conexao->prepare($query_fallback);
        if ($stmt_fallback) {
            $stmt_fallback->bind_param("i", $limite);
            $stmt_fallback->execute();
            $result_fallback = $stmt_fallback->get_result();
            
            while ($row = $result_fallback->fetch_assoc()) {
                $recomendacoes[] = $row;
            }
            $stmt_fallback->close();
        }
    }
    
    // Limpa qualquer output buffer antes de retornar JSON
    ob_clean();
    
    // Remove duplicatas mantendo apenas a primeira ocorrência
    $ids_vistos = [];
    $recomendacoes_unicas = [];
    foreach ($recomendacoes as $rec) {
        if (!in_array($rec['id'], $ids_vistos)) {
            $ids_vistos[] = $rec['id'];
            $recomendacoes_unicas[] = $rec;
        }
    }
    $recomendacoes = array_slice($recomendacoes_unicas, 0, $limite);
    
    // Sempre retorna sucesso, mesmo se não houver recomendações
    echo json_encode([
        'success' => true,
        'recomendacoes' => $recomendacoes,
        'total' => count($recomendacoes),
        'debug' => [
            'usuario_id' => $usuario_id,
            'table_exists' => isset($table_exists) ? $table_exists : false,
            'recomendados_manual' => count(array_filter($recomendacoes, function($v) { return isset($v['recomendado']) && $v['recomendado'] == 1; }))
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Em caso de erro, retorna vídeos populares como fallback
    try {
        $query_fallback = "SELECT v.*, 
                                  s.nome AS setor_nome,
                                  m.nome AS modulo_nome,
                                  m.icone AS modulo_icone,
                                  m.cor AS modulo_cor,
                                  (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = v.id) AS curtidas,
                                  (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = v.id) AS total_comentarios
                           FROM videos v
                           JOIN setores s ON v.setor_id = s.id
                           LEFT JOIN modulos m ON v.modulo_id = m.id
                           ORDER BY v.visualizacoes DESC, v.data_upload DESC
                           LIMIT ?";
        
        $stmt_fallback = $conexao->prepare($query_fallback);
        $recomendacoes_fallback = [];
        if ($stmt_fallback) {
            $limite_fallback = isset($limite) ? $limite : 6;
            $stmt_fallback->bind_param("i", $limite_fallback);
            $stmt_fallback->execute();
            $result_fallback = $stmt_fallback->get_result();
            
            while ($row = $result_fallback->fetch_assoc()) {
                $recomendacoes_fallback[] = $row;
            }
            $stmt_fallback->close();
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'recomendacoes' => $recomendacoes_fallback,
            'total' => count($recomendacoes_fallback),
            'message' => 'Mostrando vídeos populares'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e2) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar recomendações: ' . $e->getMessage(),
            'recomendacoes' => []
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>

