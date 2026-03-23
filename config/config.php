<?php
// config.php: configuration of connexion to database and mail server
$host = 'localhost';
$db   = 'ups_onduleur'; //name of the database//
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
define('MAIL_CHARSET', 'UTF-8');
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'ondulateur.alertes@gmail.com');
define('MAIL_PASSWORD', 'cshy fwzk wnis rfid'); // app password generated for the email account, not the actual email password
define('MAIL_FROM', 'ondulateur.alertes@gmail.com');
define('MAIL_FROM_NAME', 'Onduleur - CEREEP');

// for alert signature
define('SIGNATURE_SECRET', 'une_cle_ultra_secrete_a_ne_pas_commit');

// mail template function
function mailTemplate($title, $contentHtml) {
    $logoCid = 'logo_cid';
    return "
    <html>
    <head>
    <meta charset='UTF-8'>
    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            color: #1b1f23;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            margin:0; padding:0;
        }
        .mail-container {
            width: 100%;
            max-width: 600px;
            margin: auto;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
        }
        .mail-header { text-align:center; padding:20px 0; }
        .mail-body {
            padding:20px;
            background:#f9f9f9;
            border-radius:8px;
        }
        .mail-body h2 { margin-top:0; }
        .mail-footer { font-style:italic; color:#555; margin-top:20px; }
        .mail-info {
            margin-top:10px;
            padding:8px 12px;
            border-radius:6px;
            font-weight:bold;
            box-shadow:1px 1px 3px rgba(0,0,0,0.1);
            background-color:#ff8c42;
        }
        a.button {
            background:#0073e6;
            color:#fff;
            text-decoration:none;
            padding:10px 20px;
            border-radius:5px;
            display:inline-block;
        }
    </style>
    </head>
    <body>
        <table class='mail-container'>
            <tr>
                <td class='mail-header'>
                    <img src='cid:$logoCid' alt='Company Logo' style='width:150px; max-width:100%; height:auto;'>
                </td>
            </tr>
            <tr>
                <td class='mail-body'>
                    <h2>$title</h2>
                    $contentHtml
                    <p class='mail-footer'>
                        Attention: vous devez être sur le réseau de l'entreprise pour accéder au site web.
                    </p>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}