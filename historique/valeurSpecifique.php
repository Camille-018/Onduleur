<?php
//valeurSpecifique.php: page where we can filter the history by specific values (ex: all entries where battery_charge = 50%)
require_once __DIR__ . '/../auth/authCheck.php';

// pagination
$collects = 15;
$sheet = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($sheet - 1) * $collects;

// get the filter values from the form
$colonnes = $_GET['colonne'] ?? [];
$valeurs  = $_GET['valeur'] ?? [];

if (!is_array($colonnes)) $colonnes = [$colonnes];
if (!is_array($valeurs)) $valeurs = [$valeurs];

// define the type of each column for proper query building
$colonnes_float = ['input_voltage','output_voltage'];
$colonnes_int   = ['id','ups_id','battery_runtime','battery_charge','ups_load'];
$colonnes_text  = ['ups_status'];

$where = [];
$params = [];

// build the WHERE clause based on the filters
foreach ($colonnes as $i => $colonne) {
    $valeur = $valeurs[$i] ?? '';
    if (!$colonne || $valeur === '') continue;

    if (in_array($colonne, $colonnes_float)) {
        $valeur = (float)$valeur;
        $epsilon = 0.01;
        $where[] = "$colonne BETWEEN :min$i AND :max$i";
        $params[":min$i"] = $valeur - $epsilon;
        $params[":max$i"] = $valeur + $epsilon;
    } elseif (in_array($colonne, $colonnes_int)) {
        $valeur = (int)$valeur;
        $where[] = "$colonne = :val$i";
        $params[":val$i"] = $valeur;
    } else { // texte
        $where[] = "$colonne LIKE :val$i";
        $params[":val$i"] = "%$valeur%";
    }
}

// total for pagination
$totalSql = "SELECT COUNT(*) FROM ups_history";
if ($where) {
    $totalSql .= " WHERE " . implode(" AND ", $where);
}
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$totalSheet = ceil($total / $collects);
$sheet = min($sheet, $totalSheet ?: 1);
$offset = ($sheet - 1) * $collects;

// get the current page
$sql = "SELECT * FROM ups_history";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    if (is_int($v)) {
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($k, $v);
    }
}
$stmt->bindValue(':limit', $collects, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$historique = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>Onduleur - Résultat du Filtre</title>
</head>
<body>
    <h1>Onduleur - Résultat du Filtre</h1>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <a href="historique.php">Aller à l'Historique</a><br>
    <a href="../index.php">Aller à l'Accueil</a><br><br>

    <!-- Display applied filters -->
    <?php if (!empty($colonnes) && !empty($valeurs)): ?>
        <h3>Filtres appliqués:</h3>
        <ul>
        <?php foreach ($colonnes as $i => $colonneFiltre): ?>
            <?php $valeurFiltre = $valeurs[$i] ?? ''; ?>
            <li><?= htmlspecialchars($colonneFiltre) ?> = <?= htmlspecialchars($valeurFiltre) ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Display results -->
    <?php if (!empty($historique)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ID Onduleur</th>
                <th>Charge de la Batterie</th>
                <th>Runtime de la Batterie</th>
                <th>Tension d'Entrée</th>
                <th>Tension de Sortie</th>
                <th>Charge de l'Onduleur</th>
                <th>Status de l'Onduleur</th>
                <th>Date</th>
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

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($sheet > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>#tableauHistorique">&laquo;&laquo;</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$sheet-1])) ?>#tableauHistorique">&laquo;</a>
        <?php endif; ?>

        <span class="current-page">
            Page <?= $sheet ?> / <?= $totalSheet ?>
        </span>

        <?php if ($sheet < $totalSheet): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$sheet+1])) ?>#tableauHistorique">&raquo;</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$totalSheet])) ?>#tableauHistorique">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>

    <?php else: ?>
        <p>Pas de résultats trouvés.</p>
    <?php endif; ?>

</body>
</html>
