<?php
require_once '../config.php';

// récupération des paramètres GET
$colonne = $_GET['colonne'] ?? '';
$valeur  = $_GET['valeur'] ?? '';

$colonnes_valides = ['autonomieRestante','etatBatterie','santeBatterie','tensionEntree','tensionSortie','heureCollecte'];

if (!in_array($colonne, $colonnes_valides)) {
    die('Colonne invalide');
}

// requête préparée
if (in_array($colonne, ['autonomieRestante','santeBatterie','tensionEntree','tensionSortie'])) {
    $stmt = $pdo->prepare("SELECT * FROM donnees WHERE $colonne = :valeur ORDER BY heureCollecte DESC");
    $stmt->execute([':valeur' => $valeur]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM donnees WHERE $colonne LIKE :valeur ORDER BY heureCollecte DESC");
    $stmt->execute([':valeur' => "%$valeur%"]);
}

$historique = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ondulateur - Résultat Filtre</title>
</head>
<body>
    <h1>Ondulateur - Résultat Filtre</h1>
    <a href="historique.php">Retour à l'historique</a><br>
    <a href="../index.php">Retour à l'accueil</a><br><br>
     <h3>Filtre : <?= htmlspecialchars($colonne) ?> = <?= htmlspecialchars($valeur) ?></h3> <!--appel du filtre -->
    <?php if (!empty($historique)): ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Autonomie</th>
                <th>État Batterie</th>
                <th>Santé Batterie</th>
                <th>Tension Entrée</th>
                <th>Tension Sortie</th>
                <th>Heure</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($historique as $row): ?>
            <tr>
                <td><?= $row['idCollecte'] ?></td>
                <td><?= $row['autonomieRestante'] ?></td>
                <td><?= $row['etatBatterie'] ?></td>
                <td><?= $row['santeBatterie'] ?></td>
                <td><?= $row['tensionEntree'] ?></td>
                <td><?= $row['tensionSortie'] ?? 'N/A' ?></td>
                <td><?= $row['heureCollecte'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Aucun résultat trouvé.</p>
    <?php endif; ?>

</body>
</html>
