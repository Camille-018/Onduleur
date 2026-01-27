<!-- verifierAlerte.php: regarder la table donnees, inserer les problemes dans alertes, envoyer une notif (mail/sms) -->
 <?php
require_once '../config.php';

//envoyer une alerte par mail
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
$mailMessages = [];

//recupérer les mails des admins
function getMailsAdmins(PDO $pdo) {
    $stmt = $pdo->prepare("
        SELECT mail 
        FROM users 
        WHERE role = 'admin'
          AND mail IS NOT NULL
          AND mail != ''
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function envoyerMailAlerte($type, $messageAlerte, $id, $recorded_at, $ups_id, $pdo) {
    if (!MAIL_ENABLED) {
        return "MAIL SIMULÉ : Alerte Onduleur : $type";
    }

    $sujet = "Alerte Onduleur : $type";
    $message  = "ALERTE Onduleur : $type\n\n";
    $message .= "Message : $messageAlerte\n";
    $message .= "ID Collecte : $id\n";
    $message .= "UPS ID : $ups_id\n";
    $message .= "Heure : $recorded_at\n\n";
    $message .= "Historique : http://onduleur/historique/historique.php";

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
        $mail->addAddress(MAIL_FROM);

        $admins = getMailsAdmins($pdo);
        if (empty($admins)) {
            return "Aucun admin à notifier!!! (mail non envoyé)  <br>--> Table users, aucun role 'admin' avec mail renseigné.";
        }

        foreach ($admins as $email) {
            $mail->addBCC($email);
        }

        $mail->Subject = $sujet;
        $mail->Body    = $message;

        $mail->send();
        return "Mail envoyé : $sujet";

    } catch (Exception $e) {
        return "Erreur mail : {$mail->ErrorInfo}";
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

// Charger les seuils depuis config_seuils.json
$configSeuilsFile = __DIR__ . '/../config_seuils.json';
if (file_exists($configSeuilsFile)) {
    $seuils = json_decode(file_get_contents($configSeuilsFile), true);
} else {
    $seuils = [
        'batterieFaible' => 15,
        'surcharge'      => 5.0,
        'coupure'        => 0.5
    ];
}

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


        $msg = envoyerMailAlerte($a['Type'],$a['Message'],$d['id'],$d['timestamp'],$d['ups_id'],$pdo);
        if ($msg) {$mailMessages[] = $msg;}
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Document</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <?php
        echo "<h1>Vérification des alertes</h1>";
        echo "Vérification terminée. $nbAlertes alerte(s) créée(s).<br>";
        foreach ($mailMessages as $msg) 
            {echo "<div class='mail-info'>$msg</div>";}
        echo "<br><hr>";
        echo '<br><a href="../index.php">Aller à l\'accueil</a>';
        echo '<br><a href="alerte.php">Retour aux alertes</a>';
        echo '<br><a href="../historique/historique.php">Aller à l\'Historique</a>';
    ?>
</body>
</html>