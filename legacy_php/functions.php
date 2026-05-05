<?php
function registrar_log($mensagem, $nivel = 'INFO') {
    $arquivo_log = __DIR__ . '/logs/sistema.log'; // Caminho do arquivo de log
    $data_hora = date('Y-m-d H:i:s'); // Data e hora atual
    $linha_log = "[$data_hora] [$nivel] $mensagem" . PHP_EOL;

    // Cria o diretório logs se não existir
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }

    // Grava a mensagem no arquivo
    file_put_contents($arquivo_log, $linha_log, FILE_APPEND);
}
?>
