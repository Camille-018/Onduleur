<?php
require_once __DIR__ . '/auth/authCheck.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM ups WHERE id = ?");
$stmt->execute([$id]);
$ups = $stmt->fetch();

if (!$ups) {
    die("UPS not found");
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

<a href="index.php">← Back to Overview</a>
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
            label: 'Battery (%)',
            data: battery,
            borderWidth: 2
        }]
    },
    options: {
        plugins: {
            title: {
                display: true,
                text: 'UPS battery Status'
            }
        },
        scales: {
            y: {
                title: {
                    display: true,
                    text: 'Percentage (%)'
                },
                min: 0,
                max: 100
            },
            x: {
                title: {
                    display: true,
                    text: 'Time'
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
            label: 'Output Voltage (V)',
            data: voltage,
            borderWidth: 2
        }]
    },
    options: {
        plugins: {
            title: {
                display: true,
                text: 'Output Voltage of the UPS'
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
                    text: 'Time'
                }
            }
        }
    }
});
</script>
<a href="index.php">← Back to Overview</a>
</body>
</html>