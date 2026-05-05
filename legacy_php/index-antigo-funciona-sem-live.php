<?php
session_start();
include 'db/conexao.php';
$conexao->set_charset("utf8");


// Verifica se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
$usuario_nome = $_SESSION['user_nome'] ?? null;
$usuario_adm = $_SESSION['user_adm'] ?? false;

// Configuração de busca e filtro
$filtro_setor = isset($_GET['filtroSetor']) ? intval($_GET['filtroSetor']) : 0;
$busca_titulo = isset($_GET['pesquisaTitulo']) ? mysqli_real_escape_string($conexao, $_GET['pesquisaTitulo']) : '';

// Configuração de paginação
$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$videos_por_pagina = 6;
$offset = ($pagina_atual - 1) * $videos_por_pagina;

// Construção da query dinâmica
$filtro_query = "WHERE 1=1";
if ($filtro_setor > 0) {
    $filtro_query .= " AND videos.setor_id = $filtro_setor";
}
if (!empty($busca_titulo)) {
    $filtro_query .= " AND videos.titulo LIKE '%$busca_titulo%'";
}

// Busca os vídeos com limite e offset
$videos_query = "SELECT videos.*, setores.nome AS setor_nome, 
                        (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id) AS curtidas,
                        (SELECT COUNT(*) FROM comentarios WHERE comentarios.video_id = videos.id) AS total_comentarios
                 FROM videos 
                 JOIN setores ON videos.setor_id = setores.id 
                 $filtro_query 
                 ORDER BY videos.data_upload DESC 
                 LIMIT $videos_por_pagina OFFSET $offset";

$videos_result = mysqli_query($conexao, $videos_query);

// Total de vídeos para paginação
$count_query = "SELECT COUNT(*) as total FROM videos $filtro_query";
$count_result = mysqli_query($conexao, $count_query);
$total_videos = mysqli_fetch_assoc($count_result)['total'];
$total_paginas = ceil($total_videos / $videos_por_pagina);

