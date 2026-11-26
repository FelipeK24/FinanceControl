<?php

require_once __DIR__ . '/../includes/auth.php';

exigirLogin();

$usuarioId = (int) $_SESSION['usuario_id'];





$sql = "
    SELECT
        m.*,
        COALESCE(
            SUM(
                CASE
                    WHEN c.tipo = 'deposito' THEN c.valor
                    WHEN c.tipo = 'retirada' THEN -c.valor
                    ELSE 0
                END
            ), 0
        ) AS total_contribuicoes
    FROM metas_financeiras m
    LEFT JOIN contribuicoes_metas c
        ON c.meta_id = m.id
    WHERE m.usuario_id = ?
    GROUP BY m.id
    ORDER BY
        CASE m.status
            WHEN 'ativa' THEN 1
            WHEN 'pausada' THEN 2
            WHEN 'concluida' THEN 3
        END,
        m.criado_em DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);

$metas = $stmt->fetchAll();





$sql = "
    SELECT
        c.*,
        m.nome AS nome_meta
    FROM contribuicoes_metas c
    INNER JOIN metas_financeiras m
        ON m.id = c.meta_id
    WHERE m.usuario_id = ?
    ORDER BY c.data DESC, c.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);

$contribuicoes = $stmt->fetchAll();





$totalMetas = count($metas);
$metasAtivas = 0;
$metasConcluidas = 0;
$totalAcumulado = 0;
$totalObjetivos = 0;

foreach ($metas as $meta) {

    $saldo = (float) $meta['valor_inicial']
        + (float) $meta['total_contribuicoes'];

    $totalAcumulado += $saldo;
    $totalObjetivos += (float) $meta['valor_objetivo'];

    if ($meta['status'] === 'ativa') {
        $metasAtivas++;
    }

    if ($meta['status'] === 'concluida') {
        $metasConcluidas++;
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<style>


.metas-resumo {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.meta-indicador {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
}

.meta-indicador span {
    display: block;
    color: var(--text-secondary, #777);
    font-size: 13px;
    margin-bottom: 10px;
}

.meta-indicador strong {
    font-size: 23px;
    color: var(--text);
}

.metas-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 350px);
    gap: 22px;
    align-items: start;
}

.meta-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 18px;
}

.meta-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
}

.meta-card-header h3 {
    margin: 0 0 6px;
    font-size: 17px;
}

.meta-descricao {
    color: var(--text-secondary, #777);
    font-size: 13px;
    margin: 0;
}

.meta-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.meta-status.ativa {
    background: #dcfce7;
    color: #166534;
}

.meta-status.pausada {
    background: #fef3c7;
    color: #92400e;
}

.meta-status.concluida {
    background: #dbeafe;
    color: #1e40af;
}

[data-tema="escuro"] .meta-status.ativa {
    background: rgba(53, 201, 138, .16);
    color: #72e0af;
}

[data-tema="escuro"] .meta-status.pausada {
    background: rgba(245, 190, 75, .16);
    color: #f5c76b;
}

[data-tema="escuro"] .meta-status.concluida {
    background: rgba(91, 131, 245, .18);
    color: #9db5ff;
}

.meta-valores {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin: 18px 0 10px;
}

.meta-valores strong {
    font-size: 19px;
}

.meta-valores span {
    color: var(--text-secondary, #777);
    font-size: 13px;
}

.meta-progresso {
    height: 10px;
    width: 100%;
    background: var(--border);
    border-radius: 20px;
    overflow: hidden;
}

.meta-progresso-barra {
    height: 100%;
    background: var(--green);
    border-radius: 20px;
    transition: width .3s ease;
}

.meta-percentual {
    display: block;
    text-align: right;
    font-size: 12px;
    color: var(--text-secondary, #777);
    margin-top: 7px;
}

.meta-prazo {
    font-size: 12px;
    color: var(--text-secondary, #777);
    margin-top: 13px;
}

.meta-acoes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 17px;
    padding-top: 15px;
    border-top: 1px solid var(--border);
}

.meta-acoes form {
    display: inline-flex;
}

.meta-btn {
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text);
    padding: 8px 11px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
}

.meta-btn:hover {
    opacity: .8;
}

.meta-btn-danger {
    color: var(--red);
    border-color: rgba(224, 82, 82, .35);
}

.meta-form-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.meta-form-card h3 {
    margin-top: 0;
    margin-bottom: 18px;
}

.meta-form-group {
    margin-bottom: 15px;
}

.meta-form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
}

.meta-form-group input,
.meta-form-group select,
.meta-form-group textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: var(--surface);
    color: var(--text);
    font-family: inherit;
}

