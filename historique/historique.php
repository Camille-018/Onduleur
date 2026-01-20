<?php
require_once '../config.php';

// récupérer 100 dernières collectes
$stmt = $pdo->query("SELECT * FROM ups_history ORDER BY timestamp DESC LIMIT 100");
$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onduleur - Historique</title>
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
            <option value="id">ID</option>
            <option value="ups_id">UPS ID</option>
            <option value="battery_charge">Charge Batterie</option>
            <option value="battery_runtime">Autonomie Restante Batterie</option>
            <option value="input_voltage">Tension Entrée</option>
            <option value="output_voltage">Tension Sortie</option>
            <option value="ups_load">Charge Travail Onduleur</option>
            <option value="ups_status">État Onduleur</option>
            <option value="timestamp">Heure Collecte</option>
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
                <th>UPS ID</th>
                <th>Charge Batterie</th>
                <th>Autonomie Restante Batterie</th>
                <th>Tension Entrée</th>
                <th>Tension Sortie</th>
                <th>Charge Travail Onduleur</th>
                <th>État Onduleur</th>
                <th>Heure</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($historique as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['ups_id'] ?></td>
                <td><?= $row['battery_charge'] ?>%</td>
                <td><?= $row['battery_runtime'] ?>s</td>
                <td><?= $row['input_voltage'] ?></td>
                <td><?= $row['output_voltage'] ?></td>
                <td><?= $row['ups_load'] ?></td>
                <td><?= $row['ups_status'] ?></td>
                <td><?= $row['timestamp'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

