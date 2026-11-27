<?php

require_once __DIR__ . '/auth.php';

$paginaAtual = basename($_SERVER['PHP_SELF']);
$ehAdminGeral = $_SESSION['usuario_tipo'] === 'superadmin';
$empresaSelecionada = (int) ($_SESSION['empresa_id'] ?? 0) > 0;

$tema = $_SESSION['tema'] ?? ($_COOKIE['tema'] ?? 'claro');

if (!in_array($tema, ['claro', 'escuro'], true)) {
    $tema = 'claro';
}

?>

<!DOCTYPE html>
<html lang="pt-BR" data-tema="<?= e($tema) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FinanceControl</title>

    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="logo">
            Finance<span>Control</span>
        </div>

        <?php if (!$ehAdminGeral || $empresaSelecionada): ?>

            <p class="menu-label">MENU PRINCIPAL</p>
            <nav>
                <a href="/pages/dashboard.php"
                   class="<?= $paginaAtual === 'dashboard.php' ? 'active' : '' ?>">
                    Visão geral
                </a>
                <a href="/pages/lancamentos.php"
                   class="<?= $paginaAtual === 'lancamentos.php' ? 'active' : '' ?>">
                    Lançamentos
                </a>
            </nav>

            <p class="menu-label">PLANEJAMENTO</p>
            <nav>
                <a href="/pages/metas.php"
                   class="<?= $paginaAtual === 'metas.php' ? 'active' : '' ?>">
                    Metas financeiras
                </a>
                <a href="/pages/limites.php"
                   class="<?= $paginaAtual === 'limites.php' ? 'active' : '' ?>">
                    Limites de gastos
                </a>
                <a href="/pages/recorrencias.php"
                   class="<?= $paginaAtual === 'recorrencias.php' ? 'active' : '' ?>">
                    Recorrências
                </a>
                <a href="/pages/categorias.php"
                   class="<?= $paginaAtual === 'categorias.php' ? 'active' : '' ?>">
                    Categorias
                </a>
                <a href="/pages/relatorios.php"
                   class="<?= $paginaAtual === 'relatorios.php' ? 'active' : '' ?>">
                    Relatórios financeiros
                </a>
            </nav>

        <?php endif; ?>

        <?php if ($ehAdminGeral): ?>
            <p class="menu-label">ADMINISTRAÇÃO DO SISTEMA</p>
            <nav>
                <a href="/pages/empresas.php"
                   class="<?= $paginaAtual === 'empresas.php' ? 'active' : '' ?>">
                    Todas as empresas
                </a>
            </nav>
        <?php endif; ?>

        <?php if ($_SESSION['usuario_tipo'] === 'admin' || ($ehAdminGeral && $empresaSelecionada)): ?>
            <p class="menu-label">ADMINISTRAÇÃO</p>
            <nav>
                <a href="/pages/usuarios.php"
                   class="<?= $paginaAtual === 'usuarios.php' ? 'active' : '' ?>">
                    Equipe da empresa
                </a>
                <?php if ($ehAdminGeral): ?>
                    <a href="/pages/empresas.php">Trocar empresa</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        

        <div class="sidebar-bottom">

            <div class="user-info">

                <strong><?= e($_SESSION['usuario_nome']) ?></strong>

                <span>
                    <?= $ehAdminGeral
                        ? 'Administrador geral'
                        : ($_SESSION['usuario_tipo'] === 'admin'
                            ? 'Administrador da empresa'
                            : 'Funcionário') ?>
                </span>

            </div>

            <form action="/tema.php" method="POST" class="theme-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(gerarToken()) ?>"
                >

                <input
                    type="hidden"
                    name="tema"
                    value="<?= $tema === 'claro' ? 'escuro' : 'claro' ?>"
                >

                <button type="submit" class="theme-button">
                    <?= $tema === 'claro'
                        ? 'Ativar tema escuro'
                        : 'Ativar tema claro' ?>
                </button>

            </form>

            <a href="/logout.php" class="logout-link">
                Sair da conta
            </a>

        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <span>FinanceControl / <?= e($_SESSION['empresa_nome'] ?? 'Painel') ?></span>

            <strong>
                Olá, <?= e($_SESSION['usuario_nome']) ?>
            </strong>

        </header>

        <section class="page-content">