.meta-form-group textarea {
    resize: vertical;
    min-height: 75px;
}

.meta-form-card button {
    width: 100%;
}

.meta-historico {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-top: 25px;
}

.meta-historico h3 {
    margin-top: 0;
}

.meta-tabela {
    width: 100%;
    border-collapse: collapse;
}

.meta-tabela th,
.meta-tabela td {
    padding: 12px 10px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
}

.meta-tabela th {
    color: var(--text-secondary, #777);
    font-weight: 600;
}

.meta-deposito {
    color: var(--green);
    font-weight: 600;
}

.meta-retirada {
    color: var(--red);
    font-weight: 600;
}

.meta-vazio {
    text-align: center;
    padding: 30px 15px;
    color: var(--text-secondary, #777);
}

@media (max-width: 900px) {
    .metas-layout {
        grid-template-columns: 1fr;
    }

    .metas-resumo {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 550px) {
    .metas-resumo {
        grid-template-columns: 1fr;
    }

    .meta-card-header {
        flex-direction: column;
    }

    .meta-tabela th,
    .meta-tabela td {
        padding: 9px 6px;
        font-size: 11px;
    }
}
</style>

<div class="page-heading">
    <div>
        <h1>Metas financeiras</h1>
        <p>Acompanhe seus objetivos e organize suas economias.</p>
    </div>
</div>



<div class="metas-resumo">

    <div class="meta-indicador">
        <span>Total de metas</span>
        <strong><?= $totalMetas ?></strong>
    </div>

    <div class="meta-indicador">
        <span>Metas ativas</span>
        <strong><?= $metasAtivas ?></strong>
    </div>

    <div class="meta-indicador">
        <span>Valor acumulado</span>
        <strong><?= dinheiro($totalAcumulado) ?></strong>
    </div>

</div>

<div class="metas-layout">

    

    <section>

        <h2>Minhas metas</h2>

        <?php if (empty($metas)): ?>

            <div class="meta-card meta-vazio">
                <h3>Nenhuma meta cadastrada</h3>
                <p>Crie sua primeira meta financeira para começar.</p>
            </div>

        <?php else: ?>

            <?php foreach ($metas as $meta): ?>

                <?php

                $saldoAtual = (float) $meta['valor_inicial']
                    + (float) $meta['total_contribuicoes'];

                $objetivo = (float) $meta['valor_objetivo'];

                $percentual = $objetivo > 0
                    ? ($saldoAtual / $objetivo) * 100
                    : 0;

                $percentualBarra = min(100, max(0, $percentual));

                ?>

                <article class="meta-card">

                    <div class="meta-card-header">

                        <div>
                            <h3><?= e($meta['nome']) ?></h3>

                            <?php if (!empty($meta['descricao'])): ?>
                                <p class="meta-descricao">
                                    <?= e($meta['descricao']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <span class="meta-status <?= e($meta['status']) ?>">
                            <?php

                            $nomesStatus = [
                                'ativa' => 'Ativa',
                                'pausada' => 'Pausada',
                                'concluida' => 'Concluída'
                            ];

                            echo $nomesStatus[$meta['status']] ?? 'Indefinida';

                            ?>
                        </span>

                    </div>

                    <div class="meta-valores">
                        <strong><?= dinheiro($saldoAtual) ?></strong>

                        <span>
                            de <?= dinheiro($objetivo) ?>
                        </span>
                    </div>

                    <div class="meta-progresso">
                        <div
                            class="meta-progresso-barra"
                            style="width: <?= $percentualBarra ?>%;"
                        ></div>
                    </div>

                    <span class="meta-percentual">
                        <?= number_format($percentual, 1, ',', '.') ?>% concluído
                    </span>

                    <p class="meta-prazo">
                        Início:
                        <?= date('d/m/Y', strtotime($meta['data_inicio'])) ?>

                        <?php if (!empty($meta['prazo'])): ?>
                            | Prazo:
                            <?= date('d/m/Y', strtotime($meta['prazo'])) ?>
                        <?php else: ?>
                            | Sem prazo definido
                        <?php endif; ?>
                    </p>

                    <div class="meta-acoes">

                        <?php if ($meta['status'] === 'ativa'): ?>

                            <form method="POST" action="/acao.php">

                                <input type="hidden" name="csrf_token"
                                    value="<?= e(gerarToken()) ?>">

                                <input type="hidden" name="acao"
                                    value="alterar_status_meta">

                                <input type="hidden" name="meta_id"
                                    value="<?= (int) $meta['id'] ?>">

                                <input type="hidden" name="status"
                                    value="pausada">

                                <button type="submit" class="meta-btn">
                                    Pausar
                                </button>

                            </form>

                            <?php if ($saldoAtual >= $objetivo): ?>

                            <form method="POST" action="/acao.php">

                                <input type="hidden" name="csrf_token"
                                    value="<?= e(gerarToken()) ?>">

                                <input type="hidden" name="acao"
                                    value="alterar_status_meta">

                                <input type="hidden" name="meta_id"
                                    value="<?= (int) $meta['id'] ?>">

                                <input type="hidden" name="status"
                                    value="concluida">

                                <button type="submit" class="meta-btn">
                                    Concluir
                                </button>

                            </form>

                            <?php endif; ?>

                        <?php elseif ($meta['status'] === 'pausada'): ?>

                            <form method="POST" action="/acao.php">

                                <input type="hidden" name="csrf_token"
                                    value="<?= e(gerarToken()) ?>">

                                <input type="hidden" name="acao"
                                    value="alterar_status_meta">

                                <input type="hidden" name="meta_id"
                                    value="<?= (int) $meta['id'] ?>">

                                <input type="hidden" name="status"
                                    value="ativa">

                                <button type="submit" class="meta-btn">
                                    Reativar
                                </button>

                            </form>

                            <?php if ($saldoAtual >= $objetivo): ?>

                            <form method="POST" action="/acao.php">

                                <input type="hidden" name="csrf_token"
                                    value="<?= e(gerarToken()) ?>">

                                <input type="hidden" name="acao"
                                    value="alterar_status_meta">

                                <input type="hidden" name="meta_id"
                                    value="<?= (int) $meta['id'] ?>">

                                <input type="hidden" name="status"
                                    value="concluida">

                                <button type="submit" class="meta-btn">
                                    Concluir
                                </button>

                            </form>

                            <?php endif; ?>

                        <?php endif; ?>

                        <form
                            method="POST"
                            action="/acao.php"
                            onsubmit="return confirm('Tem certeza que deseja excluir esta meta e todo o seu histórico?');"
                        >

                            <input type="hidden" name="csrf_token"
                                value="<?= e(gerarToken()) ?>">

                            <input type="hidden" name="acao"
                                value="excluir_meta">

                            <input type="hidden" name="meta_id"
                                value="<?= (int) $meta['id'] ?>">

                            <button type="submit"
                                class="meta-btn meta-btn-danger">
                                Excluir
                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

    

    <aside>

        <div class="meta-form-card">

            <h3>Nova meta</h3>

            <form method="POST" action="/acao.php">

                <input type="hidden" name="csrf_token"
                    value="<?= e(gerarToken()) ?>">

                <input type="hidden" name="acao"
                    value="criar_meta">

                <div class="meta-form-group">

                    <label for="nome">Nome da meta</label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="120"
                        placeholder="Ex: Comprar um computador"
                        required
                    >

                </div>

                <div class="meta-form-group">

                    <label for="descricao">Descrição</label>

                    <textarea
                        id="descricao"
                        name="descricao"
                        maxlength="255"
                        placeholder="Descreva seu objetivo"
                    ></textarea>

                </div>

                <div class="meta-form-group">

                    <label for="valor_objetivo">Valor desejado (R$)</label>

                    <input
                        type="number"
                        id="valor_objetivo"
                        name="valor_objetivo"
                        min="0.01"
                        step="0.01"
                        placeholder="5000.00"
                        required
                    >

                </div>

                <div class="meta-form-group">

                    <label for="valor_inicial">Valor inicial (R$)</label>

                    <input
                        type="number"
                        id="valor_inicial"
                        name="valor_inicial"
                        min="0"
                        step="0.01"
                        value="0.00"
                        required
                    >

                </div>

                <div class="meta-form-group">

                    <label for="data_inicio">Data de início</label>

                    <input
                        type="date"
                        id="data_inicio"
                        name="data_inicio"
                        value="<?= date('Y-m-d') ?>"
                        required
                    >

                </div>

                <div class="meta-form-group">

                    <label for="prazo">Prazo (opcional)</label>

                    <input
                        type="date"
                        id="prazo"
                        name="prazo"
                    >

                </div>

                <button type="submit" class="btn-primary">
                    Criar meta
                </button>

            </form>

        </div>

        

        <div class="meta-form-card">

            <h3>Registrar contribuição</h3>

            <?php

            $metasAtivasParaContribuir = array_filter(
                $metas,
                function ($meta) {
                    return $meta['status'] === 'ativa';
                }
            );

            ?>

            <?php if (!empty($metasAtivasParaContribuir)): ?>

                <form method="POST" action="/acao.php">

                    <input type="hidden" name="csrf_token"
                        value="<?= e(gerarToken()) ?>">

                    <input type="hidden" name="acao"
                        value="contribuir_meta">

                    <div class="meta-form-group">

                        <label for="meta_id">Meta</label>

                        <select id="meta_id" name="meta_id" required>

                            <option value="">Selecione uma meta</option>

                            <?php foreach ($metasAtivasParaContribuir as $meta): ?>

                                <option value="<?= (int) $meta['id'] ?>">
                                    <?= e($meta['nome']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="meta-form-group">

                        <label for="tipo">Movimentação</label>

                        <select id="tipo" name="tipo" required>

                            <option value="deposito">Depósito</option>
                            <option value="retirada">Retirada</option>

                        </select>

                    </div>

                    <div class="meta-form-group">

                        <label for="valor">Valor (R$)</label>

                        <input
                            type="number"
                            id="valor"
                            name="valor"
                            min="0.01"
                            step="0.01"
                            placeholder="100.00"
                            required
                        >

                    </div>

                    <div class="meta-form-group">

                        <label for="descricao_contribuicao">
                            Descrição
                        </label>

                        <input
                            type="text"
                            id="descricao_contribuicao"
                            name="descricao"
                            maxlength="150"
                            placeholder="Ex: Economia do mês"
                        >

                    </div>

                    <div class="meta-form-group">

                        <label for="data_contribuicao">Data</label>

                        <input
                            type="date"
                            id="data_contribuicao"
                            name="data"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>

                    <button type="submit" class="btn-primary">
                        Registrar movimentação
                    </button>

                </form>

            <?php else: ?>

                <div class="meta-vazio">
                    <p>Você precisa ter uma meta ativa para registrar contribuições.</p>
                </div>

            <?php endif; ?>

        </div>

    </aside>

</div>



<div class="meta-historico">

    <h3>Histórico de contribuições</h3>

    <?php if (empty($contribuicoes)): ?>

        <div class="meta-vazio">
            <p>Nenhuma contribuição registrada até o momento.</p>
        </div>

    <?php else: ?>

        <div class="table-container">

            <table class="meta-tabela">

                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Meta</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Descrição</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($contribuicoes as $contribuicao): ?>

                        <tr>

                            <td>
                                <?= date(
                                    'd/m/Y',
                                    strtotime($contribuicao['data'])
                                ) ?>
                            </td>

                            <td>
                                <?= e($contribuicao['nome_meta']) ?>
                            </td>

                            <td>
                                <?= $contribuicao['tipo'] === 'deposito'
                                    ? 'Depósito'
                                    : 'Retirada' ?>
                            </td>

                            <td class="<?= $contribuicao['tipo'] === 'deposito'
                                ? 'meta-deposito'
                                : 'meta-retirada' ?>">

                                <?= $contribuicao['tipo'] === 'deposito'
                                    ? '+ '
                                    : '- ' ?>

                                <?= dinheiro($contribuicao['valor']) ?>

                            </td>

                            <td>
                                <?= e($contribuicao['descricao'] ?? '-') ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>
