<?php
/**
 * Funções de verificação de permissões baseadas em setores
 */

/**
 * Verifica se o usuário tem permissão para acessar um setor específico
 * Agora também verifica clientes
 * @param mysqli $conexao Conexão com o banco
 * @param int $usuario_id ID do usuário
 * @param int $setor_id ID do setor
 * @return bool True se tiver permissão, False caso contrário
 */
function usuario_tem_acesso_setor($conexao, $usuario_id, $setor_id) {
    // Verifica se é cliente (está na tabela clientes)
    $query_cliente = "SELECT id FROM clientes WHERE id = ?";
    $stmt = $conexao->prepare($query_cliente);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $eh_cliente = $result->num_rows > 0;
    $stmt->close();
    
    if ($eh_cliente) {
        // Clientes podem ter acesso a setores via cliente_setores
        $query_setor = "SELECT COUNT(*) as total FROM cliente_setores 
                        WHERE cliente_id = ? AND setor_id = ?";
        $stmt = $conexao->prepare($query_setor);
        $stmt->bind_param('ii', $usuario_id, $setor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return ($row['total'] > 0);
    }
    
    // Admin tem acesso a todos os setores
    $query_admin = "SELECT ADM FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query_admin);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    if ($usuario && $usuario['ADM'] === 'S') {
        $stmt->close();
        return true; // Admin tem acesso a tudo
    }
    $stmt->close();
    
    // Verifica se o usuário está vinculado ao setor
    $query_setor = "SELECT COUNT(*) as total FROM usuario_setores 
                    WHERE usuario_id = ? AND setor_id = ?";
    $stmt = $conexao->prepare($query_setor);
    $stmt->bind_param('ii', $usuario_id, $setor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return ($row['total'] > 0);
}

/**
 * Verifica se o usuário pode editar/excluir um vídeo específico
 * @param mysqli $conexao Conexão com o banco
 * @param int $usuario_id ID do usuário
 * @param int $video_id ID do vídeo
 * @return bool True se puder editar, False caso contrário
 */
function usuario_pode_editar_video($conexao, $usuario_id, $video_id) {
    // Busca o setor do vídeo
    $query_video = "SELECT setor_id FROM videos WHERE id = ?";
    $stmt = $conexao->prepare($query_video);
    $stmt->bind_param('i', $video_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $video = $result->fetch_assoc();
    $stmt->close();
    
    if (!$video) {
        return false; // Vídeo não existe
    }
    
    return usuario_tem_acesso_setor($conexao, $usuario_id, $video['setor_id']);
}

/**
 * Retorna array com IDs dos setores que o usuário tem acesso
 * Agora também verifica setores de clientes
 * @param mysqli $conexao Conexão com o banco
 * @param int $usuario_id ID do usuário
 * @return array Array de IDs de setores
 */
function get_setores_usuario($conexao, $usuario_id) {
    // Verifica se é cliente (está na tabela clientes)
    $query_cliente = "SELECT id FROM clientes WHERE id = ?";
    $stmt = $conexao->prepare($query_cliente);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $eh_cliente = $result->num_rows > 0;
    $stmt->close();
    
    if ($eh_cliente) {
        // Se for cliente, busca setores da tabela cliente_setores
        $query_setores = "SELECT setor_id FROM cliente_setores WHERE cliente_id = ?";
        $stmt = $conexao->prepare($query_setores);
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $setores = [];
        while ($row = $result->fetch_assoc()) {
            $setores[] = $row['setor_id'];
        }
        $stmt->close();
        return $setores;
    }
    
    // Verifica se é admin (usuário do sistema)
    $query_admin = "SELECT ADM FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query_admin);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    // Se for admin, retorna todos os setores ativos
    if ($usuario && $usuario['ADM'] === 'S') {
        $query_all = "SELECT id FROM setores WHERE ativo = 'S'";
        $result = $conexao->query($query_all);
        $setores = [];
        while ($row = $result->fetch_assoc()) {
            $setores[] = $row['id'];
        }
        return $setores;
    }
    
    // Caso contrário, retorna apenas os setores vinculados (usuario_setores)
    $query_setores = "SELECT setor_id FROM usuario_setores WHERE usuario_id = ?";
    $stmt = $conexao->prepare($query_setores);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $setores = [];
    while ($row = $result->fetch_assoc()) {
        $setores[] = $row['setor_id'];
    }
    $stmt->close();
    
    return $setores;
}

/**
 * Verifica se o usuário é cliente (não admin e sem setores vinculados)
 * Agora também verifica se é da tabela clientes
 * @param mysqli $conexao Conexão com o banco
 * @param int $usuario_id ID do usuário
 * @return bool True se for cliente, False caso contrário
 */
function usuario_eh_cliente($conexao, $usuario_id) {
    // Verifica se está na tabela clientes
    $query_cliente = "SELECT id FROM clientes WHERE id = ?";
    $stmt = $conexao->prepare($query_cliente);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $eh_cliente_tabela = $result->num_rows > 0;
    $stmt->close();
    
    if ($eh_cliente_tabela) {
        return true; // Se está na tabela clientes, é cliente
    }
    
    // Se não está na tabela clientes, verifica se é usuário sem setores
    $query = "SELECT ADM FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    // Se for admin, não é cliente
    if ($usuario && $usuario['ADM'] === 'S') {
        return false;
    }
    
    // Verifica se tem setores vinculados
    $setores = get_setores_usuario($conexao, $usuario_id);
    return empty($setores);
}

/**
 * Verifica se o usuário pode fazer upload de vídeo para um setor
 * @param mysqli $conexao Conexão com o banco
 * @param int $usuario_id ID do usuário
 * @param int $setor_id ID do setor
 * @return bool True se puder fazer upload, False caso contrário
 */
function usuario_pode_upload_setor($conexao, $usuario_id, $setor_id) {
    // Admin pode fazer upload para qualquer setor
    return usuario_tem_acesso_setor($conexao, $usuario_id, $setor_id);
}

?>

