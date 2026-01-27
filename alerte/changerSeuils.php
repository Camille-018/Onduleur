<!-- changerSeuils.php: admin seulement
1) vérifier que l'utilisateur est admin (niveau=3) //verra apres
2) formulaire pour changer les seuils
3) sauvegarde - json --> 

<?php
require_once '../config.php';
session_start();

// gérer le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batterieFaible = floatval($_POST['batterieFaible']);
    $surcharge      = floatval($_POST['surcharge']);
    $coupure        = floatval($_POST['coupure']);

    $seuils = [
        'batterieFaible' => $batterieFaible,
        'surcharge'      => $surcharge,
        'coupure'        => $coupure
    ];
    file_put_contents('../config_seuils.json', json_encode($seuils));

    // créer un message avec les nouvelles valeurs
    $_SESSION['message_seuils'] = "Seuils mis à jour : Batterie faible = {$batterieFaible}%, Surcharge = {$surcharge}V, Coupure = {$coupure}V";
    
    // rediriger pour afficher le message
    header('Location: changerSeuils.php');
    exit;
}

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

// récupérer le message éventuel
$message = '';
if (!empty($_SESSION['message_seuils'])) {
    $message = $_SESSION['message_seuils'];
    unset($_SESSION['message_seuils']);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Changer les seuils</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>Changer les seuils d'alerte</h1>
    <a href="../index.php">Aller à l'accueil</a><br>
    <a href="../alerte/alerte.php">Retour aux Alertes</a><br>
    <a href="../historique/historique.php">Aller à l'Historique</a><br><br>
    <hr>


    <h2>Changer les seuils</h2>
    <form method="post">
         <label for="batterieFaible">Batterie faible <i>(% trop faible)</i>:</label>
        <input type="number" id="batterieFaible" name="batterieFaible" value="<?= $seuils['batterieFaible'] ?>" step="0.1" style="width:60px; font-size:13px;">%<br>

        <label for="surcharge">Surcharge <i> (= Tension entree trop forte)</i>:</label>
        <input type="number" id="surcharge" name="surcharge" value="<?= $seuils['surcharge'] ?>" step="0.1" style="width:60px; font-size:13px;">V<br>

        <label for="coupure">Coupure <i> (= Tension sortie trop faible)</i>:</label>
        <input type="number" id="coupure" name="coupure" value="<?= $seuils['coupure'] ?>" step="0.1" style="width:60px; font-size:13px;">V<br>

        <input type="submit" value="Mettre à jour les seuils">
    </form>
    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>
</body>
</html>