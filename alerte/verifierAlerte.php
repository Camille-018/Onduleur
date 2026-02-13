<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../config/config.php';


/**
 * Vérifie les alertes pour UNE collecte
 * Appelé directement après insertion dans ups_history
 */
function verifierAlertePourCollecte(PDO $pdo, int $collectId) {

    // ===== RÉCUP COLLECTE =====
    $stmt = $pdo->prepare("SELECT * FROM ups_history WHERE id = ?");
    $stmt->execute([$collectId]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$d) return;

    // ===== CHARGE SEUILS =====
    $seuils = json_decode(file_get_contents(__DIR__ . '/../config/config_seuils.json'), true);
    $alertes_a_creer = [];

    // =========================================================
    // 1️⃣ CHECK STATUS UPS (OFF / BYPASS uniquement)
    // =========================================================
    $statusList = explode(' ', $d['ups_status']);

    if (in_array('OFF', $statusList)) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'off')) {
            $alertes_a_creer[] = [
                'Type' => 'off',
                'Message' => "UPS powered off"
            ];
        }
    }

    if (in_array('BYPASS', $statusList)) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'bypass')) {
            $alertes_a_creer[] = [
                'Type' => 'bypass',
                'Message' => "UPS in bypass mode"
            ];
        }
    }

    // =========================================================
    // 2️⃣ CHECK SEUILS
    // =========================================================

    if ($d['battery_charge'] !== null && $d['battery_charge'] < $seuils['batterieFaible']) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'batterieFaible')) {
            $alertes_a_creer[] = [
                'Type' => 'batterieFaible',
                'Message' => "Battery low : {$d['battery_charge']}%"
            ];
        }
    }

    if ($d['input_voltage'] !== null && $d['input_voltage'] > $seuils['surcharge']&& $d['input_voltage'] >0 ) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'surcharge')) {
            $alertes_a_creer[] = [
                'Type' => 'surcharge',
                'Message' => "Input voltage too high : {$d['input_voltage']}V"
            ];
        }
    }

    if ($d['output_voltage'] !== null && $d['output_voltage'] > 0 && $d['output_voltage'] < $seuils['coupure']) {
        if (!alerteRecenteExiste($pdo, $d['ups_id'], 'coupure')) {
            $alertes_a_creer[] = [
                'Type' => 'coupure',
                'Message' => "Output voltage too low : {$d['output_voltage']}V"
            ];
        }
    }

    // =========================================================
    // 3️⃣ INSERT ALERTES + MAIL
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
 * Empêche spam : vérifie si une alerte identique récente existe
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
 * Récup mails admins
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
        $mail->isHTML(true);
        $mail->Subject = "UPS Alert: $type";
        $mail->addEmbeddedImage(__DIR__ . '/../style/images/cereep.jpg', 'logo_cid');
        $mail->Body = mailTemplate("UPS Alert: $type", $messageHtml);


        $admins = getMailsAdmins($pdo);
        foreach ($admins as $email) {
            $mail->addBCC($email);
        }

        $mail->send();
        return "Mail Sent";

    } catch (Exception $e) {
        return "Mail error : {$mail->ErrorInfo}";
    }
}
