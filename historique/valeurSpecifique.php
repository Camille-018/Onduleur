<?php
// valeurSpecifique.php : page pour filtrer l'historique selon une ou plusieurs valeurs
require_once __DIR__ . '/../auth/authCheck.php';
include __DIR__ . '/../style/navbar.php';   

// ----------------------------
// Pagination
// ----------------------------
$collects = 15;
$sheet = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($sheet - 1) * $collects;

// ----------------------------
// Récupération des filtres
// ----------------------------
$colonnes   = $_GET['colonne'] ?? [];
$valeurs    = $_GET['valeur'] ?? [];
$operateurs = $_GET['operateur'] ?? [];

if (!is_array($colonnes)) $colonnes = [$colonnes];
if (!is_array($valeurs)) $valeurs = [$valeurs];

// ----------------------------
// Construction du WHERE
// ----------------------------
$where  = [];
$params = [];

foreach ($colonnes as $i => $colonne) {

    $valeur  = $valeurs[$i] ?? '';
    $op      = $operateurs[$i] ?? '=';

    if (!$colonne || $valeur === '') continue;
    if (in_array($op, ['>','<','='])) {
        $where[] = "$colonne $op :val$i";
        $params[":val$i"] = $valeur;
    } elseif ($op === "like") {
        $where[] = "$colonne LIKE :val$i";
        $params[":val$i"] = "%$valeur%";
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
// Requête principale
// ----------------------------
$sql = "SELECT * FROM ups_history";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Bind des paramètres
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v); // MySQL fait le cast automatiquement
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
    <title>Onduleur - Résultat du Filtre</title>
</head>
<body>
<h1>Onduleur - Résultat du Filtre</h1>

<!-- Affichage des filtres -->
<?php if (!empty($colonnes)): ?>
<h3>Filtres appliqués :</h3>
<ul>
<?php foreach ($colonnes as $i => $colonneFiltre):
    $valeurFiltre  = $valeurs[$i] ?? '';
    $op = $operateurs[$i] ?? '='; ?>
    <li>
        <?php
        if ($op === 'like') {
            echo htmlspecialchars($colonneFiltre) . " contient " . htmlspecialchars($valeurFiltre);
        } else {
            echo htmlspecialchars($colonneFiltre) . " $op " . htmlspecialchars($valeurFiltre);
        }
        ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<!-- Affichage des résultats -->
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