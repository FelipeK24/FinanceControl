<?php

require_once __DIR__ . '/../includes/auth.php';

exigirAdmin();

$empresaId = (int) $_SESSION['empresa_id'];

$sql = "SELECT
            u.id,
            u.nome,
            u.email,
            u.tipo,
            COUNT(l.id) AS total_lancamentos
        FROM usuarios u
        LEFT JOIN lancamentos l ON l.usuario_id = u.id
        WHERE u.empresa_id = ?
        GROUP BY u.id
        ORDER BY u.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$empresaId]);
    $usuarios = $stmt->fetchAll();

    $stmt = $pdo->prepare(
    "SELECT s.usuario_id, s.solicitada_em, u.nome, u.email
     FROM solicitacoes_redefinicao_senha s
     INNER JOIN usuarios u ON u.id = s.usuario_id
         WHERE s.pendente = 1 AND u.empresa_id = ?
     ORDER BY s.solicitada_em ASC"
    );
    $stmt->execute([$empresaId]);
    $solicitacoesSenha = $stmt->fetchAll();

$totalUsuarios = count($usuarios);
$sucesso = $_GET['sucesso'] ?? '';
$erro = $_GET['erro'] ?? '';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">

    <div>
        <h1>Equipe da empresa</h1>
        <p>Gerencie os acessos de administradores e funcionários.</p>
    </div>

</div>

<?php if ($sucesso === 'funcionario'): ?>
    <div class="sucesso">Funcionário adicionado à equipe.</div>
<?php elseif ($sucesso === 'senha_redefinida'): ?>
    <div class="sucesso">A senha do funcionário foi redefinida.</div>
<?php elseif ($erro === 'email'): ?>
    <div class="erro">Já existe uma conta com esse e-mail.</div>
<?php elseif ($erro === 'dados'): ?>
    <div class="erro">Confira os dados e tente novamente. A senha deve ter ao menos 8 caracteres.</div>
<?php elseif ($erro === 'senha'): ?>
    <div class="erro">As senhas devem ter ao menos 8 caracteres e ser iguais.</div>
<?php elseif ($erro === 'solicitacao'): ?>
    <div class="erro">Essa solicitação não está mais pendente.</div>
<?php endif; ?>

<div class="cards">

    <div class="card">
        <span class="card-label">Membros da equipe</span>
        <strong><?= $totalUsuarios ?></strong>
    </div>

</div>

<div class="content-card" style="margin-bottom: 24px;">

    <h2>Adicionar funcionário</h2>

    <form action="/acao.php" method="POST" class="form-grid">

        <input type="hidden" name="csrf_token" value="<?= e(gerarToken()) ?>">
        <input type="hidden" name="acao" value="criar_usuario">

        <div class="form-group">
            <label for="nome_funcionario">Nome completo</label>
            <input
                type="text"
                id="nome_funcionario"
                name="nome"
                maxlength="100"
                autocomplete="name"
                required
            >
        </div>

        <div class="form-group">
            <label for="email_funcionario">E-mail</label>
            <input
                type="email"
                id="email_funcionario"
                name="email"
                maxlength="150"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="senha_funcionario">Senha inicial</label>
            <input
                type="password"
                id="senha_funcionario"
                name="senha"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirmar_funcionario">Confirmar senha</label>
            <input
                type="password"
                id="confirmar_funcionario"
                name="confirmar"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Adicionar funcionário</button>
        </div>

    </form>

</div>

<div class="section-heading">
    <h2>Solicitações de recuperação de senha</h2>
</div>

<?php if (empty($solicitacoesSenha)): ?>

    <div class="content-card text-muted">
        Não há solicitações pendentes.
    </div>

<?php else: ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>E-mail</th>
                    <th>Solicitada em</th>
                    <th>Definir nova senha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitacoesSenha as $solicitacao): ?>
                    <tr>
                        <td><?= e($solicitacao['nome']) ?></td>
                        <td><?= e($solicitacao['email']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($solicitacao['solicitada_em'])) ?></td>
                        <td>
                            <form action="/acao.php" method="POST" class="team-password-form">
                                <input type="hidden" name="csrf_token" value="<?= e(gerarToken()) ?>">
                                <input type="hidden" name="acao" value="redefinir_senha_usuario">
                                <input type="hidden" name="usuario_id" value="<?= (int) $solicitacao['usuario_id'] ?>">
                                <input
                                    type="password"
                                    name="senha"
                                    minlength="8"
                                    autocomplete="new-password"
                                    placeholder="Nova senha"
                                    aria-label="Nova senha para <?= e($solicitacao['nome']) ?>"
                                    required
                                >
                                <input
                                    type="password"
                                    name="confirmar"
                                    minlength="8"
                                    autocomplete="new-password"
                                    placeholder="Confirmar senha"
                                    aria-label="Confirmar nova senha para <?= e($solicitacao['nome']) ?>"
                                    required
                                >
                                <button type="submit" class="btn-primary">Redefinir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<div class="section-heading">
    <h2>Equipe cadastrada</h2>
</div>

<div class="table-container">

    <table>

        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil de acesso</th>
                <th>Lançamentos</th>
                <th>Cadastro</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

            <?php foreach ($usuarios as $usuario): ?>

                <tr>

                    <td>
                        <?= e($usuario['nome']) ?>

                        <?php if ((int) $usuario['id'] === (int) $_SESSION['usuario_id']): ?>
                            <span class="you-label">Você</span>
                        <?php endif; ?>
                    </td>

                    <td><?= e($usuario['email']) ?></td>

                    <td>
                        <span class="badge <?= $usuario['tipo'] ?>">
                            <?= $usuario['tipo'] === 'admin'
                                ? 'Administrador da empresa'
                                : 'Funcionário' ?>
                        </span>
                    </td>

                    <td><?= (int) $usuario['total_lancamentos'] ?></td>

                    <td class="actions">

                        <?php if ((int) $usuario['id'] !== (int) $_SESSION['usuario_id']): ?>

                            <form action="/acao.php" method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="alterar_usuario"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $usuario['id'] ?>"
                                >

                                <select name="tipo" class="small-select">

                                    <option value="usuario"
                                        <?= $usuario['tipo'] === 'usuario' ? 'selected' : '' ?>>
                                        Funcionário
                                    </option>

                                    <option value="admin"
                                        <?= $usuario['tipo'] === 'admin' ? 'selected' : '' ?>>
                                        Administrador da empresa
                                    </option>

                                </select>

                                <button type="submit" class="action-edit">
                                    Salvar perfil
                                </button>

                            </form>

                            <form action="/acao.php" method="POST"
                                  onsubmit="return confirm('Deseja excluir esta conta? Os lançamentos deste usuário também serão excluídos.');">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="excluir_usuario"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $usuario['id'] ?>"
                                >

                                <button type="submit" class="action-delete">
                                    Excluir
                                </button>

                            </form>

                        <?php else: ?>

                            <span class="text-muted">
                                Conta atual
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (count($usuarios) === 0): ?>

                <tr>
                    <td colspan="6" class="empty">
                        Nenhum usuário cadastrado.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
