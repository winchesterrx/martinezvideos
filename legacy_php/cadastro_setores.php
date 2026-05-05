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

// Atualiza o status do setor (Ativar/Inativar) usando prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && isset($_POST['setor_id'])) {
    $setor_id = intval($_POST['setor_id']);
    $acao = $_POST['acao'] === 'ativar' ? 'S' : 'N';

    $update_query = "UPDATE setores SET ativo = ? WHERE id = ?";
    $stmt = $conexao->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param("si", $acao, $setor_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_setores.php');
    exit;
}

// Exclui um setor usando prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['setor_id'])) {
    $setor_id = intval($_POST['setor_id']);
    $delete_query = "DELETE FROM setores WHERE id = ?";
    $stmt = $conexao->prepare($delete_query);
    if ($stmt) {
        $stmt->bind_param("i", $setor_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_setores.php');
    exit;
}

// Adiciona um novo setor usando prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_setor'])) {
    $nome_setor = trim($_POST['nome_setor']);
    $insert_query = "INSERT INTO setores (nome, ativo) VALUES (?, 'S')";
    $stmt = $conexao->prepare($insert_query);
    if ($stmt) {
        $stmt->bind_param("s", $nome_setor);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_setores.php');
    exit;
}

// Busca todos os setores
$setores_query = "SELECT * FROM setores";
$setores_result = mysqli_query($conexao, $setores_query);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Setores</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .header {
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .header .logo {
            height: 50px;
        }

        .header .welcome {
            font-size: 14px;
            font-weight: 500;
        }

        .main-title {
            text-align: center;
            margin: 20px 0;
            font-size: 2.5rem;
            color: #ff6f00;
            font-weight: bold;
        }

        .btn-back {
            background: transparent;
            border: 1px solid #ff6f00;
            color: #ff6f00;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #ff6f00;
            color: white;
        }

        .btn-add {
            background: #ff6f00;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            background: #ff8c1a;
        }

        .btn-status, .btn-delete {
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 12px;
            color: white;
            border: none;
            transition: transform 0.3s ease;
        }

        .btn-status.ativar {
            background-color: #28a745;
        }

        .btn-status.inativar {
            background-color: #dc3545;
        }

        .btn-delete {
            background-color: #6c757d;
        }

        .btn-status:hover, .btn-delete:hover {
            transform: scale(1.1);
        }

        .table {
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
        }

        .footer a {
            color: #ff6f00;
        }

        .footer a:hover {
            color: #ff8c1a;
        }
        /* ===== DARK MODE ===== */
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        [data-theme="dark"] body {
            background: #0f172a;
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .header {
            background: linear-gradient(90deg, #1e293b, #334155);
        }

        [data-theme="dark"] .container {
            background: #1e293b;
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .card {
            background: #334155;
            color: rgba(255, 255, 255, 0.9);
            border-color: #475569;
        }

        [data-theme="dark"] .table {
            color: rgba(255, 255, 255, 0.9);
        }

        [data-theme="dark"] .table thead th {
            background: #334155;
            color: rgba(255, 255, 255, 0.9);
            border-color: #475569;
        }

        [data-theme="dark"] .table tbody td {
            border-color: #475569;
        }

        [data-theme="dark"] .table tbody tr:hover {
            background: #475569;
        }

        [data-theme="dark"] .form-control {
            background: #334155;
            color: rgba(255, 255, 255, 0.9);
            border-color: #475569;
        }

        [data-theme="dark"] .btn-primary {
            background: #ff6f00;
            border-color: #ff6f00;
        }

        [data-theme="dark"] .btn-primary:hover {
            background: #ff8c1a;
            border-color: #ff8c1a;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="img/martinez.png" alt="Logo" class="logo">
        <div style="display: flex; align-items: center; gap: 15px;">
            <button class="theme-toggle" id="themeToggle" title="Alternar tema">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <span class="welcome">Bem-vindo, <?= htmlspecialchars($usuario_nome ?? 'Usuário') ?>!</span>
        </div>
    </div>

    <!-- Main Title -->
    <h1 class="main-title">Gerenciar Setores</h1>

    <!-- Botão Voltar -->
    <div class="container mb-3">
    <button onclick="window.history.back()" class="btn-back">
        <i class="fas fa-arrow-left"></i> Voltar
    </button>
</div>

    <!-- Adicionar Novo Setor -->
    <div class="container">
        <form method="POST" action="cadastro_setores.php" class="d-flex justify-content-center align-items-center gap-3 mt-4">
            <input type="text" name="nome_setor" class="form-control" placeholder="Digite o nome do setor" required>
            <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Adicionar</button>
        </form>

        <!-- Tabela de Setores -->
        <table class="table table-striped text-center">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($setor = mysqli_fetch_assoc($setores_result)): ?>
                    <tr>
                        <td><?= $setor['id'] ?></td>
                        <td><?= htmlspecialchars($setor['nome']) ?></td>
                        <td><?= $setor['ativo'] === 'S' ? 'Ativo' : 'Inativo' ?></td>
                        <td>
                            <form method="POST" action="cadastro_setores.php" class="d-inline">
                                <input type="hidden" name="setor_id" value="<?= $setor['id'] ?>">
                                <?php if ($setor['ativo'] === 'S'): ?>
                                    <button type="submit" name="acao" value="inativar" class="btn-status inativar"><i class="fas fa-times"></i> Inativar</button>
                                <?php else: ?>
                                    <button type="submit" name="acao" value="ativar" class="btn-status ativar"><i class="fas fa-check"></i> Ativar</button>
                                <?php endif; ?>
                            </form>
                            <form method="POST" action="cadastro_setores.php" class="d-inline">
                                <input type="hidden" name="setor_id" value="<?= $setor['id'] ?>">
                                <button type="submit" name="delete" value="delete" class="btn-delete"><i class="fas fa-trash-alt"></i> Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Gabriel Silva. Todos os direitos reservados.</p>
        <p><a href="#">Política de Privacidade</a> | <a href="#">Termos de Uso</a></p>
    </div>

    <script src="js/theme.js"></script>
</body>
</html>
