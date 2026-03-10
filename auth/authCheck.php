<?php
// authCheck.php
session_start();

// --- vérifie si l'utilisateur est connecté ---
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// --- connexion à la base ---
require_once __DIR__ . '/../config/config.php';

// --- vérifie que le compte est actif ---
$stmt = $pdo->prepare("SELECT status, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}

// --- stock le username pour affichage ---
$_SESSION['user'] = $user['username'];
?>

<!-- top-bar visible sur toutes les pages protégées -->
<div class="top-bar">
    <img src="/style/images/cereep.jpg" alt="logo" class="logo">
    <p class="user-info">
        Connecté : <b><?= htmlspecialchars($_SESSION['user']) ?></b><br>
        <a href="/auth/logout.php">Se déconnecter</a>
    </p>
</div>

