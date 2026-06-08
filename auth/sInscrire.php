<?php
// sInscrire.php : page d'inscription qui crée un utilisateur en attente et envoie un e-mail au gestionnaire pour validation

// PHPMailer est utilisé pour envoyer les e-mails
require_once '../config/config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = "";
$error = "";

// message de succès après redirection depuis validerInscription
if (isset($_GET['success'])) {
    $success = "Demande envoyée.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $mail     = trim($_POST['mail']);
    $password = $_POST['password'];

    // validation basique
    if (strlen($username) < 3 || strlen($username) > 50) {
        $error = "L'Utilisateur Invalide (3 < user < 50)";
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (strlen($password) < 8) {
        $error = "Mot de passe trop court (<8).";
    } else {

        // 1️⃣ Vérifie si le nom d'utilisateur ou l'e-mail existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR mail = ?");
        $stmt->execute([$username, $mail]);
        if ($stmt->fetch()) {
            header("Location: sInscrire.php?error=exists");
            exit;
        } else {
            // 2️⃣ Tout est valide → hache le mot de passe
            $role = 'user';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 3️⃣ Insère l'utilisateur en base avec le statut en attente
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, mail, role, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$username, $passwordHash, $mail, $role]);
            $userId = $pdo->lastInsertId();

            // Génération des signatures pour les liens de validation
            // UPS : local (onduleur = UPS en français)
            $signature = hash_hmac('sha256', $userId, SIGNATURE_SECRET);
            $lienAccept = "http://onduleur/auth/validerInscription.php?action=accept&id=$userId&sig=$signature";
            $lienRefuse = "http://onduleur/auth/validerInscription.php?action=refuse&id=$userId&sig=$signature";
            $lienAcceptAdmin = "http://onduleur/auth/validerInscription.php?action=acceptAdmin&id=$userId&sig=$signature";


            // 4️⃣ Envoie l'e-mail au gestionnaire
            $mailObj = new PHPMailer(true);
            $mailObj->isSMTP();
            $mailObj->Host = MAIL_HOST;
            $mailObj->SMTPAuth = true;
            $mailObj->Username = MAIL_USERNAME;
            $mailObj->Password = MAIL_PASSWORD;
            $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailObj->Port = MAIL_PORT;
            $mailObj->CharSet = MAIL_CHARSET;
            $mailObj->Encoding = MAIL_ENCODING;

            $mailObj->setFrom(MAIL_FROM, MAIL_FROM_NAME);
####################### Go to config/config.php/L36 ##################################
            $mailObj->addAddress(GESTIONNAIRE_EMAIL);
######################################################################################
            $mailObj->isHTML(true);
            $contentHtml = "
            Username: $username<br>
            Email: $mail<br><br>
            <h3>Actions:</h3>
            <a href='$lienAccept' style='background:#28a745;color:#fff;padding:8px 15px;border-radius:5px;text-decoration:none;'>
                <span style='color:#ffff00;'>✅</span> Accept
            </a><br><br>

            <a href='$lienAcceptAdmin' style='background:#F4AA0B;color:#000;padding:8px 15px;border-radius:5px;text-decoration:none;'>
                <span style='color:#ff6600;'>⭐</span> Accept as Admin
            </a><br><br>

            <a href='$lienRefuse' style='background:#C82909;color:#fff;padding:8px 15px;border-radius:5px;text-decoration:none;'>
                <span style='color:#ffcdd2;'>❌</span> Refuse
            </a>
            ";

            $mailObj->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid');
            $mailObj->Body = mailTemplate("Registration Request", $contentHtml);
            $mailObj->Subject = "Demande d'inscription - CEREEP - UPS";

            try {
                $mailObj->send();
                // Redirige avec un message de succès (pour éviter la resoumission du formulaire au rafraîchissement)
                header("Location: sInscrire.php?success=1");
                exit;
            } catch (Exception $e) {
                $error = "Error sending email: " . $mailObj->ErrorInfo;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- html : formulaire d'inscription -->
    <meta charset="UTF-8">
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="stylesheet" href="../style/auth.css">
    <title>UPS - S'inscrire</title>
</head>
<!-- pop up d'erreur si utilisateur ou email existe déjà -->
<?php if (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
    <script>
        alert("Utilisateur ou email déjà utilisé.");
    </script>
<?php endif; ?>
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
<script src="../style/message.js"></script>
</html>
