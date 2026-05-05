<?php
session_start();
include 'db/conexao.php';
// Configura charset para UTF-8 (utf8mb4) para suportar caracteres especiais e emojis
$conexao->set_charset("utf8mb4");
$conexao->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
$conexao->query("SET CHARACTER SET utf8mb4");
$conexao->query("SET character_set_connection=utf8mb4");
$conexao->query("SET character_set_client=utf8mb4");
$conexao->query("SET character_set_results=utf8mb4");

// Verifica se o usuário está logado E é administrador
$is_logged_in = isset($_SESSION['user_id']);
$usuario_nome = $_SESSION['user_nome'] ?? null;
$usuario_adm = isset($_SESSION['user_adm']) && $_SESSION['user_adm'] === true;

// REDIRECIONAR SE NÃO FOR ADMINISTRADOR
if (!$is_logged_in || !$usuario_adm) {
    header('Location: login.php?erro=acesso_negado');
    exit;
}

// Atualiza o status do módulo (Ativar/Inativar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && isset($_POST['modulo_id'])) {
    $modulo_id = intval($_POST['modulo_id']);
    $acao = $_POST['acao'] === 'ativar' ? 'S' : 'N';

    $update_query = "UPDATE modulos SET ativo = ? WHERE id = ?";
    $stmt = $conexao->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param("si", $acao, $modulo_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_modulos.php');
    exit;
}