// Busca os setores para filtro e modal de upload
$setores_query = "SELECT id, nome FROM setores where ativo='S'";
$setores_result = mysqli_query($conexao, $setores_query);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Treinamentos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Popper.js (necessário para Dropdown do Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <style>
        body {
    padding-top: 80px; /* Ajustar conforme necessário */
}

.header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .header {
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
   
        .header .logo {
            height: 50px;
        }

        .header .btn {
            font-size: 14px;
            padding: 2px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .header .btn:hover {
            text-decoration: underline;
        }

        /* Estilo Principal do Título */
.main-title {
    text-align: center;
    font-size: 2.8rem;
    font-weight: 700;
    color: #2c3e50; /* Azul escuro neutro */
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin: 20px 0;
    font-family: 'Poppins', sans-serif;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2); /* Sombra leve */
    position: relative;
}

/* Texto com Destaque */
.highlight {
    color: #ff6f00; /* Cor laranja vibrante */
    font-weight: 800;
    position: relative;
    padding: 0 5px;
    border-bottom: 3px solid #ff6f00; /* Barra de destaque */
    transition: all 0.3s ease-in-out;
}

/* Efeito Hover Suave */
.highlight:hover {
    color: #d35400; /* Laranja mais escuro no hover */
    border-bottom: 3px solid #d35400;
    text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3); /* Sombra mais intensa */
}

/* Linha Decorativa Simples */
.main-title::after {
    content: "";
    width: 50%;
    height: 4px;
    background: #ff6f00;
    display: block;
    margin: 12px auto 0;
    border-radius: 2px;
}


        /* Contêiner Principal */
.filters {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px; /* Espaçamento entre os elementos */
    padding: 15px 20px;
    margin: 20px auto;
    max-width: 800px;
    background: linear-gradient(145deg, #ffffff, #f9f9f9); /* Fundo sutil */
    border-radius: 20px; /* Bordas arredondadas */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); /* Sombra leve */
    position: relative;
    overflow: hidden;
}

/* Elemento Decorativo no Fundo */
.filters::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 150px;
    height: 150px;
    background: linear-gradient(90deg, #ff6f00, #ffa726);
    opacity: 0.1;
    border-radius: 50%;
    z-index: 0;
    animation: rotateBlob 8s infinite linear;
}

/* Animação do Blob */
@keyframes rotateBlob {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Formulário Interno */
.filters form {
    display: flex;
    align-items: center;
    gap: 10px; /* Espaçamento entre os campos */
    width: 100%;
    position: relative;
    z-index: 1;
}

/* Campos de Input e Select */
.filters .form-select,
.filters .form-control {
    padding: 10px 15px;
    border: 1px solid #ddd; /* Borda discreta */
    border-radius: 8px;
    font-size: 0.9rem;
    color: #2c3e50;
    background: #ffffff;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); /* Sombra inicial */
    outline: none;
    flex: 1; /* Campos ocupam o mesmo espaço */
}

/* Hover e Foco nos Campos */
.filters .form-select:hover,
.filters .form-control:hover {
    border-color: #ff6f00; /* Destaque em laranja */
    background: #fff8ec; /* Fundo levemente laranja */
    box-shadow: 0 4px 8px rgba(255, 111, 0, 0.1); /* Sombra no hover */
}

.filters .form-select:focus,
.filters .form-control:focus {
    border-color: #ff6f00;
    box-shadow: 0 4px 10px rgba(255, 111, 0, 0.2); /* Destaque no foco */
}

/* Placeholder */
.filters .form-control::placeholder {
    color: #bbb;
    font-style: italic;
}

/* Botão de Filtrar */
.filters .btn-primary {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: bold;
    text-transform: uppercase;
    color: white;
    background: linear-gradient(90deg, #ff6f00, #ffa726); /* Gradiente elegante */
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
    position: relative;
    z-index: 1;
}

/* Hover no Botão */
.filters .btn-primary:hover {
    background: linear-gradient(90deg, #ffa726, #ffd54f); /* Gradiente mais claro */
    transform: translateY(-1px); /* Leve elevação */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra aprimorada */
}

/* Animação ao Carregar */
.filters .form-select,
.filters .form-control,
.filters .btn-primary {
    opacity: 0;
    transform: translateY(10px);
    animation: fadeIn 0.8s ease forwards;
}

.filters .form-select {
    animation-delay: 0.2s;
}

.filters .form-control {
    animation-delay: 0.4s;
}

.filters .btn-primary {
    animation-delay: 0.6s;
}

/* Animação de Fade-In */
@keyframes fadeIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsividade */
@media (max-width: 768px) {
    .filters {
        flex-direction: column; /* Empilha os elementos no mobile */
        padding: 20px;
    }

    .filters form {
        flex-direction: column; /* Reorganiza os elementos verticalmente */
        gap: 15px;
    }

    .filters .form-select,
    .filters .form-control,
    .filters .btn-primary {
        width: 100%; /* Campos ocupam toda a largura */
    }
}

        .video-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .video-item:hover {
            transform: scale(1.05);
        }

        .video-item video {
            width: 100%;
            height: 180px;
            border-radius: 10px;
        }

        .btn-group {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-custom {
            padding: 8px 12px;
            font-size: 14px;
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn-custom.green { color: #28a745; }
        .btn-custom.blue { color: #17a2b8; }
        .btn-custom.orange { color: #ffc107; }
        .btn-custom.red { color: #dc3545; }

        .btn-custom:hover {
            transform: scale(1.1);
        }

        /* Notificações Flutuantes */
        .notification-container {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #333;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-size: 14px;
            opacity: 0;
            transform: translateY(-20px);
            animation: fade-in-out 4s forwards;
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        @keyframes fade-in-out {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }
            10% {
                opacity: 1;
                transform: translateY(0);
            }
            90% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateY(-20px);
            }
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
        /* Modal Container */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Fundo escuro semitransparente */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* Modal Content */
.modal-content {
    background: white;
    padding: 30px;
    border-radius: 15px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    animation: fadeIn 0.3s ease-in-out;
}

/* Modal Title */
.modal-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    text-align: center;
    margin-bottom: 20px;
}

/* Input Fields */
.form-control {
    border: 1px solid #ddd;
    border-radius: 22px;
    padding: 10px;
    font-size: 14px;
    margin-top: -2px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #ff6f00;
    outline: none;
    box-shadow: 0 0 5px rgba(255, 111, 0, 0.5);
}

/* Buttons */
.modal-buttons {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    flex: 1;
    padding: 10px 15px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
}

/* Minimalist Primary Button */
.btn-minimal-primary {
    background-color: #ff6f00; /* Laranja forte */
    color: white;
    border: none;
}

.btn-minimal-primary:hover {
    background-color: #ff8c1a; /* Laranja mais claro no hover */
    color: white;
    box-shadow: 0 4px 10px rgba(255, 111, 0, 0.3);
}

/* Minimalist Secondary Button */
.btn-minimal-secondary {
    background-color: transparent; /* Fundo transparente */
    color: #ff6f00; /* Texto laranja */
    border: 1px solid #ff6f00;
}

.btn-minimal-secondary:hover {
    background-color: #ff6f00; /* Laranja no hover */
    color: white; /* Texto branco */
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

        .notification {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
        }
        .notification.error {
            background: #dc3545;
        }
        #notification-container {
    position: fixed;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 90%;
    max-width: 400px;
}

.notification {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-radius: 8px;
    background-color: #fff;
    color: #333;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    font-size: 14px;
    opacity: 0;
    transform: translateY(-20px);
    animation: fadeIn 0.4s forwards, fadeOut 0.4s 3.6s forwards;
    transition: transform 0.3s ease-in-out;
}

.notification.success {
    border-left: 4px solid #28a745;
}

.notification.error {
    border-left: 4px solid #dc3545;
}

.notification.info {
    border-left: 4px solid #17a2b8;
}

.notification .btn-close {
    background: none;
    border: none;
    font-size: 16px;
    color: #666;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.2s ease;
}

.notification .btn-close:hover {
    color: #333;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeOut {
    0% {
        opacity: 1;
        transform: translateY(0);
    }
    100% {
        opacity: 0;
        transform: translateY(-20px);
    }
}


.progress {
    margin-top: 10px;
    width: 100%;
    height: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    overflow: hidden;
    display: none;
}

.progress-bar {
    height: 100%;
    width: 0;
    background-color: #28a745;
    transition: width 0.4s ease;
}
.header {
    background: linear-gradient(90deg, #ff6f00, #ff8c1a);
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 10px; /* Espaço entre os botões */
}

.btn {
    font-size: 14px;
    padding: 8px 15px;
    border-radius: 5px;
    background: transparent;
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.btn:hover {
    text-decoration: underline;
}

/* Modal editar */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease-in-out;
    z-index: 1000;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    position: relative;
    animation: slideIn 0.3s ease-in-out forwards;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.modal-header .modal-title {
    font-size: 20px;
    font-weight: bold;
    color: #ff6f00;
}

.modal-header .btn-close {
    background: transparent;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    transition: color 0.3s ease;
}

.modal-header .btn-close:hover {
    color: #ff6f00;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.modal-actions .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    padding: 10px 20px;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.modal-actions .btn-primary {
    background: #ff6f00;
    color: white;
}

.modal-actions .btn-primary:hover {
    background: #ff8c1a;
}

.modal-actions .btn-danger {
    background: transparent;
    color: #dc3545;
    border: 2px solid #dc3545;
}

.modal-actions .btn-danger:hover {
    background: #dc3545;
    color: white;
}

/* Animações */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        transform: scale(0.9);
    }
    to {
        transform: scale(1);
    }
}
/* Estilo do Dropdown */
.dropdown-menu {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
    padding: 10px 15px;
    font-size: 14px;
    color: #333;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.dropdown-item:hover {
    background: #ff6f00;
    color: white;
}

.dropdown-toggle {
    background: transparent;
    color: white;
    border: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 15px;
}

.dropdown-toggle:hover {
    text-decoration: underline;
}
.filters select {
    width: auto; /* Ajusta a largura automaticamente ao conteúdo */
    min-width: 200px; /* Define uma largura mínima */
    padding: 10px; /* Espaçamento interno para melhor visualização */
    white-space: nowrap; /* Evita quebra de texto */
    overflow: hidden; /* Esconde texto que ultrapassar */
    text-overflow: ellipsis; /* Adiciona "..." para texto muito longo */
}
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8); /* Fundo semitransparente */
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.4s ease;
}

.modal-header {
    display: flex;
    justify-content: center;
    align-items: center;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 22px;
    font-weight: bold;
    color: #ff6f00;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-size: 14px;
    font-weight: bold;
    color: #333;
    display: block;
    margin-bottom: 8px;
}

.form-label span {
    color: #ff6f00;
}

.form-control {
    width: 100%;
    padding: 12px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s ease;
    outline: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.form-control:focus {
    border-color: #ff6f00;
    box-shadow: 0 0 5px rgba(255, 111, 0, 0.5);
}

.btn {
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(90deg, #ff6f00, #ffa726);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #ffa726, #ffd54f);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
    background: transparent;
    color: #666;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #f9f9f9;
    color: #333;
}

.progress {
    display: none;
    width: 100%;
    height: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background-color: #ff6f00;
    transition: width 0.4s ease;
}

/* Animação de entrada */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
 .header {
        position: fixed;
        top: 0;
        width: 100%;
        background: linear-gradient(to right, #ff7f00, #ffae42);
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 100;
        transition: transform 0.5s ease, opacity 0.5s ease;
    }

    .hidden {
        transform: translateY(-100%);
        opacity: 0;
    }
    .click-instruction {
    font-size: 0.75rem;       /* Tamanho menor da fonte */
    color: rgba(0, 0, 0, 0.3); /* Cor mais transparente */
    margin-top: 0.5rem;       /* Espaçamento superior sutil */
    font-style: italic;       /* Estilo itálico minimalista */
    line-height: 1.2;         /* Linha um pouco mais compacta */
}
.video-title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    margin: 0.5rem 0;   /* Espaçamento sutil acima e abaixo */
    line-height: 1.2;   /* Linha mais compacta */
    /* Remova ou comente as linhas abaixo se estiverem presentes:
       text-overflow: ellipsis;
       white-space: nowrap;
       overflow: hidden; */
}
    </style>
</head>
<body>

    <div id="notification-container"></div>
    <!-- O resto do conteúdo da página -->


    

<div class="header">
    <img src="img/martinez.png" alt="Logo" class="logo">
    <div>
        <?php if ($is_logged_in): ?>
            <span>Bem-vindo, <?= htmlspecialchars($usuario_nome) ?>!</span>
            <?php if ($usuario_adm): ?>
                <button class="btn" onclick="openUploadModal()">
                    <i class="fas fa-upload"></i> Upload
                </button>
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-plus"></i> Cadastros
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="listar_usuarios.php">Cadastro de Usuários</a></li>
                        <li><a class="dropdown-item" href="cadastro_setores.php">Cadastro de Setores</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            <a href="logout.php" class="btn">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        <?php else: ?>
            <a href="login.php" class="btn">Login</a>
        <?php endif; ?>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-video"></i> Upload de Vídeo</h2>
        </div>
        <form id="uploadForm">
            <div class="form-group">
                <label for="titulo" class="form-label">
                    <i class="fas fa-heading"></i> Título <span>*</span>
                </label>
                <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Digite o título do vídeo" required>
            </div>
            <div class="form-group">
                <label for="descricao" class="form-label">
                    <i class="fas fa-align-left"></i> Descrição <span>*</span>
                </label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Descreva brevemente o vídeo" required></textarea>
            </div>
            <div class="form-group">
                <label for="setor" class="form-label">
                    <i class="fas fa-briefcase"></i> Setor <span>*</span>
                </label>
                <select class="form-control" id="setor" name="setor_id" required>
                    <option value="">Selecione um setor</option>
                    <?php
                    $setores_query = "SELECT id, nome FROM setores WHERE ativo = 'S'";
                    $setores_result = mysqli_query($conexao, $setores_query);

                    if ($setores_result && mysqli_num_rows($setores_result) > 0) {
                        while ($setor = mysqli_fetch_assoc($setores_result)) {
                            echo '<option value="' . htmlspecialchars($setor['id']) . '">' . htmlspecialchars($setor['nome']) . '</option>';
                        }
                    } else {
                        echo '<option value="">Erro ao carregar setores</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="video" class="form-label">
                    <i class="fas fa-file-video"></i> Arquivo de Vídeo <span>*</span>
                </label>
                <input type="file" class="form-control" id="video" name="video" accept="video/*" required>
            </div>
            <div class="form-actions d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-primary" onclick="uploadVideo()">
                    <i class="fas fa-cloud-upload-alt"></i> Enviar
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
            <div class="progress mt-3">
                <div class="progress-bar" id="uploadProgressBar" role="progressbar" style="width: 0;"></div>
            </div>
        </form>
        <div id="uploadNotification" class="notification hidden"></div>
    </div>
</div>


        

<h1 class="main-title">
    Plataforma de <span class="highlight">Treinamentos</span>
</h1>


    <!-- Filtros -->
    <div class="filters">
        <form method="GET" action="index.php" class="d-flex gap-2">
            <select name="filtroSetor" class="form-select">
                <option value="0">Todos os Setores</option>
                <?php
mysqli_data_seek($setores_result, 0); // Reposiciona o ponteiro da consulta
while ($setor = mysqli_fetch_assoc($setores_result)): ?>
    <option value="<?= $setor['id'] ?>" <?= $filtro_setor == $setor['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($setor['nome']) ?>
    </option>
<?php endwhile; ?>

            </select>
            <input type="text" name="pesquisaTitulo" placeholder="Pesquisar por título..." value="<?= htmlspecialchars($busca_titulo) ?>" class="form-control">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>
    </div>

    <!-- Notificações -->
    <div id="notification-container"></div>

    <!-- Galeria de Vídeos -->
<div class="container">
    <div class="row g-3 mt-4">
        <?php if (mysqli_num_rows($videos_result) > 0): ?>
            <?php while ($video = mysqli_fetch_assoc($videos_result)): ?>
                <div class="col-md-4">
                    <div class="video-item" onclick="window.location.href='video_detalhes.php?id=<?= $video['id'] ?>'" style="cursor: pointer;">
    <video controls>
        <source src="<?= htmlspecialchars($video['url_video']) ?>" type="video/mp4">
    </video>
    <h3 class="video-title"><?= htmlspecialchars($video['titulo']) ?></h3>
    <p><?= htmlspecialchars($video['descricao']) ?></p>
    <p class="text-muted">Setor: <?= htmlspecialchars($video['setor_nome']) ?> | Adicionado em: <?= $video['data_upload'] ?></p>
    <div class="btn-group">
        <button class="btn-custom green" onclick="event.stopPropagation(); <?= $is_logged_in ? "curtirVideo(event, {$video['id']})" : 'showLoginMessage(event)' ?>">
            <i class="fas fa-thumbs-up"></i> Curtir
        </button>
        <button class="btn-custom blue" onclick="event.stopPropagation(); compartilharVideo('<?= htmlspecialchars($video['titulo']) ?>', <?= $video['id'] ?>)">
            <i class="fas fa-share-alt"></i> Compartilhar
        </button>
        <?php if ($usuario_adm): ?>
            <button class="btn-custom orange" onclick="event.stopPropagation(); abrirEditarModal(<?= $video['id'] ?>, '<?= htmlspecialchars($video['titulo']) ?>', '<?= htmlspecialchars($video['descricao']) ?>', <?= $video['setor_id'] ?>)">
                <i class="fas fa-edit"></i> Editar
            </button>
            <button class="btn-custom red" onclick="event.stopPropagation(); excluirVideo(<?= $video['id'] ?>)">
                <i class="fas fa-trash-alt"></i> Excluir
            </button>
        <?php endif; ?>
    </div>
    <p class="text-muted mt-2">
        <i class="fas fa-heart"></i> <span id="curtidas-<?= $video['id'] ?>"><?= $video['curtidas'] ?></span> curtidas
        &nbsp;&nbsp;
        <i class="fas fa-comments"></i> <span id="comentarios-<?= $video['id'] ?>"><?= $video['total_comentarios'] ?></span> comentários
    </p>
    <p class="click-instruction">Clique no vídeo ou no contêiner para ver mais detalhes</p>
</div>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center">Nenhum vídeo encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Editar Vídeo</h5>
            <button class="btn-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm">
            <input type="hidden" id="editVideoId" name="video_id">
            <div class="form-group mb-3">
                <label for="editTitulo" class="form-label">Título</label>
                <input type="text" id="editTitulo" name="titulo" class="form-control" placeholder="Digite o título" required>
            </div>
            <div class="form-group mb-3">
                <label for="editDescricao" class="form-label">Descrição</label>
                <textarea id="editDescricao" name="descricao" class="form-control" rows="3" placeholder="Digite a descrição" required></textarea>
            </div>
            <div class="form-group mb-3">
                <label for="editSetor" class="form-label">Setor</label>
                <select id="editSetor" name="setor_id" class="form-select" required>
                    <?php mysqli_data_seek($setores_result, 0); ?>
                    <?php while ($setor = mysqli_fetch_assoc($setores_result)): ?>
                        <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>


    <!-- Paginação -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>&filtroSetor=<?= $filtro_setor ?>&pesquisaTitulo=<?= $busca_titulo ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

   <!-- Footer -->
<div class="footer" id="footer">
    <p>&copy; 2024 Gabriel Bárbara. Todos os direitos reservados.</p>
    <p>
        <a href="#" class="open-modal" data-modal="privacy-modal">Política de Privacidade</a> |
        <a href="#" class="open-modal" data-modal="terms-modal">Termos de Uso</a>
    </p>
</div>

<!-- Modais -->
<div id="privacy-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('privacy-modal')">&times;</span>
        <h2>Política de Privacidade</h2>
        <p>A sua privacidade é importante para nós. É política do Biblioteca de Treinamento respeitar a sua privacidade em relação a qualquer informação sua que possamos coletar no site <a href="Biblioteca de Treinamento">Biblioteca de Treinamento</a>, e outros sites que possuímos e operamos.</p>
        <p>Solicitamos informações pessoais apenas quando realmente precisamos delas para lhe fornecer um serviço. Fazemo-lo por meios justos e legais, com o seu conhecimento e consentimento. Também informamos por que estamos coletando e como será usado.</p>
        <p>Apenas retemos as informações coletadas pelo tempo necessário para fornecer o serviço solicitado. Quando armazenamos dados, protegemos dentro de meios comercialmente aceitáveis ​​para evitar perdas e roubos, bem como acesso, divulgação, cópia, uso ou modificação não autorizados.</p>
        <p>Não compartilhamos informações de identificação pessoal publicamente ou com terceiros, exceto quando exigido por lei.</p>
        <p>O nosso site pode ter links para sites externos que não são operados por nós. Esteja ciente de que não temos controle sobre o conteúdo e práticas desses sites e não podemos aceitar responsabilidade por suas respectivas <a href="https://politicaprivacidade.com/" rel="noopener noreferrer" target="_blank">políticas de privacidade</a>.</p>
        <p>Você é livre para recusar a nossa solicitação de informações pessoais, entendendo que talvez não possamos fornecer alguns dos serviços desejados.</p>
        <p>O uso continuado de nosso site será considerado como aceitação de nossas práticas em torno de privacidade e informações pessoais.</p>
    </div>
</div>

<div id="terms-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('terms-modal')">&times;</span>
        <h2>Termos de Uso</h2>
        <h2>1. Termos</h2>
        <p>Ao acessar ao site <a href='Biblioteca De Treinamento'>Biblioteca De Treinamento</a>, concorda em cumprir estes <a href=https://privacidade.me/ target='_BLANK'>termos de uso</a>, todas as leis e regulamentos aplicáveis ​​e concorda que é responsável pelo cumprimento de todas as leis locais aplicáveis.</p>
        <h2>2. Uso de Licença</h2>
        <p>É concedida permissão para baixar temporariamente uma cópia dos materiais (informações ou software) no site Biblioteca De Treinamento, apenas para visualização transitória pessoal e não comercial.</p>
        <ol>
            <li>Modificar ou copiar os materiais;</li>
            <li>Usar os materiais para qualquer finalidade comercial ou para exibição pública;</li>
            <li>Tentar descompilar ou fazer engenharia reversa de qualquer software;</li>
        </ol>
        <p>Esta licença será automaticamente rescindida se você violar alguma dessas restrições.</p>
    </div>
</div>

<style>
/* Estilo Geral */
body { font-family: Arial, sans-serif; margin: 0; padding: 0; position: relative; }

/* Footer */
.footer {
    position: relative;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(255, 255, 255, 0.95);
    color: #333;
    text-align: center;
    padding: 8px 0;
    box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.1);
    font-size: 12px;
}
.footer a {
    color: #007bff;
    text-decoration: none;
    margin: 0 8px;
    transition: color 0.3s;
}
.footer a:hover { color: #0056b3; }

/* Modais */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.modal-content {
    background: #fff;
    padding: 20px;
    width: 80%;
    max-width: 500px;
    border-radius: 8px;
    animation: fadeIn 0.5s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}
.close {
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.querySelectorAll('.open-modal').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = link.dataset.modal;
        document.getElementById(modalId).style.display = 'flex';
    });
});

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = (event) => {
    document.querySelectorAll('.modal').forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};
</script>



    <script>
       function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.error('Notification container not found!');
        return;
    }

    // Cria o elemento da notificação
    const notification = document.createElement('div');
    notification.className = `notification ${type}`; // Adiciona a classe de tipo
    notification.innerHTML = `
        <span>${message}</span>
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;

    // Adiciona a notificação ao contêiner
    container.appendChild(notification);

    // Remove a notificação automaticamente após 4 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 4000);
}




        function curtirVideo(event, videoId) {
    event.preventDefault(); // Impede o comportamento padrão do botão/link.

    fetch('toggle_curtida.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: videoId })
    })
    .then(response => response.json())
    .then(data => {
        const curtidasSpan = document.querySelector(`#curtidas-${videoId}`);
        if (data.success) {
            if (data.action === 'added') {
                curtidasSpan.textContent = parseInt(curtidasSpan.textContent) + 1;
                showNotification(data.message, 'success');
            } else if (data.action === 'removed') {
                curtidasSpan.textContent = parseInt(curtidasSpan.textContent) - 1;
                showNotification(data.message, 'info');
            }
        } else {
            showNotification(data.error || 'Erro ao processar a curtida.', 'error');
        }
    })
    .catch(() => {
        showNotification('Erro ao processar a curtida.', 'error');
    });
}

function showLoginMessage(event) {
    event.preventDefault();
    showNotification('Você precisa estar logado para curtir.', 'error');
}


function openUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'flex';
}

function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'none';
}

        function uploadVideo() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);

    const notification = document.getElementById('uploadNotification');
    const progressBar = document.getElementById('uploadProgressBar');
    const progressContainer = document.querySelector('.progress');

    // Reseta as notificações e a barra de progresso
    notification.style.display = 'none';
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';

    fetch('upload_ajax.php', {
        method: 'POST',
        body: formData,
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualiza a barra de progresso para 100%
            progressBar.style.width = '100%';

            // Mostra notificação de sucesso
            notification.textContent = data.message;
            notification.className = 'notification success';
            notification.style.display = 'block';

            // Redireciona ou atualiza a página após um pequeno delay
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            // Mostra notificação de erro
            notification.textContent = data.error || 'Erro ao realizar o upload.';
            notification.className = 'notification error';
            notification.style.display = 'block';

            // Esconde a barra de progresso
            progressContainer.style.display = 'none';
        }
    }).catch(error => {
        console.error('Erro no upload:', error);

        // Mostra notificação de erro
        notification.textContent = 'Erro inesperado ao realizar o upload.';
        notification.className = 'notification error';
        notification.style.display = 'block';

        // Esconde a barra de progresso
        progressContainer.style.display = 'none';
    });
}


        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }
        document.addEventListener('DOMContentLoaded', () => {
    const dropdownToggle = document.getElementById('cadastrosDropdown');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    dropdownToggle.addEventListener('click', (event) => {
        event.stopPropagation(); // Impede que o clique feche o menu.
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', () => {
        if (dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
        }
    });
});

