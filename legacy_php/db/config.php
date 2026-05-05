<?php
// Verifica se as constantes já estão definidas, se não estiverem, define-as.
if (!defined('SERVIDOR')) {
    // define('SERVIDOR', 'martinezvideo.mysql.uhserver.com'); // Hostname original (timeout)
    define('SERVIDOR', '200.147.61.74'); // Fix: Usando IP direto para evitar problemas de DNS/IPv6 e Timeout
}

if (!defined('USUARIO')) {
    define('USUARIO', 'winchester123'); // ou seu usuário de banco de dados
}

if (!defined('SENHA')) {
    define('SENHA', '@Saopaulop45'); // ou sua senha de banco de dados
}

if (!defined('BANCO')) {
    define('BANCO', 'martinezvideo'); // ou seu banco de dados
}
?>