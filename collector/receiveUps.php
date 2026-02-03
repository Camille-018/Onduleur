<?php
session_start();
require_once "../config/config.php";
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 2️⃣ URL de l’API Flask
$url = "http://192.168.66.20:5000/api/ups";

// 3️⃣ Récupération du JSON
$json = @file_get_contents($url);
$data = json_decode($json, true);

// ❌ API inaccessible ou JSON vide
if (!$data || !is_array($data)) {
    http_response_code(202);
    echo "<script>
        alert('UPS non connecté ou API indisponible');
        window.location.href = '../index.php';
        </script>";
    exit;
}

// 4️⃣ Récupération sécurisée des infos UPS
$serial = $data['device.serial']
       ?? $data['ups_id']
       ?? null;

$model = $data['device.model']
      ?? $data['source']
      ?? 'unknown';

// ❌ Pas d’onduleur détecté → on STOP proprement
if ($serial === null) {
    http_response_code(202);
    echo "<script>
        UPS non connecté (aucun identifiant)
        window.location.href = '../index.php';
        </script>";
    exit;
}

// 5️⃣ Vérifier si l’UPS existe déjà
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

// 6️⃣ Timestamp
$timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');

// 7️⃣ Insertion historique
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
    $data['ups.status'] ?? 'DISCONNECTED',
    $timestamp
]);

echo "<script>
        alert('Collecte OK');
        window.location.href = '../index.php';
        </script>";
    exit;