function excluirVideo(videoId) {
    if (!confirm('Tem certeza que deseja excluir este vídeo?')) return;

    fetch('delete_video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `video_id=${videoId}`,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Vídeo excluído com sucesso!', 'success');
            setTimeout(() => location.reload(), 1000); // Atualiza a página após 1s
        } else {
            showNotification(data.error || 'Erro ao excluir o vídeo.', 'error');
        }
    })
    .catch(() => {
        showNotification('Erro ao excluir o vídeo.', 'error');
    });
}
function abrirEditarModal(id, titulo, descricao, setorId) {
    document.getElementById('editVideoId').value = id;
    document.getElementById('editTitulo').value = titulo;
    document.getElementById('editDescricao').value = descricao;
    document.getElementById('editSetor').value = setorId;
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('edit_video.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Vídeo atualizado com sucesso!', 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000); // Atualiza a página após 1s
        } else {
            showNotification(data.error || 'Erro ao atualizar o vídeo.', 'error');
        }
    })
    .catch(() => {
        showNotification('Erro ao atualizar o vídeo.', 'error');
    });
});
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    notification.classList.add('notification', type);
    notification.innerHTML = `
        <span>${message}</span>
        <button class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    container.appendChild(notification);
    setTimeout(() => notification.remove(), 4000);
}
document.addEventListener('click', function (event) {
    const dropdown = document.querySelector('.dropdown-menu');
    if (!event.target.closest('.dropdown')) {
        dropdown.classList.remove('show');
    }
});
function compartilharVideo(titulo, videoId) {
    const url = `${window.location.origin}/video_detalhes.php?id=${videoId}`;
    if (navigator.share) {
        navigator.share({
            title: titulo,
            text: `Confira o video da Martinez & Carvalho: ${titulo}`,
            url: url
        }).catch(err => console.error('Erro ao compartilhar:', err));
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Link copiado para a área de transferência!', 'success');
        }).catch(() => {
            showNotification('Erro ao copiar o link para a área de transferência.', 'error');
        });
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.header');
    const headerHeight = header.offsetHeight;
    document.body.style.paddingTop = `${headerHeight}px`;
});
function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}

function uploadVideo() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);

    const notification = document.getElementById('uploadNotification');
    const progressBar = document.getElementById('uploadProgressBar');
    const progressContainer = document.querySelector('.progress');

    // Reseta barra de progresso e notificação
    notification.style.display = 'none';
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';

    fetch('upload_ajax.php', {
        method: 'POST',
        body: formData,
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualiza barra de progresso para 100%
            progressBar.style.width = '100%';

            // Mostra mensagem de sucesso
            notification.textContent = data.message;
            notification.className = 'notification success';
            notification.style.display = 'block';

            // Redireciona ou atualiza a página
            setTimeout(() => window.location.reload(), 2000);
        } else {
            // Mostra mensagem de erro
            notification.textContent = data.error || 'Erro ao realizar o upload.';
            notification.className = 'notification error';
            notification.style.display = 'block';

            // Esconde a barra de progresso
            progressContainer.style.display = 'none';
        }
    }).catch(() => {
        notification.textContent = 'Erro inesperado ao realizar o upload.';
        notification.className = 'notification error';
        notification.style.display = 'block';
        progressContainer.style.display = 'none';
    });

    // Simula progresso durante o upload (se necessário)
    let progress = 0;
    const interval = setInterval(() => {
        if (progress >= 90) {
            clearInterval(interval);
        } else {
            progress += 10;
            progressBar.style.width = `${progress}%`;
        }
    }, 200);
}
let prevScrollPos = window.pageYOffset;

window.onscroll = () => {
    const header = document.querySelector('.header');
    const currentScrollPos = window.pageYOffset;

    if (prevScrollPos > currentScrollPos) {
        // Mostra o header ao rolar para cima
        header.style.transform = 'translateY(0)';
        header.style.transition = 'transform 0.3s ease';
    } else {
        // Esconde o header ao rolar para baixo
        header.style.transform = 'translateY(-100%)';
        header.style.transition = 'transform 0.3s ease';
    }

    prevScrollPos = currentScrollPos;
};


    </script>
</body>
</html>
