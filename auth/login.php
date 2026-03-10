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
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - S'identifier</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>S'identifier</h1>
    <p>Veuillez vous connecter pour accéder au tableau de bord</p>
    <form method="POST">
        <input type="text" name="username" placeholder="Utilisateur" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <button type="submit">S'identifier</button>
        <br><br>
        <a href="forgotPassword.php">Mot de passe oublié?</a><br>
        <a href="sInscrire.php">S'inscrire</a>
    </form>
    <p style="color:red"><?= $error ?></p>
</body>
</html>
