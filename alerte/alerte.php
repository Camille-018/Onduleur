<?php
require_once __DIR__ . '/../auth/authCheck.php';

// pagination
$parPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $parPage;

// récupérer les alertes de la page
$stmt = $pdo->prepare("
    SELECT * 
    FROM Alertes 
    ORDER BY heureAlerte DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$totalStmt = $pdo->query("SELECT COUNT(*) FROM alertes");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $parPage);
$page = min($page, $totalPages ?: 1);

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

    <h2>UPS Status Explanation</h2>
    <p><i>
    The UPS automatically generates status alerts based on the data it collects.<br>
    Each status reflects the current operating condition of the UPS.
    </i></p>
    <div class="grid">
        <!-- NORMAL -->
        <div class="card normal-card">
            <h3>Normal Status</h3>
            <p>UPS operating normally, no immediate action required.</p>
            <p>
                <span class="status green">OL</span> On Line – UPS supplying power normally<br>
                <span class="status green">CHRG</span> Charging – Battery currently charging
            </p>
        </div>

        <!-- WARNING -->
        <div class="card warning-card">
            <h3>Warning Status</h3>
            <p>UPS is operating but attention may be required.</p>
            <p>
                <span class="status orange">OB</span> On Battery – Running on battery power<br>
                <span class="status orange">DISCHRG</span> Discharging – Battery in use<br>
                <span class="status orange">TEST</span> Test in progress<br>
                <span class="status orange">CAL</span> Calibration in progress
            </p>
        </div>

        <!-- CRITICAL -->
        <div class="card critical-card">
            <h3>Critical Status</h3>
            <p>Immediate action required. Risk of shutdown or power loss.</p>
            <p>
                <span class="status red">LB</span> Low Battery<br>
                <span class="status red">OVER</span> Overload detected<br>
                <span class="status red">BYPASS</span> Bypass mode active<br>
                <span class="status red">OFF</span> UPS powered off
            </p>
        </div>
    </div>
    <p class="mail-info"><i>
    Note: Status values are generated directly by the UPS and may represent different operational conditions depending on the situation.
    </i></p><hr>

    <h2>Explanation of alerts (created with thresholds) </h2>
    <h3>Created with thresholds</h3>
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
        </li>
    </ul>
    <p class="mail-info"><i>Note: the thresholds for these alerts can be changed in the "Change alert thresholds" page.</i></p>
    <hr>

    <h2>The Alerts</h2>
    <p>Here is the table with all the alerts with <strong>thresholds</strong> and some critical UPS statuses <strong>(offline and bypass)</strong>, sorted from most recent to oldest.</p>
    <?php if (!empty($alertes)): ?>
    <table id="tableauAlerte">
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
    <div class="pagination">
        <!-- début -->
        <?php if ($page > 1): ?>
            <a href="?page=1#tableauAlerte">&laquo;&laquo;</a>
        <?php endif; ?>

        <!-- précédent -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>#tableauAlerte">&laquo;</a>
        <?php endif; ?>

        <!-- page actuelle -->
        <span class="current-page">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <!-- suivant -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>#tableauAlerte">&raquo;</a>
        <?php endif; ?>

        <!-- fin -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $totalPages ?>#tableauAlerte">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <p>No alerts found.</p>
    <?php endif; ?>
</body>
</html>
