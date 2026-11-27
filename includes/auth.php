<?php

require_once __DIR__ . '/../config/conexao.php';

function exigirLogin() {

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }

    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT u.nome, u.tipo, u.empresa_id, u.tema, e.nome AS empresa_nome
         FROM usuarios u
         LEFT JOIN empresas e ON e.id = u.empresa_id
         WHERE u.id = ?
         LIMIT 1"
    );
    $stmt->execute([(int) $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario || ($usuario['tipo'] !== 'superadmin' && !$usuario['empresa_id'])) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /login.php');
        exit;
    }

    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['tema'] = in_array($usuario['tema'], ['claro', 'escuro'], true)
        ? $usuario['tema']
        : 'claro';

    if ($usuario['tipo'] === 'superadmin') {
        $contextoEmpresaId = (int) ($_SESSION['empresa_contexto_id'] ?? 0);
        $_SESSION['empresa_id'] = 0;
        $_SESSION['empresa_nome'] = 'Admin geral';

        if ($contextoEmpresaId > 0) {
            $stmt = $pdo->prepare(
                "SELECT id, nome FROM empresas WHERE id = ? LIMIT 1"
            );
            $stmt->execute([$contextoEmpresaId]);
            $empresa = $stmt->fetch();

            if ($empresa) {
                $_SESSION['empresa_id'] = (int) $empresa['id'];
                $_SESSION['empresa_nome'] = $empresa['nome'];
            } else {
                unset($_SESSION['empresa_contexto_id']);
            }
        }
    } else {
        $_SESSION['empresa_id'] = (int) $usuario['empresa_id'];
        $_SESSION['empresa_nome'] = $usuario['empresa_nome'];
        unset($_SESSION['empresa_contexto_id']);
    }
}

function exigirEmpresa() {
    exigirLogin();

    if ((int) ($_SESSION['empresa_id'] ?? 0) <= 0) {
        header('Location: /pages/empresas.php');
        exit;
    }
}

function ehAdminEmpresa() {
    return in_array(
        $_SESSION['usuario_tipo'] ?? '',
        ['admin', 'superadmin'],
        true
    ) && (int) ($_SESSION['empresa_id'] ?? 0) > 0;
}

function exigirAdminGeral() {
    exigirLogin();

    if ($_SESSION['usuario_tipo'] !== 'superadmin') {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}

function exigirAdmin() {
    exigirEmpresa();

    if (!ehAdminEmpresa()) {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}
