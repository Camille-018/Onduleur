<?php
require_once '../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $mail     = trim($_POST['mail']);

    if (strlen($username) < 3 || strlen($username) > 50) {
    die("Nom d'utilisateur invalide.");
    }

    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        die("Adresse mail invalide.");
    }

    if (strlen($_POST['password']) < 8) {
        die("Mot de passe trop court.");
    }


    $role = 'user';
    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
    SELECT id FROM users
    WHERE username = ? OR mail = ?
    ");
    $stmt->execute([$username, $mail]);

    if ($stmt->fetch()) {
        die("Nom d'utilisateur ou mail déjà utilisé.");
}




    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, mail, role, status )
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$username, $passwordHash, $mail, $role]);

    $userId = $pdo->lastInsertId();

    // signature basée sur l'id
    $signature = hash_hmac('sha256', $userId, SIGNATURE_SECRET);

    $lienAccept = "http://onduleur/auth/validerInscription.php?action=accept&id=$userId&sig=$signature";
    $lienRefuse = "http://onduleur/auth/validerInscription.php?action=refuse&id=$userId&sig=$signature";


    $mailObj = new PHPMailer(true);
    $mailObj->isSMTP();
    $mailObj->Host = MAIL_HOST;
    $mailObj->SMTPAuth = true;
    $mailObj->Username = MAIL_USERNAME;
    $mailObj->Password = MAIL_PASSWORD;
    $mailObj->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailObj->Port = MAIL_PORT;

    $mailObj->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mailObj->addAddress("X@domaine.fr");

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

    $mailObj->send();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Onduleur - Inscription</title>
</head>
<body>
    <h1>Inscription</h1>
    <p>Veuillez vous inscrire pour accéder au tableau de bord</p>
    
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h2>Formulaire d'inscription</h2>
    <p><i>Un mail sera envoye a X pour valider votre compte</i></p>
    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
        <input type="password" name="password" placeholder="Mot de passe" required><br>
        <input type="email" name="mail" placeholder="Adresse mail" required><br>
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>