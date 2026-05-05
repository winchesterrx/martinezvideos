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

// Configuração de busca
$busca_nome = isset($_GET['pesquisaNome']) ? trim($_GET['pesquisaNome']) : '';

// Configuração de paginação
$usuarios_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $usuarios_por_pagina;

// Configuração de ordenação
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$direcao = isset($_GET['direcao']) ? $_GET['direcao'] : 'asc';

// Valida os critérios de ordenação
$colunas_permitidas = ['id', 'nome', 'ADM', 'estado', 'cidade'];
if (!in_array($ordenar_por, $colunas_permitidas)) {
    $ordenar_por = 'id';
}
$direcao = ($direcao === 'desc') ? 'desc' : 'asc';

// Query para contar total de usuários
$total_query = "SELECT COUNT(*) as total FROM usuarios 
                WHERE nome LIKE ? OR email LIKE ?";
$total_stmt = $conexao->prepare($total_query);
$search_param = "%$busca_nome%";
$total_stmt->bind_param('ss', $search_param, $search_param);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_usuarios = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_usuarios / $usuarios_por_pagina);

// Query para listagem de usuários
$usuarios_query = "SELECT u.id, u.nome, u.email, u.ADM, m.nome AS cidade, e.sigla AS estado
                   FROM usuarios u
                   LEFT JOIN municipio m ON u.municipio_id = m.id
                   LEFT JOIN UF e ON u.estado_id = e.id
                   WHERE u.nome LIKE ? OR u.email LIKE ?
                   ORDER BY $ordenar_por $direcao
                   LIMIT $usuarios_por_pagina OFFSET $offset";
