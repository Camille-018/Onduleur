<?php
require_once '../config/config.php';

$errors = [];
$success = "";
$showForm = true;

// Récupérer username et mot temporaire depuis GET ou POST
$username = $_GET['u'] ?? ($_POST['username'] ?? '');
$tempPassword = $_GET['temp'] ?? ($_POST['temp_password'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = trim($_POST['new_password']);
    $confirmPass = trim($_POST['confirm_password']);

    // 1️⃣ Récupérer l'utilisateur
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $errors[] = "No user found with that username.";
    } else {
        // 2️⃣ Vérifier que le mot temporaire correspond
        if (!password_verify($tempPassword, $user['password'])) {
            $errors[] = "Invalid temporary password.";
        }
    }

    // 3️⃣ Vérifier le nouveau mot de passe
    if (strlen($newPass) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($newPass !== $confirmPass) {
        $errors[] = "Passwords do not match.";
    }

    // 4️⃣ Update si tout ok
    if (empty($errors)) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([':pass' => $hash, ':id' => $user['id']]);

        $success = "Your password has been changed successfully!";
        $showForm = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style/style.css">
    <title>Change Password</title>
</head>
<body>
    <h1>Change your password</h1>

    <?php
    if (!empty($errors)) {
        echo "<div class='errors'><ul>";
        foreach ($errors as $e) echo "<li>$e</li>";
        echo "</ul></div>";
    }

    if ($success) {
        echo "<div class='success'>$success</div>";
        echo "<a href='../auth/login.php'>Go to login</a>";
    }

    if ($showForm) {
    ?>
    <form method="POST">
        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
        <input type="hidden" name="temp_password" value="<?= htmlspecialchars($tempPassword) ?>">

        <label>New Password:<br>
            <input type="password" name="new_password" required>
        </label><br><br>

        <label>Confirm New Password:<br>
            <input type="password" name="confirm_password" required>
        </label><br><br>

        <button type="submit">Change Password</button>
    </form>
    <?php } ?>
</body>
</html>
