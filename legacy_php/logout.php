<?php
session_start();

// Destroi a sessão atual
session_unset();
session_destroy();

// Redireciona para a página inicial ou de login
header("Location: index.php");
exit;
?>
