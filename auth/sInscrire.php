<?php
// sInscrire.php: sign up page, insert user with pending status, send mail to admin for validation

//PHP Mailer is used to send mails
require_once '../config/config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = "";
$error = "";

// success message after redirection from validerInscription
if (isset($_GET['success'])) {
    $success = "Demande envoyée.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $mail     = trim($_POST['mail']);
    $password = $_POST['password'];

    // basic validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        $error = "L'Utilisateur Invalide (3 < user < 50)";
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (strlen($password) < 8) {
        $error = "Mot de passe trop court (<8).";
    } else {

        // 1️⃣ Check if username or mail alr exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR mail = ?");
        $stmt->execute([$username, $mail]);
        if ($stmt->fetch()) {
            echo '<script>alert("Utilisateur ou email déjà existant.");</script>';
            exit;
        } else {
            // 2️⃣ Everything ok → hash password
            $role = 'user';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 3️⃣ insert in database with pending status
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, mail, role, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$username, $passwordHash, $mail, $role]);
            $userId = $pdo->lastInsertId();

            // signatures for validation links
            #### onduleur: local (onduleur = UPS in french)
            $signature = hash_hmac('sha256', $userId, SIGNATURE_SECRET);
            $lienAccept = "http://onduleur/auth/validerInscription.php?action=accept&id=$userId&sig=$signature";
            $lienRefuse = "http://onduleur/auth/validerInscription.php?action=refuse&id=$userId&sig=$signature";
            $lienAcceptAdmin = "http://onduleur/auth/validerInscription.php?action=acceptAdmin&id=$userId&sig=$signature";


            // 4️⃣ Send mail to 1 admin
            $mailObj = new PHPMailer(true);
            $mailObj->isSMTP();
            $mailObj->Host = MAIL_HOST;
            $mailObj->SMTPAuth = true;
            $mailObj->Username = MAIL_USERNAME;
            $mailObj->Password = MAIL_PASSWORD;
            $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailObj->Port = MAIL_PORT;

            $mailObj->setFrom(MAIL_FROM, MAIL_FROM_NAME);
####################### change mail there #######################
            $mailObj->addAddress("erzasu45.008@gmail.com");
#################################################################
            $mailObj->isHTML(true);
            $contentHtml = "
            Utilisateur: $username<br>
            Mail: $mail<br><br>
            <h3>Actions:</h3>
            <a href='$lienAccept' style='background:#28a745;color:#fff;padding:8px 15px;border-radius:5px;text-decoration:none;'>
                <span style='color:#ffff00;'>✅</span> Accepter
            </a><br><br>

            <a href='$lienAcceptAdmin' style='background:#F4AA0B;color:#000;padding:8px 15px;border-radius:5px;text-decoration:none;'>
                <span style='color:#ff6600;'>⭐</span> Accepter en Admin
            </a><br><br>

            <a href='$lienRefuse' style='background:#C82909;color:#fff;padding:8px 15px;border-radius:5px;text-decoration:none;'>
                <span style='color:#ffcdd2;'>❌</span> Refuser
            </a>
            ";

            $mailObj->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid');
            $mailObj->Body = mailTemplate("Demande d'inscription", $contentHtml);
            $mailObj->Subject = "Demande d'inscription - CEREEP - onduleur";

            try {
                $mailObj->send();
                // redirect with success message (to avoid resending form on refresh)
                header("Location: sInscrire.php?success=1");
                exit;
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi de l'email: " . $mailObj->ErrorInfo;
            }
        }
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
    <title>UPS - S'inscrire</title>
</head>
<body>

    <div class="auth-container">
        <img src="../style/images/cereep.jpg" class="auth-logo">
        <h1>Créer un compte</h1>
        <p>Accès au tableau de bord UPS</p>
        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <p style="font-size:13px;margin-bottom:15px;">
        Un administrateur devra valider votre compte avant l'accès.
        </p>

        <form method="POST">
        <input type="text" name="username" placeholder="Utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe (min 8 caractères)" required>
        <input type="email" name="mail" placeholder="Adresse email" required>
        <button type="submit">Créer le compte</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Déjà un compte ? Se connecter</a>
        </div>
    </div>

</body>
</html>
