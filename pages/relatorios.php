<?php

require_once __DIR__ . '/../includes/auth.php';

exigirEmpresa();

$usuarioId = (int) $_SESSION['usuario_id'];
$empresaId = (int) $_SESSION['empresa_id'];
$ehAdmin = ehAdminEmpresa();





$dataInicial = $_GET['data_inicial'] ?? date('Y-01-01');
$dataFinal = $_GET['data_final'] ?? date('Y-m-d');

function dataRelatorioValida($data)
{
    if ($data === '') {
        return false;
    }

    $dataObjeto = DateTime::createFromFormat('!Y-m-d', $data);

    return $dataObjeto && $dataObjeto->format('Y-m-d') === $data;
}

if (!dataRelatorioValida($dataInicial)) {
    $dataInicial = date('Y-01-01');
}

if (!dataRelatorioValida($dataFinal)) {
    $dataFinal = date('Y-m-d');
}

if ($dataInicial > $dataFinal) {
    $dataInicial = date('Y-01-01');
    $dataFinal = date('Y-m-d');
}





$where = " WHERE l.data >= ? AND l.data <= ?";

$parametros = [
    $dataInicial,
    $dataFinal
];

if ($ehAdmin) {
    $where .= " AND l.usuario_id IN (
        SELECT id FROM usuarios WHERE empresa_id = ?
    )";
    $parametros[] = $empresaId;
} else {
    $where .= " AND l.usuario_id = ?";
    $parametros[] = $usuarioId;
}





$sqlResumo = "
    SELECT
        COUNT(*) AS quantidade,

        COALESCE(
            SUM(CASE
                WHEN l.tipo = 'receita' THEN l.valor
                ELSE 0
            END), 0
        ) AS receitas,

        COALESCE(
            SUM(CASE
                WHEN l.tipo = 'despesa' THEN l.valor
                ELSE 0
            END), 0
        ) AS despesas

    FROM lancamentos l

    $where
";

$stmt = $pdo->prepare($sqlResumo);
$stmt->execute($parametros);

$resumo = $stmt->fetch();

$totalReceitas = (float) $resumo['receitas'];
$totalDespesas = (float) $resumo['despesas'];
$quantidadeLancamentos = (int) $resumo['quantidade'];

$saldo = $totalReceitas - $totalDespesas;

$percentualDespesas = $totalReceitas > 0
    ? ($totalDespesas / $totalReceitas) * 100
    : 0;





$sqlMensal = "
    SELECT
        DATE_FORMAT(l.data, '%Y-%m') AS mes,

        COALESCE(
            SUM(CASE
                WHEN l.tipo = 'receita' THEN l.valor
                ELSE 0
            END), 0
        ) AS receitas,

        COALESCE(
            SUM(CASE
                WHEN l.tipo = 'despesa' THEN l.valor
                ELSE 0
            END), 0
        ) AS despesas

    FROM lancamentos l

    $where

    GROUP BY DATE_FORMAT(l.data, '%Y-%m')

    ORDER BY mes ASC
";

$stmt = $pdo->prepare($sqlMensal);
$stmt->execute($parametros);

$dadosMensais = $stmt->fetchAll();

$maiorValorMensal = 0;

foreach ($dadosMensais as $mes) {
    $maiorValorMensal = max(
        $maiorValorMensal,
        (float) $mes['receitas'],
        (float) $mes['despesas']
    );
}





$sqlCategorias = "
    SELECT
        c.id,
        c.nome AS categoria,
        COALESCE(SUM(l.valor), 0) AS total,
        COUNT(l.id) AS quantidade

    FROM lancamentos l

    INNER JOIN categorias c
        ON c.id = l.categoria_id

    $where

    AND l.tipo = 'despesa'

    GROUP BY c.id, c.nome

    ORDER BY total DESC
";

$stmt = $pdo->prepare($sqlCategorias);
$stmt->execute($parametros);

$despesasCategorias = $stmt->fetchAll();

$maiorCategoria = 0;

