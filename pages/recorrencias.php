<?php

require_once __DIR__ . '/../includes/auth.php';

exigirLogin();

$usuarioId = (int) $_SESSION['usuario_id'];
$empresaId = (int) $_SESSION['empresa_id'];
$hoje = date('Y-m-d');





$stmt = $pdo->prepare(
    "SELECT id, nome, tipo
     FROM categorias
    WHERE empresa_id = ?
     ORDER BY tipo, nome"
);

$stmt->execute([$empresaId]);
$categorias = $stmt->fetchAll();





$stmt = $pdo->prepare(
    "SELECT
        r.*,
        c.nome AS categoria_nome
     FROM recorrencias r
     INNER JOIN categorias c
        ON c.id = r.categoria_id
     WHERE r.usuario_id = ?
     ORDER BY
        r.ativa DESC,
        r.proxima_data ASC,
        r.id DESC"
);

$stmt->execute([$usuarioId]);

$recorrencias = $stmt->fetchAll();





$pendentes = [];

foreach ($recorrencias as $recorrencia) {

    $dataFim = $recorrencia['data_fim'];

    if (
        (int) $recorrencia['ativa'] === 1 &&
        $recorrencia['proxima_data'] <= $hoje &&
        (
            empty($dataFim) ||
            $recorrencia['proxima_data'] <= $dataFim
        )
    ) {
        $pendentes[] = $recorrencia;
    }
}





$totalRecorrencias = count($recorrencias);

$totalAtivas = 0;
$totalInativas = 0;
$totalPendentes = count($pendentes);

foreach ($recorrencias as $recorrencia) {

    if ((int) $recorrencia['ativa'] === 1) {
        $totalAtivas++;
    } else {
        $totalInativas++;
    }
}

$formatarData = static function ($data) {
    return date('d/m/Y', strtotime($data));
};

$nomeFrequencia = [
    'semanal' => 'Semanal',
    'quinzenal' => 'Quinzenal',
    'mensal' => 'Mensal',
    'anual' => 'Anual'
];

$nomeTipo = [
    'receita' => 'Receita',
    'despesa' => 'Despesa'
];

require_once __DIR__ . '/../includes/header.php';

?>

<style>



.recorrencias-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.recorrencias-container * {
    box-sizing: border-box;
}

.recorrencias-indicadores {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 25px;
}

