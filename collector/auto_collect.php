<?php
// Afficher erreurs (debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion BDD
$pdo = new PDO(
    "mysql:host=localhost;dbname=ups_onduleur;charset=utf8",
    "root",
    ""
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// URL API Flask
$url = "http://192.168.66.20:5000/api/ups";

echo "Collecteur UPS démarré...\n";

while (true) {

    echo "[" . date('Y-m-d H:i:s') . "] Collecte...\n";

    // Lire JSON depuis Flask
    $json = @file_get_contents($url);

    if ($json === false) {
        echo "❌ API inaccessible\n";
        sleep(60);
        continue;
    }

    $data = json_decode($json, true);

    if (!$data) {
        echo "❌ JSON invalide\n";
        sleep(60);
        continue;
    }

    // Infos fixes
    $serial = $data['device.serial'] ?? null;
    $model  = $data['device.model'] ?? null;

    if (!$serial) {
        echo "❌ device.serial manquant\n";
        sleep(60);
        continue;
    }

    // UPS existe ?
    $stmt = $pdo->prepare("SELECT id FROM ups WHERE device_serial = ?");
    $stmt->execute([$serial]);
    $ups = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ups) {
        $stmt = $pdo->prepare(
            "INSERT INTO ups (device_serial, device_model) VALUES (?, ?)"
        );
        $stmt->execute([$serial, $model]);
        $ups_id = $pdo->lastInsertId();
    } else {
        $ups_id = $ups['id'];
    }

    // Timestamp
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

    // Insertion historique
    $stmt = $pdo->prepare("
        INSERT INTO ups_history (
            ups_id,
            battery_charge,
            battery_runtime,
            input_voltage,
            output_voltage,
            ups_load,
            ups_status,
            timestamp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $ups_id,
        $data['battery.charge'] ?? null,
        $data['battery.runtime'] ?? null,
        $data['input.voltage'] ?? null,
        $data['output.voltage'] ?? null,
        $data['ups.load'] ?? null,
        $data['ups.status'] ?? null,
        $timestamp
    ]);

    echo "✅ Données enregistrées\n";

    // Attente 60 secondes
    sleep(60);
}
