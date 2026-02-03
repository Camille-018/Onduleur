<?php
session_start();
require_once "../config/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user["password"])) 
    {
    if ($user['status'] !== 'active') {
        die("Compte non validé.");
    }
    $_SESSION["user"] = $user["username"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["mail"] = $user["mail"];

    header("Location: ../index.php");
    exit;
    } 
    
else {
    $error = "Identifiants incorrects";
    }


}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Onduleur - Connexion</title>
</head>
<body>
    <h1>Connexion</h1>
    <p>Veuillez vous connecter pour accéder au tableau de bord</p>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">Se connecter</button>
        <br><br>
        <a href="sInscrire.php">S'inscrire</a>
    </form>

    <p style="color:red"><?= $error ?></p>
</body>
</html>
