<!-- verifierAlerte.php: regarder la table donnees, inserer les problemes dans alertes, envoyer une notif (mail/sms) -->
 <?php
require_once '../config.php';

// Récupérer toutes les collectes qui n'ont pas encore généré d'alerte
$stmt = $pdo->query("
    SELECT d.idCollecte, d.autonomieRestante, d.etatBatterie, d.santeBatterie, d.tensionEntree, d.tensionSortie, d.heureCollecte
    FROM donnees d
    LEFT JOIN Alertes a ON d.idCollecte = a.idCollecte
    WHERE a.idCollecte IS NULL
    ORDER BY d.heureCollecte ASC
");

$donnees = $stmt->fetchAll();

// Seuils pour les alertes
$seuils = [
    'batterieFaible' => 15,
    'surcharge'      => 5.0,
    'coupure'        => 0.5
];

// Vérifier chaque collecte et créer des alertes si nécessaire
$nbAlertes = 0;
foreach ($donnees as $d) {
    $alertes_a_creer = [];

    if ($d['autonomieRestante'] < $seuils['batterieFaible']) {
        $alertes_a_creer[] = ['Type'=>'batterieFaible','Message'=>"Autonomie critique : {$d['autonomieRestante']}%"];
    }

    if ($d['tensionEntree'] > $seuils['surcharge']) {
        $alertes_a_creer[] = ['Type'=>'surcharge','Message'=>"Tension entrée trop élevée : {$d['tensionEntree']}V"];
    }

    if ($d['tensionSortie'] < $seuils['coupure']) {
        $alertes_a_creer[] = ['Type'=>'coupure','Message'=>"Tension sortie trop basse : {$d['tensionSortie']}V"];
    }

    foreach ($alertes_a_creer as $a) {
        $stmt = $pdo->prepare("
            INSERT INTO Alertes (idCollecte, Type, Message, heureAlerte)
            VALUES (:idCollecte, :type, :message, NOW())
        ");
        $stmt->execute([
            ':idCollecte' => $d['idCollecte'],
            ':type' => $a['Type'],
            ':message' => $a['Message']
        ]);
        $nbAlertes++;
    }
}

echo "Vérification terminée. $nbAlertes alerte(s) créée(s).";
echo '<br><a href="alerte.php">Retour aux alertes</a>';
echo '<br><a href="../historique/historique.php">Aller à l\'Historique</a>';
echo '<br><a href="../index.php">Retour à l\'accueil</a>';

?>

