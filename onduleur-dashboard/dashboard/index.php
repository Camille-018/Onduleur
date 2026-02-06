<?php
require_once __DIR__ . '/../auth/check.php';
require_once __DIR__ . '/../config/database.php';

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
    <title>Onduleurs</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<h1>📊 Liste des onduleurs</h1>
<a href="../auth/logout.php">Déconnexion</a>

<div class="grid">
<?php foreach ($upsList as $ups): ?>
<a class="card" href="ups.php?id=<?= $ups['id'] ?>">
    <h3><?= htmlspecialchars($ups['device_model']) ?></h3>
    <p>Batterie : <?= $ups['battery_charge'] ?? '--' ?> %</p>
    <span class="status <?= statusColor($ups['ups_status']) ?>">
        <?= $ups['ups_status'] ?? 'INCONNU' ?>
    </span>
</a>
<?php endforeach; ?>
</div>

</body>
</html>
