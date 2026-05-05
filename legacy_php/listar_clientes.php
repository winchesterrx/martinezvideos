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
$clientes_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_atual - 1) * $clientes_por_pagina;

// Configuração de ordenação
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$direcao = isset($_GET['direcao']) ? $_GET['direcao'] : 'asc';

// Valida os critérios de ordenação
$colunas_permitidas = ['id', 'nome', 'email', 'estado', 'cidade'];
if (!in_array($ordenar_por, $colunas_permitidas)) {
    $ordenar_por = 'id';
}
$direcao = ($direcao === 'desc') ? 'desc' : 'asc';

// Query para contar total de clientes
$total_query = "SELECT COUNT(*) as total FROM clientes 
                WHERE (nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
$total_stmt = $conexao->prepare($total_query);
$search_param = "%$busca_nome%";
$total_stmt->bind_param('sss', $search_param, $search_param, $search_param);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_clientes = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_clientes / $clientes_por_pagina);

// Query para listagem de clientes
$clientes_query = "SELECT c.id, c.nome, c.email, c.telefone, c.cpf_cnpj, c.ativo, 
                          m.nome AS cidade, e.sigla AS estado
                   FROM clientes c
                   LEFT JOIN municipio m ON c.municipio_id = m.id
                   LEFT JOIN UF e ON c.estado_id = e.id
                   WHERE (c.nome LIKE ? OR c.email LIKE ? OR c.telefone LIKE ?)
                   ORDER BY $ordenar_por $direcao
                   LIMIT $clientes_por_pagina OFFSET $offset";
