<?php
// authCheck.php
session_start();

// --- Timeout automatic after 10 minutes ---
$timeout = 600; // secondes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: /auth/login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// --- check if the user is connected ---
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// --- Connection to database---
require_once __DIR__ . '/../config/config.php';

// --- Check if account is valid (active) ---
$stmt = $pdo->prepare("SELECT status, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}

// --- Stock the user to display it ---
$_SESSION['user'] = $user['username'];

// Top-bar seulement si l'utilisateur est connecté
if (isset($_SESSION['user'])): ?>
<div class="top-bar">
    <img src="/style/images/cereep.jpg" alt="logo" class="logo">
    <p class="user-info">
        Connecté : <b><?= htmlspecialchars($_SESSION['user']) ?></b><br>
        <a href="/auth/logout.php">Se déconnecter</a>
    </p>
</div>
<?php endif; ?>

<!-- =========================
     Logout JS when we close the web page
========================= -->
<script>
window.addEventListener("beforeunload", function () {
    navigator.sendBeacon("/auth/logout.php");
});
</script>