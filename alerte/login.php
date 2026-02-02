<?php
// login.php
require_once '../config.php';
session_start();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // récupérer l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && $user['password'] === $password) { // ici mot de passe en clair, ok pour test
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: ../index.php'); // redirige vers l'accueil
        exit;
    } else {
        $message = "Identifiant ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <h1>Connexion</h1>
    <?php if($message) echo "<p style='color:red;'>$message</p>"; ?>
    <form method="post">
        <label>Nom d'utilisateur :</label>
        <input type="text" name="username" required><br><br>
        <label>Mot de passe :</label>
        <input type="password" name="password" required><br><br>
        <button type="submit">Se connecter</button>
    </form>
    <br>
    <a href="../index.php">Retour à l'accueil</a>
</body>
</html>
