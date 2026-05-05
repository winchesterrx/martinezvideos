<?php
session_start();
include 'db/conexao.php';
$conexao->set_charset("utf8");

// Verifica se o usuário está logado E é administrador
$is_logged_in = isset($_SESSION['user_id']);
$usuario_nome = $_SESSION['user_nome'] ?? null;
$usuario_adm = isset($_SESSION['user_adm']) && $_SESSION['user_adm'] === true;

// REDIRECIONAR SE NÃO FOR ADMINISTRADOR
if (!$is_logged_in || !$usuario_adm) {
    header('Location: login.php?erro=acesso_negado');
    exit;
}

// Atualiza o status do sistema (Ativar/Inativar) usando prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && isset($_POST['sistema_id'])) {
    $sistema_id = intval($_POST['sistema_id']);
    $acao = $_POST['acao'] === 'ativar' ? 'S' : 'N';

    $update_query = "UPDATE sistemas SET ativo = ? WHERE id = ?";
    $stmt = $conexao->prepare($update_query);
    if ($stmt) {
        $stmt->bind_param("si", $acao, $sistema_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_sistemas.php');
    exit;
}

// Exclui um sistema usando prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['sistema_id'])) {
    $sistema_id = intval($_POST['sistema_id']);
    $delete_query = "DELETE FROM sistemas WHERE id = ?";
    $stmt = $conexao->prepare($delete_query);
    if ($stmt) {
        $stmt->bind_param("i", $sistema_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_sistemas.php');
    exit;
}

// Adiciona um novo sistema usando prepared statement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_sistema'])) {
    $nome_sistema = trim($_POST['nome_sistema']);
    $descricao = trim($_POST['descricao'] ?? '');
    $icone = trim($_POST['icone'] ?? 'fas fa-cog');
    $cor = trim($_POST['cor'] ?? '#ff6f00');
    
    $insert_query = "INSERT INTO sistemas (nome, descricao, icone, cor, ativo) VALUES (?, ?, ?, ?, 'S')";
    $stmt = $conexao->prepare($insert_query);
    if ($stmt) {
        $stmt->bind_param("ssss", $nome_sistema, $descricao, $icone, $cor);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cadastro_sistemas.php');
    exit;
}

// Busca todos os sistemas
$sistemas_query = "SELECT * FROM sistemas ORDER BY nome ASC";
$sistemas_result = $conexao->query($sistemas_query);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Sistemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
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

        .main-title {
            text-align: center;
            margin: 20px 0;
            font-size: 2.5rem;
            font-weight: 700;
            color: #ff6f00;
        }

        .btn-back {
            background: #ff6f00;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #ff8c1a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.4);
        }

        .form-add {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-add h3 {
            color: #ff6f00;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .form-add .form-group {
            margin-bottom: 15px;
        }

        .form-add label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .form-add input, .form-add textarea, .form-add select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            transition: all 0.3s;
        }

        .form-add input:focus, .form-add textarea:focus, .form-add select:focus {
            border-color: #ff6f00;
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
            outline: none;
        }

        .btn-add {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.4);
            background: linear-gradient(135deg, #ff8c1a 0%, #ff6f00 100%);
        }

        .table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8c1a 100%);
            color: white;
        }

        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .sistema-icon-preview {
            font-size: 24px;
            color: var(--sistema-cor);
        }

        .btn-status {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-status.ativar {
            background: #28a745;
            color: white;
        }

        .btn-status.ativar:hover {
            background: #218838;
        }

        .btn-status.inativar {
            background: #dc3545;
            color: white;
        }

        .btn-status.inativar:hover {
            background: #c82333;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-block;
            border: 2px solid #ddd;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-status.ativo {
            background: #d4edda;
            color: #155724;
        }

        .badge-status.inativo {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h4><i class="fas fa-laptop-code"></i> Gerenciamento de Sistemas</h4>
        </div>
        <div class="welcome">
            <i class="fas fa-user"></i> <?= htmlspecialchars($usuario_nome) ?>
        </div>
    </div>

    <div class="container mt-4">
        <h1 class="main-title">
            <i class="fas fa-cogs"></i> Gerenciar Sistemas
        </h1>

        <div class="mb-3">
            <a href="javascript:history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Formulário para adicionar novo sistema -->
        <div class="form-add">
            <h3><i class="fas fa-plus-circle"></i> Adicionar Novo Sistema</h3>
            <form method="POST" action="cadastro_sistemas.php">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nome_sistema"><i class="fas fa-tag"></i> Nome do Sistema</label>
                            <input type="text" id="nome_sistema" name="nome_sistema" class="form-control" placeholder="Ex: Bancada 1 - Saúde" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="icone"><i class="fas fa-icons"></i> Ícone (Font Awesome)</label>
                            <input type="text" id="icone" name="icone" class="form-control" placeholder="fas fa-heartbeat" value="fas fa-cog">
                            <small class="text-muted">Ex: fas fa-heartbeat, fas fa-calculator</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cor"><i class="fas fa-palette"></i> Cor</label>
                            <input type="color" id="cor" name="cor" class="form-control" value="#ff6f00">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="2" placeholder="Descrição do sistema (opcional)"></textarea>
                </div>
                <button type="submit" class="btn-add">
                    <i class="fas fa-plus"></i> Adicionar Sistema
                </button>
            </form>
        </div>

        <!-- Tabela de Sistemas -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ícone</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Cor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sistemas_result && $sistemas_result->num_rows > 0): ?>
                        <?php while ($sistema = $sistemas_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $sistema['id'] ?></td>
                                <td>
                                    <i class="<?= htmlspecialchars($sistema['icone']) ?>" style="color: <?= htmlspecialchars($sistema['cor']) ?>; font-size: 24px;"></i>
                                </td>
                                <td><strong><?= htmlspecialchars($sistema['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($sistema['descricao'] ?? '-') ?></td>
                                <td>
                                    <div class="color-preview" style="background-color: <?= htmlspecialchars($sistema['cor']) ?>;"></div>
                                    <small><?= htmlspecialchars($sistema['cor']) ?></small>
                                </td>
                                <td>
                                    <span class="badge-status <?= $sistema['ativo'] === 'S' ? 'ativo' : 'inativo' ?>">
                                        <?= $sistema['ativo'] === 'S' ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="cadastro_sistemas.php" class="d-inline">
                                        <input type="hidden" name="sistema_id" value="<?= $sistema['id'] ?>">
                                        <?php if ($sistema['ativo'] === 'S'): ?>
                                            <button type="submit" name="acao" value="inativar" class="btn-status inativar">
                                                <i class="fas fa-times"></i> Inativar
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="acao" value="ativar" class="btn-status ativar">
                                                <i class="fas fa-check"></i> Ativar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" action="cadastro_sistemas.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este sistema?');">
                                        <input type="hidden" name="sistema_id" value="<?= $sistema['id'] ?>">
                                        <button type="submit" name="delete" value="delete" class="btn-delete">
                                            <i class="fas fa-trash-alt"></i> Excluir
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-info-circle"></i> Nenhum sistema cadastrado ainda.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

