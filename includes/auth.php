<?php

require_once __DIR__ . '/../config/conexao.php';

function exigirLogin() {

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }

    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT u.nome, u.tipo, u.empresa_id, e.nome AS empresa_nome
         FROM usuarios u
         INNER JOIN empresas e ON e.id = u.empresa_id
         WHERE u.id = ?
         LIMIT 1"
    );
    $stmt->execute([(int) $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /login.php');
        exit;
    }

    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['empresa_id'] = (int) $usuario['empresa_id'];
    $_SESSION['empresa_nome'] = $usuario['empresa_nome'];
}

function exigirAdmin() {
    exigirLogin();

    if ($_SESSION['usuario_tipo'] !== 'admin') {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}
