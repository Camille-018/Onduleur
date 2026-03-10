<?php
//alerte.php: web page to display all alerts and explain them, with pagination
require_once __DIR__ . '/../auth/authCheck.php';

// pagination
$collects= 15;
$sheet = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($sheet - 1) * $collects;

// get alerts with pagination
$stmt = $pdo->prepare("
    SELECT * 
    FROM Alertes 
    ORDER BY heureAlerte DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $collects, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$totalStmt = $pdo->query("SELECT COUNT(*) FROM alertes");
$total = $totalStmt->fetchColumn();
$totalSheet = ceil($total / $collects);
$final = min($sheet, $totalSheet ?: 1);

$alertes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel=stylesheet href="../style/style.css"></link>
    <title>UPS - Alertes</title>
</head>
<body>
    <h1>Alertes</h1>
    <a href="../index.php">Aller à l'acceuil</a><br>
    <a href="../historique/historique.php">Aller à l'historique</a><br><br>
    <a href="verifierAlerte.php">Vérifier les alertes</a><br>
    <a href="changerSeuils.php">Modifier les seuils d'alerte</a><br><br>
    <hr>

    <h2>Explication des statuts des onduleurs</h2>
    <p><i>
    L'onduleur génère automatiquement des alertes de statut basées sur les données qu'il collecte.<br>
    Chaque statut reflète la condition d'exploitation actuelle de l'onduleur.
    </i></p>
    <div class="grid">
        <!-- NORMAL -->
        <div class="card normal-card">
            <h3>Statut Normal</h3>
            <p>L'onduleur fonctionne normalement, aucune action immédiate requise.</p>
            <p>
                <span class="status green">OL</span> On Line – L'onduleur est en ligne<br>
                <span class="status green">CHRG</span> Charging – La batterie est en cours de charge
            </p>
        </div>

        <!-- WARNING -->
        <div class="card warning-card">
            <h3>Statut d'Attention</h3>
            <p>L'onduleur est en cours d'exploitation mais une attention peut être nécessaire.</p>
            <p>
                <span class="status orange">OB</span> On Battery – Fonctionne sur alimentation batterie<br>
                <span class="status orange">DISCHRG</span> Discharging – Batterie en cours d'utilisation<br>
                <span class="status orange">TEST</span> Test en cours<br>
                <span class="status orange">CAL</span> Calibration en cours
            </p>
        </div>

        <!-- CRITICAL -->
        <div class="card critical-card">
            <h3>Statut Critique</h3>
            <p> Action immédiate requise. Risque de coupure ou de perte d'alimentation.</p>
            <p>
                <span class="status red">LB</span> Low Battery - La batterie est trop faible<br>
                <span class="status red">OVER</span> Overload detected - La charge dépasse la capacité de l'onduleur<br>
                <span class="status red">BYPASS</span> Bypass mode active - L'onduleur est en mode de secours<br>
                <span class="status red">OFF</span> UPS powered off - L'onduleur est éteint
            </p>
        </div>
    </div>
    <p class="mail-info"><i>
    Note: Les statuts sont générés directement par l'onduleur et peuvent représenter différentes conditions d'exploitation selon la situation.
    </i></p><hr>

    <h2>Explication des alertes (créées avec des seuils) </h2>
    <h3>Créées avec des seuils</h3>
    <ul>
        <li>
            <strong>Batterie faible</strong> <i>(% trop bas)</i> : <code>battery_charge &lt; seuil batterieFaible</code> <br>
            -> La batterie de l'onduleur est trop faible pour assurer une alimentation de secours fiable, ce qui peut entraîner une coupure de courant en cas de panne d'alimentation principale.
        </li><br>
        <li>
            <strong>Surchage</strong> <i>(Tension d'entrée trop élevée)</i> : <code>input_voltage &gt; seuil surcharge</code> <br>
            -> La tension d'entrée est trop élevée et peut endommager l'onduleur ou l'équipement connecté.
        </li><br>
        <li>
            <strong>Cut-off</strong> <i>(Tension de sortie trop basse)</i> : <code>output_voltage &lt; seuil cut-off</code> <br>
            -> La tension de sortie est trop basse pour alimenter correctement l'équipement connecté, ce qui peut entraîner un arrêt ou un dysfonctionnement.
        </li>
    </ul>
    <p class="mail-info"><i>Note: Les seuils pour ces alertes peuvent être modifiés dans la page "Modifier les seuils d'alerte" (seulement pour les administrateurs).</i></p>
    <hr>

    <h2>Les Alertes</h2>
    <p>Voici le tableau avec toutes les alertes avec <strong>seuils</strong> et quelques statuts critiques d'onduleur <strong>(BYPASS et OFF)</strong>, triés du plus récent au plus ancien.</p>
    <?php if (!empty($alertes)): ?>
    <table id="tableauAlerte">
        <thead>
            <tr>
                <th>ID</th>
                <th>Id Collecte</th>
                <th>Type</th>
                <th>Message</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($alertes as $row): ?>
            <tr>
                <td><?= $row['idAlerte'] ?></td>
                <td><?= $row['idCollecte'] ?></td>
                <td><?= $row['type'] ?></td>
                <td><?= $row['message'] ?></td>
                <td><?= $row['heureAlerte'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="pagination">
        <!-- first -->
        <?php if ($sheet > 1): ?>
            <a href="?page=1#tableauAlerte">&laquo;&laquo;</a>
        <?php endif; ?>

        <!-- previous -->
        <?php if ($sheet > 1): ?>
            <a href="?page=<?= $sheet - 1 ?>#tableauAlerte">&laquo;</a>
        <?php endif; ?>

        <!-- current -->
        <span class="current-page">
            Page <?= $sheet ?> / <?= $totalSheet ?>
        </span>

        <!-- next -->
        <?php if ($sheet < $totalSheet): ?>
            <a href="?page=<?= $sheet + 1 ?>#tableauAlerte">&raquo;</a>
        <?php endif; ?>

        <!-- last -->
        <?php if ($sheet < $totalSheet): ?>
            <a href="?page=<?= $totalSheet ?>#tableauAlerte">&raquo;&raquo;</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <p>Auncune alertes trouvées.</p>
    <?php endif; ?>
</body>
</html>
