<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    if ($user && $_POST['password'] === $user['password']) {
        $_SESSION['user'] = $user['username'];
        header("Location: http://localhost/onduleur-dashboard/dashboard/index.php");
        exit;
    } else {
        $error = "Identifiants incorrects";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login">
<form method="post">
    <h2>Connexion</h2>
    <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
    <input name="username" placeholder="Utilisateur" required>
    <input name="password" type="password" placeholder="Mot de passe" required>
    <button type="submit">Se connecter</button>
</form>
</body>
</html>
