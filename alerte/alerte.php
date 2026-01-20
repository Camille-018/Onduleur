<?php
require_once '../config.php';
// alerte.php: affiche les alertes depuis la base de données

// récupérer toutes les alertes (les 100 dernières)
$stmt = $pdo->query("SELECT * FROM Alertes ORDER BY heureAlerte DESC LIMIT 100");
$alertes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onduleur - Alertes</title>
</head>
<body>
    <h1>Alertes</h1>
    <a href="../index.php">Accueil</a><br>
    <a href="../historique/historique.php">Historique</a><br>
    <a href="verifierAlerte.php">Vérifier les alertes</a><br><br>
    <a href="changerSeuils.php">Changer les seuils d'alerte</a>
    <br><br>

    <?php if (!empty($alertes)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
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
