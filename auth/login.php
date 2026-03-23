<?php
// login.php: Login page, check credentials, set session
session_start();
require_once "../config/config.php";

// show success message after password reset request
$error = "";
if (isset($_GET['reset'])) {
    echo "<script>alert('Si le compte existe, un email de réinitialisation a été envoyé.');</script>";
}

// verify if user and password are correct
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
    <!-- html: form to login -->
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
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
