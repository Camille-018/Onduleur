<?php
// index.php : tableau de bord principal affichant tous les onduleurs avec leur dernier statut
require_once __DIR__ . '/auth/authCheck.php';
include __DIR__ . '/style/navbar.php';

// Récupère la liste des onduleurs avec leur dernier statut
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

// couleur selon le statut
function statusColor($s) {
    // $criticalStates = ['LB', 'OVER', 'BYPASS', 'OFF']; -> Rouge
    // $warningStates  = ['OB', 'DISCHRG', 'TEST', 'CAL']; -> Orange
    // $normalStates   = ['OL', 'CHRG']; -> Vert
    if (!$s) return 'grey'; // Aucun statut
    else if (str_contains($s,'LB') || str_contains($s,'OVER') || str_contains($s,'BYPASS')|| str_contains($s,'OFF') ) return 'red';
    else if (str_contains($s,'OB') || str_contains($s,'DISCHRG') || str_contains($s,'TEST') || str_contains($s,'CAL')) return 'orange';
    else return 'green';
}
?>


<!DOCTYPE html>
<!-- html : afficher toutes les cartes d'UPS -->
<html>
<head>
    <meta charset="utf-8">
    <link rel="icon" href="style/images/cereep32.ico" >
    <link rel="stylesheet" href="style/style.css">
    <title>UPS</title>
</head>
<body>
<h1 class="title">📊 Liste des Onduleurs</h1>
<p>Cliquez sur une carte d'onduleur pour voir les informations détaillées.</p>
<p><i>Aller aux alertes pour comprendre le sens des statuts (rouge: critique, orange: avertissement, vert: normal)</i></p>
<!-- Affiche les cartes des UPS -->
<div class="grid">
<?php foreach ($upsList as $ups): ?>
<a class="card" href="ups.php?id=<?= $ups['id'] ?>">
    <h3><?= htmlspecialchars($ups['device_model']) ?></h3>
    <p>Batterie : <?= $ups['battery_charge'] ?? '--' ?> %</p>
    <span class="status <?= statusColor($ups['ups_status']) ?>">
        <?= $ups['ups_status'] ?? 'None' ?>
    </span>
</a>
<?php endforeach; ?>
</div>
</body>
</html>

