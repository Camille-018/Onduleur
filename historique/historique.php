<?php
// historique.php - web page to display the history of collects from the UPS
require_once __DIR__ . '/../auth/authCheck.php';
include __DIR__ . '/../style/navbar.php';   

// pagination (15 collects per page)
$collects = 15;
$sheet = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($sheet - 1) * $collects;

// get the collects for the current page
$stmt = $pdo->prepare("
    SELECT * 
    FROM ups_history 
    ORDER BY timestamp DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $collects, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// Get total collects for pagination
$totalStmt = $pdo->query("SELECT COUNT(*) FROM ups_history");
$total = $totalStmt->fetchColumn();
$totalSheet = ceil($total / $collects);
$final = min($sheet, $totalSheet ?: 1);

$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/style/images/cereep32.ico">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Historique</title>
</head>
<body>
    <h1>Historique des Collectes</h1>
    <br><hr>
    <!-- Form to filter by specific value -->
    <h2>Filtrer par une valeur spécifique</h2>
    <form action="valeurSpecifique.php" method="GET">
        <div id="filters">
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

                <!-- drop-down menu for the operator -->
                <select name="operateur[]">
                    <option value="=">=</option>
                    <option value=">">&gt;</option>
                    <option value="<">&lt;</option>
                    <option value="like">contient</option>
                    <option value="between">entre</option>
                </select>
                <input type="text" name="valeur[]" placeholder="valeur">
            </div>
        </div>

        <!-- Add/Remove/Apply filter -->
        <div class="filter-actions">
            <button type="button" onclick="addFilter()">+ Ajouter</button>
            <button type="button" onclick="removeLastFilter()">- Retirer</button>
            <button type="submit">Filtrer</button>
        </div>
    </form>

    <script>
        // JavaScript to add more filter rows
        function addFilter() {
            const container = document.getElementById('filters');
            const currentFilters = container.querySelectorAll('.filter-row').length;
            if (currentFilters >= 5) {
                alert("Max 5 filtres.");
                return;
            }
            const row = container.querySelector('.filter-row').cloneNode(true);
            row.querySelectorAll('input').forEach(i => i.value = '');
            container.appendChild(row);
        }

        // JavaScript to remove filter 
        function removeLastFilter() {
            const container = document.getElementById('filters');
            const rows = container.querySelectorAll('.filter-row');
            if (rows.length > 1) {
                rows[rows.length - 1].remove();
            } else {
                alert("Min 1 filtre.");
            }
        }

        // for between
        document.addEventListener("change", function(e) {
            if (e.target.name === "operateur[]") {
                const row = e.target.closest('.filter-row');
                // Remove old BETWEEN fields
                row.querySelectorAll('.between').forEach(el => el.remove());
                const inputNormal = row.querySelector('input[name="valeur[]"]');
                if (e.target.value === "between") {
                    //  Hide normal field ("valeur")
                    inputNormal.style.display = "none";
                    // input min
                    const min = document.createElement("input");
                    min.type = "text";
                    min.name = "valeur_min[]";
                    min.placeholder = "min";
                    min.classList.add("between");
                    // input max
                    const max = document.createElement("input");
                    max.type = "text";
                    max.name = "valeur_max[]";
                    max.placeholder = "max";
                    max.classList.add("between");
                    row.appendChild(min);
                    row.appendChild(max);
                } else {
                    // Show normal field ("valeur")
                    inputNormal.style.display = "inline";
                }
            }
        });
    </script>
    <br>
    <hr>

<!-- All collects (with a table) -->
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
        <?php if ($sheet > 1): ?>
            <a href="?page=1#tableauHistorique">&laquo;&laquo;</a>
        <?php endif; ?>

        <!-- previous page -->
        <?php if ($sheet > 1): ?>
            <a href="?page=<?= $sheet - 1 ?>#tableauHistorique">&laquo;</a>
        <?php endif; ?>

        <!-- current page -->
        <span class="current-page">
            Page <?= $sheet ?> / <?= $totalSheet ?>
        </span>

        <!-- next page-->
        <?php if ($sheet < $totalSheet): ?>
            <a href="?page=<?= $sheet + 1 ?>#tableauHistorique">&raquo;</a>
        <?php endif; ?>

        <!-- last page-->
        <?php if ($sheet < $totalSheet): ?>
            <a href="?page=<?= $totalSheet ?>#tableauHistorique">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>

