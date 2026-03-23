<?php
// authCheck.php: logout if the person is inactive on the website more than 10 mins + or the the person conencted has an invalid account (i.e: someone got removed from database)
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