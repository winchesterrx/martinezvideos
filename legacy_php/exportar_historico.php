<?php
session_start();
require_once 'db/conexao.php';

$usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if (!$usuario_id) {
    die('Usuário não autenticado');
}

$formato = isset($_GET['formato']) ? $_GET['formato'] : 'csv'; // 'csv' ou 'json'

try {
    $query = "SELECT 
                v.titulo,
                v.descricao,
                s.nome AS setor,
                m.nome AS modulo,
                uh.tempo_assistido,
                v.duracao,
                uh.completou,
                uh.visualizado_em,
                CASE 
                    WHEN v.duracao > 0 THEN ROUND((uh.tempo_assistido / v.duracao) * 100, 2)
                    ELSE 0
                END AS porcentagem_assistida
              FROM usuario_historico uh
              JOIN videos v ON uh.video_id = v.id
              JOIN setores s ON uh.setor_id = s.id
              LEFT JOIN modulos m ON uh.modulo_id = m.id
              WHERE uh.usuario_id = ?
              ORDER BY uh.visualizado_em DESC";
    
    $stmt = $conexao->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="historico_videos_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeçalho
        fputcsv($output, [
            'Título',
            'Setor',
            'Módulo',
            'Tempo Assistido (seg)',
            'Duração Total (seg)',
            'Porcentagem',
            'Completo',
            'Visualizado Em'
        ], ';');
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['titulo'],
                $row['setor'],
                $row['modulo'] ?? '',
                $row['tempo_assistido'],
                $row['duracao'],
                $row['porcentagem_assistida'] . '%',
                $row['completou'] ? 'Sim' : 'Não',
                $row['visualizado_em']
            ], ';');
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="historico_videos_' . date('Y-m-d') . '.json"');
        
        $historico = [];
        while ($row = $result->fetch_assoc()) {
            $historico[] = $row;
        }
        
        echo json_encode($historico, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    die('Erro: ' . $e->getMessage());
}
?>

