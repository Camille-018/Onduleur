<?php
require_once __DIR__ . '/../auth/check.php';
require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM ups WHERE id = ?");
$stmt->execute([$id]);
$ups = $stmt->fetch();

if (!$ups) {
    die("UPS introuvable");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM ups_history
    WHERE ups_id = ?
    ORDER BY timestamp DESC
    LIMIT 120
");
$stmt->execute([$id]);
$data = array_reverse($stmt->fetchAll());
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($ups['device_model']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<a href="index.php">← Retour</a>
<h1><?= htmlspecialchars($ups['device_model']) ?></h1>

<canvas id="batteryChart"></canvas>
<canvas id="voltageChart"></canvas>

<script>
const labels  = <?= json_encode(array_column($data,'timestamp')) ?>;
const battery = <?= json_encode(array_column($data,'battery_charge')) ?>;
const voltage = <?= json_encode(array_column($data,'output_voltage')) ?>;

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
            title: {
                display: true,
                text: 'Évolution du niveau de batterie'
            }
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: 'Pourcentage (%)'
                },
                min: 0,
                max: 100
            },
            x: {
                title: {
                    display: true,
                    text: 'Temps'
                }
            }
        }
    }
});

new Chart(document.getElementById('voltageChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Tension de sortie (V)',
            data: voltage,
            borderWidth: 2
        }]
    },
    options: {
        plugins: {
            title: {
                display: true,
                text: 'Tension de sortie de l’onduleur'
            }
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: 'Volts (V)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Temps'
                }
            }
        }
    }
});
</script>

</body>
</html>
