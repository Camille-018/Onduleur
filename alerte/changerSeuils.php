<!-- changerSeuils.php: for admins only
1) Check that the user is an admin
2) Display the threshold editing form
3) Compare the old and new thresholds to display a message about the changes
4) Save the new thresholds to the JSON file (config/config_seuils.json)--> 

<?php
require_once __DIR__ . '/../auth/authCheck.php';
include __DIR__ . '/../style/navbar.php';

// 1 - check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Accès refusé : seuls les admins peuvent changer les seuils.');
            window.location.href = '../index.php';
          </script>";
    exit;
}

// 2 - Threshold modification form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // thresholds previously saved in the JSON file (or default values if the file does not exist)
    $anciensSeuils = file_exists('../config/config_seuils.json')
        ? json_decode(file_get_contents('../config/config_seuils.json'), true)
        : null;

    if (!is_array($anciensSeuils)) {
        $anciensSeuils = [
            'batterieFaible' => 15,
            'surcharge' => 240,
            'coupure' => 0
        ];
    }

    // new thresholds from the form
    $nouveauxSeuils = [
        'batterieFaible' => floatval($_POST['batterieFaible']),
        'surcharge'      => floatval($_POST['surcharge']),
        'coupure'        => floatval($_POST['coupure'])
    ];

    //  Save the new thresholds to the JSON file
    file_put_contents('../config/config_seuils.json', json_encode($nouveauxSeuils));

    // compare the old and new thresholds to view the details of the changes
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

// loads the current thresholds and displays them in the form
if (file_exists('../config/config_seuils.json')) {
    $seuils = json_decode(file_get_contents('../config/config_seuils.json'), true);
    if (!is_array($seuils)) {
        $seuils = [
            'batterieFaible' => 15,
            'surcharge'      => 240,
            'coupure'        => 0
        ];
    }
} else {
    $seuils = [
        'batterieFaible' => 15,
        'surcharge'      => 5.0,
        'coupure'        => 0.5
    ];
}

// message to display after the form is submitted
$message = $_SESSION['message_seuils'] ?? '';
unset($_SESSION['message_seuils']);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Modifier les seuils</title>
</head>
<body>
    <h1>Modifier les seuils d'alerte</h1>

     <!-- Form for editing thresholds with current values pre-filled and a message displayed after submission -->
    <h2>Changer les seuils</h2>
    <form method="post">
         <label for="batterieFaible">Batterie faible <i>(% trop bas)</i>:</label>
        <input type="number" id="batterieFaible" name="batterieFaible" value="<?= $seuils['batterieFaible'] ?>" step="0.1" style="width:60px; font-size:13px;">%<br>

        <label for="surcharge">Surchage <i> (= Tension d'entrée trop élevée)</i>:</label>
        <input type="number" id="surcharge" name="surcharge" value="<?= $seuils['surcharge'] ?>" step="0.1" style="width:60px; font-size:13px;">V<br>

        <label for="coupure">Coupure <i> (= Tension de sortie trop basse)</i>:</label>
        <input type="number" id="coupure" name="coupure" value="<?= $seuils['coupure'] ?>" step="0.1" style="width:60px; font-size:13px;">V<br>

        <input type="submit" value="Mettre à jour les seuils">
    </form>
    <p class="mail-info">Attention: Les valeurs de seuils que vous modifier doivent être cohérente. (Sinon vous allez recevoir des alertes régulièrement)</p>

    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>
</body>
</html>
