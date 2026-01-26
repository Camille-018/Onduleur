<!-- verifierAlerte.php: regarder la table donnees, inserer les problemes dans alertes, envoyer une notif (mail/sms) -->
 <?php
require_once '../config.php';

//envoyer une alerte par mail
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';


function envoyerMailAlerte($type, $messageAlerte, $id, $recorded_at) {
    $sujet = "Alerte Onduleur: $type";
    $message = "ALERTE Onduleur : $type\n\n";
    $message .= "Message : $messageAlerte\n";
    $message .= "ID Collecte : $id\n";
    $message .= "Heure de la collecte : $recorded_at\n\n";
    $message .= "Pour plus de détails, consultez l'historique : http://onduleur/historique/historique.php";

    if (!MAIL_ENABLED) {
        // Mode simulation (tests)
        echo "MAIL SIMULÉ : $sujet - $messageAlerte<br>";
        return;
    }

    $mail = new PHPMailer(true);

    try {
        // Utilisation du SMTP Gmail (voir config.php)
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress(MAIL_TO);

        $mail->Subject = $sujet;
        $mail->Body    = $message;

        $mail->send();
        echo "Mail envoyé : $sujet<br>";
    } catch (Exception $e) {
        echo "Erreur mail : {$mail->ErrorInfo}<br>";
    }
}


// Récupérer toutes les collectes qui n'ont pas encore généré d'alerte

$stmt = $pdo->query("
    SELECT * FROM ups_history dh
    WHERE NOT EXISTS (
        SELECT 1 FROM Alertes a
        WHERE a.idCollecte = dh.id
    )

");

$donnees = $stmt->fetchAll();

// Seuils pour les alertes
$seuils = [
    'batterieFaible' => 15,
    'surcharge'      => 5.0,
    'coupure'        => 0.5 //differentielles
];

// Vérifier chaque collecte et créer des alertes si nécessaire
$nbAlertes = 0;
foreach ($donnees as $d) {
    $alertes_a_creer = [];

    if ($d['battery_charge'] < $seuils['batterieFaible']) {
        $alertes_a_creer[] = ['Type'=>'batterieFaible','Message'=>"Autonomie critique : {$d['battery_charge']}%"];
    }

    if ($d['input_voltage'] > $seuils['surcharge']) {
        $alertes_a_creer[] = ['Type'=>'surcharge','Message'=>"Tension entrée trop élevée : {$d['input_voltage']}V"];
    }

    if ($d['output_voltage'] < $seuils['coupure']) {
        $alertes_a_creer[] = ['Type'=>'coupure','Message'=>"Tension sortie trop basse : {$d['output_voltage']}V"];
    }

    foreach ($alertes_a_creer as $a) {
        $stmt = $pdo->prepare("
            INSERT INTO Alertes (idCollecte, Type, Message, heureAlerte)
            VALUES (:idCollecte, :type, :message, NOW())
        ");
        $stmt->execute([
            ':idCollecte' => $d['id'],
            ':type' => $a['Type'],
            ':message' => $a['Message']
        ]);
        $nbAlertes++;

        // passer l'ID et l'heure à la fonction
        envoyerMailAlerte($a['Type'], $a['Message'], $d['id'], $d['timestamp']);
    }

}

echo "Vérification terminée. $nbAlertes alerte(s) créée(s).";
echo '<br><a href="alerte.php">Retour aux alertes</a>';
echo '<br><a href="../historique/historique.php">Aller à l\'Historique</a>';
echo '<br><a href="../index.php">Retour à l\'accueil</a>';

?>

