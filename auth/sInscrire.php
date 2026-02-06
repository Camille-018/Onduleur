<?php
// sInscrire.php: sign up page, insert user with pending status, send mail to admin for validation
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
    $success = "Request sent, awaiting admin validation.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $mail     = trim($_POST['mail']);
    $password = $_POST['password'];

    // basic validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        $error = "username too short (3) or too long (50).";
    } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password too short (8).";
    } else {

        // 1️⃣ Check if username or mail alr exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR mail = ?");
        $stmt->execute([$username, $mail]);
        if ($stmt->fetch()) {
            echo '<script>alert("Username or email already exists.");</script>';
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
            ###### change mail there
            $mailObj->addAddress("erzasu45.008@gmail.com");

            $mailObj->isHTML(true);
            $mailObj->Subject = "Registration request - $username";
            $mailObj->Body = "
                <h1>New registration request</h1>
                <h2>User Info:</h2>
                Username: $username<br>
                Mail: $mail<br>
                <h2>Actions :</h2>
                <a href='$lienAccept'>✅ - Accept</a><br><br>
                <a href='$lienAcceptAdmin'>⭐ - Accept as Admin</a><br><br>
                <a href='$lienRefuse'>❌ - Refuse</a>
            ";

            try {
                $mailObj->send();
                // redirect with success message (to avoid resending form on refresh)
                header("Location: sInscrire.php?success=1");
                exit;
            } catch (Exception $e) {
                $error = "Error while sending email: " . $mailObj->ErrorInfo;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../style/style.css">
    <title>UPS - Sign Up</title>
</head>
<body>
    <h1>Sign Up</h1>
    <p>Please sign up to access the dashboard</p>

    <?php if (isset($_GET['success'])): ?>
    <script>
        alert("Request sent, waiting for admin validation.");
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['refused'])): ?>
    <script>
        alert("Request refused by admin.");
    </script>
    <?php endif; ?>

    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h2>Sign Up Form</h2>
    <p><i>An email will be sent to the admin to validate your account. <br>
    Then, you will receive a confirmation email about the decision. </i></p>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="email" name="mail" placeholder="Email address" required><br>
        <button type="submit">Sign Up</button>
        <br><br><a href="login.php">Already have an account? Log in</a>
    </form>
</body>
</html>
