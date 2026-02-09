<?php
require_once __DIR__ . '/../config/config.php';

$API_BASE = "http://192.168.66.20:5000/api/ups";
$loopDelay = 2;

// Timer indépendant par UPS
$lastInsertTime = [];

while (true) {

    // ===== LISTE DES UPS =====
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

        // ===== DÉTAIL UPS =====
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
            // 🔹 Nouvel UPS détecté → insertion auto
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

        // ===== DONNÉES =====
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

            $lastInsertTime[$upsId] = time();

            if ($isCritical) {
                echo date("H:i:s") . " | 🚨 ALERTE UPS $upsId ($statusRaw)\n";
            } else {
                echo date("H:i:s") . " | UPS $upsId enregistré\n";
            }
        }
    }

    sleep($loopDelay);
}