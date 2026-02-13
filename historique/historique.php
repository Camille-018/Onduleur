<?php
require_once __DIR__ . '/../auth/authCheck.php';

// pagination
$parPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $parPage;

// récupérer les 10 collectes de la page
$stmt = $pdo->prepare("
    SELECT * 
    FROM ups_history 
    ORDER BY timestamp DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$totalStmt = $pdo->query("SELECT COUNT(*) FROM ups_history");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $parPage);
$page = min($page, $totalPages ?: 1);

$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - History</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>History of Collects</h1>
    <a href="../index.php">Go to Home</a><br>
    <a href="../alerte/alerte.php">View Alerts</a>
    <br><br>
    <hr>

    <!-- Form to filter by specific value -->
    <h2>Filter by specific value</h2>
    <form action="valeurSpecifique.php" method="GET">
        <div class="filter-row">
            <select name="colonne[]" required>
                <option value="id">ID</option>
                <option value="ups_id">UPS ID</option>
                <option value="battery_charge">Battery Charge</option>
                <option value="battery_runtime">Battery Runtime</option>
                <option value="input_voltage">Input Voltage</option>
                <option value="output_voltage">Output Voltage</option>
                <option value="ups_load">UPS Load</option>
                <option value="ups_status">UPS Status</option>
            </select>
            <input type="text" name="valeur[]" required>
        </div>
        <button type="button" onclick="addFilter()">+ Add Filter</button>
        <button type="submit">Filter</button>
    </form>

    <script>
    function addFilter() {
        const form = document.querySelector('form');
        const currentFilters = form.querySelectorAll('.filter-row').length;
        if (currentFilters >= 5) {
            alert('You can only add up to 5 filters.');
            return;
        }

        const row = document.querySelector('.filter-row').cloneNode(true);
        row.querySelectorAll('input').forEach(i => i.value = '');
        form.insertBefore(row, form.children[form.children.length - 2]);
    }
    </script>
    <br>
    <hr>

<!-- The 100 most recent collects (with a table) -->
 <h2>Recent Collects</h2>
 <p>Here is the table with all collects recorded by the UPS, sorted from newest to oldest.</p>
    <table id="tableauHistorique">
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
    <div class="pagination">
        <!-- début -->
        <?php if ($page > 1): ?>
            <a href="?page=1#tableauHistorique">&laquo;&laquo;</a>
        <?php endif; ?>

        <!-- précédent -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>#tableauHistorique">&laquo;</a>
        <?php endif; ?>

        <!-- page actuelle -->
        <span class="current-page">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <!-- suivant -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>#tableauHistorique">&raquo;</a>
        <?php endif; ?>

        <!-- fin -->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $totalPages ?>#tableauHistorique">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>

