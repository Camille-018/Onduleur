<?php
require_once '../config.php';

// récupérer 100 dernières collectes
$stmt = $pdo->query("SELECT * FROM donnees ORDER BY heureCollecte DESC LIMIT 100");
$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ondulateur - Historique</title>
</head>
<body>
    <h1>Historique des Collectes</h1>
    <a href="../index.php">Retour à l'accueil</a>
    <br><br>

        <!-- Formulaire pour filtrer par valeur spécifique -->
    <h2>Filtrer par valeur spécifique</h2>
    <form action="valeurSpecifique.php" method="GET">
        <label for="colonne">Choisir la colonne :</label>
        <select name="colonne" id="colonne" required>
            <option value="autonomieRestante">Autonomie</option>
            <option value="etatBatterie">État Batterie</option>
            <option value="santeBatterie">Santé Batterie</option>
            <option value="tensionEntree">Tension Entrée</option>
            <option value="tensionSortie">Tension Sortie</option>
            <option value="heureCollecte">Heure Collecte</option>
        </select>

        <label for="valeur">Valeur :</label>
        <input type="text" name="valeur" id="valeur" required>

        <button type="submit">Filtrer</button>
    </form>
    <hr>
    <br><br>

<!-- les 100 dernières collectes en tableau -->
 <h2>Les 100 dernieres collectes</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Autonomie</th>
                <th>État Batterie</th>
                <th>Santé Batterie</th>
                <th>Tension Entrée</th>
                <th>Tension Sortie</th>
                <th>Heure</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($historique as $row): ?>
            <tr>
                <td><?= $row['idCollecte'] ?></td>
                <td><?= $row['autonomieRestante'] ?></td>
                <td><?= $row['etatBatterie'] ?></td>
                <td><?= $row['santeBatterie'] ?></td>
                <td><?= $row['tensionEntree'] ?></td>
                <td><?= $row['tensionSortie'] ?? 'N/A' ?></td>
                <td><?= $row['heureCollecte'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
