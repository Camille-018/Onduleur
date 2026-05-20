<?php
// validerInscription.php : page pour valider/refuser une inscription, accessible via le lien email

// PHPMailer est utilisé pour envoyer les mails
require_once '../config/config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// récupère les paramètres
$action = $_GET['action'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("Invalid ID.");
$sig    = $_GET['sig'] ?? null;

if (!$action || !$id || !$sig) {
    die("Requete invalide.");
}

// vérifie la signature (hash)
$expectedSig = hash_hmac('sha256', $id, SIGNATURE_SECRET);
if (!hash_equals($expectedSig, $sig)) {
    die("Signature invalide.");
}

// récupère l'utilisateur
$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE id = ?
      AND status = 'pending'
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Compte déjà traité ou n'existe pas.");
}

// décide de l'action (accepter/refuser) et met à jour la base
if ($action === 'accept') {
    $stmt = $pdo->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->execute([$user['id']]);

    $contentHtml = "
    <p>Bonjour <strong>{$user['username']}</strong>,</p>
    <p>Votre compte a été validé.<br>Vous pouvez maintenant vous connecter au tableau de bord:</p>
    <p>Connexion: <a href='http://onduleur'>Tableau de bord</a></p>
    <p><i>Message automatique, ne pas répondre.</i></p>
    ";
    $subject = "Compte activé";

} elseif ($action === 'acceptAdmin') {
    $stmt = $pdo->prepare("UPDATE users SET status='active', role='admin' WHERE id=?");
    $stmt->execute([$user['id']]);

    $contentHtml = "
    <p>Bonjour <strong>{$user['username']}</strong>,</p>
    <p>Votre compte a été validé. Vous êtes maintenant un admin.<br>
    <i><strong>En tant qu'admin, vous pourrez modifier les seuils d'alerte.</strong></i>
    Vous pouvez maintenant vous connecter au tableau de bord:</p>
    <p>Connexion: <a href='http://onduleur'>Tableau de bord</a></p>
    <p><i>Message automatique, ne pas répondre.</i></p>
    ";
    $subject = "Compte activé en tant qu'admin";

} elseif ($action === 'refuse') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$user['id']]);

    $contentHtml = "
    <p>Bonjour <strong>{$user['username']}</strong>,</p>
    <p>Votre demande d'inscription a été refusée.<br>Si vous pensez que c'est une erreur, contactez un administrateur.</p>
    <p>Connexion: <a href='http://onduleur'>Tableau de bord</a></p>
    <p><i>Message automatique, ne pas répondre.</i></p>
    ";
    $subject = "Inscription refusée";

} else {
    die("Action inconnue.");
}

// 2️⃣ envoie un mail à l'utilisateur concernant la décision
$mailUser = new PHPMailer(true);
$mailUser->isSMTP();
$mailUser->Host = MAIL_HOST;
$mailUser->SMTPAuth = true;
$mailUser->Username = MAIL_USERNAME;
$mailUser->Password = MAIL_PASSWORD;
$mailUser->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mailUser->Port = MAIL_PORT;

$mailUser->setFrom(MAIL_FROM, MAIL_FROM_NAME);
$mailUser->addAddress($user['mail']); // adresse email de l'utilisateur
$mailUser->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid'); // logo en ligne
$mailUser->isHTML(true);
$mailUser->Subject = $subject;
$mailUser->Body = mailTemplate($subject, $contentHtml);

try {
    $mailUser->send();
} catch (Exception $e) {
    die("Erreur lors de l'envoi du mail: " . $mailUser->ErrorInfo);
}

// 3️⃣ affiche une alerte puis ferme la fenêtre (pour l'admin)
$message = ($action === 'refuse') 
    ? "Demande refusée." 
    : ($action === 'acceptAdmin' ? "Compte activé en tant qu'admin" : "Compte activé avec succès");

echo "<script>
alert(" . json_encode($message) . ");
window.close();
</script>";

