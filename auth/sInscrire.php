<?php
require_once '../config/config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = "";
$error = "";

// message de succès après redirection
if (isset($_GET['success'])) {
    $success = "Demande envoyée, en attente de validation admin.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $mail     = trim($_POST['mail']);
    $password = $_POST['password'];

    // validations simples
    if (strlen($username) < 3 || strlen($username) > 50) {
        $error = "Nom d'utilisateur invalide.";
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse mail invalide.";
    } elseif (strlen($password) < 8) {
        $error = "Mot de passe trop court.";
    } else {

        // 1️⃣ vérifier si username ou mail existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR mail = ?");
        $stmt->execute([$username, $mail]);

        if ($stmt->fetch()) {
            $error = "Nom d'utilisateur ou mail déjà utilisé.";
        } else {
            // 2️⃣ tout est ok → hash password
            $role = 'user';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 3️⃣ insert en DB
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, mail, role, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$username, $passwordHash, $mail, $role]);
            $userId = $pdo->lastInsertId();

            // signature pour liens accept/refuse
            #### onduleur: local
            $signature = hash_hmac('sha256', $userId, SIGNATURE_SECRET);
            $lienAccept = "http://onduleur/auth/validerInscription.php?action=accept&id=$userId&sig=$signature";
            $lienRefuse = "http://onduleur/auth/validerInscription.php?action=refuse&id=$userId&sig=$signature";

            // 4️⃣ envoi mail à l'admin
            $mailObj = new PHPMailer(true);
            $mailObj->isSMTP();
            $mailObj->Host = MAIL_HOST;
            $mailObj->SMTPAuth = true;
            $mailObj->Username = MAIL_USERNAME;
            $mailObj->Password = MAIL_PASSWORD;
            $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailObj->Port = MAIL_PORT;

            $mailObj->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            ######mettre l'adresse de l'admin ici
            $mailObj->addAddress("erzasu45.008@gmail.com");

            $mailObj->isHTML(true);
            $mailObj->Subject = "Demande d'inscription - $username";
            $mailObj->Body = "
                Nouvelle inscription<br><br>
                User : $username<br>
                Mail : $mail<br>
                Rôle : $role<br><br>
                <a href='$lienAccept'>✅ Accepter</a><br>
                <a href='$lienRefuse'>❌ Refuser</a>
            ";

            try {
                $mailObj->send();
                // redirection pour éviter double soumission
                header("Location: sInscrire.php?success=1");
                exit;
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi du mail : " . $mailObj->ErrorInfo;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style/style.css">
    <title>Onduleur - Inscription</title>
</head>
<body>
    <h1>Inscription</h1>
    <p>Veuillez vous inscrire pour accéder au tableau de bord</p>

    <?php if ($success): ?>
        <p style="color:green"><?= $success ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red"><?= $error ?></p>
    <?php endif; ?>

    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h2>Formulaire d'inscription</h2>
    <p><i>Un mail sera envoyé à l'admin pour valider votre compte</i></p>

    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <input type="email" name="mail" placeholder="Adresse mail" required><br>
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>