.recorrencia-indicador {
    padding: 20px;
    border-radius: 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.recorrencia-indicador span {
    display: block;
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 9px;
}

.recorrencia-indicador strong {
    font-size: 25px;
    color: var(--text);
}

.recorrencia-secao {
    margin-bottom: 28px;
}

.recorrencia-secao h2 {
    font-size: 19px;
    margin: 0 0 17px;
    color: var(--text);
}

.recorrencia-painel {
    padding: 22px;
    border-radius: 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.recorrencia-formulario {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.recorrencia-campo {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.recorrencia-campo.completo {
    grid-column: 1 / -1;
}

.recorrencia-campo label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
}

.recorrencia-campo input,
.recorrencia-campo select {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    color: var(--text);
    font: inherit;
}

.recorrencia-campo input:focus,
.recorrencia-campo select:focus {
    outline: 2px solid var(--primary);
    outline-offset: 1px;
}

.recorrencia-campo select option {
    background: var(--surface);
    color: var(--text);
}

.recorrencia-botao {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 39px;
    padding: 9px 14px;
    border: 0;
    border-radius: 8px;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: opacity .2s, background .2s;
}

.recorrencia-botao:hover {
    opacity: .85;
}

.recorrencia-botao.primario {
    background: var(--primary);
    color: #fff;
}

.recorrencia-botao.primario:hover {
    background: var(--primary-hover);
}

.recorrencia-botao.secundario {
    background: var(--surface-secondary);
    color: var(--text);
    border: 1px solid var(--border);
}

.recorrencia-botao.perigo {
    background: var(--red);
    color: #fff;
}

.recorrencia-botao.sucesso {
    background: var(--green);
    color: #fff;
}

.recorrencia-botoes {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.recorrencia-lista {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.recorrencia-card {
    padding: 19px;
    border: 1px solid var(--border);
    border-radius: 11px;
    background: var(--surface);
    box-shadow: var(--shadow);
}

.recorrencia-card-topo {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
}

.recorrencia-card h3 {
    margin: 0 0 7px;
    font-size: 16px;
    color: var(--text);
}

.recorrencia-card p {
    margin: 5px 0;
    font-size: 13px;
    color: var(--text-secondary);
}

.recorrencia-valor {
    font-size: 19px;
    font-weight: 700;
    white-space: nowrap;
    color: var(--text);
}

.recorrencia-etiquetas {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 11px;
}

.recorrencia-etiqueta {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: var(--surface-secondary);
    color: var(--text);
}

.recorrencia-etiqueta.ativa {
    background: rgba(22, 163, 106, 0.14);
    color: var(--green);
}

.recorrencia-etiqueta.inativa {
    background: rgba(224, 82, 82, 0.14);
    color: var(--red);
}

.recorrencia-etiqueta.pendente {
    background: rgba(217, 119, 6, 0.14);
    color: #d97706;
}

.recorrencia-pendente {
    border-color: #d97706;
}

.recorrencia-edicao {
    margin-top: 15px;
    border-top: 1px solid var(--border);
    padding-top: 14px;
}

.recorrencia-edicao summary {
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
}

.recorrencia-edicao form {
    margin-top: 17px;
}

.recorrencia-vazio {
    padding: 28px 18px;
    text-align: center;
    border: 1px dashed var(--border);
    border-radius: 10px;
    color: var(--text-secondary);
    font-size: 14px;
    background: var(--surface);
}

.recorrencia-aviso {
    padding: 13px 15px;
    border-radius: 9px;
    margin-bottom: 17px;
    background: rgba(217, 119, 6, 0.12);
    border: 1px solid rgba(217, 119, 6, 0.4);
    color: var(--text);
    font-size: 13px;
    line-height: 1.5;
}

.recorrencia-ajuda {
    font-size: 12px;
    color: var(--text-secondary);
    line-height: 1.5;
}

@media (max-width: 850px) {
    .recorrencias-indicadores {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .recorrencia-formulario {
        grid-template-columns: 1fr;
    }

    .recorrencia-campo.completo {
        grid-column: auto;
    }

    .recorrencia-card-topo {
        flex-direction: column;
    }

    .recorrencia-indicador {
        padding: 15px;
    }

    .recorrencia-indicador strong {
        font-size: 21px;
    }

    .recorrencia-painel {
        padding: 16px;
    }
}

</style>

<div class="recorrencias-container">

    <div class="recorrencia-secao">
        <h1>Lançamentos recorrentes</h1>

        <p class="recorrencia-ajuda">
            Organize receitas e despesas periódicas e confirme manualmente
            cada ocorrência antes de registrá-la no seu financeiro.
        </p>
    </div>

    

    <section class="recorrencias-indicadores">

        <div class="recorrencia-indicador">
            <span>Total de recorrências</span>
            <strong><?= $totalRecorrencias ?></strong>
        </div>

        <div class="recorrencia-indicador">
            <span>Recorrências ativas</span>
            <strong><?= $totalAtivas ?></strong>
        </div>

        <div class="recorrencia-indicador">
            <span>Recorrências pausadas</span>
            <strong><?= $totalInativas ?></strong>
        </div>

        <div class="recorrencia-indicador">
            <span>Aguardando confirmação</span>
            <strong><?= $totalPendentes ?></strong>
        </div>

    </section>


    

    <section class="recorrencia-secao">

        <h2>Nova recorrência</h2>

        <div class="recorrencia-painel">

            <?php if (empty($categorias)): ?>

                <div class="recorrencia-vazio">
                    Não existem categorias cadastradas.
                    Cadastre uma categoria antes de criar uma recorrência.
                </div>

            <?php else: ?>

                <form action="/acao.php" method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(gerarToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="acao"
                        value="criar_recorrencia"
                    >

                    <div class="recorrencia-formulario">

                        <div class="recorrencia-campo completo">
                            <label for="nova-descricao">Descrição</label>

                            <input
                                type="text"
                                id="nova-descricao"
                                name="descricao"
                                maxlength="150"
                                placeholder="Ex.: Salário, aluguel, internet..."
                                required
                            >
                        </div>

                        <div class="recorrencia-campo">
                            <label for="nova-valor">Valor (R$)</label>

                            <input
                                type="number"
                                id="nova-valor"
                                name="valor"
                                min="0.01"
                                step="0.01"
                                placeholder="0,00"
                                required
                            >
                        </div>

                        <div class="recorrencia-campo">
                            <label for="nova-tipo">Tipo</label>

                            <select
                                name="tipo"
                                id="nova-tipo"
                                required
                            >
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                            </select>
                        </div>

                        <div class="recorrencia-campo">
                            <label for="nova-categoria">Categoria</label>

                            <select
                                name="categoria_id"
                                id="nova-categoria"
                                required
                            >
                                <option value="">Selecione uma categoria</option>

                                <?php foreach ($categorias as $categoria): ?>

                                    <?php if ((int) $categoria['ativa'] === 1): ?>

                                    <option
                                        value="<?= (int) $categoria['id'] ?>"
                                        data-tipo="<?= e($categoria['tipo']) ?>"
                                        <?= $categoria['tipo'] === 'despesa' ? '' : 'hidden' ?>
                                    >
                                        <?= e($categoria['nome']) ?>
                                        (<?= e($nomeTipo[$categoria['tipo']] ?? $categoria['tipo']) ?>)
                                    </option>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="recorrencia-campo">
                            <label for="nova-frequencia">Frequência</label>

                            <select
                                name="frequencia"
                                id="nova-frequencia"
                                required
                            >
                                <option value="semanal">Semanal</option>
                                <option value="quinzenal">Quinzenal</option>
                                <option value="mensal" selected>Mensal</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>

                        <div class="recorrencia-campo">
                            <label for="nova-data-inicio">Data da primeira ocorrência</label>

                            <input
                                type="date"
                                name="data_inicio"
                                id="nova-data-inicio"
                                value="<?= e($hoje) ?>"
                                required
                            >
                        </div>

                        <div class="recorrencia-campo">
                            <label for="nova-data-fim">Data final (opcional)</label>

                            <input
                                type="date"
                                name="data_fim"
                                id="nova-data-fim"
                            >
                        </div>

                        <div class="recorrencia-campo completo">

                            <p class="recorrencia-ajuda">
                                A primeira ocorrência será agendada para a data
                                informada. Quando essa data chegar, você poderá
                                confirmar a criação do lançamento.
                            </p>

                            <button
                                type="submit"
                                class="recorrencia-botao primario"
                            >
                                Cadastrar recorrência
                            </button>

                        </div>

                    </div>

                </form>

            <?php endif; ?>

        </div>

    </section>


    

    <section class="recorrencia-secao">

        <h2>Ocorrências aguardando confirmação</h2>

        <?php if (empty($pendentes)): ?>

            <div class="recorrencia-vazio">
                Não existem ocorrências aguardando confirmação.
            </div>

        <?php else: ?>

            <div class="recorrencia-aviso">
                Estas recorrências chegaram à data prevista.
                Confira os dados e confirme somente os lançamentos que deseja registrar.
                Nenhum lançamento será criado sem sua confirmação.
            </div>

            <div class="recorrencia-lista">

                <?php foreach ($pendentes as $recorrencia): ?>

                    <article class="recorrencia-card recorrencia-pendente">

                        <div class="recorrencia-card-topo">

                            <div>

                                <h3><?= e($recorrencia['descricao']) ?></h3>

                                <p>
                                    Categoria:
                                    <?= e($recorrencia['categoria_nome']) ?>
                                </p>

                                <p>
                                    Tipo:
                                    <?= e($nomeTipo[$recorrencia['tipo']] ?? $recorrencia['tipo']) ?>
                                </p>

                                <p>
                                    Data prevista:
                                    <?= $formatarData($recorrencia['proxima_data']) ?>
                                </p>

                                <div class="recorrencia-etiquetas">

                                    <span class="recorrencia-etiqueta pendente">
                                        Aguardando confirmação
                                    </span>

                                    <span class="recorrencia-etiqueta">
                                        <?= e($nomeFrequencia[$recorrencia['frequencia']] ?? $recorrencia['frequencia']) ?>
                                    </span>

                                </div>

                            </div>

                            <div class="recorrencia-valor">
                                <?= dinheiro($recorrencia['valor']) ?>
                            </div>

                        </div>

                        <div class="recorrencia-botoes">

                            <form action="/acao.php" method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="confirmar_recorrencia"
                                >

                                <input
                                    type="hidden"
                                    name="recorrencia_id"
                                    value="<?= (int) $recorrencia['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="recorrencia-botao sucesso"
                                    onclick="return confirm('Confirmar a criação deste lançamento?');"
                                >
                                    Confirmar lançamento
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>


    

    <section class="recorrencia-secao">

        <h2>Minhas recorrências</h2>

        <?php if (empty($recorrencias)): ?>

            <div class="recorrencia-vazio">
                Você ainda não cadastrou nenhuma recorrência.
            </div>

        <?php else: ?>

            <div class="recorrencia-lista">

                <?php foreach ($recorrencias as $recorrencia): ?>

                    <?php
                    $estaAtiva = (int) $recorrencia['ativa'] === 1;

                    $estaPendente = $estaAtiva &&
                        $recorrencia['proxima_data'] <= $hoje;
                    ?>

                    <article class="recorrencia-card">

                        <div class="recorrencia-card-topo">

                            <div>

                                <h3><?= e($recorrencia['descricao']) ?></h3>

                                <p>
                                    Categoria:
                                    <?= e($recorrencia['categoria_nome']) ?>
                                </p>

                                <p>
                                    Tipo:
                                    <?= e($nomeTipo[$recorrencia['tipo']] ?? $recorrencia['tipo']) ?>
                                </p>

                                <p>
                                    Frequência:
                                    <?= e($nomeFrequencia[$recorrencia['frequencia']] ?? $recorrencia['frequencia']) ?>
                                </p>

                                <p>
                                    Próxima ocorrência:
                                    <?= $formatarData($recorrencia['proxima_data']) ?>
                                </p>

                                <p>
                                    Início:
                                    <?= $formatarData($recorrencia['data_inicio']) ?>
                                </p>

                                <p>
                                    Término:
                                    <?= !empty($recorrencia['data_fim'])
                                        ? $formatarData($recorrencia['data_fim'])
                                        : 'Sem data final' ?>
                                </p>

                                <div class="recorrencia-etiquetas">

                                    <span class="recorrencia-etiqueta <?= $estaAtiva ? 'ativa' : 'inativa' ?>">
                                        <?= $estaAtiva ? 'Ativa' : 'Inativa' ?>
                                    </span>

                                    <?php if ($estaPendente): ?>

                                        <span class="recorrencia-etiqueta pendente">
                                            Pendente
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="recorrencia-valor">
                                <?= dinheiro($recorrencia['valor']) ?>
                            </div>

                        </div>


                        

                        <div class="recorrencia-botoes">

                            <form action="/acao.php" method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="alterar_status_recorrencia"
                                >

                                <input
                                    type="hidden"
                                    name="recorrencia_id"
                                    value="<?= (int) $recorrencia['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="ativa"
                                    value="<?= $estaAtiva ? 0 : 1 ?>"
                                >

                                <button
                                    type="submit"
                                    class="recorrencia-botao secundario"
                                >
                                    <?= $estaAtiva ? 'Desativar' : 'Ativar' ?>
                                </button>

                            </form>

                            <form action="/acao.php" method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="excluir_recorrencia"
                                >

                                <input
                                    type="hidden"
                                    name="recorrencia_id"
                                    value="<?= (int) $recorrencia['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="recorrencia-botao perigo"
                                    onclick="return confirm('Excluir esta recorrência? Os lançamentos já gerados serão preservados.');"
                                >
                                    Excluir
                                </button>

                            </form>

                        </div>


                        

                        <details class="recorrencia-edicao">

                            <summary>Editar recorrência</summary>

                            <form action="/acao.php" method="POST">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(gerarToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="editar_recorrencia"
                                >

                                <input
                                    type="hidden"
                                    name="recorrencia_id"
                                    value="<?= (int) $recorrencia['id'] ?>"
                                >

                                <div class="recorrencia-formulario">

                                    <div class="recorrencia-campo completo">

                                        <label>
                                            Descrição
                                        </label>

                                        <input
                                            type="text"
                                            name="descricao"
                                            maxlength="150"
                                            value="<?= e($recorrencia['descricao']) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="recorrencia-campo">

                                        <label>
                                            Valor (R$)
                                        </label>

                                        <input
                                            type="number"
                                            name="valor"
                                            min="0.01"
                                            step="0.01"
                                            value="<?= e($recorrencia['valor']) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="recorrencia-campo">

                                        <label>
                                            Tipo
                                        </label>

                                        <select
                                            name="tipo"
                                            class="recorrencia-tipo-edicao"
                                            required
                                        >

                                            <option
                                                value="despesa"
                                                <?= $recorrencia['tipo'] === 'despesa' ? 'selected' : '' ?>
                                            >
                                                Despesa
                                            </option>

                                            <option
                                                value="receita"
                                                <?= $recorrencia['tipo'] === 'receita' ? 'selected' : '' ?>
                                            >
                                                Receita
                                            </option>

                                        </select>

                                    </div>

                                    <div class="recorrencia-campo">

                                        <label>
                                            Categoria
                                        </label>

                                        <select
                                            name="categoria_id"
                                            class="recorrencia-categoria-edicao"
                                            required
                                        >

                                            <?php foreach ($categorias as $categoria): ?>

                                                <?php if (
                                                    (int) $categoria['ativa'] === 1 ||
                                                    (int) $recorrencia['categoria_id'] === (int) $categoria['id']
                                                ): ?>

                                                <option
                                                    value="<?= (int) $categoria['id'] ?>"
                                                    data-tipo="<?= e($categoria['tipo']) ?>"
                                                    <?= (int) $recorrencia['categoria_id'] === (int) $categoria['id'] ? 'selected' : '' ?>
                                                >
                                                    <?= e($categoria['nome']) ?>
                                                    <?= (int) $categoria['ativa'] === 1 ? '' : '(histórica)' ?>
                                                    (<?= e($nomeTipo[$categoria['tipo']] ?? $categoria['tipo']) ?>)
                                                </option>

                                                <?php endif; ?>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>

                                    <div class="recorrencia-campo">

                                        <label>
                                            Frequência
                                        </label>

                                        <select
                                            name="frequencia"
                                            required
                                        >

                                            <option value="semanal" <?= $recorrencia['frequencia'] === 'semanal' ? 'selected' : '' ?>>
                                                Semanal
                                            </option>

                                            <option value="quinzenal" <?= $recorrencia['frequencia'] === 'quinzenal' ? 'selected' : '' ?>>
                                                Quinzenal
                                            </option>

                                            <option value="mensal" <?= $recorrencia['frequencia'] === 'mensal' ? 'selected' : '' ?>>
                                                Mensal
                                            </option>

                                            <option value="anual" <?= $recorrencia['frequencia'] === 'anual' ? 'selected' : '' ?>>
                                                Anual
                                            </option>

                                        </select>

                                    </div>

                                    <div class="recorrencia-campo">

                                        <label>
                                            Data de início
                                        </label>

                                        <input
                                            type="date"
                                            name="data_inicio"
                                            value="<?= e($recorrencia['data_inicio']) ?>"
                                            required
                                        >

                                    </div>

                                    <div class="recorrencia-campo">

                                        <label>
                                            Data final (opcional)
                                        </label>

                                        <input
                                            type="date"
                                            name="data_fim"
                                            value="<?= e($recorrencia['data_fim'] ?? '') ?>"
                                        >

                                    </div>

                                    <div class="recorrencia-campo completo">

                                        <button
                                            type="submit"
                                            class="recorrencia-botao primario"
                                        >
                                            Salvar alterações
                                        </button>

                                    </div>

                                </div>

                            </form>

                        </details>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    function configurarCategorias(tipoSelect, categoriaSelect) {

        if (!tipoSelect || !categoriaSelect) {
            return;
        }

        function atualizarCategorias() {

            const tipo = tipoSelect.value;
            let selecionadaVisivel = false;

            Array.from(categoriaSelect.options).forEach(function (opcao) {

                if (!opcao.value) {
                    opcao.hidden = false;
                    return;
                }

                const corresponde = opcao.dataset.tipo === tipo;

                opcao.hidden = !corresponde;

                if (corresponde && opcao.selected) {
                    selecionadaVisivel = true;
                }
            });

            if (!selecionadaVisivel) {

                const primeiraDisponivel = Array.from(categoriaSelect.options)
                    .find(function (opcao) {
                        return opcao.value &&
                            opcao.dataset.tipo === tipo;
                    });

                categoriaSelect.value = primeiraDisponivel
                    ? primeiraDisponivel.value
                    : '';
            }
        }

        tipoSelect.addEventListener('change', atualizarCategorias);

        atualizarCategorias();
    }

    configurarCategorias(
        document.getElementById('nova-tipo'),
        document.getElementById('nova-categoria')
    );

    document.querySelectorAll('.recorrencia-card').forEach(function (card) {

        configurarCategorias(
            card.querySelector('.recorrencia-tipo-edicao'),
            card.querySelector('.recorrencia-categoria-edicao')
        );

    });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
