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
    if (!$s) return 'grey';
    if (str_contains($s,'LB') || str_contains($s,'OFF')) return 'red';
    if (str_contains($s,'OB')) return 'orange';
    return 'green';
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