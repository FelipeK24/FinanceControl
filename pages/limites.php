<?php

require_once __DIR__ . '/../includes/auth.php';

exigirEmpresa();

$usuarioId = (int) $_SESSION['usuario_id'];
$empresaId = (int) $_SESSION['empresa_id'];





$mesAtual = (int) date('m');
$anoAtual = (int) date('Y');

$mesSelecionado = filter_var(
    $_GET['mes'] ?? $mesAtual,
    FILTER_VALIDATE_INT
);

$anoSelecionado = filter_var(
    $_GET['ano'] ?? $anoAtual,
    FILTER_VALIDATE_INT
);

if ($mesSelecionado === false || $mesSelecionado < 1 || $mesSelecionado > 12) {
    $mesSelecionado = $mesAtual;
}

if ($anoSelecionado === false || $anoSelecionado < 2000 || $anoSelecionado > 2100) {
    $anoSelecionado = $anoAtual;
}

$inicioMes = sprintf(
    '%04d-%02d-01',
    $anoSelecionado,
    $mesSelecionado
);

$dataInicio = new DateTime($inicioMes);
$dataFim = (clone $dataInicio)->modify('+1 month');

$inicioPeriodo = $dataInicio->format('Y-m-d');
$fimPeriodo = $dataFim->format('Y-m-d');

$meses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];

$nomeMes = $meses[$mesSelecionado];





$sql = "
    SELECT
        lg.id,
        lg.categoria_id,
        lg.ano,
        lg.mes,
        lg.valor_limite,
        c.nome AS categoria_nome,
        COALESCE(g.total_gasto, 0) AS total_gasto

    FROM limites_gastos lg

    INNER JOIN categorias c
        ON c.id = lg.categoria_id

    LEFT JOIN (
        SELECT
            categoria_id,
            SUM(valor) AS total_gasto

        FROM lancamentos

        WHERE usuario_id = ?
          AND tipo = 'despesa'
          AND data >= ?
          AND data < ?

        GROUP BY categoria_id
    ) g
        ON g.categoria_id = lg.categoria_id

    WHERE lg.usuario_id = ?
      AND lg.ano = ?
      AND lg.mes = ?

    ORDER BY c.nome ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $usuarioId,
    $inicioPeriodo,
    $fimPeriodo,
    $usuarioId,
    $anoSelecionado,
    $mesSelecionado
]);

$limites = $stmt->fetchAll();





$stmt = $pdo->prepare(
    "SELECT id, nome
     FROM categorias
         WHERE tipo = 'despesa'
             AND ativa = 1
             AND empresa_id = ?
     ORDER BY nome ASC"
);

$stmt->execute([$empresaId]);

$categorias = $stmt->fetchAll();





$categoriasUtilizadas = [];

foreach ($limites as $limite) {
    $categoriasUtilizadas[] = (int) $limite['categoria_id'];
}

$categoriasDisponiveis = array_filter(
    $categorias,
    function ($categoria) use ($categoriasUtilizadas) {
        return !in_array(
            (int) $categoria['id'],
            $categoriasUtilizadas,
            true
        );
    }
);





$totalLimites = count($limites);
$totalPlanejado = 0;
$totalGasto = 0;
$limitesExcedidos = 0;
$limitesAtencao = 0;

foreach ($limites as $limite) {

    $valorLimite = (float) $limite['valor_limite'];
    $gasto = (float) $limite['total_gasto'];

    $totalPlanejado += $valorLimite;
    $totalGasto += $gasto;

    $percentual = $valorLimite > 0
        ? ($gasto / $valorLimite) * 100
        : 0;

    if ($percentual >= 100) {
        $limitesExcedidos++;
    } elseif ($percentual >= 80) {
        $limitesAtencao++;
    }
}

$saldoDisponivel = $totalPlanejado - $totalGasto;

require_once __DIR__ . '/../includes/header.php';

?>

<style>



.limites-resumo {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.limite-indicador {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.limite-indicador span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 10px;
}

.limite-indicador strong {
    font-size: 22px;
    color: var(--text);
}

.limites-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 350px);
    gap: 22px;
    align-items: start;
}

