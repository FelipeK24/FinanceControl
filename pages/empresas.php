<?php

require_once __DIR__ . '/../includes/auth.php';

exigirAdminGeral();

$empresas = $pdo->query(
    "SELECT
        e.id,
        e.nome,
        e.criado_em,
        COUNT(DISTINCT u.id) AS total_usuarios,
        COUNT(DISTINCT l.id) AS total_lancamentos
     FROM empresas e
     LEFT JOIN usuarios u ON u.empresa_id = e.id
     LEFT JOIN lancamentos l ON l.usuario_id = u.id
     GROUP BY e.id
     ORDER BY e.nome, e.id"
)->fetchAll();

$empresaSelecionadaId = (int) ($_SESSION['empresa_contexto_id'] ?? 0);
$erro = $_GET['erro'] ?? '';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">
    <div>
        <h1>Empresas</h1>
        <p>Admin geral · selecione uma empresa para abrir seu painel.</p>
    </div>
</div>

<?php if ($erro === 'empresa'): ?>
    <div class="erro">A empresa selecionada não foi encontrada.</div>
<?php endif; ?>

<?php if ($empresaSelecionadaId > 0): ?>
    <div class="content-card" style="margin-bottom: 24px;">
        <p>Contexto atual: <strong><?= e($_SESSION['empresa_nome']) ?></strong></p>
        <form action="/acao.php" method="POST" style="margin-top: 16px;">
            <input type="hidden" name="csrf_token" value="<?= e(gerarToken()) ?>">
            <input type="hidden" name="acao" value="limpar_empresa">
            <button type="submit" class="btn-secondary">Sair do painel da empresa</button>
        </form>
    </div>
<?php endif; ?>

<?php if (empty($empresas)): ?>
    <div class="content-card text-muted">Ainda não há empresas cadastradas.</div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Membros</th>
                    <th>Lançamentos</th>
                    <th>Criada em</th>
                    <th>Acesso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empresas as $empresa): ?>
                    <tr>
                        <td><?= e($empresa['nome']) ?></td>
                        <td><?= (int) $empresa['total_usuarios'] ?></td>
                        <td><?= (int) $empresa['total_lancamentos'] ?></td>
                        <td><?= date('d/m/Y', strtotime($empresa['criado_em'])) ?></td>
                        <td>
                            <form action="/acao.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= e(gerarToken()) ?>">
                                <input type="hidden" name="acao" value="selecionar_empresa">
                                <input type="hidden" name="empresa_id" value="<?= (int) $empresa['id'] ?>">
                                <button type="submit" class="btn-primary">
                                    <?= $empresaSelecionadaId === (int) $empresa['id']
                                        ? 'Abrir novamente'
                                        : 'Abrir empresa' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>