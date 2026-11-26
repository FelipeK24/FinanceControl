<?php

require_once __DIR__ . '/../includes/auth.php';

exigirEmpresa();

$usuarioId = $_SESSION['usuario_id'];
$empresaId = (int) $_SESSION['empresa_id'];
$ehAdmin = ehAdminEmpresa();





$busca = trim($_GET['busca'] ?? '');
$tipoFiltro = $_GET['tipo'] ?? '';
$categoriaFiltro = (int) ($_GET['categoria'] ?? 0);
$dataInicial = $_GET['data_inicial'] ?? '';
$dataFinal = $_GET['data_final'] ?? '';
$ordenacao = $_GET['ordenacao'] ?? 'data_desc';

$editar = isset($_GET['editar']) ? (int) $_GET['editar'] : 0;

$lancamentoEditar = null;


function dataFiltroValida($data)
{
    if ($data === '') {
        return true;
    }

    $dataObjeto = DateTime::createFromFormat('!Y-m-d', $data);

    return $dataObjeto && $dataObjeto->format('Y-m-d') === $data;
}

if (!dataFiltroValida($dataInicial)) {
    $dataInicial = '';
}

if (!dataFiltroValida($dataFinal)) {
    $dataFinal = '';
}


if ($dataInicial !== '' && $dataFinal !== '' && $dataInicial > $dataFinal) {
    $dataInicial = '';
    $dataFinal = '';
}


if (!in_array($tipoFiltro, ['', 'receita', 'despesa'], true)) {
    $tipoFiltro = '';
}


$ordenacoesPermitidas = [
    'data_desc' => 'l.data DESC, l.id DESC',
    'data_asc' => 'l.data ASC, l.id ASC',
    'valor_desc' => 'l.valor DESC, l.data DESC',
    'valor_asc' => 'l.valor ASC, l.data DESC'
];

if (!array_key_exists($ordenacao, $ordenacoesPermitidas)) {
    $ordenacao = 'data_desc';
}





if ($editar > 0) {

    $sql = "SELECT l.*
            FROM lancamentos l
            INNER JOIN usuarios u ON u.id = l.usuario_id
            WHERE l.id = ?";

    if ($ehAdmin) {
        $sql .= " AND u.empresa_id = ?";
    } else {
        $sql .= " AND l.usuario_id = ?";
    }

    $stmt = $pdo->prepare($sql);

    $stmt->execute(
        $ehAdmin ? [$editar, $empresaId] : [$editar, $usuarioId]
    );

    $lancamentoEditar = $stmt->fetch();

    if (!$lancamentoEditar) {
        $editar = 0;
    }
}





$categorias = $pdo->query(
    "SELECT * FROM categorias
     WHERE empresa_id = " . $empresaId . "
     ORDER BY nome"
)->fetchAll();





$sql = "SELECT
            l.*,
            c.nome AS categoria,
            u.nome AS usuario
        FROM lancamentos l
        INNER JOIN categorias c ON c.id = l.categoria_id
        INNER JOIN usuarios u ON u.id = l.usuario_id
        WHERE 1 = 1";

$parametros = [];


if ($ehAdmin) {
    $sql .= " AND u.empresa_id = ?";
    $parametros[] = $empresaId;
} else {
    $sql .= " AND l.usuario_id = ?";
    $parametros[] = $usuarioId;
}


if ($busca !== '') {

    $sql .= " AND (
        l.descricao LIKE ?
        OR c.nome LIKE ?
        OR u.nome LIKE ?
    )";

    $termo = '%' . $busca . '%';

    $parametros[] = $termo;
    $parametros[] = $termo;
    $parametros[] = $termo;
}


if ($tipoFiltro !== '') {
    $sql .= " AND l.tipo = ?";
    $parametros[] = $tipoFiltro;
}


if ($categoriaFiltro > 0) {
    $sql .= " AND l.categoria_id = ?";
    $parametros[] = $categoriaFiltro;
}


if ($dataInicial !== '') {
    $sql .= " AND l.data >= ?";
    $parametros[] = $dataInicial;
}


if ($dataFinal !== '') {
    $sql .= " AND l.data <= ?";
    $parametros[] = $dataFinal;
}


$sql .= " ORDER BY " . $ordenacoesPermitidas[$ordenacao];

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

$lancamentos = $stmt->fetchAll();





$totalReceitas = 0;
$totalDespesas = 0;

foreach ($lancamentos as $lancamento) {

    if ($lancamento['tipo'] === 'receita') {
        $totalReceitas += (float) $lancamento['valor'];
    } else {
        $totalDespesas += (float) $lancamento['valor'];
    }
}

