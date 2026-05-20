<?php
require_once '../config/config.php';
session_start();

$errors = [];
$success = "";
$showForm = true;

// jeton CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// récupération du token de réinitialisation
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if (!$token) die("Lien de réinitialisation invalide.");

// supprime les tokens expirés
$pdo->exec("DELETE FROM password_resets WHERE expires_at < NOW() AND used_at IS NULL");

// trouve le token correspondant
$stmt = $pdo->query("SELECT * FROM password_resets WHERE used_at IS NULL ORDER BY created_at DESC");
$reset = null;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (password_verify($token, $row['token_hash'])) {
        // vérifie la date d'expiration
        if (strtotime($row['expires_at']) < time()) {
            die("Lien de réinitialisation expiré.");
        }

        // vérifie si le compte est actif
        $stmtUser = $pdo->prepare("SELECT status FROM users WHERE id = :id");
        $stmtUser->execute([':id' => $row['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            die("Compte inactif ou non valide.");
        }

        $reset = $row;
        break;
    }
}

if (!$reset) {
    $message = "Lien de réinitialisation invalide ou déjà utilisé.";
    echo "<script>alert(" . json_encode($message) . "); window.location.href='login.php';</script>";
    exit;
}

// si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // validation CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Requête invalide (CSRF détecté).");
    }

    $newPass = trim($_POST['new_password']);
    $confirmPass = trim($_POST['confirm_password']);

    if (strlen($newPass) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if ($newPass !== $confirmPass) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // change le mot de passe
        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([
            ':pass' => password_hash($newPass, PASSWORD_DEFAULT),
            ':id' => $reset['user_id']
        ]);

        // marque le token comme utilisé
        $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $reset['id']]);

        // déconnexion forcée
        session_unset();
        session_destroy();

        $success = "Votre mot de passe a été changé avec succès ! Veuillez vous reconnecter.";
        $showForm = false;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- html : formulaire de changement de mot de passe -->
    <meta charset="UTF-8">
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="stylesheet" href="../style/auth.css">
    <title>UPS - Changer de mot de passe</title>
</head>
<body>
<div class="auth-container">
    <img src="../style/images/cereep.jpg" class="auth-logo">
    <h1>Nouveau mot de passe</h1>
    <p>Choisissez un nouveau mot de passe.</p>

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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="password" name="new_password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <button type="submit">Changer le mot de passe</button>
        </form>
    <?php endif; ?>
</div>
</body>
<script src="../style/message.js"></script>
</html>