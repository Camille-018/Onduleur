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
    $message = "<strong>ALERTE Onduleur : $type</strong><br><br>";
    $message .= $messageAlerte; // ton texte déjà formaté en HTML avec <br> et <i>
    $message .= "<br><br>ID Collecte : $id<br>";
    $message .= "UPS ID : $ups_id<br>";
    $message .= "Heure : $recorded_at<br><br>";
    $message .= "Historique : <a href='http://onduleur/historique/historique.php'>lien historique</a><br>";
    $message .= "Collecte avec l'erreur : <a href='http://onduleur/historique/valeurSpecifique.php?colonne=id&valeur=$id'>lien spécifique</a>";


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
        $mail->isHTML(true);  
        $mail->Body    = $message;

        $mail->send();
        return "Mail envoyé - $sujet";

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

    if (!empty($alertes_a_creer)) 
    {
        // 1) Insert toutes les alertes dans la BDD
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
        }

        // 2) Préparer le mail unique avec toutes les alertes de cette collecte
        $typeList = implode(", ", array_column($alertes_a_creer, 'Type'));

        $messageList = "";
        foreach ($alertes_a_creer as $a) {
            switch ($a['Type']) {
                case 'batterieFaible':
                    $messageList .= "- batterieFaible -> {$a['Message']} (<i>{$seuils['batterieFaible']}% = seuil batterieFaible</i>)<br>";
                    break;

                case 'surcharge':
                    $messageList .= "- surcharge -> {$a['Message']} (<i>{$seuils['surcharge']}V = seuil surcharge</i>)<br>";
                    break;

                case 'coupure':
                    $messageList .= "- coupure -> {$a['Message']} (<i>{$seuils['coupure']}V = seuil coupure</i>)<br>";
                    break;
            }
        }

        // 3) Envoyer le mail
        $msg = envoyerMailAlerte(
            $typeList,
            $messageList,
            $d['id'],
            $d['timestamp'],
            $d['ups_id'],
            $pdo);
        $mailMessages[] = $msg;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Onduleur - Vérifier alertes</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <?php
        echo "<h1>Vérification des alertes</h1>";
        echo '<br><a href="../index.php">Aller à l\'accueil</a>';
        echo '<br><a href="../historique/historique.php">Aller à l\'Historique</a>';
        echo '<br><a href="alerte.php">Retour aux alertes</a>';
        echo "<br><hr>";
        echo "Vérification terminée. $nbAlertes alerte(s) créée(s).<br>";
        foreach ($mailMessages as $msg) 
            {echo "<div class='mail-info'>$msg</div>";}

    ?>
</body>
</html>