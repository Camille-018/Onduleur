<?php
session_start();
require_once '../config/config.php';

// historique.php: display the history of collects, with a form to filter by specific value

//1 - get the 100 last collects from the database
$stmt = $pdo->query("SELECT * FROM ups_history ORDER BY timestamp DESC LIMIT 100");
$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - History</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>History of Collects</h1>
    <a href="../index.php">Go to Home</a><br>
    <a href="../alerte/alerte.php">View Alerts</a>
    <br><br>
    <hr>

    <!-- Form to filter by specific value -->
    <h2>Filter by specific value</h2>
    <form action="valeurSpecifique.php" method="GET">
        <label for="colonne">Choose the column :</label>
        <select name="colonne" id="colonne" required>
            <option value="id">ID</option>
            <option value="ups_id">UPS ID</option>
            <option value="battery_charge">Battery Charge</option>
            <option value="battery_runtime">Battery Runtime</option>
            <option value="input_voltage">Input Voltage</option>
            <option value="output_voltage">Output Voltage</option>
            <option value="ups_load">UPS Load</option>
            <option value="ups_status">UPS Status</option>
        </select>

        <label for="valeur">Value :</label>
        <input type="text" name="valeur" id="valeur" required>
        <button type="submit">Filter</button>
    </form>
    <br>
    <hr>

<!-- The 100 most recent collects (with a table) -->
 <h2>100 Most Recent Collects</h2>
 <p>Here is the table of the 100 most recent collects recorded by the UPS, sorted from newest to oldest.</p>
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
</body>
</html>

