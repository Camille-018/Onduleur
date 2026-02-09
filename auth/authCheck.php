<?php
// authCheck.php: centralize the authentication check, for each page, it checks if the user is logged in and if the account is active, otherwise redirect to login page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/config.php';

$stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}
