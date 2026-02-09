<!-- verifierAlerte.php: check the ups_history table and create alerts if needed, send a mail to admin -->
 <?php
require_once __DIR__ . '/../auth/authCheck.php';

//send a mail to all admins with the alert info
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
$mailMessages = [];

//get all admin mails from users table
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
        return "MAIL SIMULATION : UPS Alert : $type";
    }

    $sujet = "UPS Alert : $type";
    $message = "<strong>UPS Alert : $type</strong><br><br>";
    $message .= $messageAlerte;
    $message .= "<br><br>ID Collect : $id<br>";
    $message .= "UPS ID : $ups_id<br>";
    $message .= "Timestamp : $recorded_at<br>";
    $message .= "History : <a href='http://onduleur/historique/historique.php'>Go to history</a><br>";
    $message .= "Collect with the alert: <a href='http://onduleur/historique/valeurSpecifique.php?colonne=id&valeur=$id'>Go to the specific collect</a>";
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
            return "No admin to notify!!! (mail not sent)  <br>--> Table users, no 'admin' role with email provided.";
        }

        foreach ($admins as $email) {
            $mail->addBCC($email);
        }

        $mail->Subject = $sujet;
        $mail->isHTML(true);  
        $mail->Body    = $message;

        $mail->send();
        return "Mail Sent - $sujet";

    } catch (Exception $e) {
        return "Mail error : {$mail->ErrorInfo}";
    }
}



// Get all records from ups_history that do not have an alert yet
$stmt = $pdo->query("
    SELECT * FROM ups_history dh
    WHERE NOT EXISTS (
        SELECT 1 FROM Alertes a
        WHERE a.idCollecte = dh.id
    )
");

$donnees = $stmt->fetchAll();

// Load thresholds from config_seuils.json
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

// Check each record against the thresholds and create alerts if needed
$nbAlertes = 0;
foreach ($donnees as $d) {
    $alertes_a_creer = [];

    if ($d['battery_charge'] < $seuils['batterieFaible']) {
        $alertes_a_creer[] = ['Type'=>'batterieFaible','Message'=>"Critical Autonomy : {$d['battery_charge']}%"];
    }

    if ($d['input_voltage'] > $seuils['surcharge']) {
        $alertes_a_creer[] = ['Type'=>'surcharge','Message'=>"Input voltage too high : {$d['input_voltage']}V"];
    }

    if ($d['output_voltage'] < $seuils['coupure']) {
        $alertes_a_creer[] = ['Type'=>'coupure','Message'=>"Output voltage too low : {$d['output_voltage']}V"];
    }

    if (!empty($alertes_a_creer)) 
    {
        // 1) Insert alerts into the Alertes table for this collect
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

        // 2) Prepare the mail content (1 mail per collect, even if multiple alerts)
        // map french → english
        $typeMap = [
            'batterieFaible' => 'Low Battery',
            'surcharge'      => 'Overload',
            'coupure'        => 'Cutoff'
        ];

        // traduire les types pour le sujet
        $typeListEn = implode(", ", array_map(fn($t) => $typeMap[$t] ?? $t, array_column($alertes_a_creer, 'Type')));

        // sujet du mail en anglais
        $sujet = "UPS Alert: $typeListEn";


        $messageList = "";
        foreach ($alertes_a_creer as $a) {
            switch ($a['Type']) {
                case 'batterieFaible':
                    $messageList .= "- LowBattery -> {$a['Message']} (<i><{$seuils['batterieFaible']}% = threshold for LowBattery</i>)<br>";
                    break;

                case 'surcharge':
                    $messageList .= "- Overload -> {$a['Message']} (<i>>{$seuils['surcharge']}V = threshold for overload</i>)<br>";
                    break;

                case 'coupure':
                    $messageList .= "- cutoff -> {$a['Message']} (<i><{$seuils['coupure']}V = threshold for cutoff</i>)<br>";
                    break;
            }
        }

        // 3) Send the mail
        $msg = envoyerMailAlerte(
            $typeListEn, // sujet du mail en anglais
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Check alerts</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <?php
        echo "<h1>Checking alerts</h1>";
        echo '<br><a href="../index.php">Go to home</a>';
        echo '<br><a href="../historique/historique.php">Go to History</a>';
        echo '<br><a href="alerte.php">Go to alerts</a>';
        echo "<br><hr>";
        echo "Checking complete. $nbAlertes alert(s) created.<br>";
        foreach ($mailMessages as $msg) 
            {echo "<div class='mail-info'>$msg</div>";}

    ?>
</body>
</html>