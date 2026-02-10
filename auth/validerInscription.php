<?php
// validerInscription.php: Page to validate/refuse registration, accessed via email link
require_once '../config/config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_GET['action'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("Invalid ID.");
$sig    = $_GET['sig'] ?? null;

if (!$action || !$id || !$sig) {
    die("Invalid request.");
}

// check signature (hash)
$expectedSig = hash_hmac('sha256', $id, SIGNATURE_SECRET);
if (!hash_equals($expectedSig, $sig)) {
    die("Invalid signature.");
}

// get the user
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE id = ?
      AND status = 'pending'
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Account already processed or does not exist.");
}

// 1️⃣ decide action (accept/refuse) and update database
if ($action === 'accept') {
    $stmt = $pdo->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->execute([$user['id']]);

    $contentHtml = "
    <p>Hello <strong>{$user['username']}</strong>,</p>
    <p>Your account has been validated.<br>You can now log in to the dashboard:</p>
    <p>Login: <a href='http://onduleur'>Dashboard</a></p>
    <p><i>Automatic message, do not reply.</i></p>
    ";
    $subject = "Account activated";

} elseif ($action === 'acceptAdmin') {
    $stmt = $pdo->prepare("UPDATE users SET status='active', role='admin' WHERE id=?");
    $stmt->execute([$user['id']]);

    $contentHtml = "
    <p>Hello <strong>{$user['username']}</strong>,</p>
    <p>Your account has been validated. You are now an admin.<br>
    <i><strong>As an admin, you will be able to change alert thresholds.</strong></i>
    You can now log in to the dashboard:</p>
    <p>Login: <a href='http://onduleur'>Dashboard</a></p>
    <p><i>Automatic message, do not reply.</i></p>
    ";
    $subject = "Account activated as admin";

} elseif ($action === 'refuse') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$user['id']]);

    $contentHtml = "
    <p>Hello <strong>{$user['username']}</strong>,</p>
    <p>Your registration request has been refused.<br>If you think this is an error, contact an administrator.</p>
    <p>Login: <a href='http://onduleur'>Dashboard</a></p>
    <p><i>Automatic message, do not reply.</i></p>
    ";
    $subject = "Registration refused";

} else {
    die("Unknown action.");
}

// 2️⃣ send mail to user about the decision
$mailUser = new PHPMailer(true);
$mailUser->isSMTP();
$mailUser->Host = MAIL_HOST;
$mailUser->SMTPAuth = true;
$mailUser->Username = MAIL_USERNAME;
$mailUser->Password = MAIL_PASSWORD;
$mailUser->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mailUser->Port = MAIL_PORT;

$mailUser->setFrom(MAIL_FROM, MAIL_FROM_NAME);
$mailUser->addAddress($user['mail']); // mail of user
$mailUser->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid'); // logo inline
$mailUser->isHTML(true);
$mailUser->Subject = $subject;
$mailUser->Body = mailTemplate($subject, $contentHtml);

try {
    $mailUser->send();
} catch (Exception $e) {
    die("Error sending mail to user: " . $mailUser->ErrorInfo);
}

// 3️⃣ popup message and close window (for admin)
$message = ($action === 'refuse') 
    ? "Registration refused" 
    : ($action === 'acceptAdmin' ? "Account activated as admin" : "Account activated successfully");

echo "<script>
alert(" . json_encode($message) . ");
window.close();
</script>";

