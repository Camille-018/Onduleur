<?php
require_once '../config.php';
// alerte.php: affiche les alertes depuis la base de données

// récupérer toutes les alertes (les 100 dernières)
$stmt = $pdo->query("SELECT * FROM Alertes ORDER BY heureAlerte DESC LIMIT 100");
$alertes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Onduleur - Alertes</title>
</head>
<body>
    <h1>Alertes</h1>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <a href="../index.php">Accueil</a><br>
    <a href="../historique/historique.php">Historique</a><br><br>
    <a href="verifierAlerte.php">Vérifier les alertes</a><br>
    <a href="changerSeuils.php">Changer les seuils d'alerte</a><br><br>
    <hr>

    <h2>Explication des alertes</h2>
    <ul>
        <li>
            <strong>Batterie faible</strong> <i>(% trop faible)</i> : <code>battery_charge &lt; seuil batterieFaible</code> <br>
            -> La batterie de l'onduleur est trop déchargée et risque de ne plus alimenter les équipements.
        </li><br>
        <li>
            <strong>Surcharge</strong> <i>(Tension d'entrée trop forte)</i> : <code>input_voltage &gt; seuil surcharge</code> <br>
            -> La tension électrique en entrée dépasse la limite sécurisée, ce qui peut endommager l'onduleur ou le matériel connecté.
        </li><br>
        <li>
            <strong>Coupure</strong> <i>(Tension de sortie trop faible)</i> : <code>output_voltage &lt; seuil coupure</code> <br>
            -> La tension de sortie est trop basse pour alimenter correctement les équipements, pouvant provoquer un arrêt ou un dysfonctionnement.
        </li><br>
    </ul>
    <hr>

    <h2>Les 100 dernières alertes</h2>
    <p>Voici le tableau des 100 dernières alertes enregistrées par l'onduleur, classées de la plus récente à la plus ancienne.</p>
    <?php if (!empty($alertes)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Id Collecte</th>
                <th>Type</th>
                <th>Message</th>
                <th>Heure</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($alertes as $row): ?>
            <tr>
                <td><?= $row['idAlerte'] ?></td>
                <td><?= $row['idCollecte'] ?></td>
                <td><?= $row['type'] ?></td>
                <td><?= $row['message'] ?></td>
                <td><?= $row['heureAlerte'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Aucune alerte trouvée.</p>
    <?php endif; ?>
</body>
</html>
