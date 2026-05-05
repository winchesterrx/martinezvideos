<?php
session_start();
include 'db/conexao.php';

// Mensagens de feedback
$sucesso_registro = isset($_GET['registro']) && $_GET['registro'] === 'success';
$erro_acesso = isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = $_POST['senha'];

    // Primeiro tenta buscar como usuário do sistema
    $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Cria a sessão com os dados do usuário do sistema
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nome'] = $usuario['nome'];
        $_SESSION['user_adm'] = ($usuario['ADM'] === 'S');
        $_SESSION['user_type'] = 'usuario'; // Identifica que é usuário do sistema
        header('Location: index.php');
        exit;
    } else {
        // Se não encontrou como usuário, tenta como cliente
        $stmt = $conexao->prepare("SELECT * FROM clientes WHERE email = ? AND ativo = 'S'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente = $result->fetch_assoc();
        $stmt->close();

        if ($cliente && password_verify($senha, $cliente['senha'])) {
            // Cria a sessão com os dados do cliente
            $_SESSION['user_id'] = $cliente['id'];
            $_SESSION['user_nome'] = $cliente['nome'];
            $_SESSION['user_adm'] = false; // Clientes nunca são admin
            $_SESSION['user_type'] = 'cliente'; // Identifica que é cliente
            header('Location: index.php');
            exit;
        } else {
            $erro = "Email ou senha inválidos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom right, #ff6f00, #ff9800);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container img.logo {
            width: 100px;
            margin-bottom: 20px;
        }

        .login-container h1 {
            margin-bottom: 20px;
            font-weight: 600;
            color: #333;
        }

        .login-container form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .login-container form input:focus {
            border-color: #ff6f00;
            outline: none;
            box-shadow: 0 0 5px rgba(255, 111, 0, 0.5);
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input {
            padding-right: 40px;
        }

        .password-container i {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }

        .password-container i:hover {
            color: #ff6f00;
        }

        .login-container form button {
            width: 100%;
            padding: 12px;
            background: #ff6f00;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .login-container form button:hover {
            background: #ff9800;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .error {
            color: #dc3545;
            font-size: 14px;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .success {
            color: #155724;
            font-size: 14px;
            margin-bottom: 15px;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }

        .warning {
            color: #856404;
            font-size: 14px;
            margin-bottom: 15px;
            padding: 10px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 5px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }

        .footer a {
            color: #ff6f00;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
        
    </style>
    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const senhaIcon = document.getElementById('senha-icon');
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                senhaIcon.classList.remove('fa-eye');
                senhaIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                senhaIcon.classList.remove('fa-eye-slash');
                senhaIcon.classList.add('fa-eye');
            }
        }
    </script>
</head>
<body>
    <div class="login-container">
        <img src="img/martinez.png" alt="Logo" class="logo">
        <h1>Login</h1>
        <?php if ($sucesso_registro): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <strong>Sucesso!</strong> Conta criada com sucesso! Faça login para continuar.
            </div>
        <?php endif; ?>
        <?php if ($erro_acesso): ?>
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i> <strong>Acesso Negado!</strong> Você não tem permissão para acessar essa página. Apenas administradores podem acessar.
            </div>
        <?php endif; ?>
        <?php if (!empty($erro)): ?>
            <div class="error">
                <i class="fas fa-times-circle"></i> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Digite seu email" required>
            <div class="password-container">
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                <i class="fas fa-eye" id="senha-icon" onclick="togglePassword()"></i>
            </div>
            <button type="submit">Entrar</button>
        </form>
        <div class="footer">
            <p>Não possui uma conta? <a href="registro.php">Registre-se aqui</a>.</p>
        </div>
    </div>
</body>
</html>
