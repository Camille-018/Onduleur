<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include_once '../config.php';

require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/config.php';  // tes constantes MAIL_...

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
    $mail->addAddress(MAIL_TO);

    $mail->Subject = "TEST MAIL PHPMailer";
    $mail->Body    = "Ceci est un test d'envoi de mail via PHPMailer.";

    $mail->send();
    echo "Mail envoyé avec succès !";
} catch (Exception $e) {
    echo "Erreur mail : {$mail->ErrorInfo}";
}
?>