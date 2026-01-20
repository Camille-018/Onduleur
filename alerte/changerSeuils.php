<!-- changerSeuils.php: admin seulement
1) vérifier que l'utilisateur est admin (niveau=3)
2) formulaire pour changer les seuils
3) sauvegarde - json --> 

<?php
require_once '../config.php'; //dans la table "authentification", l'utilisateur admin a le niveau 3
session_start();//en theorie pas besoin: conencter sur index.php
if (!isset($_SESSION['user_id'])) {
     die('Accès refusé. Veuillez vous connecter.');
 }

// vérifier le niveau d'accès
$stmt = $pdo->prepare("SELECT droit FROM authentication WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || $user['droit'] < 3) {
    die('Accès refusé. Niveau d\'accès insuffisant.');
}

// gérer le formulaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batterieFaible = floatval($_POST['batterieFaible']);
    $surcharge      = floatval($_POST['surcharge']);
    $coupure        = floatval($_POST['coupure']);

    // sauvegarder les seuils dans un fichier ou une table (ici fichier pour simplicité)
    $seuils = [
        'batterieFaible' => $batterieFaible,
        'surcharge'      => $surcharge,
        'coupure'        => $coupure
    ];
    file_put_contents('../config_seuils.json', json_encode($seuils));
    $message = 'Seuils mis à jour avec succès.';
} else {
    // charger les seuils existants
    if (file_exists('../config_seuils.json')) {
        $seuils = json_decode(file_get_contents('../config_seuils.json'), true);
    } else {
        $seuils = [
            'batterieFaible' => 15,
            'surcharge'      => 5.0,
            'coupure'        => 0.5
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer les seuils</title>
</head>
<body>
    <h1>Changer les seuils d'alerte</h1>
    <a href="../index.php">Accueil</a><br>
    <a href="../alerte/alerte.php">Alertes</a><br>
    <a href="../historique/historique.php">Historique</a><br><br>

    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="batterieFaible">Batterie faible (en %):</label>
        <input type="number" id="batterieFaible" name="batterieFaible" value="<?= $seuils['batterieFaible'] ?>" step="0.1"><br><br>

        <label for="surcharge">Surcharge (en A):</label>
        <input type="number" id="surcharge" name="surcharge" value="<?= $seuils['surcharge'] ?>" step="0.1"><br><br>

        <label for="coupure">Coupure (en A):</label>
        <input type="number" id="coupure" name="coupure" value="<?= $seuils['coupure'] ?>" step="0.1"><br><br>

        <input type="submit" value="Mettre à jour les seuils">
    </form>
</body>
</html>