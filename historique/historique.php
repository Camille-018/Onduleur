<?php
// historique.php - web page to display the history of collects from the UPS
require_once __DIR__ . '/../auth/authCheck.php';

// pagination (15 collects per page)
$parPage = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $parPage;

// get the collects for the current page
$stmt = $pdo->prepare("
    SELECT * 
    FROM ups_history 
    ORDER BY timestamp DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// Get total collects for pagination
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
    <title>Onduleur - Historique</title>
</head>
<body>
    <img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
    <h1>Historique des Collectes</h1>
    <a href="../index.php">Retour à l'accueil</a><br>
    <a href="../alerte/alerte.php">Voir les Alertes</a>
    <br><br>
    <hr>

    <!-- Form to filter by specific value -->
    <h2>Filtrer par une valeur spécifique</h2>
    <form action="valeurSpecifique.php" method="GET">
        <div class="filter-row">
            <select name="colonne[]" required>
                <option value="id">ID</option>
                <option value="ups_id">ID de l'onduleur</option>
                <option value="battery_charge">Charge de la Batterie</option>
                <option value="battery_runtime">Runtime de la Batterie</option>
                <option value="input_voltage">Tension d'Entrée</option>
                <option value="output_voltage">Tension de Sortie</option>
                <option value="ups_load">Charge de l'Onduleur</option>
                <option value="ups_status">Status de l'Onduleur</option>
            </select>
            <input type="text" name="valeur[]" required>
        </div>
        <button type="button" onclick="addFilter()">+ Ajouter un Filtre</button>
        <button type="submit">Filtrer</button>
    </form>

    <script>
        // JavaScript to add more filter rows
    function addFilter() {
        const form = document.querySelector('form');
        const currentFilters = form.querySelectorAll('.filter-row').length;
        if (currentFilters >= 5) {
            alert('Vous pouvez ajouter jusqu\'à 5 filtres seulement.');
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
 <h2>Collectes Récentes</h2>
 <p>Voici le tableau avec toutes les collectes enregistrées par l'Onduleur, triées de la plus récente à la plus ancienne.</p>
    <table id="tableauHistorique">
        <thead>
            <tr>
                <th>ID</th>
                <th>ID de l'onduleur</th>
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
    <div class="pagination">
        <!-- first page -->
        <?php if ($page > 1): ?>
            <a href="?page=1#tableauHistorique">&laquo;&laquo;</a>
        <?php endif; ?>

        <!-- previous page -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>#tableauHistorique">&laquo;</a>
        <?php endif; ?>

        <!-- current page -->
        <span class="current-page">
            Page <?= $page ?> / <?= $totalPages ?>
        </span>

        <!-- next page-->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>#tableauHistorique">&raquo;</a>
        <?php endif; ?>

        <!-- last page-->
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $totalPages ?>#tableauHistorique">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>

