<?php

require_once __DIR__ . '/../includes/auth.php';

exigirLogin();

$usuarioId = $_SESSION['usuario_id'];
$empresaId = (int) $_SESSION['empresa_id'];
$ehAdmin = $_SESSION['usuario_tipo'] === 'admin';

if ($ehAdmin) {

    $filtro = 'WHERE usuario_id IN (
        SELECT id FROM usuarios WHERE empresa_id = ?
    )';
    $parametros = [$empresaId];

} else {

    $filtro = 'WHERE usuario_id = ?';
    $parametros = [$usuarioId];

}

$sqlReceitas = "SELECT COALESCE(SUM(valor), 0)
                FROM lancamentos
                $filtro
                " . ($filtro ? "AND tipo = 'receita'" : "WHERE tipo = 'receita'");

$stmt = $pdo->prepare($sqlReceitas);
$stmt->execute($parametros);
$totalReceitas = $stmt->fetchColumn();

$sqlDespesas = "SELECT COALESCE(SUM(valor), 0)
                FROM lancamentos
                $filtro
                " . ($filtro ? "AND tipo = 'despesa'" : "WHERE tipo = 'despesa'");

$stmt = $pdo->prepare($sqlDespesas);
$stmt->execute($parametros);
$totalDespesas = $stmt->fetchColumn();

$saldo = $totalReceitas - $totalDespesas;

$sql = "SELECT
            l.*,
            c.nome AS categoria,
            u.nome AS usuario
        FROM lancamentos l
        INNER JOIN categorias c ON c.id = l.categoria_id
        INNER JOIN usuarios u ON u.id = l.usuario_id";

if ($ehAdmin) {
    $sql .= " WHERE u.empresa_id = ?";
} else {
    $sql .= " WHERE l.usuario_id = ?";
}

$sql .= " ORDER BY l.data DESC, l.id DESC LIMIT 5";

$stmt = $pdo->prepare($sql);
$stmt->execute($ehAdmin ? [$empresaId] : [$usuarioId]);

$ultimosLancamentos = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">

    <div>
        <h1>Visão geral</h1>
        <p>Acompanhe o resumo das suas finanças.</p>
    </div>

    <a href="/pages/lancamentos.php" class="btn-primary">
        Novo lançamento
    </a>

</div>

<div class="cards">

    <div class="card">
        <span class="card-label">Receitas</span>
        <strong class="valor-positivo">
            <?= dinheiro($totalReceitas) ?>
        </strong>
    </div>

    <div class="card">
        <span class="card-label">Despesas</span>
        <strong class="valor-negativo">
            <?= dinheiro($totalDespesas) ?>
        </strong>
    </div>

    <div class="card">
        <span class="card-label">Saldo atual</span>
        <strong class="<?= $saldo >= 0 ? 'valor-positivo' : 'valor-negativo' ?>">
            <?= dinheiro($saldo) ?>
        </strong>
    </div>

</div>

<div class="section-heading">
    <h2>Últimos lançamentos</h2>
    <a href="/pages/lancamentos.php">Ver todos</a>
</div>

<div class="table-container">

    <table>

        <thead>
            <tr>
                <th>Descrição</th>
                <th>Categoria</th>
                <?php if ($ehAdmin): ?>
                    <th>Usuário</th>
                <?php endif; ?>
                <th>Data</th>
                <th>Tipo</th>
                <th>Valor</th>
            </tr>
        </thead>

        <tbody>

            <?php if (count($ultimosLancamentos) > 0): ?>

                <?php foreach ($ultimosLancamentos as $lancamento): ?>

                    <tr>

                        <td><?= e($lancamento['descricao']) ?></td>

                        <td><?= e($lancamento['categoria']) ?></td>

                        <?php if ($ehAdmin): ?>
                            <td><?= e($lancamento['usuario']) ?></td>
                        <?php endif; ?>

                        <td>
                            <?= date('d/m/Y', strtotime($lancamento['data'])) ?>
                        </td>

                        <td>
                            <span class="badge <?= $lancamento['tipo'] ?>">
                                <?= ucfirst($lancamento['tipo']) ?>
                            </span>
                        </td>

                        <td><?= dinheiro($lancamento['valor']) ?></td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="<?= $ehAdmin ? 6 : 5 ?>" class="empty">
                        Nenhum lançamento cadastrado.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