$saldoFiltrado = $totalReceitas - $totalDespesas;

require_once __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">

    <div>
        <h1>Lançamentos</h1>
        <p>Gerencie suas receitas e despesas.</p>
    </div>

</div>

<div class="content-card">

    <h2><?= $lancamentoEditar ? 'Editar lançamento' : 'Novo lançamento' ?></h2>

    <form action="/acao.php" method="POST" class="form-grid">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(gerarToken()) ?>"
        >

        <input
            type="hidden"
            name="acao"
            value="<?= $lancamentoEditar ? 'editar_lancamento' : 'criar_lancamento' ?>"
        >

        <?php if ($lancamentoEditar): ?>
            <input
                type="hidden"
                name="id"
                value="<?= (int) $lancamentoEditar['id'] ?>"
            >
        <?php endif; ?>

        <div class="form-group">
            <label>Descrição</label>

            <input
                type="text"
                name="descricao"
                maxlength="150"
                value="<?= e($lancamentoEditar['descricao'] ?? '') ?>"
                placeholder="Ex.: Compra no mercado"
                required
            >
        </div>

        <div class="form-group">
            <label>Valor (R$)</label>

            <input
                type="number"
                name="valor"
                min="0.01"
                step="0.01"
                value="<?= e($lancamentoEditar['valor'] ?? '') ?>"
                placeholder="0,00"
                required
            >
        </div>

        <div class="form-group">
            <label>Tipo</label>

            <select name="tipo" required>

                <option value="">Selecione</option>

                <option
                    value="receita"
                    <?= ($lancamentoEditar['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>
                >
                    Receita
                </option>

                <option
                    value="despesa"
                    <?= ($lancamentoEditar['tipo'] ?? '') === 'despesa' ? 'selected' : '' ?>
                >
                    Despesa
                </option>

            </select>
        </div>

        <div class="form-group">
            <label>Categoria</label>

            <select name="categoria_id" required>

                <option value="">Selecione</option>

                <?php foreach ($categorias as $categoria): ?>

                    <?php if (
                        (int) $categoria['ativa'] === 1 ||
                        (int) ($lancamentoEditar['categoria_id'] ?? 0) === (int) $categoria['id']
                    ): ?>

                    <option
                        value="<?= (int) $categoria['id'] ?>"
                        <?= (int) ($lancamentoEditar['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>
                    >
                        <?= e($categoria['nome']) ?>
                        <?= (int) $categoria['ativa'] === 1 ? '' : '(histórica)' ?>
                        (<?= e($categoria['tipo']) ?>)
                    </option>

                    <?php endif; ?>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="form-group">
            <label>Data</label>

            <input
                type="date"
                name="data"
                value="<?= e($lancamentoEditar['data'] ?? date('Y-m-d')) ?>"
                required
            >
        </div>

        <div class="form-actions">

            <button type="submit" class="btn-primary">
                <?= $lancamentoEditar ? 'Salvar alterações' : 'Cadastrar lançamento' ?>
            </button>

            <?php if ($lancamentoEditar): ?>

                <a href="/pages/lancamentos.php" class="btn-secondary">
                    Cancelar
                </a>

            <?php endif; ?>

        </div>

    </form>

</div>

<div class="section-heading">
    <h2>Histórico de lançamentos</h2>
</div>





<div class="content-card">

    <h2>Filtros avançados</h2>

    <form method="GET" class="form-grid">

        <div class="form-group">
            <label>Pesquisar</label>

            <input
                type="text"
                name="busca"
                value="<?= e($busca) ?>"
                placeholder="Descrição, categoria ou usuário..."
            >
        </div>

        <div class="form-group">
            <label>Tipo</label>

            <select name="tipo">

                <option value="">Todos os tipos</option>

                <option
                    value="receita"
                    <?= $tipoFiltro === 'receita' ? 'selected' : '' ?>
                >
                    Receita
                </option>

                <option
                    value="despesa"
                    <?= $tipoFiltro === 'despesa' ? 'selected' : '' ?>
                >
                    Despesa
                </option>

            </select>
        </div>

        <div class="form-group">
            <label>Categoria</label>

            <select name="categoria">

                <option value="0">Todas as categorias</option>

                <?php foreach ($categorias as $categoria): ?>

                    <option
                        value="<?= (int) $categoria['id'] ?>"
                        <?= $categoriaFiltro === (int) $categoria['id'] ? 'selected' : '' ?>
                    >
                        <?= e($categoria['nome']) ?>
                        (<?= e($categoria['tipo']) ?>)
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="form-group">
            <label>Data inicial</label>

            <input
                type="date"
                name="data_inicial"
                value="<?= e($dataInicial) ?>"
            >
        </div>

        <div class="form-group">
            <label>Data final</label>

            <input
                type="date"
                name="data_final"
                value="<?= e($dataFinal) ?>"
            >
        </div>

        <div class="form-group">
            <label>Ordenar por</label>

            <select name="ordenacao">

                <option
                    value="data_desc"
                    <?= $ordenacao === 'data_desc' ? 'selected' : '' ?>
                >
                    Data: mais recentes
                </option>

                <option
                    value="data_asc"
                    <?= $ordenacao === 'data_asc' ? 'selected' : '' ?>
                >
                    Data: mais antigas
                </option>

                <option
                    value="valor_desc"
                    <?= $ordenacao === 'valor_desc' ? 'selected' : '' ?>
                >
                    Valor: maior para menor
                </option>

                <option
                    value="valor_asc"
                    <?= $ordenacao === 'valor_asc' ? 'selected' : '' ?>
                >
                    Valor: menor para maior
                </option>

            </select>
        </div>

        <div class="form-actions">

            <button type="submit" class="btn-primary">
                Aplicar filtros
            </button>

            <a href="/pages/lancamentos.php" class="btn-secondary">
                Limpar filtros
            </a>

        </div>

    </form>

</div>





<div class="dashboard-grid">

    <div class="content-card">
        <h3>Receitas encontradas</h3>
        <p><?= dinheiro($totalReceitas) ?></p>
    </div>

    <div class="content-card">
        <h3>Despesas encontradas</h3>
        <p><?= dinheiro($totalDespesas) ?></p>
    </div>

    <div class="content-card">
        <h3>Saldo dos resultados</h3>
        <p><?= dinheiro($saldoFiltrado) ?></p>
    </div>

</div>





<div class="content-card no-print">

    <div class="export-area">

        <div>
            <h3>Exportar lançamentos</h3>

            <p>
                Baixe os lançamentos encontrados em uma planilha
                compatível com Excel.
            </p>
        </div>

        <form action="/exportar.php" method="GET">

            <input
                type="hidden"
                name="busca"
                value="<?= e($busca) ?>"
            >

            <input
                type="hidden"
                name="tipo"
                value="<?= e($tipoFiltro) ?>"
            >

            <input
                type="hidden"
                name="categoria"
                value="<?= (int) $categoriaFiltro ?>"
            >

            <input
                type="hidden"
                name="data_inicial"
                value="<?= e($dataInicial) ?>"
            >

            <input
                type="hidden"
                name="data_final"
                value="<?= e($dataFinal) ?>"
            >

            <input
                type="hidden"
                name="ordenacao"
                value="<?= e($ordenacao) ?>"
            >

            <button type="submit" class="btn-primary">
                Exportar CSV
            </button>

        </form>

    </div>

</div>

<p>
    Exibindo <?= count($lancamentos) ?>
    lançamento(s) com os filtros selecionados.
</p>





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
                <th>Ações</th>

            </tr>
        </thead>

        <tbody>

            <?php if (count($lancamentos) > 0): ?>

                <?php foreach ($lancamentos as $lancamento): ?>

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
                            <span class="badge <?= e($lancamento['tipo']) ?>">
                                <?= ucfirst($lancamento['tipo']) ?>
                            </span>
                        </td>

                        <td><?= dinheiro($lancamento['valor']) ?></td>

                        <td class="actions">

                            <a
                                href="/pages/lancamentos.php?editar=<?= (int) $lancamento['id'] ?>"
                                class="action-edit"
                            >
                                Editar
                            </a>

                            <form
                                action="/acao.php"
                                method="POST"
                                onsubmit="return confirm('Deseja realmente excluir este lançamento?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="excluir_lancamento"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $lancamento['id'] ?>"
                                >

                                <button type="submit" class="action-delete">
                                    Excluir
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="<?= $ehAdmin ? 7 : 6 ?>" class="empty">
                        Nenhum lançamento encontrado.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<style>
.export-area {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.export-area h3 {
    margin: 0 0 7px;
}

.export-area p {
    margin: 0;
    color: var(--text-secondary, #6b7280);
    font-size: 14px;
}

.export-area form {
    margin: 0;
}

@media (max-width: 600px) {
    .export-area {
        align-items: stretch;
        flex-direction: column;
    }

    .export-area button {
        width: 100%;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