.limite-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 18px;
    box-shadow: var(--shadow);
}

.limite-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
}

.limite-card-header h3 {
    margin: 0 0 6px;
    font-size: 17px;
    color: var(--text);
}

.limite-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.limite-status.normal {
    background: rgba(22, 163, 106, 0.14);
    color: var(--green);
}

.limite-status.atencao {
    background: rgba(217, 119, 6, 0.14);
    color: #d97706;
}

.limite-status.excedido {
    background: rgba(224, 82, 82, 0.14);
    color: var(--red);
}

.limite-valores {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin: 18px 0 10px;
}

.limite-valores strong {
    font-size: 19px;
    color: var(--text);
}

.limite-valores span {
    color: var(--text-secondary);
    font-size: 13px;
}

.limite-progresso {
    height: 10px;
    width: 100%;
    background: var(--surface-secondary);
    border-radius: 20px;
    overflow: hidden;
}

.limite-progresso-barra {
    height: 100%;
    border-radius: 20px;
    transition: width .3s ease;
}

.limite-progresso-barra.normal {
    background: var(--green);
}

.limite-progresso-barra.atencao {
    background: #d97706;
}

.limite-progresso-barra.excedido {
    background: var(--red);
}

.limite-percentual {
    display: block;
    text-align: right;
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 7px;
}

.limite-restante {
    margin-top: 12px;
    font-size: 13px;
    color: var(--text-secondary);
}

.limite-acoes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 17px;
    padding-top: 15px;
    border-top: 1px solid var(--border);
}

.limite-acoes form {
    display: inline-flex;
}

.limite-btn {
    border: 1px solid var(--border);
    background: var(--surface-secondary);
    color: var(--text);
    padding: 8px 11px;
    border-radius: 7px;
    cursor: pointer;
    font-size: 12px;
}

.limite-btn:hover {
    filter: brightness(0.95);
}

.limite-btn-danger {
    color: var(--red);
    border-color: var(--border);
}

.limite-form-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
}

.limite-form-card h3 {
    margin-top: 0;
    margin-bottom: 18px;
    color: var(--text);
}

.limite-form-group {
    margin-bottom: 15px;
}

.limite-form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
    color: var(--text);
}

.limite-form-group input,
.limite-form-group select {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: var(--surface);
    color: var(--text);
    font-family: inherit;
    outline: none;
}

.limite-form-group input:focus,
.limite-form-group select:focus {
    border-color: var(--primary);
}

.limite-form-group select option {
    background: var(--surface);
    color: var(--text);
}

.limite-form-card button {
    width: 100%;
}

.limite-vazio {
    text-align: center;
    padding: 30px 15px;
    color: var(--text-secondary);
}

.limite-vazio h3 {
    color: var(--text);
    margin-bottom: 10px;
}

.limite-filtro {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 12px;
    margin-bottom: 20px;
}

.limite-filtro .limite-form-group {
    margin-bottom: 0;
    min-width: 130px;
}

.limite-filtro button {
    padding: 10px 16px;
    border: none;
    border-radius: 7px;
    background: var(--primary);
    color: #ffffff;
    cursor: pointer;
    font-weight: 600;
}

.limite-filtro button:hover {
    background: var(--primary-hover);
}

.limite-alerta {
    font-size: 13px;
    margin-top: 12px;
    font-weight: 600;
}

.limite-alerta.atencao {
    color: #d97706;
}

.limite-alerta.excedido {
    color: var(--red);
}

.limites-layout > section > h2 {
    color: var(--text);
    margin-bottom: 18px;
    font-size: 18px;
}

#formulario-edicao[hidden] {
    display: none;
}

