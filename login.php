<?php

require_once __DIR__ . '/config/conexao.php';

if (isset($_SESSION['usuario_id'])) {

    header('Location: ' . ($_SESSION['usuario_tipo'] === 'superadmin'
        ? '/pages/empresas.php'
        : '/pages/dashboard.php'));
    exit;

}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validarToken();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

            $sql = "SELECT u.id, u.nome, u.email, u.senha, u.tipo, u.empresa_id,
                         e.nome AS empresa_nome
            FROM usuarios u
                     LEFT JOIN empresas e ON e.id = u.empresa_id
            WHERE u.email = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {

        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        $_SESSION['empresa_id'] = (int) ($usuario['empresa_id'] ?? 0);
        $_SESSION['empresa_nome'] = $usuario['empresa_nome'] ?? 'Admin geral';

        header('Location: ' . ($usuario['tipo'] === 'superadmin'
            ? '/pages/empresas.php'
            : '/pages/dashboard.php'));
        exit;

    } else {

        $erro = 'E-mail ou senha incorretos.';

    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | FinanceControl</title>

    <link rel="stylesheet" href="/assets/css/style.css">

</head>

<body class="auth-body">

<div class="auth-container">

    <h1>Finance<span>Control</span></h1>

    <p class="subtitle">
        Acompanhe o caixa, os custos e as decisões da sua empresa.
    </p>

    <?php if (isset($_GET['cadastro'])): ?>

        <div class="sucesso">
            Cadastro realizado! Faça seu login.
        </div>

    <?php endif; ?>

    <?php if ($erro): ?>

        <div class="erro">
            <?= e($erro) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(gerarToken()) ?>"
        >

        <label>E-mail</label>

        <input
            type="email"
            name="email"
            placeholder="seu@email.com"
            required
        >

        <label>Senha</label>

        <input
            type="password"
            name="senha"
            placeholder="Sua senha"
            required
        >

        <button type="submit">
            Entrar
        </button>

    </form>

    <p class="auth-link">
        <a href="/recuperar_senha.php">Esqueci minha senha</a>
    </p>

    <p class="auth-link">

        Ainda não possui uma conta?

        <a href="/cadastro.php">Cadastre-se</a>

    </p>

</div>

</body>
</html>