foreach ($despesasCategorias as $categoria) {
    $maiorCategoria = max(
        $maiorCategoria,
        (float) $categoria['total']
    );
}





$meses = [
    1 => 'janeiro',
    2 => 'fevereiro',
    3 => 'março',
    4 => 'abril',
    5 => 'maio',
    6 => 'junho',
    7 => 'julho',
    8 => 'agosto',
    9 => 'setembro',
    10 => 'outubro',
    11 => 'novembro',
    12 => 'dezembro'
];

$dataGeracao = date('d/m/Y H:i');





require_once __DIR__ . '/../includes/header.php';

?>

<style>



.reports-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin: 22px 0;
}

.report-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    min-width: 0;
}

.report-card h3 {
    font-size: 14px;
    font-weight: 500;
    margin: 0 0 12px;
    color: var(--text-secondary);
}

.report-card strong {
    display: block;
    font-size: 23px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.report-card small {
    display: block;
    margin-top: 8px;
    color: var(--text-secondary);
}

.report-positive {
    color: var(--green);
}

.report-negative {
    color: var(--red);
}

.report-neutral {
    color: var(--text);
}

.report-section {
    margin-top: 30px;
}

.report-section h2 {
    margin-bottom: 6px;
    color: var(--text);
}

.report-description {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 20px;
}



.print-header {
    display: none;
}



.monthly-list {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.monthly-item {
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border);
}

.monthly-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.monthly-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.monthly-header strong {
    font-size: 15px;
    color: var(--text);
}

.monthly-values {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 13px;
}

.monthly-values span {
    color: var(--text-secondary);
}

.monthly-values .income-text {
    color: var(--green);
}

.monthly-values .expense-text {
    color: var(--red);
}

.bar-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.bar-track {
    width: 100%;
    height: 12px;
    background: var(--surface-secondary);
    border-radius: 20px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    min-width: 0;
    border-radius: 20px;
}

.bar-income {
    background: var(--green);
}

.bar-expense {
    background: var(--red);
}

.bar-label {
    font-size: 11px;
    color: var(--text-secondary);
}



.category-report-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.category-report-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.category-report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.category-report-header strong {
    font-size: 14px;
    color: var(--text);
}

.category-report-header span {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}

.category-report-footer {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--text-secondary);
}



.report-filter {
    margin-bottom: 24px;
}

.report-filter h2 {
    color: var(--text);
}

.report-filter .form-grid {
    align-items: end;
}

.report-filter .form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.report-note {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 12px;
}

.report-empty {
    padding: 25px;
    text-align: center;
    color: var(--text-secondary);
    background: var(--surface-secondary);
    border-radius: 8px;
}

.report-indicator {
    margin-top: 22px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--surface);
}

.report-indicator > strong {
    color: var(--text);
}

.report-indicator p {
    margin: 7px 0 0;
    color: var(--text-secondary);
    font-size: 14px;
}



@media (max-width: 1000px) {
    .reports-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .reports-summary {
        grid-template-columns: 1fr;
    }

    .monthly-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .monthly-values {
        flex-direction: column;
        gap: 5px;
    }
}



@page {
    size: A4 portrait;
    margin: 14mm 13mm 15mm 13mm;
}

