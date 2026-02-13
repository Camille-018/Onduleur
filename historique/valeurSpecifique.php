<?php
require_once __DIR__ . '/../auth/authCheck.php';

// pagination
$parPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $parPage;

// récupérer les filtres
$colonnes = $_GET['colonne'] ?? [];
$valeurs  = $_GET['valeur'] ?? [];

if (!is_array($colonnes)) $colonnes = [$colonnes];
if (!is_array($valeurs)) $valeurs = [$valeurs];

// définir le type des colonnes
$colonnes_float = ['input_voltage','output_voltage'];
$colonnes_int   = ['id','ups_id','battery_runtime','battery_charge','ups_load'];
$colonnes_text  = ['ups_status'];

$where = [];
$params = [];

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

// total pour pagination
$totalSql = "SELECT COUNT(*) FROM ups_history";
if ($where) {
    $totalSql .= " WHERE " . implode(" AND ", $where);
}
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $parPage);
$page = min($page, $totalPages ?: 1);
$offset = ($page - 1) * $parPage;

// récupérer la page courante
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
$stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
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
    <a href="historique.php">Go to History</a><br>
    <a href="../index.php">Go to Home</a><br><br>

    <!-- Affichage de tous les filtres appliqués -->
    <?php if (!empty($colonnes) && !empty($valeurs)): ?>
        <h3>Filters applied:</h3>
        <ul>
        <?php foreach ($colonnes as $i => $colonneFiltre): ?>
            <?php $valeurFiltre = $valeurs[$i] ?? ''; ?>
            <li><?= htmlspecialchars($colonneFiltre) ?> = <?= htmlspecialchars($valeurFiltre) ?></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

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

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>#tableauHistorique">&laquo;&laquo;</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>#tableauHistorique">&laquo;</a>
        <?php endif; ?>

        <span class="current-page">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>#tableauHistorique">&raquo;</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$totalPages])) ?>#tableauHistorique">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>

    <?php else: ?>
        <p>No results found.</p>
    <?php endif; ?>

</body>
</html>
