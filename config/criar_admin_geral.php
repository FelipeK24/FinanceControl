<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/conexao.php';

if ($argc !== 3) {
    fwrite(STDERR, "Uso: php config/criar_admin_geral.php \"Nome\" email@empresa.com\n");
    exit(1);
}

$nome = trim($argv[1]);
$email = trim($argv[2]);

if (
    $nome === '' ||
    strlen($nome) > 100 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 150
) {
    fwrite(STDERR, "Nome ou e-mail inválido.\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    fwrite(STDERR, "Esse e-mail já possui uma conta.\n");
    exit(1);
}

$senhaInicial = bin2hex(random_bytes(16));
$hash = password_hash($senhaInicial, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO usuarios (empresa_id, nome, email, senha, tipo)
     VALUES (NULL, ?, ?, ?, 'superadmin')"
);
$stmt->execute([$nome, $email, $hash]);

fwrite(STDOUT, "Admin geral criado para {$email}.\n");
fwrite(STDOUT, "Senha inicial (guarde-a agora): {$senhaInicial}\n");