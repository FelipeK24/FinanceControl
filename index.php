<?php

require_once __DIR__ . '/config/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: /pages/dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Organize o fluxo de caixa, os custos e as receitas da sua empresa com o FinanceControl.">
    <title>FinanceControl | Finanças para empresas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="landing-page">
    <header class="landing-header">
        <a class="landing-brand" href="/" aria-label="FinanceControl, início">
            <span class="landing-brand-mark" aria-hidden="true">FC</span>
            <span>Finance<span>Control</span></span>
        </a>

        <nav class="landing-nav" aria-label="Navegação principal">
            <a href="#visao">Visão do negócio</a>
            <a href="#recursos">Recursos</a>
            <a href="#categorias">Categorias</a>
            <a href="#planos">Planos</a>
        </nav>

        <div class="landing-header-actions">
            <a class="landing-login" href="/login.php">Entrar</a>
            <a class="landing-button landing-button-dark" href="/cadastro.php">
                Criar conta <span aria-hidden="true">↗</span>
            </a>
        </div>
    </header>

    <main>
        <section class="landing-hero">
            <img
                class="landing-hero-image"
                src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=2400&q=85"
                alt=""
                fetchpriority="high"
            >
            <div class="landing-hero-content">
                <p class="landing-eyebrow"><span></span> Finanças para empresas</p>
                <h1>FinanceControl</h1>
                <p class="landing-hero-copy">
                    Mais clareza sobre o caixa. Mais confiança em cada decisão.
                    Organize receitas, despesas e compromissos da sua empresa em um só lugar.
                </p>
                <div class="landing-hero-actions">
                    <a class="landing-button landing-button-light" href="/cadastro.php">
                        Começar agora <span aria-hidden="true">↗</span>
                    </a>
                    <a class="landing-text-link" href="/login.php">Já tenho uma conta <span aria-hidden="true">→</span></a>
                </div>
                <p class="landing-hero-note">Fluxo de caixa · custos operacionais · planejamento</p>
            </div>
            <a class="landing-scroll-cue" href="#visao" aria-label="Conheça o FinanceControl">
                <span aria-hidden="true">↓</span>
            </a>
        </section>

        <section class="landing-overview" id="visao">
            <div class="landing-section-copy">
                <p class="landing-kicker">01 / VISÃO DO NEGÓCIO</p>
                <h2>As finanças da empresa, <em>sem pontos cegos.</em></h2>
                <p>
                    Acompanhe entradas, custos e saldo com informações organizadas por período.
                    Menos tempo reunindo números; mais tempo para decidir os próximos passos.
                </p>
                <a class="landing-inline-link" href="/cadastro.php">Organizar minhas finanças <span aria-hidden="true">↗</span></a>
            </div>

            <div class="landing-dashboard" aria-label="Exemplo ilustrativo do painel financeiro">
                <div class="landing-dashboard-top">
                    <div>
                        <span class="landing-dashboard-caption">EXEMPLO DE PAINEL</span>
                        <h3>Resumo financeiro</h3>
                    </div>
                    <span class="landing-period">Este mês <span aria-hidden="true">⌄</span></span>
                </div>

                <div class="landing-metrics">
                    <div>
                        <span>Receitas</span>
                        <strong>R$ 84.620</strong>
                        <small class="landing-positive">Entradas do período</small>
                    </div>
                    <div>
                        <span>Despesas</span>
                        <strong>R$ 56.370</strong>
                        <small>Custos e compromissos</small>
                    </div>
                    <div class="landing-balance">
                        <span>Saldo operacional</span>
                        <strong>R$ 28.250</strong>
                        <small>Exemplo ilustrativo</small>
                    </div>
                </div>

                <div class="landing-chart-heading">
                    <div><strong>Movimentação</strong><span>Entradas e saídas mensais</span></div>
                    <div class="landing-chart-key"><span></span> Receitas <i></i> Despesas</div>
                </div>
                <div class="landing-chart" aria-hidden="true">
                    <div class="landing-chart-month"><div><i style="height: 46%"></i><b style="height: 31%"></b></div><span>Jan</span></div>
                    <div class="landing-chart-month"><div><i style="height: 61%"></i><b style="height: 38%"></b></div><span>Fev</span></div>
                    <div class="landing-chart-month"><div><i style="height: 55%"></i><b style="height: 43%"></b></div><span>Mar</span></div>
                    <div class="landing-chart-month"><div><i style="height: 76%"></i><b style="height: 49%"></b></div><span>Abr</span></div>
                    <div class="landing-chart-month"><div><i style="height: 66%"></i><b style="height: 53%"></b></div><span>Mai</span></div>
                    <div class="landing-chart-month"><div><i style="height: 89%"></i><b style="height: 61%"></b></div><span>Jun</span></div>
                </div>
            </div>
        </section>

        <section class="landing-capabilities" id="recursos">
            <div class="landing-capabilities-heading">
                <p class="landing-kicker">02 / CONTROLE OPERACIONAL</p>
                <h2>Rotina financeira, equipe e controles no mesmo painel.</h2>
            </div>
            <div class="landing-capability-list">
                <article>
                    <span class="landing-capability-number">01</span>
                    <div><h3>Fluxo de caixa organizado</h3><p>Registre receitas e despesas e acompanhe o resultado do período.</p></div>
                    <span class="landing-capability-mark" aria-hidden="true">↗</span>
                </article>
                <article>
                    <span class="landing-capability-number">02</span>
                    <div><h3>Custos sob acompanhamento</h3><p>Defina limites por categoria e acompanhe compromissos recorrentes.</p></div>
                    <span class="landing-capability-mark" aria-hidden="true">↗</span>
                </article>
                <article>
                    <span class="landing-capability-number">03</span>
                    <div><h3>Relatórios para decidir</h3><p>Consulte receitas, despesas e tendências em uma visão clara do negócio.</p></div>
                    <span class="landing-capability-mark" aria-hidden="true">↗</span>
                </article>
                <article>
                    <span class="landing-capability-number">04</span>
                    <div><h3>Permissões para cada função</h3><p>Administradores gerenciam categorias e acessos; funcionários registram a rotina financeira.</p></div>
                    <span class="landing-capability-mark" aria-hidden="true">↗</span>
                </article>
            </div>
        </section>

        <section class="landing-categories" id="categorias">
            <div class="landing-categories-intro">
                <p class="landing-kicker">03 / CATEGORIAS EMPRESARIAIS</p>
                <h2>Uma estrutura que fala a língua da empresa.</h2>
                <p>Separe as movimentações por origem e natureza para entender onde o dinheiro entra e para onde vai.</p>
            </div>
            <div class="landing-category-columns">
                <div>
                    <span class="landing-category-label">Receitas</span>
                    <ul>
                        <li>Venda de produtos</li>
                        <li>Prestação de serviços</li>
                        <li>Receitas financeiras</li>
                        <li>Comissões e royalties</li>
                        <li>Outras receitas</li>
                    </ul>
                </div>
                <div>
                    <span class="landing-category-label">Custos e despesas</span>
                    <ul>
                        <li>Fornecedores e terceiros</li>
                        <li>Folha, encargos e benefícios</li>
                        <li>Impostos e serviços profissionais</li>
                        <li>Marketing, tecnologia e operações</li>
                        <li>Logística e despesas bancárias</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="landing-plans" id="planos">
            <div class="landing-plans-heading">
                <div>
                    <p class="landing-kicker">04 / PLANOS PARA EMPRESAS</p>
                    <h2>Um plano para cada momento do negócio.</h2>
                </div>
                <p>Opções para organizar a operação, dar autonomia à equipe e acompanhar a empresa de perto.</p>
            </div>

            <div class="landing-plan-grid">
                <article class="landing-plan">
                    <div class="landing-plan-top">
                        <span class="landing-plan-number">01</span>
                        <span class="landing-plan-audience">Pequenas empresas</span>
                    </div>
                    <h3>Essencial</h3>
                    <p class="landing-plan-description">Para estruturar o controle financeiro do dia a dia.</p>
                    <p class="landing-plan-price"><strong>Sob consulta</strong><span>valor definido para a empresa</span></p>
                    <ul>
                        <li>Painel com receitas, despesas e saldo</li>
                        <li>Lançamentos organizados por categoria</li>
                        <li>Categorias empresariais compartilhadas</li>
                        <li>Metas financeiras e contribuições</li>
                        <li>Limites mensais por categoria</li>
                        <li>Histórico de movimentações</li>
                    </ul>
                </article>

                <article class="landing-plan landing-plan-featured">
                    <div class="landing-plan-top">
                        <span class="landing-plan-number">02</span>
                        <span class="landing-plan-audience">Equipes em expansão</span>
                    </div>
                    <h3>Equipe</h3>
                    <p class="landing-plan-description">Para dividir a rotina financeira com responsabilidades claras.</p>
                    <p class="landing-plan-price"><strong>Sob consulta</strong><span>valor definido para a empresa</span></p>
                    <ul>
                        <li>Perfis separados para administradores e funcionários</li>
                        <li>Gestão de equipe e controle de acessos</li>
                        <li>Categorias compartilhadas sob gestão do admin</li>
                        <li>Visão financeira consolidada da empresa</li>
                        <li>Lançamentos recorrentes e metas</li>
                        <li>Acompanhamento de despesas por limite</li>
                    </ul>
                </article>

                <article class="landing-plan">
                    <div class="landing-plan-top">
                        <span class="landing-plan-number">03</span>
                        <span class="landing-plan-audience">Gestão e supervisão</span>
                    </div>
                    <h3>Gestão</h3>
                    <p class="landing-plan-description">Para acompanhar resultados e coordenar os acessos da empresa.</p>
                    <p class="landing-plan-price"><strong>Sob consulta</strong><span>valor definido para a empresa</span></p>
                    <ul>
                        <li>Relatórios financeiros por período</li>
                        <li>Exportação de lançamentos para CSV</li>
                        <li>Supervisão dos registros da equipe</li>
                        <li>Gestão de perfis e acessos</li>
                        <li>Planejamento de despesas recorrentes</li>
                        <li>Metas e limites para acompanhar resultados</li>
                    </ul>
                </article>
            </div>
            <p class="landing-plans-note">Planos apresentados como opções comerciais; preços, contratação e limites não são processados pelo sistema.</p>
        </section>

        <section class="landing-cta">
            <p class="landing-kicker">FINANCECONTROL</p>
            <h2>Decisões melhores começam com números organizados.</h2>
            <a class="landing-button landing-button-light" href="/cadastro.php">Criar minha conta <span aria-hidden="true">↗</span></a>
        </section>
    </main>

    <footer class="landing-footer">
        <a class="landing-brand" href="/">
            <span class="landing-brand-mark" aria-hidden="true">FC</span>
            <span>Finance<span>Control</span></span>
        </a>
        <span>Finanças empresariais, com mais clareza.</span>
        <a href="/login.php">Acessar conta <span aria-hidden="true">↗</span></a>
    </footer>
</body>
</html>
