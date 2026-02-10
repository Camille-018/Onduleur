<?php
require_once '../config/config.php';

$errors = [];
$success = "";
$showForm = true;

// Récupérer token
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (!$token) {
    die("Invalid reset link.");
}

// Chercher reset valide
$stmt = $pdo->prepare("
    SELECT * FROM password_resets
    WHERE used_at IS NULL
");
$stmt->execute();

$reset = null;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (password_verify($token, $row['token_hash'])) {
        if (strtotime($row['expires_at']) > time()) {
            $reset = $row;
        }
        break;
    }
}

if (!$reset) {
    die("Invalid or expired reset link.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newPass = trim($_POST['new_password']);
    $confirmPass = trim($_POST['confirm_password']);

    // Validation
    if (strlen($newPass) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($newPass !== $confirmPass) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        // Update password
        $hash = password_hash($newPass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([
            ':pass' => $hash,
            ':id' => $reset['user_id']
        ]);

        // Marquer token utilisé
        $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $reset['id']]);

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
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">

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
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

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
