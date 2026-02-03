<?php
// 🔐 Protection par authentification
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: ../auth/login.php");
    exit;
}

// 🔌 Connexion BDD
require_once "../config/database.php";

// 📊 Dernière mesure UPS
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

// Sécurités
$battery = (int)($data['battery_charge'] ?? 0);
$runtime = $data['battery_runtime'] ?? '—';
$load    = (int)($data['ups_load'] ?? 0);
$status  = $data['ups_status'] ?? 'DISCONNECTED';
$isOnline = ($status === 'OL');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard UPS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col">

<!-- 🔝 NAVBAR -->
<nav class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-sm">
    <div class="flex items-center gap-3">
        <div class="bg-blue-600 text-white p-2 rounded-lg">
            <i class="fas fa-server"></i>
        </div>
        <h1 class="font-bold text-xl">Moniteur UPS</h1>
    </div>

    <div class="flex items-center gap-4 text-sm">
        <span class="text-slate-500 hidden sm:inline">
            Connecté :
            <span class="font-semibold text-slate-700">
                <?= htmlspecialchars($_SESSION["user"]) ?>
            </span>
        </span>

        <a href="../auth/logout.php"
           class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition font-medium">
            <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
        </a>
    </div>
</nav>

<!-- 📊 CONTENU -->
<main class="flex-grow p-6">
    <div class="max-w-5xl mx-auto">

        <!-- HEADER UPS -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">
                    <?= htmlspecialchars($data['device_model'] ?? 'UPS inconnu') ?>
                </h2>
                <p class="text-slate-500 text-sm mt-1">
                    S/N: <?= htmlspecialchars($data['device_serial'] ?? '—') ?>
                    • Dernière mise à jour : <?= $data['timestamp'] ?? '—' ?>
                </p>
            </div>

            <!-- STATUT -->
            <div class="<?= $isOnline
                ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                : 'bg-red-100 text-red-700 border-red-200'
            ?> px-4 py-2 rounded-full font-bold flex items-center gap-2 border">
                <span class="relative flex h-3 w-3">
                    <span class="relative inline-flex rounded-full h-3 w-3 <?= $isOnline ? 'bg-emerald-500' : 'bg-red-500' ?>"></span>
                </span>
                Statut : <?= htmlspecialchars($status) ?>
            </div>
        </div>

        <!-- 🔲 GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- 🔋 BATTERIE -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                        <i class="fas fa-battery-three-quarters text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold"><?= $battery ?>%</span>
                </div>

                <h3 class="text-slate-500 font-medium mb-2">Charge Batterie</h3>

                <div class="w-full bg-slate-100 rounded-full h-2.5 mb-4">
                    <div class="bg-blue-600 h-2.5 rounded-full"
                         style="width: <?= $battery ?>%"></div>
                </div>

                <div class="text-sm text-slate-500">
                    Autonomie estimée :
                    <span class="font-semibold text-slate-700"><?= $runtime ?> sec</span>
                </div>
            </div>

            <!-- 🔌 CHARGE -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-xl">
                        <i class="fas fa-plug text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold"><?= $load ?>%</span>
                </div>

                <h3 class="text-slate-500 font-medium mb-2">Charge Onduleur</h3>

                <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-orange-500 h-2.5 rounded-full"
                         style="width: <?= $load ?>%"></div>
                </div>
            </div>

            <!-- ⚡ TENSIONS -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border flex flex-col gap-4">
                <div class="flex justify-between">
                    <span class="text-slate-500">Entrée</span>
                    <span class="font-bold"><?= $data['input_voltage'] ?? '—' ?> V</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Sortie</span>
                    <span class="font-bold"><?= $data['output_voltage'] ?? '—' ?> V</span>
                </div>
            </div>

        </div>
    </div>
</main>

<footer class="text-center py-6 text-slate-400 text-sm">
    &copy; 2024 Monitoring UPS
</footer>

</body>
</html>
