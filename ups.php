<?php
//ups.php: details page for a single UPS, showing graphs of battery level and output voltage over time
require_once __DIR__ . '/auth/authCheck.php';
include __DIR__ . '/style/navbar.php';   

// Get UPS ID from query parameter
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM ups WHERE id = ?");
$stmt->execute([$id]);
$ups = $stmt->fetch();

if (!$ups) {
    die("Onduleur non trouvé");
}

#retrieve last 50 entries for this UPS
$stmt = $pdo->prepare("
    SELECT *
    FROM ups_history
    WHERE ups_id = ?
    ORDER BY timestamp DESC
    LIMIT 50
");
$stmt->execute([$id]);
$data = array_reverse($stmt->fetchAll());
?>

<!DOCTYPE html>
<html>
<head>
     <link rel=stylesheet href="/style/style.css"></link>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($ups['device_model']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1><?= htmlspecialchars($ups['device_model']) ?></h1>
<h2 style="text-align: center;">Statut de la Batterie de l'Onduleur</h2>
<canvas id="batteryChart"></canvas><hr>

<h2 style="text-align: center;">Tension de Sortie de l'Onduleur</h2>
<canvas id="voltageChart"></canvas>

<script>
const labels  = <?= json_encode(array_column($data,'timestamp')) ?>;
const battery = <?= json_encode(array_column($data,'battery_charge')) ?>;
const voltage = <?= json_encode(array_column($data,'output_voltage')) ?>;

// Graphic configuration - Battery
new Chart(document.getElementById('batteryChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Batterie (%)',
            data: battery,
            borderWidth: 2
        }]
    },
    options: {
        plugins: {
            title: { display: false } // we delete the default title (already in h2)
        },
        scales: {
            y: {
                title: { display: true, text: 'Pourcentage (%)' },
                min: 0,
                max: 100
            },
            x: {
                title: { display: true, text: 'Temps' }
            }
        }
    }
});

// Graphics configuration - Voltage
new Chart(document.getElementById('voltageChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Tension de Sortie (V)',
            data: voltage,
            borderWidth: 2
        }]
    },
    options: {
        plugins: { title: { display: false } }, // titre désactivé
        scales: {
            y: { title: { display: true, text: 'Volts (V)' } },
            x: { title: { display: true, text: 'Temps' } }
        }
    }
});
</script>
</body>
</html>