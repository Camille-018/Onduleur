<?php
session_start();
require_once '../config/config.php';

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
    <title>UPS - Filter Result</title>
</head>
<body>
    <h1>UPS - Filter Result</h1>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <a href="historique.php">Back to History</a><br>
    <a href="../index.php">Back to Home</a><br><br>
     <h3>Filter : <?= htmlspecialchars($colonne) ?> = <?= htmlspecialchars($valeur) ?></h3> <!--filter call -->
    <?php if (!empty($historique)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>UPS ID</th>
                <th>Battery Charge</th>
                <th>Battery Runtime</th>
                <th>Input Voltage</th>
                <th>Output Voltage</th>
                <th>UPS Load</th>
                <th>UPS Status</th>
                <th>Timestamp</th>
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
        <p>No results found.</p>
    <?php endif; ?>

</body>
</html>
