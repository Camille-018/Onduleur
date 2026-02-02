<?php
session_start();
require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user["password"]) {
        $_SESSION["user"] = $user["username"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["mail"] = $user["mail"];

        header("Location: ../dashboard/index.php");
        exit;
    } else {
        $error = "Identifiants incorrects";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
</head>
<body>
    <h2>Connexion</h2>

    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">Se connecter</button>
    </form>

    <p style="color:red"><?= $error ?></p>
</body>
</html>
