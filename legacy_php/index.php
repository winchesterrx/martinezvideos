<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$nome = "Gabriel da Silva Santos";
$cargo = "Desenvolvedor Web";
$idade = 23;
$descricao = "Criativo, inovador e apaixonado por tecnologia.";

$projetos = [
    [
        "titulo" => "Plataforma de Treinamento",
        "link" => "https://xofome.online/treinamento/index.php",
        "imagem" => "uploads/Screenshot_1.png",
        "descricao" => "Site de Hospedagem de videos e lives de Treinamento personalizado para uma empresa de Votuporanga"
    ],
    [
        "titulo" => "Sistema de Gestão",
        "link" => "https://xofome.online/cardapio-digital",
        "imagem" => "uploads/cardapio.png",
        "descricao" => "Sistema completo para gerenciamento de dados e relatórios administrativos."
    ],
    [
        "titulo" => "E-commerce Completo",
        "link" => "https://meuprojeto3.com",
        "imagem" => "uploads/ecommerce.jpg",
        "descricao" => "Plataforma de e-commerce funcional com carrinho de compras e pagamentos."
    ]
];



$contato = [
    "telefone" => "(17) 99779-9982",
    "whatsapp" => "https://wa.me/5511997799982",
    "email" => "menored45@gmail.com",
    "endereco" => "Votuporanga, SP - Brasil",
    "linkedin" => "https://linkedin.com/in/gabriel-da-silva-santos-61b419196",
    "github" => "https://github.com/winchesterrx",
    "curriculo" => "uploads/curriculum-gabriel.pdf",
    "idade" => "23"
];

$habilidades = [
    ["nome" => "PHP", "icone" => "bi-code-slash", "nivel" => 90],
    ["nome" => "JavaScript", "icone" => "bi-filetype-js", "nivel" => 85],
    ["nome" => "HTML & CSS", "icone" => "bi-filetype-html", "nivel" => 95],
    ["nome" => "Bootstrap", "icone" => "bi-bootstrap", "nivel" => 80],
    ["nome" => "MySQL", "icone" => "bi-database", "nivel" => 75]
];


$experiencia = [
    ["cargo" => "Analista de suporte computacional", "empresa" => "Martinez&Carvalho - Fiorilli", "periodo" => "2022 - Data Atual", "descricao" => "Suporte em Software de gestão municipal"],
   
];

$formacoes = [
    ["curso" => "Ensino Medio Completo", "instituicao" => "Escola Estadual Geraldo Alves Machado", "periodo" => "2015 - 2018"],
    ["curso" => "Projeto de Sistemas Web e Banco de Dados 1: Fundamentos e Estrutura de Dados", "instituicao" => "IFRS - Instituto Federal do Rio Grande do Sul", "periodo" => "2024"]
];

