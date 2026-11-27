<?php

require_once __DIR__ . '/config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}

validarToken();

$tema = $_POST['tema'] ?? 'claro';

if (!in_array($tema, ['claro', 'escuro'], true)) {
    $tema = 'claro';
}

$stmt = $pdo->prepare(
    "UPDATE usuarios SET tema = ? WHERE id = ?"
);
$stmt->execute([$tema, (int) $_SESSION['usuario_id']]);
$_SESSION['tema'] = $tema;

setcookie(
    'tema',
    $tema,
    [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);

header('Location: /pages/dashboard.php');
exit;
