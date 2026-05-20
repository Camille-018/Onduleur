<!-- changerSeuils.php : réservé aux admins
1) vérifier que l'utilisateur est admin
2) afficher le formulaire de modification des seuils
3) comparer les anciens et nouveaux seuils pour afficher un message des modifications
4) enregistrer les nouveaux seuils dans le fichier JSON (config/config_seuils.json)--> 

<?php
require_once __DIR__ . '/../auth/authCheck.php';
include __DIR__ . '/../style/navbar.php';

// 1 - vérifier que l'utilisateur est admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
            alert('Accès refusé : seuls les admins peuvent changer les seuils.');
            window.location.href = '../index.php';
          </script>";
    exit;
}

// 2 - formulaire de modification des seuils
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // seuils précédemment enregistrés dans le fichier JSON (ou valeurs par défaut si le fichier n'existe pas)
    $anciensSeuils = file_exists('../config/config_seuils.json')
        ? json_decode(file_get_contents('../config/config_seuils.json'), true)
        : [
            'batterieFaible' => 15,
            'surcharge' => 5.0,
            'coupure' => 0.5
        ];

    // nouveaux seuils depuis le formulaire
    $nouveauxSeuils = [
        'batterieFaible' => floatval($_POST['batterieFaible']),
        'surcharge'      => floatval($_POST['surcharge']),
        'coupure'        => floatval($_POST['coupure'])
    ];

    // sauvegarde des nouveaux seuils dans le fichier JSON
    file_put_contents('../config/config_seuils.json', json_encode($nouveauxSeuils));

    // compare les anciens et nouveaux seuils pour afficher le détail des changements
    $changements = [];
    foreach ($nouveauxSeuils as $cle => $valeur) {
        if ($anciensSeuils[$cle] != $valeur) {
            $changements[] = "- " . ucfirst($cle) . " : {$anciensSeuils[$cle]} → {$valeur}";
        }
    }

    // message à afficher
    if ($changements) {
        $_SESSION['message_seuils'] = "<u>Seuils modifiés :</u><br>" . implode('<br>', $changements);
    } else {
        $_SESSION['message_seuils'] = "Aucun seuil n’a été modifié.";
    }
    header('Location: changerSeuils.php');
    exit;
}

// charge les seuils actuels pour les afficher dans le formulaire
if (file_exists('../config/config_seuils.json')) {
    $seuils = json_decode(file_get_contents('../config/config_seuils.json'), true);
} else {
    $seuils = [
        'batterieFaible' => 15,
        'surcharge'      => 5.0,
        'coupure'        => 0.5
    ];
}

// message à afficher après la soumission du formulaire
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

    <!-- formulaire de modification des seuils avec valeurs actuelles préremplies et message après soumission -->
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