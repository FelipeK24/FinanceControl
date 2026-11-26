<?php

require_once __DIR__ . '/config/conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validarToken();

    $email = $_POST['email'] ?? '';
    $email = is_string($email) ? trim($email) : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Informe um e-mail válido.';

    } else {

        $stmt = $pdo->prepare(
            "SELECT id FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $stmt = $pdo->prepare(
                "INSERT INTO solicitacoes_redefinicao_senha
                    (usuario_id, solicitada_em, pendente, resolvida_em)
                 VALUES (?, CURRENT_TIMESTAMP, 1, NULL)
                 ON DUPLICATE KEY UPDATE
                    solicitada_em = IF(pendente = 1, solicitada_em, CURRENT_TIMESTAMP),
                    resolvida_em = NULL,
                    pendente = 1"
            );
            $stmt->execute([(int) $usuario['id']]);
        }

        $mensagem = 'Se este e-mail estiver cadastrado, a solicitação será exibida ao administrador da empresa na aba Equipe.';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha | FinanceControl</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body">
    <main class="auth-container">
        <h1>Finance<span>Control</span></h1>
        <p class="subtitle">Solicite a redefinição da senha da sua conta.</p>

        <?php if ($mensagem !== ''): ?>
            <div class="sucesso"><?= e($mensagem) ?></div>
        <?php elseif ($erro !== ''): ?>
            <div class="erro"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(gerarToken()) ?>">

            <label for="email_recuperacao">E-mail da conta</label>
            <input
                type="email"
                id="email_recuperacao"
                name="email"
                autocomplete="email"
                placeholder="seu@email.com"
                required
            >

            <button type="submit">Solicitar redefinição</button>
        </form>

        <p class="auth-link">
            <a href="/login.php">Voltar ao login</a>
        </p>
    </main>
</body>
</html>