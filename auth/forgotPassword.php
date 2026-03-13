<?php
//forgotPassword.php: page to request password reset, check user exists, create token, send mail with reset link

//Php Mailer is used to send mails
require_once '../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['username_or_email']);

    // 1️⃣ Check if user exists by username or email
    $stmt = $pdo->prepare("SELECT id, username, mail FROM users WHERE username = :u OR mail = :u");
    $stmt->execute([':u' => $userInput]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2️⃣ Check if a reset was already requested in the last 5 minutes
        $stmt = $pdo->prepare("
            SELECT created_at 
            FROM password_resets
            WHERE user_id = :id
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([':id' => $user['id']]);
        $lastReset = $stmt->fetch();

        date_default_timezone_set('Europe/Paris'); // UTC+1 (Database)
        if ($lastReset) {
            $diff = time() - strtotime($lastReset['created_at']);
            if ($diff < 300) { // 5 minutes
                $secondsLeft = 300 - $diff;
                $errors[] = "You must wait $secondsLeft seconds before requesting a new password reset.";
            }
        }

        // 3️⃣ If no recent reset, create token, store in DB and send mail
        if (empty($errors)) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = password_hash($token, PASSWORD_DEFAULT);
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Delete any existing resets for this user
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :id");
            $stmt->execute([':id' => $user['id']]);

            // Create new reset entry
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:user_id, :token_hash, :expires_at)
            ");
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $tokenHash,
                ':expires_at' => $expires
            ]);

            // 4️⃣ Send reset email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = MAIL_PORT;

                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($user['mail']);
                $mail->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid');

                $contentHtml= 
                "<table style='width:100%; max-width:600px; margin:auto; font-family:Arial,sans-serif; border-collapse:collapse;'>
                    <tr>
                        <td style='text-align:center; padding:20px 0;'>
                        <img src='cid:logo_cid' alt='Company Logo' style='width:150px; max-width:100%; height:auto;'>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding:20px; background:#f9f9f9; border-radius:8px;'>
                        <p>Bonjour <strong>{$user['username']}</strong>,</p>
                        <p>Un lien de réinitialisation de mot de passe a été demandé pour votre compte.</p>
                        <p>Cliquez sur le lien ci-dessous pour choisir un nouveau mot de passe:</p>
                        <p style='text-align:center; margin:20px 0;'>
                            <a href='http://onduleur/auth/changePassword.php?token=$token' 
                            style='background:#0073e6; color:#fff; text-decoration:none; padding:10px 20px; border-radius:5px; display:inline-block;'>
                            Reinitialiser mon mot de passe
                            </a>
                        </p>
                        <p>Ce lien expire dans <strong>30 minutes</strong>.</p>
                        <p>Si vous n'avez pas demandé de réinitialisation de mot de passe, ignorez cet email.</p>
                        <p style='font-style:italic; color:#555;'>Attention: vous devez être sur le réseau de l'entreprise pour accéder au site web.</p>
                        </td>
                    </tr>
                </table>";

                $mail->isHTML(true);  
                $mail->Body = mailTemplate("Requete de réinitialisation de mot de passe", $contentHtml);
                $mail->Subject = "Requete de réinitialisation de mot de passe";

                $mail->send();

            } catch (Exception $e) {
                echo "Erreur lors de l'envoi de l'email";
            }
        }
    }
    header("Location: login.php?reset=sent");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="stylesheet" href="../style/style.css">
    <title>UPS - Mot de passe oublié</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>Mot de passe oublié</h1>

    <form method="POST">
        <label>Utilisateur ou Email:<br>
            <input type="text" name="username_or_email" required>
        </label><br>
        <button type="submit">Envoyer le lien de réinitialisation</button>
    </form>
    <br><a href="login.php">Aller à la page de connexion</a>
    <br><a href="sInscrire.php">S'inscrire (Pas encore de compte)</a>

    <?php
    if (!empty($errors)) {
        echo "<div class='errors'><ul>";
        foreach ($errors as $e) echo "<li>$e</li>";
        echo "</ul></div>";
    }
    ?>
</body>
</html>