$stmt = $conexao->prepare($usuarios_query);
$stmt->bind_param('ss', $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Usuários</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            min-height: 100vh;
            padding-top: 70px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            color: white;
            padding: 12px 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header img {
            height: 45px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .header img:hover {
            transform: scale(1.05);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-actions .btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .header-actions .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .page-title .badge {
    background: #ff6f00;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .btn-add-new {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-add-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 0, 0.4);
            color: white;
        }

        .filters-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .filters-card .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }

        .filters-card .form-control:focus {
            border-color: #ff6f00;
            box-shadow: 0 0 0 4px rgba(255, 111, 0, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 111, 0, 0.3);
        }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table {
            margin: 0;
        }

        .table thead {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            color: white;
        }

        .table thead th {
            border: none;
            padding: 18px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table thead th a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table thead th a:hover {
            opacity: 0.9;
        }

        .table tbody tr {
            transition: all 0.3s;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table tbody td {
            padding: 18px 20px;
            vertical-align: middle;
            color: #333;
        }

        .badge-admin {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-admin.sim {
            background: #d4edda;
            color: #155724;
        }

        .badge-admin.nao {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: #ff6f00;
            color: white;
        }

        .btn-edit:hover {
            background: #ff8c1a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .pagination-wrapper {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .pagination .page-link {
            color: #ff6f00;
            border: 1px solid #e0e0e0;
            padding: 10px 16px;
            margin: 0 4px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .pagination .page-link:hover {
            background: #ff6f00;
            color: white;
            border-color: #ff6f00;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #ff6f00, #ff8c1a);
            border-color: #ff6f00;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th,
            .table tbody td {
                padding: 12px 8px;
            }

            .btn-action {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
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

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
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
        <div class="header-content">
            <button class="theme-toggle" id="themeToggle" title="Alternar tema">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <img src="img/martinez.png" alt="Logo" onclick="window.location.href='index.php'">
            <div class="header-actions">
                <button class="theme-toggle" id="themeToggle" title="Alternar tema">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($usuario_nome) ?></span>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Início</a>
        <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
    </div>
</div>

    <div class="container-main">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1>Usuários do Sistema</h1>
                    <span class="badge"><?= $total_usuarios ?> cadastrados</span>
                </div>
            </div>
            <a href="registro.php" class="btn-add-new">
                <i class="fas fa-user-plus"></i>
                Novo Usuário
            </a>
</div>

        <!-- Filtros -->
        <div class="filters-card">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text" style="background: #ff6f00; color: white; border: none;">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="pesquisaNome" 
                               placeholder="Pesquisar por nome ou email..." 
                               value="<?= htmlspecialchars($busca_nome) ?>" 
                               class="form-control">
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-filter">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabela -->
        <div class="table-card">
    <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
    <tr>
        <th>
                                <a href="?ordenar_por=id&direcao=<?= $ordenar_por === 'id' && $direcao === 'asc' ? 'desc' : 'asc' ?>&pesquisaNome=<?= urlencode($busca_nome) ?>">
                ID <?= $ordenar_por === 'id' ? ($direcao === 'asc' ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>') : '' ?>
            </a>
        </th>
        <th>
                                <a href="?ordenar_por=nome&direcao=<?= $ordenar_por === 'nome' && $direcao === 'asc' ? 'desc' : 'asc' ?>&pesquisaNome=<?= urlencode($busca_nome) ?>">
                Nome <?= $ordenar_por === 'nome' ? ($direcao === 'asc' ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>') : '' ?>
            </a>
        </th>
        <th>Email</th>
        <th>
                                <a href="?ordenar_por=cidade&direcao=<?= $ordenar_por === 'cidade' && $direcao === 'asc' ? 'desc' : 'asc' ?>&pesquisaNome=<?= urlencode($busca_nome) ?>">
                Cidade <?= $ordenar_por === 'cidade' ? ($direcao === 'asc' ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>') : '' ?>
            </a>
        </th>
        <th>
                                <a href="?ordenar_por=estado&direcao=<?= $ordenar_por === 'estado' && $direcao === 'asc' ? 'desc' : 'asc' ?>&pesquisaNome=<?= urlencode($busca_nome) ?>">
                Estado <?= $ordenar_por === 'estado' ? ($direcao === 'asc' ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>') : '' ?>
            </a>
        </th>
        <th>
                                <a href="?ordenar_por=ADM&direcao=<?= $ordenar_por === 'ADM' && $direcao === 'asc' ? 'desc' : 'asc' ?>&pesquisaNome=<?= urlencode($busca_nome) ?>">
                Administrador <?= $ordenar_por === 'ADM' ? ($direcao === 'asc' ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>') : '' ?>
            </a>
        </th>
        <th>Ações</th>
    </tr>
</thead>
<tbody>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2" style="width: 40px; height: 40px; background: linear-gradient(135deg, #ff6f00, #ff8c1a); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                                <?= strtoupper(substr($row['nome'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($row['nome']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-envelope text-muted me-1"></i>
                                        <?= htmlspecialchars($row['email']) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['cidade']): ?>
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            <?= htmlspecialchars($row['cidade']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['estado']): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($row['estado']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-admin <?= $row['ADM'] === 'S' ? 'sim' : 'nao' ?>">
                                            <?= $row['ADM'] === 'S' ? '<i class="fas fa-shield-alt"></i> Sim' : '<i class="fas fa-user"></i> Não' ?>
                                        </span>
                </td>
                <td>
                    <div class="d-flex gap-2">
                                            <button class="btn btn-action btn-edit btn-edit-user" 
    data-id="<?= $row['id'] ?>" 
    data-bs-toggle="modal" 
    data-bs-target="#editUserModal">
    <i class="fas fa-edit"></i> Editar
</button>
                                            <button class="btn btn-action btn-delete btn-excluir" 
                                                    data-id="<?= $row['id'] ?>">
                    <i class="fas fa-trash-alt"></i> Excluir
                </button>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>Nenhum usuário encontrado</h3>
                                    <p>Comece cadastrando seu primeiro usuário do sistema.</p>
                                    <a href="registro.php" class="btn-add-new mt-3" style="display: inline-flex;">
                                        <i class="fas fa-user-plus"></i> Novo Usuário
                                    </a>
            </td>
        </tr>
    <?php endif; ?>
</tbody>

                    </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination-wrapper">
<nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $i ?>&pesquisaNome=<?= urlencode($busca_nome) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal para Edição -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ff6f00, #ff8c1a); color: white;">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="fas fa-edit"></i> Editar Usuário
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" name="id" id="editUserId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editNome" class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNome" name="nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="editCidade" name="cidade">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editAdm" class="form-label">Administrador</label>
                                <select class="form-control" id="editAdm" name="adm">
                                    <option value="0">Não</option>
                                    <option value="1">Sim</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveUserChanges" style="background: linear-gradient(135deg, #ff6f00, #ff8c1a); border: none;">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = new bootstrap.Modal(document.getElementById("editUserModal"));

            // Editar usuário
            document.querySelectorAll(".btn-edit-user").forEach(button => {
        button.addEventListener("click", () => {
            const userId = button.getAttribute("data-id");
                    fetch(`registro.php?id=${userId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                return;
                            }
                            document.getElementById("editUserId").value = data.id;
                            document.getElementById("editNome").value = data.nome || '';
                            document.getElementById("editEmail").value = data.email || '';
                            document.getElementById("editCidade").value = data.municipio_id || '';
                            document.getElementById("editAdm").value = data.ADM === 'S' ? '1' : '0';
                        })
                        .catch(err => {
                            console.error("Erro ao carregar dados:", err);
                            alert("Erro ao carregar dados do usuário.");
                        });
        });
    });

            // Salvar alterações
    document.getElementById("saveUserChanges").addEventListener("click", () => {
        const form = document.getElementById("editUserForm");
        const formData = new FormData(form);

                fetch("update_usuarios.php", {
            method: "POST",
            body: formData
        })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }
                    return response.text();
                })
        .then(data => {
                    if (data) {
                        try {
                            const json = JSON.parse(data);
                            if (json.success) {
                alert("Usuário atualizado com sucesso!");
                                location.reload();
            } else {
                                alert("Erro: " + (json.error || 'Erro desconhecido'));
                            }
                        } catch (e) {
                            // Se não for JSON, pode ser redirect
                            location.reload();
                        }
                    }
                })
                .catch(err => {
                    console.error("Erro:", err);
                    alert("Erro ao salvar alterações.");
    });
});

            // Excluir usuário
    document.querySelectorAll(".btn-excluir").forEach(button => {
        button.addEventListener("click", () => {
            const userId = button.getAttribute("data-id");
            if (confirm("Tem certeza que deseja excluir este usuário?")) {
                fetch("excluir_usuario.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${userId}`
                })
                        .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Usuário excluído com sucesso!");
                                location.reload();
                    } else {
                                alert("Erro ao excluir usuário: " + data.error);
                    }
                })
                .catch(error => {
                            console.error("Erro:", error);
                            alert("Erro inesperado ao excluir usuário.");
                });
            }
        });
    });
});
    </script>
    <script src="js/theme.js"></script>
</body>
</html>
