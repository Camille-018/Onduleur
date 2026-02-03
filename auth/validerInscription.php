<?php
require_once '../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;

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

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = MAIL_HOST;
$mail->SMTPAuth = true;
$mail->Username = MAIL_USERNAME;
$mail->Password = MAIL_PASSWORD;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = MAIL_PORT;
$mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
$mail->addAddress($user['mail']);
$mail->isHTML(true);

if ($action === 'accept') {

    $stmt = $pdo->prepare("
        UPDATE users
        SET status = 'active'
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);

    $mail->Subject = "Inscription acceptée";
    $mail->Body = "Ton compte est validé. Tu peux te connecter.";

} elseif ($action === 'refuse') {

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);

    $mail->Subject = "Inscription refusée";
    $mail->Body = "Ta demande a été refusée.";

} else {
    die("Action inconnue.");
}

$mail->send();
echo "Traitement terminé.";
?>