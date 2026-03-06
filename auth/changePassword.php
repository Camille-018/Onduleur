<?php
//changePassword.php: page to change password after clicking reset link, validate token, update password
require_once '../config/config.php';

$errors = [];
$success = "";
$showForm = true;

// Get token from GET or POST (hidden input)
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (!$token) {
    die("Lien de réinitialisation invalide.");
}

// Find the reset request matching the token (hash)
$stmt = $pdo->prepare("
    SELECT * FROM password_resets
    WHERE used_at IS NULL
");
$stmt->execute();

$reset = null;

// We compare the provided token with the hashed tokens in the database using password_verify, and also check expiration
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (password_verify($token, $row['token_hash'])) {
        if (strtotime($row['expires_at']) > time()) {
            $reset = $row;
        }
        break;
    }
}

if (!$reset) {
    die("Lien de réinitialisation invalide ou expiré.");
}

// If form submitted, validate and update password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newPass = trim($_POST['new_password']);
    $confirmPass = trim($_POST['confirm_password']);

    // Validation
    if (strlen($newPass) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if ($newPass !== $confirmPass) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {

        // Update password
        $hash = password_hash($newPass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([
            ':pass' => $hash,
            ':id' => $reset['user_id']
        ]);

        // Mark reset as used
        $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $reset['id']]);

        $success = "Votre mot de passe a été changé avec succès!";
        $showForm = false;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style/style.css">
    <title>Onduleur - Changer de mot de passe</title>
</head>
<body>
    <h1>Changer ton mot de passe</h1>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">

    <?php
    // Display errors or success message
    if (!empty($errors)) {
        echo "<div class='errors'><ul>";
        foreach ($errors as $e) echo "<li>$e</li>";
        echo "</ul></div>";
    }

    if ($success) {
        echo "<div class='success'>$success</div>";
        echo "<a href='../auth/login.php'>Go to login</a>";
    }

    // Show form if no success yet
    if ($showForm) {
    ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label>Nouveau mot de passe:<br>
            <input type="password" name="new_password" required>
        </label><br><br>

        <label>Confirmer le nouveau mot de passe:<br>
            <input type="password" name="confirm_password" required>
        </label><br><br>

        <button type="submit">Changer le mot de passe</button>
    </form>
    <?php } ?>
</body>
</html>
