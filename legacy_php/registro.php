<?php
include 'db/conexao.php';
$conexao->set_charset("utf8");

// Exibir erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (isset($_GET['edit']) && $_GET['edit'] == 1 && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario) {
        // Inclua o formulário com os valores do usuário preenchidos
        echo '<input type="hidden" name="id" value="' . htmlspecialchars($usuario['id']) . '">';
        echo '<div class="mb-3">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" name="nome" value="' . htmlspecialchars($usuario['nome']) . '" required>
              </div>';
        // Repita para outros campos
    } else {
        echo '<p>Usuário não encontrado.</p>';
    }
    exit;
}

// Inicializar variáveis
$sucesso = false;
$erro = "";

// Buscar estados usando prepared statement
$queryUF = "SELECT id, nome FROM UF ORDER BY nome ASC";
$resultUF = $conexao->query($queryUF);

$ufs = [];
if ($resultUF) {
while ($row = $resultUF->fetch_assoc()) {
    $ufs[] = $row;
    }
} else {
    $erro = "Erro ao carregar os estados: " . $conexao->error;
}

// Carregar municípios agrupados por estado
$municipios = [];

// Verificar estrutura da tabela municipio para descobrir o nome correto da coluna de estado
$resultCheck = $conexao->query("SHOW COLUMNS FROM municipio");

$estadoColumn = null;
if ($resultCheck) {
    while ($column = $resultCheck->fetch_assoc()) {
        $columnName = strtolower($column['Field']);
        // Verificar se a coluna contém 'estado' ou 'uf' no nome
        if ((strpos($columnName, 'estado') !== false || strpos($columnName, 'uf') !== false) && $columnName !== 'id') {
            $estadoColumn = $column['Field'];
            break;
        }
    }
}

// Se não encontrou, tenta os nomes mais comuns
if (!$estadoColumn) {
    // Tenta verificar se a coluna existe testando uma query
    $testColumns = ['estado_id', 'uf_id', 'id_estado', 'id_uf'];
    foreach ($testColumns as $col) {
        $testQuery = $conexao->query("SELECT $col FROM municipio LIMIT 1");
        if ($testQuery) {
            $estadoColumn = $col;
            break;
        }
    }
}

// Se ainda não encontrou, usa estado_id como padrão
if (!$estadoColumn) {
    $estadoColumn = 'estado_id';
}

// Buscar todos os municípios
$queryMunicipios = "SELECT id, nome, $estadoColumn FROM municipio ORDER BY nome ASC";
$resultMunicipios = $conexao->query($queryMunicipios);

if ($resultMunicipios) {
    while ($row = $resultMunicipios->fetch_assoc()) {
        $estadoId = intval($row[$estadoColumn]);
        if (!isset($municipios[$estadoId])) {
            $municipios[$estadoId] = [];
        }
        $municipios[$estadoId][] = [
            'id' => intval($row['id']),
            'nome' => $row['nome']
        ];
    }
} else {
    $erro = "Erro ao carregar os municípios: " . $conexao->error;
    // Debug: mostrar erro detalhado
    error_log("Erro na query de municípios: " . $conexao->error);
}

// Diferenciar entre GET e POST
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Requisição para buscar dados de um usuário
    header('Content-Type: application/json; charset=utf-8');
    $id = intval($_GET['id']);

    $query = "SELECT id, nome, email, municipio_id, ADM FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($query);

    if (!$stmt) {
        echo json_encode(['error' => 'Erro ao preparar a consulta.']);
        exit;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Usuário não encontrado.']);
    }
    exit;
}

