<?php
// login.php : page de connexion, vérifie les identifiants et initialise la session
session_start();
require_once "../config/config.php";

// affiche un message de succès après la demande de réinitialisation de mot de passe
$error = "";
if (isset($_GET['reset'])) {
    echo "<script>alert('Si le compte existe, un email de réinitialisation a été envoyé.');</script>";
}

// vérifie si l'utilisateur et le mot de passe sont corrects
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user["password"])) 
    {
    if ($user['status'] !== 'active') {
        die("Compte non actif.");
    }
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user"] = $user["username"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["mail"] = $user["mail"];

    header("Location: ../index.php");
    session_regenerate_id(true);
    exit;
    } 
    
else {
    $error = "utilisateur ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <!-- html : formulaire de connexion -->
    <meta charset="utf-8">
    <link rel="icon" href="../style/images/cereep32.ico">
    <link rel="stylesheet" href="../style/auth.css">
    <title>UPS - S'identifier</title>
</head>
<body>
    <div class="auth-container">
        <img src="../style/images/cereep.jpg" class="auth-logo">
        <h1>Connexion</h1>
        <p>Accès au tableau de bord</p>
        <form method="POST">
            <input type="text" name="username" placeholder="Utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <div class="auth-links">
            <a href="forgotPassword.php">Mot de passe oublié</a><br>
            <a href="sInscrire.php">Créer un compte</a>
        </div>
        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
    </div>
</body>
<script src="../style/message.js"></script>
</html>
