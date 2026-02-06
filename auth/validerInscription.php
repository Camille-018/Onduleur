<?php
require_once '../config/config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$action = $_GET['action'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("ID invalide.");
$sig    = $_GET['sig'] ?? null;

if (!$action || !$id || !$sig) {
    die("Lien invalide.");
}

// Vérifier la signature
$expectedSig = hash_hmac('sha256', $id, SIGNATURE_SECRET);
if (!hash_equals($expectedSig, $sig)) {
    die("Signature invalide.");
}

// récupérer le user
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE id = ?
      AND status = 'pending'
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Compte déjà traité ou inexistant.");
}

// 1️⃣ définir le message
if ($action === 'accept') {
    $stmt = $pdo->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->execute([$user['id']]);
    $subject = "Inscription acceptée";
    $body = "Bonjour {$user['username']},<br>
    Votre compte a été validé.<br>
    Vous pouvez vous connecter sur le tableau de bord : 
    <a href='http://onduleur'>Connexion</a><br>
    <strong>Attention : vous devez être sur le réseau de l'entreprise pour y accéder.</strong>
    ";

} elseif ($action === 'acceptAdmin') {
    $stmt = $pdo->prepare("UPDATE users SET status='active', role='admin' WHERE id=?");
    $stmt->execute([$user['id']]);
    $subject = "Inscription acceptée (Admin)";
    $body = "Bonjour {$user['username']},<br>
    Votre compte a été validé. Vous êtes Admin.
    Vous pouvez vous connecter sur le tableau de bord: 
    <a href='http://onduleur'>Connexion</a><br>
    <strong>Attention : vous devez être sur le réseau de l'entreprise pour y accéder.</strong>
    <i><strong>En tant qu'admin, vous pourrez changer les seuils d'alertes.</strong></i>
    <i>Note: Message automatique, merci de ne pas répondre.</i>
    ";

} elseif ($action === 'refuse') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$user['id']]);
    $subject = "Inscription refusée";
    $body = "Bonjour {$user['username']},<br>
    Votre demande d'inscription a été refusée.<br>
    Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur.<br>
    Site web : <a href='http://onduleur'>http://onduleur</a><br>
    <i>Note: Message automatique, merci de ne pas répondre.</i>
    ";
} else {
    die("Action inconnue.");
}

// 2️⃣ envoyer le mail au user
$mailUser = new PHPMailer(true);
$mailUser->isSMTP();
$mailUser->Host = MAIL_HOST;
$mailUser->SMTPAuth = true;
$mailUser->Username = MAIL_USERNAME;
$mailUser->Password = MAIL_PASSWORD;
$mailUser->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mailUser->Port = MAIL_PORT;
$mailUser->setFrom(MAIL_FROM, MAIL_FROM_NAME);
$mailUser->addAddress($user['mail']); // mail du user
$mailUser->isHTML(true);
$mailUser->Subject = $subject;
$mailUser->Body = $body;

try {
    $mailUser->send();
} catch (Exception $e) {
    die("Erreur envoi mail au user : " . $mailUser->ErrorInfo);
}

// 3️⃣ message pop-up pour l’admin
$message = ($action === 'refuse') 
    ? "Inscription refusée" 
    : ($action === 'acceptAdmin' ? "Compte activé en tant qu'admin" : "Compte activé avec succès");

echo "<script>
alert(" . json_encode($message) . ");
window.close();
</script>";

