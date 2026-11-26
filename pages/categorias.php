<?php

require_once __DIR__ . '/../includes/auth.php';

exigirLogin();

$ehAdmin = $_SESSION['usuario_tipo'] === 'admin';

$editar = isset($_GET['editar']) ? (int) $_GET['editar'] : 0;
$categoriaEditar = null;

if ($editar > 0 && $ehAdmin) {

    $stmt = $pdo->prepare(
        "SELECT * FROM categorias
         WHERE id = ? AND ativa = 1 AND empresa_id = ?"
    );

    $stmt->execute([$editar, (int) $_SESSION['empresa_id']]);

    $categoriaEditar = $stmt->fetch();

}

$categorias = $pdo->query(
    "SELECT * FROM categorias
     WHERE ativa = 1 AND empresa_id = " . (int) $_SESSION['empresa_id'] . "
     ORDER BY tipo, nome"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">

    <div>
        <h1>Categorias da empresa</h1>
        <p>Classificações compartilhadas para as movimentações financeiras.</p>
    </div>

</div>

<?php if ($ehAdmin): ?>

<div class="content-card">

        <h2>
            <?= $categoriaEditar ? 'Editar categoria' : 'Nova categoria' ?>
        </h2>

        <form action="/acao.php" method="POST" class="form-grid">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(gerarToken()) ?>"
            >

            <input
                type="hidden"
                name="acao"
                value="<?= $categoriaEditar ? 'editar_categoria' : 'criar_categoria' ?>"
            >

            <?php if ($categoriaEditar): ?>
                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $categoriaEditar['id'] ?>"
                >
            <?php endif; ?>

            <div class="form-group">
                <label>Nome da categoria</label>

                <input
                    type="text"
                    name="nome"
                    maxlength="80"
                    value="<?= e($categoriaEditar['nome'] ?? '') ?>"
                    placeholder="Ex.: Alimentação"
                    required
                >
            </div>

            <div class="form-group">
                <label>Tipo</label>

                <select name="tipo" required>

                    <option value="">Selecione</option>

                    <option
                        value="receita"
                        <?= ($categoriaEditar['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>
                    >
                        Receita
                    </option>

                    <option
                        value="despesa"
                        <?= ($categoriaEditar['tipo'] ?? '') === 'despesa' ? 'selected' : '' ?>
                    >
                        Despesa
                    </option>

                </select>
            </div>

            <div class="form-actions">

                <button type="submit" class="btn-primary">
                    <?= $categoriaEditar ? 'Salvar alterações' : 'Cadastrar categoria' ?>
                </button>

                <?php if ($categoriaEditar): ?>
                    <a href="/pages/categorias.php" class="btn-secondary">
                        Cancelar
                    </a>
                <?php endif; ?>

            </div>

        </form>

</div>

<?php else: ?>

<p class="text-muted">As categorias da empresa são gerenciadas pelos administradores.</p>

<?php endif; ?>

<div class="section-heading">
    <h2>Categorias cadastradas</h2>
</div>

<div class="table-container">

    <table>

        <thead>
            <tr>
                <th>Nome</th>
                <th>Tipo</th>

                <?php if ($ehAdmin): ?>
                    <th>Ações</th>
                <?php endif; ?>

            </tr>
        </thead>

        <tbody>

            <?php foreach ($categorias as $categoria): ?>

                <tr>

                    <td><?= e($categoria['nome']) ?></td>

                    <td>
                        <span class="badge <?= $categoria['tipo'] ?>">
                            <?= ucfirst($categoria['tipo']) ?>
                        </span>
                    </td>

                    <?php if ($ehAdmin): ?>

                        <td class="actions">

                            <a
                                href="/pages/categorias.php?editar=<?= (int) $categoria['id'] ?>"
                                class="action-edit"
                            >
                                Editar
                            </a>

                            <form action="/acao.php" method="POST"
                                  onsubmit="return confirm('Deseja excluir esta categoria?');">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="excluir_categoria"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $categoria['id'] ?>"
                                >

                                <button type="submit" class="action-delete">
                                    Excluir
                                </button>

                            </form>

                        </td>

                    <?php endif; ?>

                </tr>

            <?php endforeach; ?>

            <?php if (count($categorias) === 0): ?>

                <tr>
                    <td colspan="<?= $ehAdmin ? 3 : 2 ?>" class="empty">
                        Nenhuma categoria cadastrada.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
