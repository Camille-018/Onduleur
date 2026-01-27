<?php
require_once '../config.php';

// récupération des paramètres GET
$colonne = $_GET['colonne'] ?? ''; 
$valeur  = $_GET['valeur'] ?? '';

$colonnes_float = ['input_voltage','output_voltage'];
$colonnes_int   = ['id','ups_id','battery_runtime','battery_charge','ups_load'];
$colonnes_text  = ['ups_status'];

if (in_array($colonne, $colonnes_float)) {
    $valeur = (float)$valeur;
    $epsilon = 0.01;
    $stmt = $pdo->prepare("SELECT * FROM ups_history WHERE $colonne BETWEEN :min AND :max ORDER BY timestamp DESC");
    $stmt->execute([
        ':min' => $valeur - $epsilon,
        ':max' => $valeur + $epsilon
    ]);
} elseif (in_array($colonne, $colonnes_int)) {
    $valeur = (int)$valeur;
    $stmt = $pdo->prepare("SELECT * FROM ups_history WHERE $colonne = :valeur ORDER BY timestamp DESC");
    $stmt->execute([':valeur' => $valeur]);
} else { // texte
    $stmt = $pdo->prepare("SELECT * FROM ups_history WHERE $colonne LIKE :valeur ORDER BY timestamp DESC");
    $stmt->execute([':valeur' => "%$valeur%"]);
}


$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Onduleur - Résultat Filtre</title>
</head>
<body>
    <h1>Onduleur - Résultat Filtre</h1>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <a href="historique.php">Retour à l'historique</a><br>
    <a href="../index.php">Retour à l'accueil</a><br><br>
     <h3>Filtre : <?= htmlspecialchars($colonne) ?> = <?= htmlspecialchars($valeur) ?></h3> <!--appel du filtre -->
    <?php if (!empty($historique)): ?>
    <table>
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
                <td><?= $row['input_voltage'] ?>V</td>
                <td><?= $row['output_voltage'] ?>V</td>
                <td><?= $row['ups_load'] ?></td>
                <td><?= $row['ups_status'] ?></td>
                <td><?= $row['timestamp'] ?></td>
            </tr>

            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Aucun résultat trouvé.</p>
    <?php endif; ?>

</body>
</html>
