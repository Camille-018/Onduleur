<?php
// authCheck.php
session_start();

$timeout = 600; // 10 minutes in seconds

// --- Check if the user is active ---
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header('Location: /auth/login.php?timeout=1');
    exit;
}

// --- Update activity timer ---
$_SESSION['LAST_ACTIVITY'] = time();

// --- Check if the user is connected ---
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// --- Connection to database ---
require_once __DIR__ . '/../config/config.php';

// --- Check if the account is active (status)---
$stmt = $pdo->prepare("SELECT status, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['status'] !== 'active') {
    session_destroy();
    header('Location: /auth/login.php');
    exit;
}

// --- stock the suer to display it ---
$_SESSION['user'] = $user['username'];
?>

<!-- top-bar for every web page -->
<div class="top-bar">
    <img src="/style/images/cereep.jpg" alt="logo" class="logo">
    <p class="user-info">
        Connecté : <b><?= htmlspecialchars($_SESSION['user']) ?></b><br>
        <a href="/auth/logout.php">Se déconnecter</a>
    </p>
</div>

<script>
// time before logout (10 min = 600000 ms)
const logoutTime = 10 * 60 * 1000;
let logoutTimer;

// Function to redirect to logout
function autoLogout() {
    window.location.href = "/auth/logout.php";
}

// Restart the timer when there is activity
function resetTimer() {
    clearTimeout(logoutTimer);
    logoutTimer = setTimeout(autoLogout, logoutTime);
}

// Listen all the events -> rester timer
['click', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetTimer, false);
});

// Start the timer
resetTimer();
</script>
