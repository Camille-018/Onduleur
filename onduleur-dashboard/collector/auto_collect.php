<?php
require_once __DIR__ . "/../config/database.php";

$base_url = "http://192.168.66.20:5000/api/ups";

/* Horodatage de la dernière insertion */
$lastInsertTime = time();

while (true) {

    /* 1️⃣ Récupération de la liste des UPS */
    $liste_json = @file_get_contents($base_url);
    $liste = json_decode($liste_json, true);

    if (!$liste || !isset($liste['ups'])) {
        echo "Erreur récupération liste UPS\n";
        sleep(2);
        continue;
    }

    /* 2️⃣ Lecture des données toutes les 2 secondes */
    foreach ($liste['ups'] as $ups_api_id) {

        $data_json = @file_get_contents($base_url . "/" . $ups_api_id);
        $data = json_decode($data_json, true);

        if (!$data) {
            echo "Erreur données UPS $ups_api_id\n";
            continue;
        }

        /* 3️⃣ EXTRACTION DU SERIAL (depuis l'ID NUT) */
        // Exemple : ups_0463_ffff_G186T15143 → G186T15143
        $serial = substr($ups_api_id, strrpos($ups_api_id, '_') + 1);

        /* 4️⃣ CORRESPONDANCE AVEC LA TABLE ups */
        $stmt = $pdo->prepare("SELECT id FROM ups WHERE device_serial = ?");
        $stmt->execute([$serial]);
        $upsRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$upsRow) {
            echo "UPS inconnu en base : $serial\n";
            continue;
        }

        $ups_db_id = $upsRow['id'];

        /* 5️⃣ INSERTION UNE FOIS PAR MINUTE */
        if (time() - $lastInsertTime >= 60) {

            $sql = "INSERT INTO ups_history
            (ups_id, source, battery_charge, battery_runtime, input_voltage, output_voltage, ups_load, ups_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $ups_db_id,
                $data['source'] ?? 'usb',                 // usb / ethernet / esp32
                $data['battery.charge']  ?? null,
                $data['battery.runtime'] ?? null,
                $data['input.voltage']   ?? null,
                $data['output.voltage']  ?? null,
                $data['ups.load']        ?? null,
                $data['ups.status']      ?? null
            ]);

            echo date("H:i:s") . " | UPS $serial enregistré\n";
        }
    }

    /* 6️⃣ Reset du timer d'insertion */
    if (time() - $lastInsertTime >= 60) {
        $lastInsertTime = time();
        echo "---- Insertion minute OK ----\n";
    }

    /* ⏱️ Pause 2 secondes */
    sleep(2);
}