// Exclui um módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['modulo_id'])) {
    $modulo_id = intval($_POST['modulo_id']);
    $delete_query = "DELETE FROM modulos WHERE id = ?";
    $stmt = $conexao->prepare($delete_query);
    if ($stmt) {
        $stmt->bind_param("i", $modulo_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_modulos.php');
    exit;
}

// Adiciona um novo módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_modulo'])) {
    $nome_modulo = trim($_POST['nome_modulo']);
    $setor_id = intval($_POST['setor_id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $icone = trim($_POST['icone'] ?? 'fas fa-cube');
    $cor = trim($_POST['cor'] ?? '#6366f1');
    
    if ($setor_id > 0) {
        $insert_query = "INSERT INTO modulos (setor_id, nome, descricao, icone, cor, ativo) VALUES (?, ?, ?, ?, ?, 'S')";
        $stmt = $conexao->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("issss", $setor_id, $nome_modulo, $descricao, $icone, $cor);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: cadastro_modulos.php');
    exit;
}

// Busca todos os setores para o select
$setores_query = "SELECT * FROM setores WHERE ativo = 'S' ORDER BY nome ASC";
$setores_result = $conexao->query($setores_query);

// Busca todos os módulos com informações do setor
$modulos_query = "SELECT m.*, s.nome AS setor_nome 
                  FROM modulos m 
                  LEFT JOIN setores s ON m.setor_id = s.id 
                  ORDER BY s.nome ASC, m.nome ASC";
$modulos_result = $conexao->query($modulos_query);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Módulos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="js/theme.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffffff;
            color: #262626;
            min-height: 100vh;
        }

        [data-theme="dark"] body {
            background-color: #1a1a1a !important;
            color: #e0e0e0;
        }

        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            padding: 24px;
            min-height: calc(100vh - 70px);
        }

        [data-theme="dark"] .main-content {
            background: #1a1a1a !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: #262626;
        }

        [data-theme="dark"] .page-title {
            color: #e0e0e0;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #efefef;
            margin-bottom: 24px;
        }

        [data-theme="dark"] .card {
            background: #1a1a1a !important;
            border-color: #363636;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #efefef;
            background: transparent;
        }

        [data-theme="dark"] .card-header {
            border-bottom-color: #363636;
        }

        .card-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #262626;
            margin-bottom: 8px;
        }

        [data-theme="dark"] .form-label {
            color: #e0e0e0;
        }

        .form-control, .form-select {
            border: 1px solid #dbdbdb;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background: #2a2a2a;
            border-color: #404040;
            color: #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0095f6;
            box-shadow: 0 0 0 3px rgba(0, 149, 246, 0.1);
        }

        .btn-primary {
            background: #0095f6;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #0284c7;
            transform: translateY(-1px);
        }

        .modulo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid #efefef;
            transition: background 0.2s ease;
        }

        [data-theme="dark"] .modulo-item {
            border-bottom-color: #363636;
        }

        .modulo-item:hover {
            background: #fafafa;
        }

        [data-theme="dark"] .modulo-item:hover {
            background: #2a2a2a;
        }

        .modulo-item:last-child {
            border-bottom: none;
        }

        .modulo-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .modulo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .modulo-details {
            flex: 1;
        }

        .modulo-nome {
            font-weight: 600;
            color: #262626;
            margin-bottom: 4px;
        }

        [data-theme="dark"] .modulo-nome {
            color: #e0e0e0;
        }

        .modulo-sistema {
            font-size: 12px;
            color: #8e8e8e;
        }

        [data-theme="dark"] .modulo-sistema {
            color: #a8a8a8;
        }

        .modulo-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-success {
            background: #10b981;
            border: none;
        }

        .btn-danger {
            background: #ef4444;
            border: none;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-ativo {
            background: #d1fae5;
            color: #065f46;
        }

        [data-theme="dark"] .badge-ativo {
            background: #064e3b;
            color: #6ee7b7;
        }

        .badge-inativo {
            background: #fee2e2;
            color: #991b1b;
        }

        [data-theme="dark"] .badge-inativo {
            background: #7f1d1d;
            color: #fca5a5;
        }

        /* Sidebar e Header Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-logo {
            max-width: 120px;
            height: auto;
        }

        .sidebar-toggle {
            display: none;
        }

        .sidebar-content {
            padding: 20px 0;
        }

        .user-info {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .user-role {
            display: block;
            font-size: 12px;
            opacity: 0.7;
        }

        .sidebar-actions {
            padding: 0 20px;
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .sidebar-btn:hover,
        .sidebar-btn.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 12px 20px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-overlay {
            display: none;
        }

        .top-header {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 70px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            padding: 0 40px;
            z-index: 999;
            gap: 30px;
        }

        [data-theme="dark"] .top-header {
            background: #2d2d2d;
            color: #e0e0e0;
        }

        .menu-toggle {
            display: none;
        }

        .page-title {
            flex: 1;
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        [data-theme="dark"] .page-title {
            color: #e0e0e0;
        }

        .page-title .highlight {
            color: #ff6f00;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .theme-toggle,
        .btn-header-login,
        .btn-header-logout {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: none;
            background: rgba(0, 0, 0, 0.05);
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        [data-theme="dark"] .theme-toggle,
        [data-theme="dark"] .btn-header-login,
        [data-theme="dark"] .btn-header-logout {
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
        }

        .theme-toggle:hover,
        .btn-header-login:hover,
        .btn-header-logout:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .user-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-header-name {
            font-size: 14px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                margin-top: 60px !important;
                padding: 16px !important;
            }

            .top-header {
                left: 0 !important;
                padding: 0 12px;
                height: 60px;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .menu-toggle {
                display: flex !important;
                width: 44px;
                height: 44px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .modulo-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .modulo-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Sidebar será incluída aqui (mesma estrutura de video_detalhes.php)
    include 'db/funcoes_permissoes.php';
    ?>
    
    <!-- Sidebar Lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="img/martinez.png" alt="Logo" class="sidebar-logo">
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="sidebar-content">
            <?php if ($is_logged_in): ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($usuario_nome) ?></span>
                        <span class="user-role"><?= $usuario_adm ? 'Administrador' : 'Usuário' ?></span>
                    </div>
                </div>
                
                <div class="sidebar-actions">
                    <a href="index.php" class="sidebar-btn">
                        <i class="fas fa-home"></i>
                        <span>Início</span>
                    </a>
                    <?php if ($usuario_adm): ?>
                        <div class="sidebar-divider"></div>
                        <a href="listar_usuarios.php" class="sidebar-btn">
                            <i class="fas fa-user-shield"></i>
                            <span>Usuários do Sistema</span>
                        </a>
                        <a href="listar_clientes.php" class="sidebar-btn">
                            <i class="fas fa-users"></i>
                            <span>Clientes</span>
                        </a>
                        <a href="cadastro_setores.php" class="sidebar-btn">
                            <i class="fas fa-building"></i>
                            <span>Cadastro de Setores</span>
                        </a>
                        <a href="cadastro_modulos.php" class="sidebar-btn active">
                            <i class="fas fa-cubes"></i>
                            <span>Cadastro de Módulos</span>
                        </a>
                        <a href="cadastro_sistemas.php" class="sidebar-btn">
                            <i class="fas fa-cog"></i>
                            <span>Cadastro de Sistemas</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <?php if ($is_logged_in): ?>
                <a href="logout.php" class="sidebar-btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="sidebar-btn btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overlay para mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-cubes"></i> Gerenciar Módulos
            </h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Formulário para adicionar módulo -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Adicionar Novo Módulo</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Setor *</label>
                            <select name="setor_id" class="form-select" required>
                                <option value="">Selecione um setor</option>
                                <?php while ($setor = $setores_result->fetch_assoc()): ?>
                                    <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do Módulo *</label>
                            <input type="text" name="nome_modulo" class="form-control" required placeholder="Ex: Farmácia, Ambulatório">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ícone (Font Awesome)</label>
                            <input type="text" name="icone" class="form-control" value="fas fa-cube" placeholder="fas fa-cube">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cor</label>
                            <input type="color" name="cor" class="form-control form-control-color" value="#6366f1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="2" placeholder="Descrição do módulo"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Adicionar Módulo
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de módulos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Módulos Cadastrados</h5>
            </div>
            <div class="card-body p-0">
                <?php if ($modulos_result && $modulos_result->num_rows > 0): ?>
                    <?php while ($modulo = $modulos_result->fetch_assoc()): ?>
                        <div class="modulo-item">
                            <div class="modulo-info">
                                <div class="modulo-icon" style="background: <?= htmlspecialchars($modulo['cor']) ?>;">
                                    <i class="<?= htmlspecialchars($modulo['icone']) ?>"></i>
                                </div>
                                <div class="modulo-details">
                                    <div class="modulo-nome"><?= htmlspecialchars($modulo['nome']) ?></div>
                                    <div class="modulo-sistema">
                                        <i class="fas fa-folder"></i>
                                        <?= htmlspecialchars($modulo['setor_nome']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modulo-actions">
                                <span class="badge-status <?= $modulo['ativo'] === 'S' ? 'badge-ativo' : 'badge-inativo' ?>">
                                    <?= $modulo['ativo'] === 'S' ? 'Ativo' : 'Inativo' ?>
                                </span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="modulo_id" value="<?= $modulo['id'] ?>">
                                    <input type="hidden" name="acao" value="<?= $modulo['ativo'] === 'S' ? 'inativar' : 'ativar' ?>">
                                    <button type="submit" class="btn btn-sm <?= $modulo['ativo'] === 'S' ? 'btn-warning' : 'btn-success' ?>">
                                        <i class="fas fa-<?= $modulo['ativo'] === 'S' ? 'eye-slash' : 'eye' ?>"></i>
                                        <?= $modulo['ativo'] === 'S' ? 'Inativar' : 'Ativar' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este módulo?');">
                                    <input type="hidden" name="modulo_id" value="<?= $modulo['id'] ?>">
                                    <input type="hidden" name="delete" value="1">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Nenhum módulo cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Header Top -->
    <div class="top-header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><a href="index.php" style="text-decoration: none; color: inherit;">Plataforma de <span class="highlight">Treinamentos</span></a></h1>
        <div class="header-actions">
            <button class="theme-toggle" id="themeToggle" title="Alternar tema">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <?php if ($is_logged_in): ?>
                <div class="user-header-info">
                    <span class="user-header-name"><?= htmlspecialchars($usuario_nome) ?></span>
                    <a href="logout.php" class="btn-header-logout" title="Sair">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-header-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
    </script>
</body>
</html>