$stmt = $conexao->prepare($clientes_query);
$stmt->bind_param('sss', $search_param, $search_param, $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Clientes</title>
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

        [data-theme="dark"] .container-main {
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
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h1>Clientes</h1>
                    <span class="badge"><?= $total_clientes ?> cadastrados</span>
                </div>
            </div>
            <a href="registro.php" class="btn-add-new">
                <i class="fas fa-plus"></i>
                Novo Cliente
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
                               placeholder="Pesquisar por nome, email ou telefone..." 
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
                            <th>Telefone</th>
                            <th>CPF/CNPJ</th>
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
                            <th>Status</th>
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
                                        <?php if ($row['email']): ?>
                                            <i class="fas fa-envelope text-muted me-1"></i>
                                            <?= htmlspecialchars($row['email']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['telefone']): ?>
                                            <i class="fas fa-phone text-muted me-1"></i>
                                            <?= htmlspecialchars($row['telefone']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['cpf_cnpj']): ?>
                                            <?= htmlspecialchars($row['cpf_cnpj']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
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
                                        <span class="badge-status <?= $row['ativo'] === 'S' ? 'ativo' : 'inativo' ?>">
                                            <?= $row['ativo'] === 'S' ? '<i class="fas fa-check-circle"></i> Ativo' : '<i class="fas fa-times-circle"></i> Inativo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-action btn-edit btn-edit-cliente" 
                                                    data-id="<?= $row['id'] ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editClienteModal">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn btn-action btn-delete btn-excluir-cliente" 
                                                    data-id="<?= $row['id'] ?>">
                                                <i class="fas fa-trash-alt"></i> Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>Nenhum cliente encontrado</h3>
                                    <p>Comece cadastrando seu primeiro cliente.</p>
                                    <a href="registro.php" class="btn-add-new mt-3" style="display: inline-flex;">
                                        <i class="fas fa-plus"></i> Novo Cliente
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
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

    <!-- Modal de Edição -->
    <div class="modal fade" id="editClienteModal" tabindex="-1" aria-labelledby="editClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ff6f00, #ff8c1a); color: white;">
                    <h5 class="modal-title" id="editClienteModalLabel">
                        <i class="fas fa-edit"></i> Editar Cliente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <ul class="nav nav-tabs mb-3" id="clienteTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                                <i class="fas fa-user"></i> Dados Pessoais
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="setores-tab" data-bs-toggle="tab" data-bs-target="#setores" type="button" role="tab">
                                <i class="fas fa-building"></i> Setores
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="senha-tab" data-bs-toggle="tab" data-bs-target="#senha" type="button" role="tab">
                                <i class="fas fa-key"></i> Senha
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="clienteTabsContent">
                        <!-- Aba Dados Pessoais -->
                        <div class="tab-pane fade show active" id="dados" role="tabpanel">
                            <form id="editClienteForm">
                                <input type="hidden" name="id" id="editClienteId">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editNome" class="form-label">Nome <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="editNome" name="nome" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="editEmail" name="email">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editTelefone" class="form-label">Telefone</label>
                                        <input type="text" class="form-control" id="editTelefone" name="telefone" placeholder="(00) 00000-0000">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editCpfCnpj" class="form-label">CPF/CNPJ</label>
                                        <input type="text" class="form-control" id="editCpfCnpj" name="cpf_cnpj">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="editEstado" class="form-label">Estado</label>
                                        <select class="form-control" id="editEstado" name="estado_id">
                                            <option value="">Selecione um estado</option>
                                            <?php
                                            $query_uf = "SELECT id, nome, sigla FROM UF ORDER BY nome ASC";
                                            $result_uf = $conexao->query($query_uf);
                                            while ($uf = $result_uf->fetch_assoc()): ?>
                                                <option value="<?= $uf['id'] ?>"><?= htmlspecialchars($uf['sigla']) ?> - <?= htmlspecialchars($uf['nome']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="editMunicipio" class="form-label">Município</label>
                                        <select class="form-control" id="editMunicipio" name="municipio_id">
                                            <option value="">Selecione um município</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="editEndereco" class="form-label">Endereço</label>
                                    <textarea class="form-control" id="editEndereco" name="endereco" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="editObservacoes" class="form-label">Observações</label>
                                    <textarea class="form-control" id="editObservacoes" name="observacoes" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="editAtivo" class="form-label">Status</label>
                                    <select class="form-control" id="editAtivo" name="ativo">
                                        <option value="S">Ativo</option>
                                        <option value="N">Inativo</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Aba Setores -->
                        <div class="tab-pane fade" id="setores" role="tabpanel">
                            <div id="setoresContainer">
                                <p class="text-muted">Carregando setores...</p>
                            </div>
                        </div>
                        
                        <!-- Aba Senha -->
                        <div class="tab-pane fade" id="senha" role="tabpanel">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Você pode resetar a senha do cliente. Se não informar uma senha, será gerada uma senha aleatória.
                            </div>
                            <div class="mb-3">
                                <label for="novaSenha" class="form-label">Nova Senha (deixe em branco para gerar automaticamente)</label>
                                <input type="password" class="form-control" id="novaSenha" placeholder="Deixe em branco para gerar senha aleatória">
                                <small class="text-muted">A senha gerada será exibida após o reset.</small>
                            </div>
                            <button type="button" class="btn btn-warning" id="btnResetSenha">
                                <i class="fas fa-key"></i> Resetar Senha
                            </button>
                            <div id="senhaResultado" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveClienteChanges" style="background: linear-gradient(135deg, #ff6f00, #ff8c1a); border: none;">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Excluir cliente
        document.querySelectorAll(".btn-excluir-cliente").forEach(button => {
            button.addEventListener("click", () => {
                const clienteId = button.getAttribute("data-id");
                if (confirm("Tem certeza que deseja excluir este cliente?")) {
                    fetch("excluir_cliente.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `id=${clienteId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Cliente excluído com sucesso!");
                            location.reload();
                        } else {
                            alert("Erro ao excluir cliente: " + data.error);
                        }
                    })
                    .catch(error => {
                        console.error("Erro:", error);
                        alert("Erro inesperado ao excluir cliente.");
                    });
                }
            });
        });

        // Editar cliente
        document.querySelectorAll(".btn-edit-cliente").forEach(button => {
            button.addEventListener("click", () => {
                const clienteId = button.getAttribute("data-id");
                fetch(`get_cliente.php?id=${clienteId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            return;
                        }
                        document.getElementById("editClienteId").value = data.id;
                        document.getElementById("editNome").value = data.nome || '';
                        document.getElementById("editEmail").value = data.email || '';
                        document.getElementById("editTelefone").value = data.telefone || '';
                        document.getElementById("editCpfCnpj").value = data.cpf_cnpj || '';
                        document.getElementById("editEndereco").value = data.endereco || '';
                        document.getElementById("editObservacoes").value = data.observacoes || '';
                        document.getElementById("editAtivo").value = data.ativo || 'S';
                        document.getElementById("editEstado").value = data.estado_id || '';
                        
                        // Carrega municípios do estado
                        if (data.estado_id) {
                            document.getElementById("editEstado").dispatchEvent(new Event('change'));
                            setTimeout(() => {
                                document.getElementById("editMunicipio").value = data.municipio_id || '';
                            }, 500);
                        }
                        
                        // Carrega setores
                        carregarSetoresCliente(clienteId);
                    })
                    .catch(error => {
                        console.error("Erro:", error);
                        alert("Erro ao carregar dados do cliente.");
                    });
            });
        });
        
        // Função para carregar setores do cliente
        function carregarSetoresCliente(clienteId) {
            fetch(`get_setores_cliente.php?cliente_id=${clienteId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById("setoresContainer");
                    if (data.error) {
                        container.innerHTML = `<p class="text-danger">${data.error}</p>`;
                        return;
                    }
                    
                    let html = '<div class="row g-2">';
                    data.todos_setores.forEach(setor => {
                        const checked = data.setores_cliente.some(s => s.id === setor.id) ? 'checked' : '';
                        html += `
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input setor-checkbox" type="checkbox" 
                                           value="${setor.id}" id="setor_${setor.id}" ${checked}>
                                    <label class="form-check-label" for="setor_${setor.id}">
                                        ${setor.nome}
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error("Erro:", error);
                    document.getElementById("setoresContainer").innerHTML = '<p class="text-danger">Erro ao carregar setores.</p>';
                });
        }
        
        // Resetar senha
        document.getElementById("btnResetSenha")?.addEventListener("click", function() {
            const clienteId = document.getElementById("editClienteId").value;
            const novaSenha = document.getElementById("novaSenha").value;
            const resultadoDiv = document.getElementById("senhaResultado");
            
            if (!clienteId) {
                alert("Carregue os dados do cliente primeiro.");
                return;
            }
            
            if (!confirm("Tem certeza que deseja resetar a senha deste cliente?")) {
                return;
            }
            
            const formData = new FormData();
            formData.append('cliente_id', clienteId);
            if (novaSenha) {
                formData.append('nova_senha', novaSenha);
            }
            
            fetch("reset_senha_cliente.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultadoDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Senha resetada com sucesso!</strong><br>
                            ${data.nova_senha ? `Nova senha: <code>${data.nova_senha}</code>` : 'Senha atualizada.'}
                        </div>
                    `;
                    document.getElementById("novaSenha").value = '';
                } else {
                    resultadoDiv.innerHTML = `<div class="alert alert-danger">Erro: ${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error("Erro:", error);
                resultadoDiv.innerHTML = '<div class="alert alert-danger">Erro inesperado ao resetar senha.</div>';
            });
        });
        
        // Carregar municípios quando estado mudar
        document.getElementById("editEstado")?.addEventListener("change", function() {
            const estadoId = this.value;
            const municipioSelect = document.getElementById("editMunicipio");
            municipioSelect.innerHTML = '<option value="">Carregando...</option>';
            
            if (estadoId) {
                fetch(`get_municipios.php?estado_id=${estadoId}`)
                    .then(response => response.json())
                    .then(municipios => {
                        municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                        if (Array.isArray(municipios)) {
                            municipios.forEach(m => {
                                municipioSelect.innerHTML += `<option value="${m.id}">${m.nome}</option>`;
                            });
                        } else if (municipios.municipios) {
                            municipios.municipios.forEach(m => {
                                municipioSelect.innerHTML += `<option value="${m.id}">${m.nome}</option>`;
                            });
                        }
                    })
                    .catch(error => {
                        console.error("Erro ao carregar municípios:", error);
                        municipioSelect.innerHTML = '<option value="">Erro ao carregar</option>';
                    });
            } else {
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
            }
        });

        // Salvar alterações
        document.getElementById("saveClienteChanges").addEventListener("click", () => {
            const form = document.getElementById("editClienteForm");
            const formData = new FormData(form);
            
            // Salva dados do cliente
            fetch("update_cliente.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Salva setores
                    const clienteId = document.getElementById("editClienteId").value;
                    const setoresSelecionados = Array.from(document.querySelectorAll('.setor-checkbox:checked'))
                        .map(cb => cb.value);
                    
                    const formDataSetores = new FormData();
                    formDataSetores.append('cliente_id', clienteId);
                    setoresSelecionados.forEach(setorId => {
                        formDataSetores.append('setores[]', setorId);
                    });
                    
                    return fetch("update_setores_cliente.php", {
                        method: "POST",
                        body: formDataSetores
                    });
                } else {
                    throw new Error(data.error || 'Erro ao atualizar cliente');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Cliente atualizado com sucesso!");
                    location.reload();
                } else {
                    alert("Cliente atualizado, mas houve erro ao atualizar setores: " + (data.error || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error("Erro:", error);
                alert("Erro ao atualizar cliente: " + error.message);
            });
        });
    </script>
    <script src="js/theme.js"></script>
</body>
</html>