// Requisição para registrar um novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    // Corrigido: usar 'UF' que é o name do select, ou padronizar para 'uf'
    $uf_id = intval($_POST['uf'] ?? 0);
    $municipio_id = intval($_POST['municipio'] ?? 0);
    
    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha) || $uf_id <= 0 || $municipio_id <= 0) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        $senha = password_hash($senha, PASSWORD_BCRYPT);

    // Validar se o município pertence ao estado selecionado
        $municipioValido = false;
        if (isset($municipios[$uf_id])) {
            foreach ($municipios[$uf_id] as $municipio) {
                if ($municipio['id'] == $municipio_id) {
                    $municipioValido = true;
                    break;
                }
            }
        }
        
        if (!$municipioValido) {
        $erro = "Município inválido para o estado selecionado.";
    } else {
        // Verificar se o e-mail já existe (em usuarios OU clientes)
        $email_check_query = "SELECT id FROM usuarios WHERE email = ? UNION SELECT id FROM clientes WHERE email = ?";
        $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $email_existe_usuarios = $stmt->num_rows > 0;
        $stmt->close();
        
        $stmt2 = $conexao->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $stmt2->store_result();
        $email_existe_clientes = $stmt2->num_rows > 0;
        $stmt2->close();

        if ($email_existe_usuarios || $email_existe_clientes) {
            $erro = "O e-mail informado já está cadastrado.";
        } else {
            // Inserir o CLIENTE no banco de dados
            $insert_query = "INSERT INTO clientes (nome, email, senha, estado_id, municipio_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexao->prepare($insert_query);
            $stmt->bind_param("sssii", $nome, $email, $senha, $uf_id, $municipio_id);

            if ($stmt->execute()) {
                    $cliente_id = $stmt->insert_id;
                    
                    // Salvar setores selecionados pelo cliente
                    if (isset($_POST['setores']) && is_array($_POST['setores'])) {
                        $setores_selecionados = array_map('intval', $_POST['setores']);
                        
                        // Inserir relacionamento cliente-setor
                        $insert_setor_query = "INSERT INTO cliente_setores (cliente_id, setor_id) VALUES (?, ?)";
                        $stmt_setor = $conexao->prepare($insert_setor_query);
                        
                        foreach ($setores_selecionados as $setor_id) {
                            if ($setor_id > 0) {
                                $stmt_setor->bind_param("ii", $cliente_id, $setor_id);
                                $stmt_setor->execute();
                            }
                        }
                        $stmt_setor->close();
                    }
                    
                $sucesso = true;
            } else {
                $erro = "Erro ao registrar cliente. Tente novamente.";
                }
            }
        }
    }

    // Redirecionar para login após registro bem-sucedido
    if ($sucesso) {
        header('Location: login.php?registro=success');
        exit;
    } else {
        // Se houver erro, continuar na página para exibir mensagem
    }
}

// Buscar setores ativos para seleção
$setores = [];
try {
    $querySetores = "SELECT id, nome FROM setores WHERE ativo = 'S' ORDER BY nome ASC";
    $resultSetores = $conexao->query($querySetores);
    
    if ($resultSetores) {
        while ($row = $resultSetores->fetch_assoc()) {
            $setores[] = $row;
        }
    }
} catch (Exception $e) {
    // Se a tabela não existir, criar uma lista vazia
    $setores = [];
    error_log("Erro ao buscar setores: " . $e->getMessage());
}
?>





