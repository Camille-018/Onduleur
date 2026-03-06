<?php
//verifierAlerte.php : check alerts for a collect and insert into Alertes table if needed, then send mail to admins
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../config/config.php';


/**
 * Check alerts for a collect 
 * Called by collecter.php after each collect
 */
function verifierAlertePourCollecte(PDO $pdo, int $collectId) {

    // ===== Get collect =====
    $stmt = $pdo->prepare("SELECT * FROM ups_history WHERE id = ?");
    $stmt->execute([$collectId]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$d) return;

    // ===== Load thresholds =====
    $seuils = json_decode(file_get_contents(__DIR__ . '/../config/config_seuils.json'), true);
    $alertes_a_creer = [];

    // =========================================================
    // 1️⃣ CHECK STATUS UPS (OFF / BYPASS only)
    // =========================================================
    $statusList = explode(' ', $d['ups_status']);

    if (in_array('OFF', $statusList)) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'off')) {
            $alertes_a_creer[] = [
                'Type' => 'off',
                'Message' => "Onduleur éteint"
            ];
        }
    }

    if (in_array('BYPASS', $statusList)) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'bypass')) {
            $alertes_a_creer[] = [
                'Type' => 'bypass',
                'Message' => "Onduleur en mode de secours"
            ];
        }
    }

    // =========================================================
    // 2️⃣ CHECK thresholds (SEUILS)
    // =========================================================

    if ($d['battery_charge'] !== null && $d['battery_charge'] < $seuils['batterieFaible']) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'batterieFaible')) {
            $alertes_a_creer[] = [
                'Type' => 'batterieFaible',
                'Message' => "Batterie faible : {$d['battery_charge']}%"
            ];
        }
    }

    if ($d['input_voltage'] !== null && $d['input_voltage'] > $seuils['surcharge']&& $d['input_voltage'] >0 ) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'surcharge')) {
            $alertes_a_creer[] = [
                'Type' => 'surcharge',
                'Message' => "Tension d'entrée trop élevée : {$d['input_voltage']}V"
            ];
        }
    }

    if ($d['output_voltage'] !== null && $d['output_voltage'] > 0 && $d['output_voltage'] < $seuils['coupure']) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'coupure')) {
            $alertes_a_creer[] = [
                'Type' => 'coupure',
                'Message' => "Tension de sortie trop basse : {$d['output_voltage']}V"
            ];
        }
    }

    // =========================================================
    // 3️⃣ INSERT ALERTS + MAIL
    // =========================================================
    if (!empty($alertes_a_creer)) {

        foreach ($alertes_a_creer as $a) {

            $stmt = $pdo->prepare("
                INSERT INTO Alertes (idCollecte, ups_id, Type, Message, heureAlerte)
                VALUES (:idCollecte, :ups_id, :type, :message, NOW())
            ");

            $stmt->execute([
                ':idCollecte' => $d['id'],
                ':ups_id'     => $d['ups_id'],
                ':type'       => $a['Type'],
                ':message'    => $a['Message']
            ]);
        }

        envoyerMailAlerte(
            implode(", ", array_column($alertes_a_creer,'Type')),
            implode("<br>", array_column($alertes_a_creer,'Message')),
            $d['id'],
            $d['timestamp'],
            $d['ups_id'],
            $pdo
        );
    }
}


/**
 * Avoid spam: check if an alert of the same type for the same UPS has been created in the last 5 minutes
 */
function alerteRecenteExiste(PDO $pdo, int $upsId, string $type): bool {

    $stmt = $pdo->prepare("
        SELECT id
        FROM Alertes
        WHERE ups_id = :ups_id
        AND Type = :type
        AND heureAlerte > NOW() - INTERVAL 5 MINUTE
        LIMIT 1
    ");

    $stmt->execute([
        ':ups_id' => $upsId,
        ':type'   => $type
    ]);

    return (bool) $stmt->fetch();
}


/**
 * Get admins emails from database
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
 * Send alert email to admins
 */
function envoyerMailAlerte($type, $messageAlerte, $id, $recorded_at, $ups_id, $pdo) {

    if (!MAIL_ENABLED) {
        return "MAIL SIMULATION : Onduleur Alerte : $type";
    }

    $messageHtml = "
        <p><strong>Onduleur Alerte: $type</strong></p>
        <p>$messageAlerte</p>
        <p>ID Collecte: $id<br>UPS ID: $ups_id<br>Date: $recorded_at</p>
        <p>
            Historique: <a href='http://onduleur/historique/historique.php'>Aller à l'historique</a><br>
            Collecte spécifique: <a href='http://onduleur/historique/valeurSpecifique.php?colonne=id&valeur=$id'>Aller à la collecte spécifique</a>
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
        $mail->isHTML(true);
        $mail->Subject = "Onduleur Alerte: $type";
        $mail->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid');
        $mail->Body = mailTemplate("Onduleur Alerte: $type", $messageHtml);


        $admins = getMailsAdmins($pdo);
        foreach ($admins as $email) {
            $mail->addBCC($email);
        }

        $mail->send();
        return "Mail envoyé aux admins : $type";

    } catch (Exception $e) {
        return "Erreur mail: {$mail->ErrorInfo}";
    }
}
