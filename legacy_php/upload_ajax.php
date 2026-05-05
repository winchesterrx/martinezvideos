<?php
session_start();
include 'db/conexao.php';
include 'db/funcoes_permissoes.php';

// Configura charset para UTF-8 (utf8mb4) para suportar caracteres especiais e emojis
mysqli_set_charset($conexao, "utf8mb4");
mysqli_query($conexao, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
mysqli_query($conexao, "SET CHARACTER SET utf8mb4");
mysqli_query($conexao, "SET character_set_connection=utf8mb4");
mysqli_query($conexao, "SET character_set_client=utf8mb4");
mysqli_query($conexao, "SET character_set_results=utf8mb4");

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido. Use POST.');
    }

    // Verifica se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Você precisa estar logado para fazer upload de vídeos.');
    }

    $usuario_id = $_SESSION['user_id'];
    $usuario_adm = isset($_SESSION['user_adm']) && $_SESSION['user_adm'] === true;

    // Clientes não podem fazer upload
    if (usuario_eh_cliente($conexao, $usuario_id)) {
        throw new Exception('Clientes não podem fazer upload de vídeos.');
    }

    if (!isset($_FILES['video'])) {
        throw new Exception('Nenhum arquivo enviado.');
    }

    // Recebe e sanitiza os dados com encoding correto
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $setor_id = intval($_POST['setor_id'] ?? 0);
    $modulo_id = isset($_POST['modulo_id']) && $_POST['modulo_id'] !== '' ? intval($_POST['modulo_id']) : null;
    
    // Campos de sequência
    $is_sequencia = isset($_POST['is_sequencia']) && $_POST['is_sequencia'] === 'on' ? 1 : 0;
    $sequencia_id = isset($_POST['sequencia_id']) && !empty($_POST['sequencia_id']) ? intval($_POST['sequencia_id']) : null;
    $sequencia_ordem = isset($_POST['sequencia_ordem']) && !empty($_POST['sequencia_ordem']) ? intval($_POST['sequencia_ordem']) : null;
    $sequencia_titulo = isset($_POST['sequencia_titulo']) ? trim($_POST['sequencia_titulo']) : '';
    
    // Garante que os dados estão em UTF-8
    if (!mb_check_encoding($titulo, 'UTF-8')) {
        $titulo = mb_convert_encoding($titulo, 'UTF-8', 'auto');
    }
    if (!mb_check_encoding($descricao, 'UTF-8')) {
        $descricao = mb_convert_encoding($descricao, 'UTF-8', 'auto');
    }

    if (empty($titulo) || empty($descricao) || $setor_id <= 0) {
        throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
    }

    // Verifica se o usuário tem permissão para fazer upload neste setor
    if (!usuario_pode_upload_setor($conexao, $usuario_id, $setor_id)) {
        throw new Exception('Você não tem permissão para fazer upload de vídeos neste setor.');
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $videoName = time() . '-' . basename($_FILES['video']['name']);
    $videoPath = $uploadDir . $videoName;

    if (!move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
        throw new Exception('Erro ao salvar o arquivo.');
    }

    // Processar sequência se necessário
    if ($is_sequencia) {
        // Se é nova sequência, criar
        if (empty($sequencia_id) && !empty($sequencia_titulo)) {
            // Normaliza módulo_id para NULL se vazio
            $modulo_id_final = ($modulo_id !== null && $modulo_id > 0) ? $modulo_id : null;
            
            // Prepara statement com módulo_id opcional
            if ($modulo_id_final !== null) {
                $stmt_seq = $conexao->prepare("INSERT INTO sequencias (titulo, setor_id, modulo_id) VALUES (?, ?, ?)");
                if (!$stmt_seq) {
                    throw new Exception('Erro ao preparar criação de sequência: ' . $conexao->error);
                }
                $stmt_seq->bind_param("sii", $sequencia_titulo, $setor_id, $modulo_id_final);
            } else {
                $stmt_seq = $conexao->prepare("INSERT INTO sequencias (titulo, setor_id, modulo_id) VALUES (?, ?, NULL)");
                if (!$stmt_seq) {
                    throw new Exception('Erro ao preparar criação de sequência: ' . $conexao->error);
                }
                $stmt_seq->bind_param("si", $sequencia_titulo, $setor_id);
            }
            
            if (!$stmt_seq->execute()) {
                $stmt_seq->close();
                throw new Exception('Erro ao criar sequência: ' . $stmt_seq->error);
            }
            $sequencia_id = $stmt_seq->insert_id;
            $stmt_seq->close();
        }
        
        // Validar que sequencia_id foi definido
        if (empty($sequencia_id)) {
            throw new Exception('É necessário selecionar uma sequência existente ou criar uma nova.');
        }
        
        // Se não especificou ordem, pegar a próxima
        if ($sequencia_id && empty($sequencia_ordem)) {
            $stmt_ordem = $conexao->prepare("SELECT COALESCE(MAX(sequencia_ordem), 0) + 1 as proxima_ordem FROM videos WHERE sequencia_id = ? AND is_sequencia = 1");
            if ($stmt_ordem) {
                $stmt_ordem->bind_param("i", $sequencia_id);
                $stmt_ordem->execute();
                $result_ordem = $stmt_ordem->get_result();
                if ($result_ordem) {
                    $row_ordem = $result_ordem->fetch_assoc();
                    $sequencia_ordem = $row_ordem['proxima_ordem'];
                }
                $stmt_ordem->close();
            }
        }
        
        // Garantir que is_sequencia seja 1 quando há sequencia_id
        $is_sequencia = 1;
    } else {
        // Garantir que campos de sequência sejam NULL se não for sequência
        $sequencia_id = null;
        $sequencia_ordem = null;
        $is_sequencia = 0;
    }

    // Usando prepared statement para prevenir SQL Injection
    // Normaliza modulo_id para NULL se vazio
    $modulo_id_final = ($modulo_id !== null && $modulo_id > 0) ? $modulo_id : null;
    
    if ($modulo_id_final !== null) {
        $sql = "INSERT INTO videos (titulo, descricao, url_video, setor_id, modulo_id, is_sequencia, sequencia_id, sequencia_ordem, data_upload) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conexao->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Erro ao preparar a consulta: ' . $conexao->error);
        }
        
        // Ajusta tipos para bind_param (s = string, i = integer)
        // titulo, descricao, url_video = sss
        // setor_id, modulo_id, is_sequencia, sequencia_id, sequencia_ordem = iiiii
        $stmt->bind_param("sssiiiii", $titulo, $descricao, $videoPath, $setor_id, $modulo_id_final, $is_sequencia, $sequencia_id, $sequencia_ordem);
    } else {
        $sql = "INSERT INTO videos (titulo, descricao, url_video, setor_id, is_sequencia, sequencia_id, sequencia_ordem, data_upload) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conexao->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Erro ao preparar a consulta: ' . $conexao->error);
        }
        
        // Ajusta tipos para bind_param
        // titulo, descricao, url_video = sss
        // setor_id, is_sequencia, sequencia_id, sequencia_ordem = iiii
        $stmt->bind_param("sssiiii", $titulo, $descricao, $videoPath, $setor_id, $is_sequencia, $sequencia_id, $sequencia_ordem);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar os dados no banco de dados: ' . $stmt->error);
    }

    $id = $stmt->insert_id;
    $stmt->close();
    
    // Criar notificações para usuários interessados
    require_once 'criar_notificacao.php';
    criarNotificacaoVideoNovo($conexao, $id, $setor_id, $modulo_id_final);
    
    echo json_encode(['success' => true, 'message' => 'Vídeo enviado com sucesso!', 'id' => $id], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
