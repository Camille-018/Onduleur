<?php
// authCheck.php : déconnexion si l'utilisateur est inactif sur le site depuis plus de 10 minutes ou si le compte connecté est invalide (par exemple, suppression dans la BDD)
session_start();

$timeout = 600; // 10 minutes

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: /auth/login.php?timeout=1');
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';

$stmt = $pdo->prepare("SELECT status, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}

$_SESSION['user'] = $user['username'];