<?php
require_once __DIR__ . '/../auth/authCheck.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';

$mailMessages = [];

/**
 * Récupère les mails admin
 */
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

/**
 * Envoi mail alerte
 */
function envoyerMailAlerte($type, $messageAlerte, $id, $recorded_at, $ups_id, $pdo) {

    if (!MAIL_ENABLED) {
        return "MAIL SIMULATION : UPS Alert : $type";
    }

    $messageHtml = "
        <p><strong>UPS Alert: $type</strong></p>
        <p>$messageAlerte</p>
        <p>ID Collect: $id<br>UPS ID: $ups_id<br>Timestamp: $recorded_at</p>
        <p>
            History: <a href='http://onduleur/historique/historique.php'>Go to history</a><br>
            Specific collect: <a href='http://onduleur/historique/valeurSpecifique.php?colonne=id&valeur=$id'>Go to the specific collect</a>
        </p>
    ";

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

        $mail->isHTML(true);
        $mail->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid');
        $mail->Body = mailTemplate("UPS Alert: $type", $messageHtml);
        $mail->Subject = "UPS Alert: $type";

        $admins = getMailsAdmins($pdo);
        if (empty($admins)) {
            return "No admin to notify";
        }

        foreach ($admins as $email) {
            $mail->addBCC($email);
        }

        $mail->send();
        return "Mail Sent - $type";

    } catch (Exception $e) {
        return "Mail error : {$mail->ErrorInfo}";
    }
}

/**
 * Chargement seuils
 */
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

/**
 * On prend UNIQUEMENT les collectes sans alerte
 */
$stmt = $pdo->query("
    SELECT dh.*
    FROM ups_history dh
    LEFT JOIN Alertes a ON a.idCollecte = dh.id
    WHERE a.idCollecte IS NULL
    ORDER BY dh.timestamp DESC
    LIMIT 200
");

$donnees = $stmt->fetchAll();

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

    if (!empty($alertes_a_creer)) {

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

        // préparation mail
        $typeMap = [
            'batterieFaible' => 'Low Battery',
            'surcharge'      => 'Overload',
            'coupure'        => 'Cutoff'
        ];

        $typeListEn = implode(", ", array_map(
            fn($t) => $typeMap[$t] ?? $t,
            array_column($alertes_a_creer, 'Type')
        ));

        $messageList = "";
        foreach ($alertes_a_creer as $a) {
            $messageList .= "- {$a['Message']}<br>";
        }

        $msg = envoyerMailAlerte(
            $typeListEn,
            $messageList,
            $d['id'],
            $d['timestamp'],
            $d['ups_id'],
            $pdo
        );

        $mailMessages[] = $msg;
    }
}
?>