@media print {

    html,
    body {
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #1f2937 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 10pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .sidebar,
    .topbar,
    .report-filter,
    .no-print,
    button,
    nav,
    footer {
        display: none !important;
    }

    .layout {
        display: block !important;
        width: 100% !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .main-content {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-content {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    

    .print-header {
        display: block !important;
        width: 100%;
        margin-bottom: 24px;
        padding-bottom: 15px;
        border-bottom: 2px solid #1f2937;
    }

    .print-brand {
        font-size: 23pt;
        font-weight: 800;
        letter-spacing: -0.8px;
        color: #111827 !important;
        margin: 0 0 5px;
    }

    .print-subtitle {
        font-size: 10pt;
        color: #64748b !important;
        margin: 0;
    }

    .print-information {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 15px;
        margin-top: 17px;
        padding-top: 12px;
        border-top: 1px solid #d1d5db;
    }

    .print-information div {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .print-information span {
        font-size: 8pt;
        color: #64748b !important;
    }

    .print-information strong {
        font-size: 10pt;
        color: #111827 !important;
    }

    

    .page-heading {
        display: block !important;
        margin: 0 0 18px !important;
        padding: 0 !important;
    }

    .page-heading h1 {
        font-size: 17pt !important;
        color: #111827 !important;
        margin: 0 0 5px !important;
    }

    .page-heading p {
        font-size: 9pt !important;
        color: #64748b !important;
        margin: 0 !important;
    }

    

    .reports-summary {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
        margin: 15px 0 22px !important;
    }

    .report-card {
        padding: 13px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        background: #ffffff !important;
        box-shadow: none !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    .report-card h3 {
        color: #64748b !important;
        font-size: 9pt !important;
        margin-bottom: 7px !important;
    }

    .report-card strong {
        font-size: 17pt !important;
        color: #111827 !important;
    }

    .report-card small {
        font-size: 8pt !important;
        color: #64748b !important;
    }

    .report-positive {
        color: #15803d !important;
    }

    .report-negative {
        color: #b91c1c !important;
    }

    .report-neutral {
        color: #111827 !important;
    }

    

    .report-section {
        margin-top: 22px !important;
        margin-bottom: 9px !important;
    }

    .report-section h2 {
        font-size: 13pt !important;
        color: #111827 !important;
        margin: 0 !important;
    }

    .content-card {
        padding: 15px !important;
        margin: 0 0 16px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        background: #ffffff !important;
        box-shadow: none !important;
        break-inside: auto !important;
    }

    .report-description {
        color: #64748b !important;
        font-size: 9pt !important;
        margin-bottom: 14px !important;
    }

    

    .monthly-list {
        gap: 13px !important;
    }

    .monthly-item {
        padding-bottom: 12px !important;
        border-bottom: 1px solid #d1d5db !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    .monthly-item:last-child {
        border-bottom: none !important;
    }

    .monthly-header {
        margin-bottom: 8px !important;
    }

    .monthly-header strong {
        color: #111827 !important;
        font-size: 10pt !important;
    }

    .monthly-values {
        font-size: 8pt !important;
        gap: 12px !important;
    }

    .monthly-values span {
        color: #475569 !important;
    }

    .monthly-values .income-text {
        color: #15803d !important;
    }

    .monthly-values .expense-text {
        color: #b91c1c !important;
    }

    .bar-group {
        gap: 5px !important;
    }

    .bar-track {
        height: 9px !important;
        background: #e5e7eb !important;
        border-radius: 2px !important;
        overflow: hidden !important;
    }

    .bar-fill {
        border-radius: 2px !important;
    }

    .bar-income {
        background: #16a34a !important;
    }

    .bar-expense {
        background: #dc2626 !important;
    }

    .bar-label {
        color: #64748b !important;
        font-size: 7pt !important;
    }

    

    .category-report-list {
        gap: 13px !important;
    }

    .category-report-item {
        gap: 5px !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    .category-report-header strong,
    .category-report-header span {
        color: #111827 !important;
        font-size: 9pt !important;
    }

    .category-report-footer {
        color: #64748b !important;
        font-size: 8pt !important;
    }

    

    .report-indicator {
        margin-top: 17px !important;
        padding: 14px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        background: #f8fafc !important;
        box-shadow: none !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    .report-indicator strong {
        color: #111827 !important;
    }

    .report-indicator p {
        color: #475569 !important;
        font-size: 9pt !important;
    }

    

    .report-empty {
        padding: 15px !important;
        border: 1px dashed #cbd5e1 !important;
        background: #f8fafc !important;
        color: #64748b !important;
        font-size: 9pt !important;
    }

    .report-note {
        color: #64748b !important;
        font-size: 8pt !important;
    }

    

    h1,
    h2,
    h3 {
        break-after: avoid !important;
        page-break-after: avoid !important;
    }

    .report-card,
    .report-indicator,
    .monthly-item,
    .category-report-item {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    a {
        color: inherit !important;
        text-decoration: none !important;
    }

}

</style>





<div class="print-header">

    <h1 class="print-brand">
        FinanceControl
    </h1>

    <p class="print-subtitle">
        Relatório de desempenho financeiro
    </p>

    <div class="print-information">

        <div>
            <span>Período analisado</span>

            <strong>
                <?= date('d/m/Y', strtotime($dataInicial)) ?>
                até
                <?= date('d/m/Y', strtotime($dataFinal)) ?>
            </strong>
        </div>

        <div>
            <span>Data de geração</span>

            <strong>
                <?= e($dataGeracao) ?>
            </strong>
        </div>

    </div>

</div>





<div class="page-heading">

    <div>
        <h1>Relatórios financeiros</h1>

        <p>
            Analise suas receitas, despesas e evolução financeira.
        </p>
    </div>

    <button
        type="button"
        class="btn-secondary no-print"
        onclick="window.print()"
    >
        Imprimir / Salvar PDF
    </button>

</div>





<div class="content-card report-filter">

    <h2>Período do relatório</h2>

    <p class="report-description">
        Selecione o intervalo que deseja analisar.
    </p>

    <form method="GET" class="form-grid">

        <div class="form-group">

            <label>Data inicial</label>

            <input
                type="date"
                name="data_inicial"
                value="<?= e($dataInicial) ?>"
                required
            >

        </div>

        <div class="form-group">

            <label>Data final</label>

            <input
                type="date"
                name="data_final"
                value="<?= e($dataFinal) ?>"
                required
            >

        </div>

        <div class="form-actions">

            <button type="submit" class="btn-primary">
                Gerar relatório
            </button>

            <a
                href="/pages/relatorios.php"
                class="btn-secondary"
            >
                Período atual
            </a>

        </div>

    </form>

    <p class="report-note">
        Período analisado:
        <?= date('d/m/Y', strtotime($dataInicial)) ?>
        até
        <?= date('d/m/Y', strtotime($dataFinal)) ?>.
    </p>

</div>





<div class="reports-summary">

    <div class="report-card">

        <h3>Total de receitas</h3>

        <strong class="report-positive">
            <?= dinheiro($totalReceitas) ?>
        </strong>

        <small>Valores recebidos no período</small>

    </div>

    <div class="report-card">

        <h3>Total de despesas</h3>

        <strong class="report-negative">
            <?= dinheiro($totalDespesas) ?>
        </strong>

        <small>Valores gastos no período</small>

    </div>

    <div class="report-card">

        <h3>Saldo do período</h3>

        <strong class="<?= $saldo >= 0 ? 'report-positive' : 'report-negative' ?>">
            <?= dinheiro($saldo) ?>
        </strong>

        <small>Receitas menos despesas</small>

    </div>

    <div class="report-card">

        <h3>Lançamentos</h3>

        <strong class="report-neutral">
            <?= number_format($quantidadeLancamentos, 0, ',', '.') ?>
        </strong>

        <small>Registros encontrados</small>

    </div>

</div>





<div class="section-heading report-section">

    <h2>Comparativo mensal</h2>

</div>

<div class="content-card">

    <p class="report-description">
        Comparação entre as receitas e despesas de cada mês.
    </p>

    <?php if (count($dadosMensais) > 0): ?>

        <div class="monthly-list">

            <?php foreach ($dadosMensais as $mes): ?>

                <?php

                $valorReceita = (float) $mes['receitas'];
                $valorDespesa = (float) $mes['despesas'];

                $larguraReceita = $maiorValorMensal > 0
                    ? ($valorReceita / $maiorValorMensal) * 100
                    : 0;

                $larguraDespesa = $maiorValorMensal > 0
                    ? ($valorDespesa / $maiorValorMensal) * 100
                    : 0;

                $dataMes = DateTime::createFromFormat(
                    '!Y-m',
                    $mes['mes']
                );

                $nomeMes = $dataMes
                    ? $meses[(int) $dataMes->format('n')]
                    : $mes['mes'];

                ?>

                <div class="monthly-item">

                    <div class="monthly-header">

                        <strong>
                            <?= e($nomeMes) ?>
                            <?= e(substr($mes['mes'], 0, 4)) ?>
                        </strong>

                        <div class="monthly-values">

                            <span class="income-text">
                                Receitas:
                                <?= dinheiro($valorReceita) ?>
                            </span>

                            <span class="expense-text">
                                Despesas:
                                <?= dinheiro($valorDespesa) ?>
                            </span>

                        </div>

                    </div>

                    <div class="bar-group">

                        <div>

                            <div class="bar-label">Receitas</div>

                            <div class="bar-track">

                                <div
                                    class="bar-fill bar-income"
                                    style="width: <?= $larguraReceita ?>%;"
                                ></div>

                            </div>

                        </div>

                        <div>

                            <div class="bar-label">Despesas</div>

                            <div class="bar-track">

                                <div
                                    class="bar-fill bar-expense"
                                    style="width: <?= $larguraDespesa ?>%;"
                                ></div>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <p class="report-note">
            <span class="report-positive">Verde:</span> receitas.
            <span class="report-negative">Vermelho:</span> despesas.
        </p>

    <?php else: ?>

        <div class="report-empty">
            Não existem lançamentos no período selecionado.
        </div>

    <?php endif; ?>

</div>





<div class="section-heading report-section">

    <h2>Despesas por categoria</h2>

</div>

<div class="content-card">

    <p class="report-description">
        Identifique quais categorias concentram seus gastos.
    </p>

    <?php if (count($despesasCategorias) > 0): ?>

        <div class="category-report-list">

            <?php foreach ($despesasCategorias as $categoria): ?>

                <?php

                $valorCategoria = (float) $categoria['total'];

                $larguraCategoria = $maiorCategoria > 0
                    ? ($valorCategoria / $maiorCategoria) * 100
                    : 0;

                $percentualCategoria = $totalDespesas > 0
                    ? ($valorCategoria / $totalDespesas) * 100
                    : 0;

                ?>

                <div class="category-report-item">

                    <div class="category-report-header">

                        <strong>
                            <?= e($categoria['categoria']) ?>
                        </strong>

                        <span>
                            <?= dinheiro($valorCategoria) ?>
                        </span>

                    </div>

                    <div class="bar-track">

                        <div
                            class="bar-fill bar-expense"
                            style="width: <?= $larguraCategoria ?>%;"
                        ></div>

                    </div>

                    <div class="category-report-footer">

                        <span>
                            <?= number_format(
                                $categoria['quantidade'],
                                0,
                                ',',
                                '.'
                            ) ?>
                            lançamento(s)
                        </span>

                        <span>
                            <?= number_format(
                                $percentualCategoria,
                                1,
                                ',',
                                '.'
                            ) ?>%
                            das despesas
                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="report-empty">
            Não existem despesas no período selecionado.
        </div>

    <?php endif; ?>

</div>





<div class="report-indicator">

    <strong>Comprometimento das receitas</strong>

    <p>

        <?php if ($totalReceitas > 0): ?>

            Você utilizou
            <strong>
                <?= number_format(
                    $percentualDespesas,
                    1,
                    ',',
                    '.'
                ) ?>%
            </strong>
            das receitas em despesas no período.

        <?php else: ?>

            Não existem receitas registradas no período para calcular
            esse indicador.

        <?php endif; ?>

    </p>

    <?php if ($totalReceitas > 0): ?>

        <div class="bar-track" style="margin-top: 14px;">

            <div
                class="bar-fill <?= $percentualDespesas > 100 ? 'bar-expense' : 'bar-income' ?>"
                style="width: <?= min($percentualDespesas, 100) ?>%;"
            ></div>

        </div>

    <?php endif; ?>

    <?php if ($percentualDespesas > 100): ?>

        <p class="report-negative">
            Atenção: as despesas ultrapassaram o total de receitas.
        </p>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
