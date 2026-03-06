<?php
// config.php: configuration of connexion to database and mail server
$host = 'localhost';
$db   = 'ups_onduleur';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Erreur BDD : ' . $e->getMessage());
}

// for mail sending
define('MAIL_ENABLED', true);

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'ondulateur.alertes@gmail.com');
define('MAIL_PASSWORD', 'cshy fwzk wnis rfid'); // mdp de lapp
define('MAIL_FROM', 'ondulateur.alertes@gmail.com');
define('MAIL_FROM_NAME', 'Onduleur - Alertes');

// for alert signature
define('SIGNATURE_SECRET', 'une_cle_ultra_secrete_a_ne_pas_commit');

// mail template function
function mailTemplate($title, $contentHtml) {
    $logoCid = 'logo_cid';
    return "
    <table style='width:100%; max-width:600px; margin:auto; font-family:Arial,sans-serif; border-collapse:collapse;'>
        <tr>
            <td style='text-align:center; padding:20px 0;'>
                <img src='cid:$logoCid' alt='Company Logo' style='width:150px; max-width:100%; height:auto;'>
            </td>
        </tr>
        <tr>
            <td style='padding:20px; background:#f9f9f9; border-radius:8px;'>
                <h2 style='margin-top:0;'>$title</h2>
                $contentHtml
                <p style='font-style:italic; color:#555; margin-top:20px;'>
                    Attenttion: vous devez être sur le réseau de l'entreprise pour accéder au site web.
                </p>
            </td>
        </tr>
    </table>
    ";
}