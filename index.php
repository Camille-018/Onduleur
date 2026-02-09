<?php
require_once __DIR__ . '/auth/authCheck.php';

// Retrieves the list of UPS with their latest status
$upsList = $pdo->query("
    SELECT u.id, u.device_model,
           h.ups_status, h.battery_charge, h.timestamp
    FROM ups u
    LEFT JOIN ups_history h
      ON h.id = (
          SELECT id FROM ups_history
          WHERE ups_id = u.id
          ORDER BY timestamp DESC
          LIMIT 1
      )
")->fetchAll();

//color depending on status
function statusColor($s) {
    // $criticalStates = ['LB', 'OVER', 'BYPASS', 'OFF']; -> Red
    // $warningStates  = ['OB', 'DISCHRG', 'TEST', 'CAL']; -> Orange
    // $normalStates   = ['OL', 'CHRG']; -> Green
    if (!$s) return 'grey'; //None status
    else if (str_contains($s,'LB') || str_contains($s,'OVER') || str_contains($s,'BYPASS')|| str_contains($s,'OFF') ) return 'red';
    else if (str_contains($s,'OB') || str_contains($s,'DISCHRG') || str_contains($s,'TEST') || str_contains($s,'CAL')) return 'orange';
    else return 'green';
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>UPS</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>

<h1>📊 UPS List</h1>
<a href="../auth/logout.php">Logout</a><br>
<a href="../historique/historique.php">Go to History</a><br>
<a href="../alerte/alerte.php">Go to Alerts</a><br><hr>
<p>Click on an UPS card to see detailed information.</p>
<p><i>Go to alert to check to check the status meaning (red is critical, orange is warning, green is normal)</i></p>
<!-- Display UPS cards -->
<div class="grid">
<?php foreach ($upsList as $ups): ?>
<a class="card" href="ups.php?id=<?= $ups['id'] ?>">
    <h3><?= htmlspecialchars($ups['device_model']) ?></h3>
    <p>Battery : <?= $ups['battery_charge'] ?? '--' ?> %</p>
    <span class="status <?= statusColor($ups['ups_status']) ?>">
        <?= $ups['ups_status'] ?? 'None' ?>
    </span>
</a>
<?php endforeach; ?>
</div>
</body>
</html>