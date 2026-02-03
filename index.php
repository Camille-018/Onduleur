<?php
// 🔐 Protection par authentification
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: auth/login.php");
    exit;
}

// 🔌 Connexion BDD
require_once "config/config.php";

// 📊 Récupération de la dernière mesure UPS
$stmt = $pdo->query("
    SELECT 
        u.device_model,
        u.device_serial,
        h.battery_charge,
        h.battery_runtime,
        h.input_voltage,
        h.output_voltage,
        h.ups_load,
        h.ups_status,
        h.timestamp
    FROM ups_history h
    JOIN ups u ON h.ups_id = u.id
    ORDER BY h.timestamp DESC
    LIMIT 1
");

$data = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel=stylesheet href="style/style.css"></link>
    <title>Onduleur - Dashboard UPS</title>
    <style>
        .card {
            background: white;
            padding: 20px;
            width: 420px;
            margin: 50px auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;}

        .ok { color: green; font-weight: bold; }

        .alert { color: red; font-weight: bold; }
                    
        .row {margin: 8px 0;}

        .top {
            text-align: right;
            font-size: 0.9em;
            color: #555;}


    </style>
</head>
<body>
<img src="../style/images/cereep.jpg" alt="RAAAAAAAAAAAAAAAH" class="logo">
<h1>Acceuil - Dashboard</h1>
<div class="card">

    <div class="top">
        Connecté : <?= htmlspecialchars($_SESSION["user"]) ?> |
        <a href="../auth/logout.php">Déconnexion</a>
    </div>

    <h1>UPS Dashboard</h1>

    <?php if ($data): ?>
        <div class="row"><b>Modèle :</b> <?= htmlspecialchars($data['device_model']) ?></div>
        <div class="row"><b>Numéro de série :</b> <?= htmlspecialchars($data['device_serial']) ?></div>

        <hr>

        <div class="row"><b>État :</b>
            <span class="<?= $data['ups_status'] === 'OL' ? 'ok' : 'alert' ?>">
                <?= htmlspecialchars($data['ups_status']) ?>
            </span>
        </div>

        <div class="row"><b>Batterie :</b> <?= $data['battery_charge'] ?> %</div>
        <div class="row"><b>Autonomie :</b> <?= $data['battery_runtime'] ?> s</div>
        <div class="row"><b>Charge :</b> <?= $data['ups_load'] ?> %</div>
        <div class="row"><b>Tension entrée :</b> <?= $data['input_voltage'] ?> V</div>
        <div class="row"><b>Tension sortie :</b> <?= $data['output_voltage'] ?> V</div>

        <hr>

        <div class="row">
            <small>Dernière mise à jour : <?= $data['timestamp'] ?></small>
        </div>
    <?php else: ?>
        <p style="text-align:center;">Aucune donnée disponible.</p>
    <?php endif; ?>

</div>
<a href="historique/historique.php">Voir l'historique</a><br>
<a href="alerte/alerte.php">Voir les alertes</a><br>
<a href= "collector/receiveUps.php">Recevoir les données UPS</a>
</body>
</html>
