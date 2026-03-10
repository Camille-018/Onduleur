<?php
//auto_collect.php : collect automatically UPS data every 2 seconds, insert into database, and check for alerts
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../alerte/verifieralerte.php';

$API_BASE = "http://192.168.66.20:5000/api/ups";
$loopDelay = 2; // seconds

// Table to track last insert time for each UPS to avoid flooding the database
$lastInsertTime = [];
echo "=== AUTO COLLECT STARTED ".date("Y-m-d H:i:s")." ===\n";

while (true) {

    // ===== UPS LIST =====
    $json = @file_get_contents($API_BASE);
    if (!$json) {
        echo date("H:i:s") . " | API injoignable\n";
        sleep($loopDelay);
        continue;
    }

    $api = json_decode($json, true);
    if (empty($api['ups'])) {
        echo date("H:i:s") . " | Aucun UPS trouvé\n";
        sleep($loopDelay);
        continue;
    }

    foreach ($api['ups'] as $upsName) {

        // ===== UPS DETAILS =====
        $detailJson = @file_get_contents("$API_BASE/$upsName");
        if (!$detailJson) {
            echo date("H:i:s") . " | $upsName injoignable\n";
            continue;
        }

        $data = json_decode($detailJson, true);
        if (!$data || empty($data['device.serial'])) {
            echo date("H:i:s") . " | UPS invalide / déconnecté\n";
            continue;
        }

        // ===== STATUS =====
        $statusRaw = $data['ups.status'] ?? 'UNKNOWN';
        $statusList = explode(' ', $statusRaw);

        $criticalStates = ['LB', 'OVER', 'BYPASS', 'OFF'];
        $warningStates  = ['OB', 'DISCHRG', 'TEST', 'CAL'];
        $normalStates   = ['OL', 'CHRG'];

        $isCritical = false;
        $isValid = false;

        foreach ($statusList as $s) {
            if (in_array($s, $criticalStates)) {
                $isCritical = true;
                $isValid = true;
            }
            if (in_array($s, $warningStates) || in_array($s, $normalStates)) {
                $isValid = true;
            }
        }

        if (!$isValid) continue;

        // ===== UPS ID =====
        $stmt = $pdo->prepare("SELECT id FROM ups WHERE device_serial = ?");
        $stmt->execute([$data['device.serial']]);
        $ups = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ups) {
            // 🔹 New UPS detected → automatic insertion
            $stmt = $pdo->prepare("
                INSERT INTO ups (device_serial, device_model)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $data['device.serial'],
                $data['device.model'] ?? 'UPS inconnu'
            ]);

            $upsId = $pdo->lastInsertId();

            echo date("H:i:s") . " | 🆕 Nouvel UPS ajouté ($upsId)\n";
        } else {
            $upsId = $ups['id'];
        }

        if (!isset($lastInsertTime[$upsId])) {
            $lastInsertTime[$upsId] = 0;
        }

        // ===== DATA =====
        $batteryCharge   = $data['battery.charge'] ?? null;
        $batteryRuntime = $data['battery.runtime'] ?? null;
        $inputVoltage   = $data['input.voltage'] ?? null;
        $outputVoltage  = $data['output.voltage'] ?? null;
        $upsLoad        = $data['ups.load'] ?? null;

        // ===== INSERT =====
        if ($isCritical || time() - $lastInsertTime[$upsId] >= 60) {

            $stmt = $pdo->prepare("
                INSERT INTO ups_history
                (ups_id, battery_charge, battery_runtime, input_voltage, output_voltage, ups_load, ups_status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $upsId,
                $batteryCharge,
                $batteryRuntime,
                $inputVoltage,
                $outputVoltage,
                $upsLoad,
                $statusRaw
            ]);
            
            $historyId = $pdo->lastInsertId(); // 🔹 get ID of this collect
            $lastInsertTime[$upsId] = time();

            verifierAlertePourCollecte($pdo, $historyId);

            // debug log
            if ($isCritical) {
                echo date("H:i:s") . " | 🚨 ALERTE UPS $upsId ($statusRaw)\n";
            } else {
                echo date("H:i:s") . " | UPS $upsId enregistré\n";
            }
        }
    }

    sleep($loopDelay);
}