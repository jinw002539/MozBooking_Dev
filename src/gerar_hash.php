<?php
/**
 * gerar_hash.php — gera o hash bcrypt de uma password para inserir
 * manualmente na coluna `chave` da tabela staff.
 *
 * Uso (linha de comandos, nunca pelo browser):
 *   php gerar_hash.php "a_password_escolhida"
 *
 * Depois usar o resultado num INSERT/UPDATE, por exemplo:
 *   UPDATE staff SET chave = '<hash gerado>' WHERE username = 'novo_user';
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado na linha de comandos (CLI).');
}

if ($argc < 2 || trim($argv[1]) === '') {
    fwrite(STDERR, "Uso: php gerar_hash.php \"password_a_definir\"\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_BCRYPT) . "\n";
