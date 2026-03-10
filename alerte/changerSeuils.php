<!-- changerSeuils.php: admin only
1) check if the user is admin
2) form to change the thresholds
3) compare old and new thresholds to display a message of what has changed
4) save the new thresholds in a json file (config/config_seuils.json)--> 

<?php
require_once __DIR__ . '/../auth/authCheck.php';

// 1 - check if the user is admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Accès refusé : seuls les admins peuvent changer les seuils.');
            window.location.href = '../index.php';
          </script>";
    exit;
}

// 2 - form to change the thresholds
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //  thresholds perviously saved in json file (or default values if file doesn't exist)
    $anciensSeuils = file_exists('../config/config_seuils.json')
        ? json_decode(file_get_contents('../config/config_seuils.json'), true)
        : [
            'batterieFaible' => 15,
            'surcharge' => 5.0,
            'coupure' => 0.5
        ];

    // new thresholds from form
    $nouveauxSeuils = [
        'batterieFaible' => floatval($_POST['batterieFaible']),
        'surcharge'      => floatval($_POST['surcharge']),
        'coupure'        => floatval($_POST['coupure'])
    ];

    // save new thresholds in json file
    file_put_contents('../config/config_seuils.json', json_encode($nouveauxSeuils));

    // compare old and new thresholds to display a message of what has changed
    $changements = [];
    foreach ($nouveauxSeuils as $cle => $valeur) {
        if ($anciensSeuils[$cle] != $valeur) {
            $changements[] = "- " . ucfirst($cle) . " : {$anciensSeuils[$cle]} → {$valeur}";
        }
    }

    // message to display 
    if ($changements) {
        $_SESSION['message_seuils'] = "<u>Seuils modifiés :</u><br>" . implode('<br>', $changements);
    } else {
        $_SESSION['message_seuils'] = "Aucun seuil n’a été modifié.";
    }
    header('Location: changerSeuils.php');
    exit;
}

// load current thresholds to display in form
if (file_exists('../config/config_seuils.json')) {
    $seuils = json_decode(file_get_contents('../config/config_seuils.json'), true);
} else {
    $seuils = [
        'batterieFaible' => 15,
        'surcharge'      => 5.0,
        'coupure'        => 0.5
    ];
}

// message to display after form submission
$message = $_SESSION['message_seuils'] ?? '';
unset($_SESSION['message_seuils']);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Modifier les seuils</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>Modifier les seuils d'alerte</h1>
    <a href="../index.php">Aller à l'acceuil</a><br>
    <a href="../alerte/alerte.php">Aller aux alertes</a><br>
    <a href="../historique/historique.php">Aller à l'historique</a><br><br>
    <hr>

    <h2>Changer les seuils</h2>
    <p>Attention: Les valeurs de seuils que vous modifier doivent être cohérente. (Sinon vous allez recevoir des alertes régulièrement)<p>
    <form method="post">
         <label for="batterieFaible">Batterie faible <i>(% trop bas)</i>:</label>
        <input type="number" id="batterieFaible" name="batterieFaible" value="<?= $seuils['batterieFaible'] ?>" step="0.1" style="width:60px; font-size:13px;">%<br>

        <label for="surcharge">Surchage <i> (= Tension d'entrée trop élevée)</i>:</label>
        <input type="number" id="surcharge" name="surcharge" value="<?= $seuils['surcharge'] ?>" step="0.1" style="width:60px; font-size:13px;">V<br>

        <label for="coupure">Coupure <i> (= Tension de sortie trop basse)</i>:</label>
        <input type="number" id="coupure" name="coupure" value="<?= $seuils['coupure'] ?>" step="0.1" style="width:60px; font-size:13px;">V<br>

        <input type="submit" value="Mettre à jour les seuils">
    </form>
    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>
</body>
</html>