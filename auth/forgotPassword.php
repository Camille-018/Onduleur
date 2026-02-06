<?php
require_once '../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';

$msg = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['username_or_email']);

    // 1️⃣ Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, mail FROM users WHERE username = :u OR mail = :u");
    $stmt->execute([':u' => $userInput]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $errors[] = "No user found with that username or email.";
    } else {
        // 2️⃣ Generate temporary password
        $tempPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $tempHash = password_hash($tempPassword, PASSWORD_DEFAULT);

        // 3️⃣ Update user's password with temporary password (no flag)
        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([':pass' => $tempHash, ':id' => $user['id']]);

        // 4️⃣ Send mail
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
            $mail->Subject = "Temporary password for your account";
            $mail->Body = "
                Hello {$user['username']},<br>
                A temporary password has been generated for your account:<br>
                <strong>$tempPassword</strong><br><br>
                Please <a href='http://onduleur/auth/changePassword.php?u={$user['username']}&temp=$tempPassword'>click here</a> to change it immediately.<br><br>
                If you didn't request this, ignore this email.<br>
                <strong>Warning: you must be on the company's network to access the dashboard.</strong>
            ";

            $mail->send();
            $msg = "A temporary password has been sent to your email.";

        } catch (Exception $e) {
            $errors[] = "Mail could not be sent: {$mail->ErrorInfo}";
        }
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
        </label><br><br>
        <button type="submit">Send Temporary Password</button>
    </form>

    <?php
    if (!empty($errors)) {
        echo "<div class='errors'><ul>";
        foreach ($errors as $e) echo "<li>$e</li>";
        echo "</ul></div>";
    }

    if ($msg) {
        echo "<script>alert('A temporary password has been sent to your email.');</script>";
        header("Location: login.php");
        exit;
    }
    ?>
</body>
</html>
