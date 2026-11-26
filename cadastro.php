<?php

require_once __DIR__ . '/config/conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validarToken();

    $empresa = $_POST['empresa'] ?? '';
    $empresa = is_string($empresa) ? trim($empresa) : '';
    $nome = $_POST['nome'] ?? '';
    $nome = is_string($nome) ? trim($nome) : '';
    $email = $_POST['email'] ?? '';
    $email = is_string($email) ? trim($email) : '';
    $senha = $_POST['senha'] ?? null;
    $confirmar = $_POST['confirmar'] ?? null;

    if ($empresa === '' || strlen($empresa) > 150) {

        $erro = 'Informe o nome da empresa (até 150 caracteres).';

    } elseif ($nome === '' || strlen($nome) > 100) {

        $erro = 'Informe seu nome (até 100 caracteres).';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Informe um e-mail válido.';

    } elseif (strlen($email) > 150) {

        $erro = 'O e-mail deve ter até 150 caracteres.';

    } elseif (!is_string($senha) || !is_string($confirmar) || strlen($senha) < 8) {

        $erro = 'A senha precisa ter pelo menos 8 caracteres.';

    } elseif ($senha !== $confirmar) {

        $erro = 'As senhas não coincidem.';

    } else {

        $verificar = $pdo->prepare(
            "SELECT id FROM usuarios WHERE email = ? LIMIT 1"
        );
        $verificar->execute([$email]);

        if ($verificar->fetch()) {

            $erro = 'Este e-mail já está cadastrado.';

        } else {

            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $categoriasPadrao = [
                ['Venda de produtos', 'receita'],
                ['Prestação de serviços', 'receita'],
                ['Receitas financeiras', 'receita'],
                ['Comissões e royalties', 'receita'],
                ['Outras receitas', 'receita'],
                ['Fornecedores', 'despesa'],
                ['Custos com terceiros', 'despesa'],
                ['Folha de pagamento', 'despesa'],
                ['Encargos e benefícios', 'despesa'],
                ['Aluguel e condomínio', 'despesa'],
                ['Água, energia e telecomunicações', 'despesa'],
                ['Impostos e taxas', 'despesa'],
                ['Marketing e publicidade', 'despesa'],
                ['Transporte e logística', 'despesa'],
                ['Materiais e suprimentos', 'despesa'],
                ['Software e tecnologia', 'despesa'],
                ['Serviços profissionais', 'despesa'],
                ['Manutenção e equipamentos', 'despesa'],
                ['Despesas bancárias', 'despesa'],
                ['Viagens corporativas', 'despesa'],
                ['Outras despesas operacionais', 'despesa']
            ];

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO empresas (nome) VALUES (?)"
                );
                $stmt->execute([$empresa]);
                $empresaId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (empresa_id, nome, email, senha, tipo)
                     VALUES (?, ?, ?, ?, 'admin')"
                );
                $stmt->execute([$empresaId, $nome, $email, $hash]);

                $stmt = $pdo->prepare(
                    "INSERT INTO categorias (empresa_id, nome, tipo)
                     VALUES (?, ?, ?)"
                );

                foreach ($categoriasPadrao as [$nomeCategoria, $tipoCategoria]) {
                    $stmt->execute([$empresaId, $nomeCategoria, $tipoCategoria]);
                }

                $pdo->commit();

                header('Location: /login.php?cadastro=sucesso');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if ($e->getCode() === '23000') {
                    $erro = 'Este e-mail já está cadastrado.';
                } else {
                    error_log('Erro ao criar empresa: ' . $e->getMessage());
                    $erro = 'Não foi possível criar a empresa. Tente novamente.';
                }
            }

        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro | FinanceControl</title>

    <link rel="stylesheet" href="/assets/css/style.css">

</head>

<body class="auth-body">

<div class="auth-container">

    <h1>Finance<span>Control</span></h1>

    <p class="subtitle">
        Crie o espaço financeiro da empresa. Sua conta será administradora.
    </p>

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

        <label>Nome da empresa</label>

        <input
            type="text"
            name="empresa"
            value="<?= e($_POST['empresa'] ?? '') ?>"
            placeholder="Sua empresa Ltda."
            maxlength="150"
            autocomplete="organization"
            required
        >

        <label>Nome completo</label>

        <input
            type="text"
            name="nome"
            value="<?= e($_POST['nome'] ?? '') ?>"
            placeholder="Seu nome"
            maxlength="100"
            required
        >

        <label>E-mail</label>

        <input
            type="email"
            name="email"
            value="<?= e($_POST['email'] ?? '') ?>"
            placeholder="seu@email.com"
            maxlength="150"
            required
        >

        <label>Senha</label>

        <input
            type="password"
            name="senha"
            placeholder="Mínimo de 8 caracteres"
            required
        >

        <label>Confirmar senha</label>

        <input
            type="password"
            name="confirmar"
            placeholder="Digite novamente"
            required
        >

        <button type="submit">
            Criar minha conta
        </button>

    </form>

    <p class="auth-link">

        Já possui uma conta?

        <a href="/login.php">Entrar</a>

    </p>

</div>

</body>
</html>
