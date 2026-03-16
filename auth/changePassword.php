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
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="stylesheet" href="../style/auth.css">
    <title>Onduleur - Changer de mot de passe</title>
</head>
<body>
    <div class="auth-container">
        <img src="../style/images/cereep.jpg" class="auth-logo">
        <h1>Nouveau mot de passe</h1>
        <p>Choisissez un nouveau mot de passe pour votre compte</p>

        <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <?= htmlspecialchars($e) ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>

        <div class="auth-links">
            <a href="login.php">Retour à la connexion</a>
        </div>
        <?php endif; ?>

        <?php if ($showForm): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="new_password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <button type="submit">Changer le mot de passe</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