@media (max-width: 1100px) {
    .limites-resumo {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .limites-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 550px) {
    .limites-resumo {
        grid-template-columns: 1fr;
    }

    .limite-card-header {
        flex-direction: column;
    }

    .limite-valores {
        flex-direction: column;
        align-items: flex-start;
    }
}

</style>


<div class="page-heading">

    <div>
        <h1>Limites de gastos</h1>
        <p>Defina limites mensais e acompanhe suas despesas por categoria.</p>
    </div>

</div>




<form method="GET" class="limite-filtro">

    <div class="limite-form-group">

        <label for="mes">Mês</label>

        <select id="mes" name="mes">

            <?php foreach ($meses as $numero => $nome): ?>

                <option
                    value="<?= $numero ?>"
                    <?= $mesSelecionado === $numero ? 'selected' : '' ?>
                >
                    <?= e($nome) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </div>

    <div class="limite-form-group">

        <label for="ano">Ano</label>

        <input
            type="number"
            id="ano"
            name="ano"
            min="2000"
            max="2100"
            value="<?= $anoSelecionado ?>"
            required
        >

    </div>

    <button type="submit">
        Consultar período
    </button>

</form>




<div class="limites-resumo">

    <div class="limite-indicador">
        <span>Limites cadastrados</span>
        <strong><?= $totalLimites ?></strong>
    </div>

    <div class="limite-indicador">
        <span>Total planejado</span>
        <strong><?= dinheiro($totalPlanejado) ?></strong>
    </div>

    <div class="limite-indicador">
        <span>Total gasto</span>
        <strong><?= dinheiro($totalGasto) ?></strong>
    </div>

    <div class="limite-indicador">
        <span>Saldo dos limites</span>
        <strong><?= dinheiro($saldoDisponivel) ?></strong>
    </div>

</div>


<div class="limites-layout">


    

    <section>

        <h2>
            Limites de <?= e($nomeMes) ?> de <?= $anoSelecionado ?>
        </h2>

        <?php if (empty($limites)): ?>

            <div class="limite-card limite-vazio">

                <h3>Nenhum limite cadastrado</h3>

                <p>
                    Cadastre um limite para acompanhar suas despesas neste mês.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($limites as $limite): ?>

                <?php

                $valorLimite = (float) $limite['valor_limite'];
                $gasto = (float) $limite['total_gasto'];

                $percentual = $valorLimite > 0
                    ? ($gasto / $valorLimite) * 100
                    : 0;

                $percentualBarra = min(100, max(0, $percentual));

                if ($percentual >= 100) {
                    $statusClasse = 'excedido';
                    $statusTexto = 'Limite excedido';
                } elseif ($percentual >= 80) {
                    $statusClasse = 'atencao';
                    $statusTexto = 'Atenção';
                } else {
                    $statusClasse = 'normal';
                    $statusTexto = 'Dentro do limite';
                }

                $restante = $valorLimite - $gasto;

                ?>

                <article class="limite-card">

                    <div class="limite-card-header">

                        <div>
                            <h3><?= e($limite['categoria_nome']) ?></h3>
                        </div>

                        <span class="limite-status <?= $statusClasse ?>">
                            <?= $statusTexto ?>
                        </span>

                    </div>

                    <div class="limite-valores">

                        <strong>
                            <?= dinheiro($gasto) ?>
                        </strong>

                        <span>
                            de <?= dinheiro($valorLimite) ?>
                        </span>

                    </div>

                    <div class="limite-progresso">

                        <div
                            class="limite-progresso-barra <?= $statusClasse ?>"
                            style="width: <?= $percentualBarra ?>%;"
                        ></div>

                    </div>

                    <span class="limite-percentual">
                        <?= number_format($percentual, 1, ',', '.') ?>% utilizado
                    </span>

                    <p class="limite-restante">

                        <?php if ($restante >= 0): ?>

                            Restam <?= dinheiro($restante) ?> disponíveis.

                        <?php else: ?>

                            Você ultrapassou o limite em
                            <?= dinheiro(abs($restante)) ?>.

                        <?php endif; ?>

                    </p>

                    <?php if ($statusClasse === 'atencao'): ?>

                        <p class="limite-alerta atencao">
                            Atenção: você já utilizou pelo menos 80% deste limite.
                        </p>

                    <?php elseif ($statusClasse === 'excedido'): ?>

                        <p class="limite-alerta excedido">
                            Você ultrapassou o valor planejado para esta categoria.
                        </p>

                    <?php endif; ?>


                    

                    <div class="limite-acoes">

                        <button
                            type="button"
                            class="limite-btn"
                            data-editar-limite
                            data-id="<?= (int) $limite['id'] ?>"
                            data-valor="<?= e($limite['valor_limite']) ?>"
                        >
                            Editar limite
                        </button>

                        <form
                            method="POST"
                            action="/acao.php"
                            onsubmit="return confirm('Deseja realmente excluir este limite?');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(gerarToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="acao"
                                value="excluir_limite"
                            >

                            <input
                                type="hidden"
                                name="limite_id"
                                value="<?= (int) $limite['id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="mes"
                                value="<?= $mesSelecionado ?>"
                            >

                            <input
                                type="hidden"
                                name="ano"
                                value="<?= $anoSelecionado ?>"
                            >

                            <button
                                type="submit"
                                class="limite-btn limite-btn-danger"
                            >
                                Excluir
                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>


    

    <aside>


        

        <div class="limite-form-card">

            <h3>Novo limite</h3>

            <?php if (!empty($categoriasDisponiveis)): ?>

                <form method="POST" action="/acao.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(gerarToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="acao"
                        value="criar_limite"
                    >

                    <div class="limite-form-group">

                        <label for="categoria_id">
                            Categoria de despesa
                        </label>

                        <select
                            id="categoria_id"
                            name="categoria_id"
                            required
                        >

                            <option value="">
                                Selecione uma categoria
                            </option>

                            <?php foreach ($categoriasDisponiveis as $categoria): ?>

                                <option value="<?= (int) $categoria['id'] ?>">
                                    <?= e($categoria['nome']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="limite-form-group">

                        <label for="valor_limite">
                            Valor do limite (R$)
                        </label>

                        <input
                            type="number"
                            id="valor_limite"
                            name="valor_limite"
                            min="0.01"
                            step="0.01"
                            placeholder="500.00"
                            required
                        >

                    </div>

                    <input
                        type="hidden"
                        name="mes"
                        value="<?= $mesSelecionado ?>"
                    >

                    <input
                        type="hidden"
                        name="ano"
                        value="<?= $anoSelecionado ?>"
                    >

                    <button type="submit" class="btn-primary">
                        Cadastrar limite
                    </button>

                </form>

            <?php else: ?>

                <div class="limite-vazio">

                    <?php if (empty($categorias)): ?>

                        <p>
                            Cadastre primeiro uma categoria do tipo despesa.
                        </p>

                    <?php else: ?>

                        <p>
                            Todas as categorias de despesas deste mês já possuem limites.
                        </p>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>


        

        <div class="limite-form-card" id="formulario-edicao" hidden>

            <h3>Editar limite</h3>

            <form method="POST" action="/acao.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(gerarToken()) ?>"
                >

                <input
                    type="hidden"
                    name="acao"
                    value="editar_limite"
                >

                <input
                    type="hidden"
                    name="limite_id"
                    id="editar_limite_id"
                >

                <input
                    type="hidden"
                    name="mes"
                    value="<?= $mesSelecionado ?>"
                >

                <input
                    type="hidden"
                    name="ano"
                    value="<?= $anoSelecionado ?>"
                >

                <div class="limite-form-group">

                    <label for="editar_valor_limite">
                        Novo valor (R$)
                    </label>

                    <input
                        type="number"
                        id="editar_valor_limite"
                        name="valor_limite"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>

                <button type="submit" class="btn-primary">
                    Salvar alteração
                </button>

                <button
                    type="button"
                    class="limite-btn"
                    style="width: 100%; margin-top: 10px;"
                    id="cancelar-edicao"
                >
                    Cancelar
                </button>

            </form>

        </div>

    </aside>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const formulario = document.getElementById('formulario-edicao');
    const campoId = document.getElementById('editar_limite_id');
    const campoValor = document.getElementById('editar_valor_limite');
    const botaoCancelar = document.getElementById('cancelar-edicao');

    document.querySelectorAll('[data-editar-limite]').forEach(function (botao) {

        botao.addEventListener('click', function () {

            campoId.value = this.dataset.id;
            campoValor.value = this.dataset.valor;

            formulario.hidden = false;

            formulario.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            campoValor.focus();

        });

    });

    botaoCancelar.addEventListener('click', function () {

        formulario.hidden = true;
        campoId.value = '';
        campoValor.value = '';

    });

});

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
