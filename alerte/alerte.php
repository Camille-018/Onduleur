<?php
require_once __DIR__ . '/auth/auth_check.php';

// alerte.php: display the list of alerts, with an explanation of what they mean, and the form to change thresholds

// get the 100 last alerts
$stmt = $pdo->query("SELECT * FROM Alertes ORDER BY heureAlerte DESC LIMIT 100");
$alertes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Alerts</title>
</head>
<body>
    <h1>Alerts</h1>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <a href="../index.php">Go to homepage</a><br>
    <a href="../historique/historique.php">Go to history</a><br><br>
    <a href="verifierAlerte.php">Verify alerts</a><br>
    <a href="changerSeuils.php">Change alert thresholds</a><br><br>
    <hr>

    <h2>Explanation of alerts</h2>
    <ul>
        <li>
            <strong>Low battery</strong> <i>(% too low)</i> : <code>battery_charge &lt; threshold low battery</code> <br>
            -> The UPS battery is too low and may not be able to power the equipment.
        </li><br>
        <li>
            <strong>Overload</strong> <i>(Input voltage too high)</i> : <code>input_voltage &gt; threshold overload</code> <br>
            -> The input voltage is too high and may damage the UPS or connected equipment.
        </li><br>
        <li>
            <strong>Cut-off</strong> <i>(Output voltage too low)</i> : <code>output_voltage &lt; threshold cut-off</code> <br>
            -> The output voltage is too low to properly power the connected equipment, potentially causing a shutdown or malfunction.
        </li><br>
    </ul>
    <hr>

    <h2>The 100 last alerts</h2>
    <p>Here is the table of the 100 last alerts recorded by the UPS, sorted from most recent to oldest.</p>
    <?php if (!empty($alertes)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Id Collect</th>
                <th>Type</th>
                <th>Message</th>
                <th>Timestamp</th>
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
        <p>No alerts found.</p>
    <?php endif; ?>
</body>
</html>
