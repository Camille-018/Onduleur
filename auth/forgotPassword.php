<?php
require_once '../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';

$msg = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['username_or_email']);

    // 1️⃣ Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, mail FROM users WHERE username = :u OR mail = :u");
    $stmt->execute([':u' => $userInput]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // rate limit
        $stmt = $pdo->prepare("
            SELECT created_at 
            FROM password_resets
            WHERE user_id = :id
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([':id' => $user['id']]);
        $lastReset = $stmt->fetch();

        if ($lastReset) {
            $diff = time() - strtotime($lastReset['created_at']);
            if ($diff > 300) { // 300 secondes = 5 minutes
                $secondsLeft = 300 - $diff;
                $errors[] = "You must wait " . $secondsLeft . " seconds before requesting a new password reset.";
            }
        }

        if (empty($errors)) {
            // 2️⃣ Générer token
            $token = bin2hex(random_bytes(32));
            $tokenHash = password_hash($token, PASSWORD_DEFAULT);
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // 3️⃣ Supprimer anciens resets
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :id");
            $stmt->execute([':id' => $user['id']]);

            // 4️⃣ Créer reset
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:user_id, :token_hash, :expires_at)
            ");

            $stmt->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $tokenHash,
                ':expires_at' => $expires
            ]);

            // 5 Send mail
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
                $mail->isHTML(true);
                $mail->Subject = "Password reset";
                $mail->Body = "
                Hello {$user['username']},<br>
                A password reset was requested for your account.<br>
                Click the link below to choose a new password:<br>
                <a href='http://onduleur/auth/changePassword.php?token=$token'>
                Reset my password
                </a><br><br>
                This link expires in <strong>30 minutes</strong>.<br>
                If you didn't request this, ignore this email.<br><br>
                <strong><i>Warning: you must be on the company's network to access the dashboard.</i></strong>
                ";

                $mail->send();
                $msg = true;
        } catch (Exception $e) {
            $errors[] = "Mail could not be sent.";
        } 
    }
}
else {
    $msg=true;
}
if ($msg) {
        header("Location: login.php?reset=sent");
        exit;
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style/style.css">
    <title>Forgot Password</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>Forgot Password</h1>

    <form method="POST">
        <label>Username or Email:<br>
            <input type="text" name="username_or_email" required>
        </label><br>
        <button type="submit">Send Reset Link</button>
    </form>
    <br><a href="login.php">Go to login</a>
    <br><a href="sInscrire.php">Sign up (No account yet)</a>

    <?php
    if (!empty($errors)) {
        echo "<div class='errors'><ul>";
        foreach ($errors as $e) echo "<li>$e</li>";
        echo "</ul></div>";
    }
    ?>
</body>
</html>