<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            padding-bottom: 80px; /* Espaço para o footer */
        }

        /* Efeito de partículas animadas no fundo */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
            opacity: 0.3;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .register-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            flex: 1;
            display: flex;
            align-items: center;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
            width: 100%;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(255, 111, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .register-header .logo-icon i {
            font-size: 40px;
            color: white;
        }

        .register-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #ff6f00;
            margin-bottom: 10px;
        }

        .register-header p {
            color: #333;
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group label i {
            color: #ff6f00;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff6f00;
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 111, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #999;
        }

        /* Seção de Sistemas/Setores */
        .sistemas-section {
            margin: 30px 0;
        }

        .sistemas-section.full-width {
            grid-column: 1 / -1;
        }

        .sistemas-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sistemas-title i {
            color: #ff6f00;
        }

        .sistemas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            max-height: 300px;
            overflow-y: auto;
            padding: 5px;
        }

        .sistemas-grid::-webkit-scrollbar {
            width: 6px;
        }

        .sistemas-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .sistemas-grid::-webkit-scrollbar-thumb {
            background: #ff6f00;
            border-radius: 10px;
        }

        .sistema-card {
            position: relative;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .sistema-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--sistema-cor, #ff6f00);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .sistema-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--sistema-cor, #ff6f00);
        }

        .sistema-card:hover::before {
            transform: scaleX(1);
        }

        .sistema-card.selected {
            border-color: var(--sistema-cor, #ff6f00);
            background: linear-gradient(135deg, var(--sistema-cor, #ff6f00) 15%, white 15%);
            box-shadow: 0 5px 15px rgba(255, 111, 0, 0.3);
        }

        .sistema-card.selected::before {
            transform: scaleX(1);
        }

        .sistema-card input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }

        .sistema-icon {
            font-size: 32px;
            color: var(--sistema-cor, #ff6f00);
            margin-bottom: 8px;
            transition: transform 0.3s ease;
        }

        .sistema-card:hover .sistema-icon,
        .sistema-card.selected .sistema-icon {
            transform: scale(1.2) rotate(5deg);
        }

        .sistema-nome {
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
            margin-top: 5px;
        }

        .sistema-card.selected .sistema-nome {
            color: var(--sistema-cor, #ff6f00);
        }

        .checkmark {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            background: var(--sistema-cor, #ff6f00);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .sistema-card.selected .checkmark {
            display: flex;
            animation: checkPop 0.3s ease;
        }

        @keyframes checkPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .senha-strength {
            margin-top: 10px;
        }

        .progress {
            height: 6px;
            border-radius: 10px;
            background: #e0e0e0;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        #feedback-senha {
            font-size: 0.85rem;
            margin-top: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 111, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 0, 0.5);
            background: linear-gradient(135deg, #ff8c1a 0%, #ff6f00 100%);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            font-size: 18px;
        }

        .btn-back {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: #ff6f00;
            border: 2px solid #ff6f00;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #ff6f00;
            color: white;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            pointer-events: none;
        }

        .input-icon-wrapper .form-control {
            padding-left: 45px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            z-index: 100;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        footer a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        footer a:hover {
            text-decoration: underline;
            opacity: 0.8;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
                padding-bottom: 80px;
            }

            .register-container {
                padding: 25px 20px;
            }

            .sistemas-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }

            .register-header h2 {
                font-size: 1.5rem;
            }

            .register-header .logo-icon {
                width: 60px;
                height: 60px;
            }

            .register-header .logo-icon i {
                font-size: 30px;
            }

            footer {
                padding: 10px 15px;
                font-size: 0.75rem;
            }
        }

        /* Animação de loading para municípios */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #ff6f00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
        const municipios = <?= json_encode($municipios, JSON_UNESCAPED_UNICODE); ?>;

        console.log('Municípios carregados:', municipios);

       function atualizarMunicipios() {
            const ufSelect = document.getElementById('uf');
            const municipioSelect = document.getElementById('municipio');
            const municipioHelp = document.getElementById('municipio-help');
            
            if (!ufSelect || !municipioSelect) {
                console.error('Elementos do formulário não encontrados');
                return;
            }
            
            const ufId = parseInt(ufSelect.value); // Converter para número
            
            // Se nenhum estado foi selecionado
            if (!ufId || ufId <= 0) {
                municipioSelect.innerHTML = '<option value="" disabled selected>Primeiro selecione um estado</option>';
                municipioSelect.disabled = true;
                if (municipioHelp) municipioHelp.style.display = 'none';
                return;
            }

    // Limpar o campo de municípios
            municipioSelect.innerHTML = '<option value="" disabled selected>Carregando...</option>';
            
            // Desabilitar o select de municípios enquanto carrega
            municipioSelect.disabled = true;
            if (municipioHelp) municipioHelp.style.display = 'block';
            
            // Tentar primeiro usar os dados já carregados (mais rápido)
            if (municipios && municipios[ufId] && municipios[ufId].length > 0) {
                // Ordenar municípios por nome
                const municipiosOrdenados = municipios[ufId].sort((a, b) => {
                    return a.nome.localeCompare(b.nome, 'pt-BR');
                });
                
                // Limpar e adicionar opções
                municipioSelect.innerHTML = '<option value="" disabled selected>Selecione um município</option>';
                
                municipiosOrdenados.forEach(municipio => {
                    const option = document.createElement('option');
                    option.value = municipio.id;
                    option.textContent = municipio.nome;
                    municipioSelect.appendChild(option);
                });
                
                municipioSelect.disabled = false;
                if (municipioHelp) municipioHelp.style.display = 'none';
                console.log(`${municipiosOrdenados.length} municípios carregados para o estado ID: ${ufId}`);
            } else {
                // Se não encontrou nos dados carregados, buscar via AJAX
                fetch(`get_municipios.php?estado_id=${ufId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.municipios && data.municipios.length > 0) {
    municipioSelect.innerHTML = '<option value="" disabled selected>Selecione um município</option>';

                            data.municipios.forEach(municipio => {
            const option = document.createElement('option');
            option.value = municipio.id;
            option.textContent = municipio.nome;
            municipioSelect.appendChild(option);
        });
                            
                            municipioSelect.disabled = false;
                            if (municipioHelp) municipioHelp.style.display = 'none';
                            console.log(`${data.total} municípios carregados via AJAX para o estado ID: ${ufId}`);
    } else {
                            municipioSelect.innerHTML = '<option value="" disabled selected>Nenhum município encontrado</option>';
                            municipioSelect.disabled = true;
                            if (municipioHelp) municipioHelp.style.display = 'none';
                            console.warn(`Nenhum município encontrado para o estado com ID: ${ufId}`);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar municípios:', error);
                        municipioSelect.innerHTML = '<option value="" disabled selected>Erro ao carregar municípios</option>';
                        municipioSelect.disabled = true;
                        if (municipioHelp) municipioHelp.style.display = 'none';
                    });
            }
        }
        
        // Aguardar o DOM carregar antes de adicionar o event listener
        document.addEventListener('DOMContentLoaded', function() {
            const ufSelect = document.getElementById('uf');
            if (ufSelect) {
                ufSelect.addEventListener('change', atualizarMunicipios);
                console.log('Event listener adicionado ao select de estados');
            } else {
                console.error('Select de estados não encontrado');
            }

            // Adicionar interatividade aos cards de setores
            const setorCards = document.querySelectorAll('.sistema-card');
            console.log('Cards encontrados:', setorCards.length);
            
            setorCards.forEach((card, index) => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                
                if (!checkbox) {
                    console.error('Checkbox não encontrado no card', index);
                    return;
                }
                
                // Função para atualizar visual
                const updateVisual = () => {
                    if (checkbox.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                };
                
                // Clique no card inteiro
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle do checkbox
                    checkbox.checked = !checkbox.checked;
                    
                    // Atualizar visual
                    updateVisual();
                    
                    console.log('Card clicado:', card.querySelector('.sistema-nome')?.textContent, 'Checked:', checkbox.checked);
                });

                // Atualizar visual quando checkbox muda (por outros meios)
                checkbox.addEventListener('change', function() {
                    updateVisual();
                });
                
                // Inicializar visual
                updateVisual();
            });
        });


        function verificarForcaSenha() {
            const senha = document.getElementById('senha').value;
            const barraProgresso = document.getElementById('barra-progresso');
            const feedback = document.getElementById('feedback-senha');

            let forca = 0;

            if (senha.length >= 8) forca++;
            if (/[A-Z]/.test(senha)) forca++;
            if (/[a-z]/.test(senha)) forca++;
            if (/\d/.test(senha)) forca++;
            if (/[@$!%*?&]/.test(senha)) forca++;

            barraProgresso.style.width = `${forca * 20}%`;

            if (forca === 0) {
                feedback.textContent = "Muito fraca";
                barraProgresso.className = "progress-bar bg-danger";
            } else if (forca <= 2) {
                feedback.textContent = "Fraca";
                barraProgresso.className = "progress-bar bg-warning";
            } else if (forca === 3) {
                feedback.textContent = "Moderada";
                barraProgresso.className = "progress-bar bg-info";
            } else if (forca === 4) {
                feedback.textContent = "Forte";
                barraProgresso.className = "progress-bar bg-success";
            } else if (forca === 5) {
                feedback.textContent = "Muito forte";
                barraProgresso.className = "progress-bar bg-primary";
            }
        }
    </script>
</head>
<body>
<div class="register-wrapper">
<div class="register-container">
        <div class="register-header">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Criar Conta</h2>
            <p>Preencha os dados abaixo para se registrar</p>
        </div>

    <?php if ($sucesso): ?>
        <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Sucesso!</strong> Cadastro realizado com sucesso! Você será redirecionado para o login em breve.
                </div>
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        </script>
    <?php elseif (!empty($erro)): ?>
        <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Erro!</strong> <?= htmlspecialchars($erro) ?>
                </div>
        </div>
    <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-user"></i> Nome Completo
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite seu nome completo" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com" required>
                    </div>
        </div>
        </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="uf">
                        <i class="fas fa-map-marker-alt"></i> Estado (UF)
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <select id="uf" name="uf" class="form-control" required>
    <option value="" disabled selected>Selecione um estado</option>
                            <?php foreach ($ufs as $uf): ?>
                                <option value="<?= $uf['id'] ?>"><?= htmlspecialchars($uf['nome']) ?></option>
    <?php endforeach; ?>
</select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="municipio">
                        <i class="fas fa-city"></i> Município
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-city"></i>
                        <select id="municipio" name="municipio" class="form-control" required disabled>
                            <option value="" disabled selected>Primeiro selecione um estado</option>
            </select>
        </div>
                    <small id="municipio-help" class="form-text text-muted" style="display: none; margin-top: 5px;">
                        <span class="loading-spinner"></span> Carregando municípios...
                    </small>
                </div>
            </div>

            <!-- Seção de Setores -->
            <div class="form-row">
                <div class="sistemas-section form-group full-width">
                <div class="sistemas-title">
                    <i class="fas fa-building"></i>
                    <span>Qual setor você faz parte? <small class="text-muted">(Selecione um ou mais)</small></span>
                </div>
                <div class="sistemas-grid" id="setoresGrid">
                    <?php if (empty($setores)): ?>
                        <div class="alert alert-info" style="grid-column: 1 / -1; margin: 0;">
                            <i class="fas fa-info-circle"></i>
                            <div>Nenhum setor cadastrado ainda. Entre em contato com o administrador.</div>
                        </div>
                    <?php else: ?>
                        <?php 
                        // Ícones padrão para setores (pode ser personalizado depois)
                        $icones_setores = [
                            'Saúde' => 'fas fa-heartbeat',
                            'Assistência Social' => 'fas fa-hands-helping',
                            'Ensino' => 'fas fa-graduation-cap',
                            'Biblioteca' => 'fas fa-book',
                            'Flowdocs' => 'fas fa-file-alt',
                            'Tributos' => 'fas fa-file-invoice-dollar',
                            'Ouvidoria' => 'fas fa-comments',
                            'Protocolo' => 'fas fa-clipboard-list',
                            'Compras' => 'fas fa-shopping-cart',
                            'Licitação' => 'fas fa-gavel',
                            'Frotas' => 'fas fa-car',
                            'Almoxarifado' => 'fas fa-warehouse',
                            'Patrimônio' => 'fas fa-boxes',
                            'Contabilidade' => 'fas fa-calculator',
                            'Custos' => 'fas fa-chart-line',
                            'Terceiro Setor' => 'fas fa-hand-holding-heart',
                            'Controle Interno' => 'fas fa-shield-alt',
                            'Gestor Municipal' => 'fas fa-user-tie',
                            'Documentos Eletrônicos' => 'fas fa-file-pdf',
                            'Recursos Humanos' => 'fas fa-users',
                            'Folha de Pagamento' => 'fas fa-money-check-alt',
                            'Administração' => 'fas fa-building'
                        ];
                        foreach ($setores as $setor): 
                            $icone = $icones_setores[$setor['nome']] ?? 'fas fa-building';
                        ?>
                            <label class="sistema-card" style="--sistema-cor: #ff6f00">
                                <input type="checkbox" name="setores[]" value="<?= $setor['id'] ?>" class="sistema-checkbox">
                                <div class="sistema-icon">
                                    <i class="<?= htmlspecialchars($icone) ?>"></i>
                                </div>
                                <div class="sistema-nome"><?= htmlspecialchars($setor['nome']) ?></div>
                                <div class="checkmark">
                                    <i class="fas fa-check"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                <label for="senha">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-lock"></i>
            <input type="password" id="senha" name="senha" class="form-control" placeholder="Crie uma senha forte" oninput="verificarForcaSenha()" required>
                </div>
                <div class="senha-strength">
                    <div class="progress">
                <div id="barra-progresso" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
            </div>
                    <span id="feedback-senha">
                        <i class="fas fa-info-circle"></i> Muito fraca
                    </span>
                </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i>
                        Criar Conta
                    </button>
                </div>
        </div>
    </form>

        <a href="login.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Voltar para Login
        </a>
        </div>
</div>

<footer>
    <p>&copy; 2024 Sua Empresa. Todos os direitos reservados. <a href="#">Política de Privacidade</a></p>
</footer>
</body>
</html>