$diplomaDir = "uploads/";
$diplomas = array_diff(scandir($diplomaDir), array('.', '..'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currículo - <?= $nome ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background: #fff;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        .container-custom {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }
        .progress {
            height: 12px;
            border-radius: 50px;
            overflow: hidden;
            background: #ddd;
            position: relative;
        }
        .progress-bar {
            background: linear-gradient(90deg, #007bff, #6610f2);
            width: 0;
            transition: width 1.5s ease-in-out;
        }
        .progress-text {
            position: absolute;
            right: 10px;
            top: -25px;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .btn-outline-dark:hover {
            background: #007bff;
            color: white;
        }
        .card {
    border-radius: 10px;
    overflow: hidden;
}
.card-img-top {
    height: 180px;
    object-fit: cover;
}

    </style>
</head>
<body>

<div class="container mt-5">
    <div class="container-custom p-4 text-center">
        <h1 class="fw-bold"><i class="bi bi-person-circle"></i> <?= $nome ?></h1>
        <h4 class="text-primary"><i class="bi bi-briefcase"></i> <?= $cargo ?></h4>
        <p class="text-muted"><?= $descricao ?></p>

        <div class="d-flex justify-content-center mb-3">
            <a href="<?= $contato['linkedin'] ?>" class="btn btn-outline-dark me-2"><i class="bi bi-linkedin"></i> LinkedIn</a>
            <a href="<?= $contato['github'] ?>" class="btn btn-outline-dark me-2"><i class="bi bi-github"></i> GitHub</a>
            <a href="<?= $contato['curriculo'] ?>" class="btn btn-dark" download><i class="bi bi-file-earmark-pdf"></i> Curriculum</a>
        </div>

        <hr>

        <h3><i class="bi bi-person-lines-fill"></i> Informações Pessoais</h3>
        <p><i class="bi bi-envelope"></i> <?= $contato['email'] ?></p>
        <p><i class="bi bi-calendar"></i> Idade: <?= $idade ?> anos</p>
        <p>
            <a href="<?= $contato['whatsapp'] ?>" target="_blank" class="text-success fw-bold">
                <i class="bi bi-whatsapp"></i> <?= $contato['telefone'] ?>
            </a>
        </p>
        <p><i class="bi bi-geo-alt-fill"></i> <?= $contato['endereco'] ?></p>

        <hr>
        <h3><i class="bi bi-briefcase-fill"></i> Experiência Profissional</h3>
        <ul class="list-group">
            <?php foreach ($experiencia as $exp): ?>
                <li class="list-group-item">
                    <h5><i class="bi bi-person-workspace"></i> <?= $exp['cargo'] ?> - <span class="text-primary"><?= $exp['empresa'] ?></span></h5>
                    <small><i class="bi bi-calendar"></i> <?= $exp['periodo'] ?></small>
                    <p><?= $exp['descricao'] ?></p>
                </li>
            <?php endforeach; ?>
        </ul>

        <hr>

        <h3><i class="bi bi-mortarboard-fill"></i> Formação Acadêmica</h3>
        <ul class="list-group">
            <?php foreach ($formacoes as $formacao): ?>
                <li class="list-group-item">
                    <h5><i class="bi bi-journal-text"></i> <?= $formacao['curso'] ?></h5>
                    <p class="text-muted"><i class="bi bi-building"></i> <?= $formacao['instituicao'] ?></p>
                    <small><i class="bi bi-calendar"></i> <?= $formacao['periodo'] ?></small>
                </li>
            <?php endforeach; ?>
        </ul>

        <hr>

        <h3><i class="bi bi-tools"></i> Habilidades</h3>
        <?php foreach ($habilidades as $skill): ?>
            <p class="fw-bold"><i class="bi <?= $skill['icone'] ?>"></i> <?= $skill['nome'] ?> <span class="float-end"><?= $skill['nivel'] ?>%</span></p>
            <div class="progress">
                <div class="progress-bar" data-width="<?= $skill['nivel'] ?>%"></div>
            </div>
        <?php endforeach; ?>

        <hr>

        <h3><i class="bi bi-award"></i> Diplomas</h3>
        <ul class="list-group">
            <?php foreach ($diplomas as $arquivo): ?>
                <li class="list-group-item">
                    <i class="bi bi-file-earmark"></i> <?= $arquivo ?>
                    <a href="<?= $diplomaDir . $arquivo ?>" target="_blank" class="btn btn-outline-primary btn-sm float-end">
                        <i class="bi bi-download"></i> Baixar
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <hr>

<h3><i class="bi bi-folder-fill"></i> Projetos</h3>

<div class="row">
    <?php foreach ($projetos as $projeto): ?>
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <?php if (!empty($projeto['imagem'])): ?>
                    <img src="<?= $projeto['imagem'] ?>" class="card-img-top" alt="Imagem do projeto <?= $projeto['titulo'] ?>">
                <?php endif; ?>
                <div class="card-body text-center">
                    <h5 class="card-title"><?= $projeto['titulo'] ?></h5>
                    <p class="card-text text-muted"><?= $projeto['descricao'] ?></p>
                    <a href="<?= $projeto['link'] ?>" class="btn btn-primary" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i> Ver Projeto
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>


</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.progress-bar').forEach((bar) => {
            let width = bar.getAttribute('data-width');
            setTimeout(() => {
                bar.style.width = width;
            }, 500);
        });
    });
</script>

</body>
</html>
