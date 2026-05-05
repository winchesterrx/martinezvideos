<?php
$nome = "Gabriel da Silva Santos";
$cargo = "Desenvolvedor Web";
$descricao = "Criativo, inovador e apaixonado por tecnologia.";

$contato = [
    "telefone" => "(11) 99779-9982",
    "email" => "menored45@gmail.com",
    "endereco" => "Votuporanga, SP - Brasil",
    "linkedin" => "https://linkedin.com/in/gabriel-da-silva-santos-61b419196",
    "github" => "https://github.com/winchesterrx"
];

$habilidades = [
    ["nome" => "PHP", "icone" => "bi-code-slash", "nivel" => 90],
    ["nome" => "JavaScript", "icone" => "bi-filetype-js", "nivel" => 85],
    ["nome" => "HTML & CSS", "icone" => "bi-filetype-html", "nivel" => 95],
    ["nome" => "Bootstrap", "icone" => "bi-bootstrap", "nivel" => 80],
    ["nome" => "MySQL", "icone" => "bi-database", "nivel" => 75]
];

$experiencia = [
    ["cargo" => "Desenvolvedor Full Stack", "empresa" => "Tech Solutions", "periodo" => "2021 - Atual", "descricao" => "Desenvolvimento de sistemas web escaláveis."],
    ["cargo" => "Frontend Developer", "empresa" => "CodeFactory", "periodo" => "2019 - 2021", "descricao" => "Criação de interfaces responsivas e dinâmicas."]
];

$projetos = [
    ["titulo" => "Landing Page Moderna", "link" => "https://xofome.online/treinamento/index.php"],
    ["titulo" => "Sistema de Gestão", "link" => "https://meuprojeto2.com"],
    ["titulo" => "E-commerce Completo", "link" => "https://meuprojeto3.com"]
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currículo - <?= $nome ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e3e4e8);
            font-family: 'Poppins', sans-serif;
        }
        .card-custom {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        .progress-bar {
            animation: progressAnimation 1.5s ease-out;
        }
        @keyframes progressAnimation {
            from { width: 0; }
            to { width: 100%; }
        }
        .btn-custom {
            text-transform: uppercase;
            font-weight: bold;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="card card-custom shadow-lg p-4 text-center">
        <h1 class="fw-bold text-dark" data-aos="fade-down"><?= $nome ?></h1>
        <h4 class="text-primary"><?= $cargo ?></h4>
        <p class="text-muted"><?= $descricao ?></p>

        <div class="d-flex justify-content-center mb-3">
            <a href="<?= $contato['linkedin'] ?>" class="btn btn-primary btn-custom me-2"><i class="bi bi-linkedin"></i> LinkedIn</a>
            <a href="<?= $contato['github'] ?>" class="btn btn-dark btn-custom"><i class="bi bi-github"></i> GitHub</a>
            <button class="btn btn-danger btn-custom ms-2" onclick="gerarPDF()"><i class="bi bi-file-earmark-pdf"></i> Baixar PDF</button>
        </div>

        <hr>

        <h3 class="text-dark">Habilidades</h3>
        <?php foreach ($habilidades as $skill): ?>
            <p><i class="bi <?= $skill['icone'] ?>"></i> <?= $skill['nome'] ?></p>
            <div class="progress">
                <div class="progress-bar bg-primary" style="width: <?= $skill['nivel'] ?>%;" data-aos="slide-right">
                    <?= $skill['nivel'] ?>%
                </div>
            </div>
        <?php endforeach; ?>

        <hr>

        <h3 class="text-dark">Experiência Profissional</h3>
        <?php foreach ($experiencia as $exp): ?>
            <div class="text-start" data-aos="fade-up">
                <h5 class="fw-bold"><?= $exp['cargo'] ?> - <span class="text-primary"><?= $exp['empresa'] ?></span></h5>
                <p class="text-muted"><i class="bi bi-calendar"></i> <?= $exp['periodo'] ?></p>
                <p><?= $exp['descricao'] ?></p>
            </div>
        <?php endforeach; ?>

        <hr>

        <h3 class="text-dark">Projetos</h3>
        <div class="d-flex flex-wrap justify-content-center">
            <?php foreach ($projetos as $projeto): ?>
                <a href="<?= $projeto['link'] ?>" target="_blank" class="btn btn-outline-primary m-2 btn-custom">
                    <i class="bi bi-folder-fill"></i> <?= $projeto['titulo'] ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    AOS.init();
    function gerarPDF() {
        alert("Currículo em PDF será gerado aqui!");
    }
</script>

</body>
</html>
