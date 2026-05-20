<?php
// valeurSpecifique.php : filtre les collectes selon une ou plusieurs valeurs spécifiques
require_once __DIR__ . '/../auth/authCheck.php';
include __DIR__ . '/../style/navbar.php';   

// ----------------------------
// Pagination
// ----------------------------
$collects = 15;
$sheet = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($sheet - 1) * $collects;

// ----------------------------
// récupère les valeurs des filtres
// ----------------------------
$colonnes   = $_GET['colonne'] ?? [];
$valeurs    = $_GET['valeur'] ?? [];
$operateurs = $_GET['operateur'] ?? [];
$valeurs_min = $_GET['valeur_min'] ?? [];
$valeurs_max = $_GET['valeur_max'] ?? [];

if (!is_array($colonnes)) $colonnes = [$colonnes];
if (!is_array($valeurs)) $valeurs = [$valeurs];

// ----------------------------
// prépare la requête SQL
// ----------------------------
$where  = [];
$params = [];

$allowedColumns = [
    'id','ups_id','battery_charge','battery_runtime',
    'input_voltage','output_voltage','ups_load','ups_status','timestamp'
];

foreach ($colonnes as $i => $colonne) {
    if (!in_array($colonne, $allowedColumns)) continue;
    $op = $operateurs[$i] ?? '=';
    $valeur = $valeurs[$i] ?? '';
    if ($op === 'between') {
        $min = $valeurs_min[$i] ?? null;
        $max = $valeurs_max[$i] ?? null;
        if ($min !== '' && $max !== '' && $min !== null && $max !== null) {
            $where[] = "$colonne BETWEEN :min$i AND :max$i";
            $params[":min$i"] = $min;
            $params[":max$i"] = $max;
        }
    } else {
        if ($valeur === '') continue;

        if (in_array($op, ['>','<','='])) {
            $where[] = "$colonne $op :val$i";
            $params[":val$i"] = $valeur;

        } elseif ($op === "like") {
            $where[] = "$colonne LIKE :val$i";
            $params[":val$i"] = "%$valeur%";
        }
    }
}

// ----------------------------
// Pagination : total
// ----------------------------
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

// ----------------------------
// requête principale
// ----------------------------
$sql = "SELECT * FROM ups_history";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// liaison des paramètres
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v); // conversion automatique gérée par MySQL
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
    <link rel="icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/style/images/cereep32.ico" type="image/x-icon">
    <link rel="stylesheet" href="../style/style.css">
    <title>UPS - Résultat du Filtre</title>
</head>
<body>
<h1>Onduleur - Résultat du Filtre</h1>

<!-- affiche les filtres -->
<?php if (!empty($colonnes)): ?>
<h3>Filtres appliqués :</h3>
<ul>
<?php foreach ($colonnes as $i => $colonneFiltre):
    if (!in_array($colonneFiltre, $allowedColumns)) continue;
    $op = $operateurs[$i] ?? '=';
    $valeurFiltre = $valeurs[$i] ?? '';
    $min = $valeurs_min[$i] ?? '';
    $max = $valeurs_max[$i] ?? '';
?>
    <li>
        <?php
        if ($op === 'like') {
            echo htmlspecialchars($colonneFiltre) . " contient " . htmlspecialchars($valeurFiltre);

        } elseif ($op === 'between' && $min !== '' && $max !== '') {
            echo htmlspecialchars($colonneFiltre) . " entre " 
                . htmlspecialchars($min) . " et " . htmlspecialchars($max);

        } elseif ($valeurFiltre !== '') {
            echo htmlspecialchars($colonneFiltre) . " $op " . htmlspecialchars($valeurFiltre);

        }
        ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<!-- affiche les résultats -->
<?php if (!empty($historique)): ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>ID Onduleur</th>
            <th>Charge Batterie</th>
            <th>Runtime Batterie</th>
            <th>Tension Entrée</th>
            <th>Tension Sortie</th>
            <th>Charge Onduleur</th>
            <th>Status</th>
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
    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>">&laquo;&laquo;</a>
    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$sheet-1])) ?>">&laquo;</a>
<?php endif; ?>

<span>Page <?= $sheet ?> / <?= $totalSheet ?></span>

<?php if ($sheet < $totalSheet): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$sheet+1])) ?>">&raquo;</a>
    <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$totalSheet])) ?>">&raquo;&raquo;</a>
<?php endif; ?>
</div>

<?php else: ?>
<p>Pas de résultats trouvés.</p>
<?php endif; ?>

</body>
</html>