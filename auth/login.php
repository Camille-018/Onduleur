<?php
// login.php: Login page, check credentials, set session
session_start();
require_once "../config/config.php";

$error = "";
if (isset($_GET['reset'])) {
    echo "<script>alert('If the account exists, a reset email has been sent.');</script>";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user["password"])) 
    {
    if ($user['status'] !== 'active') {
        die("Account not active.");
    }
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user"] = $user["username"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["mail"] = $user["mail"];

    header("Location: ../index.php");
    exit;
    } 
    
else {
    $error = "User not found or wrong password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Login</title>
</head>
<body>
    <h1>Login</h1>
    <p>Please login to access the dashboard</p>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
        <br><br>
        <a href="forgotPassword.php">Forgot password?</a><br>
        <a href="sInscrire.php">Sign up</a>

    </form>

    <p style="color:red"><?= $error ?></p>
</body>
</html>
