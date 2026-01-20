<?php
// config.php: configuration de la connexion à la base de données
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

// pour les notifications par mail/sms
define('MAIL_ENABLED', true);

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'ondulateur.alertes@gmail.com');
define('MAIL_PASSWORD', 'cshy fwzk wnis rfid'); // mdp de lapp
define('MAIL_FROM', 'ondulateur.alertes@gmail.com');
define('MAIL_FROM_NAME', 'Onduleur - Alertes');
define('MAIL_TO', 'villemin.camille18@gmail.com');