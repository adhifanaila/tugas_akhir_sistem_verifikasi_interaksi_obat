<?php
echo "<h2>=== DEBUGGING AKTIF ===</h2>";

// 1. Load normalisasi data (Nama_Obat dan Zat_Aktif)
$normalisasi = [];
if (($handle = fopen("data_normalisasi.csv", "r")) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== false) {
        $nama_obat = strtolower(trim($data[0]));
        $zat_aktif = array_map('trim', explode(',', $data[1]));
        if (!isset($normalisasi[$nama_obat])) {
            $normalisasi[$nama_obat] = [];
        }
        foreach ($zat_aktif as $zat) {
            if (!in_array($zat, $normalisasi[$nama_obat])) {
                $normalisasi[$nama_obat][] = $zat;
            }
        }
    }
    fclose($handle);
}
echo "<pre><strong>Normalisasi Data:</strong>\n";
print_r(array_slice($normalisasi, 0, 5)); // tampilkan 5 contoh pertama
echo "</pre>";


// 2. Load data interaksi
$interaksi = [];
if (($handle = fopen("data_interaksi.csv", "r")) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== false) {
        $key = strtolower(trim($data[0])) . '|' . strtolower(trim($data[1]));
        $interaksi[$key] = $data[2]; // level interaksi
    }
    fclose($handle);
}
echo "<pre><strong>Contoh Data Interaksi:</strong>\n";
print_r(array_slice($interaksi, 0, 5));
echo "</pre>";


// 3. Load input resep
$input = json_decode(file_get_contents("input_resep_sample.json"), true);
$obat_input = [];
foreach ($input["resep"] as $resep) {
    foreach ($resep["obat"] as $obat) {
        $obat_input[] = strtolower(trim($obat));
    }
}
echo "<pre><strong>Obat Input dari JSON:</strong>\n";
print_r($obat_input);
echo "</pre>";


// 4. Ambil semua zat aktif dari input
$zat_aktif_resep = [];
foreach ($obat_input as $obat) {
    if (isset($normalisasi[$obat])) {
        foreach ($normalisasi[$obat] as $zat) {
            $zat_aktif_resep[] = strtolower($zat);
        }
    } else {
        echo "<p style='color:red'>[!] Obat tidak ditemukan di data normalisasi: <strong>$obat</strong></p>";
    }
}

// Hilangkan duplikat
$zat_aktif_resep = array_unique($zat_aktif_resep);
echo "<pre><strong>Zat Aktif dari Input Resep (setelah normalisasi):</strong>\n";
print_r($zat_aktif_resep);
echo "</pre>";


// 5. Cek interaksi antar kombinasi zat aktif
$hasil_interaksi = [];
for ($i = 0; $i < count($zat_aktif_resep); $i++) {
    for ($j = $i + 1; $j < count($zat_aktif_resep); $j++) {
        $a = $zat_aktif_resep[$i];
        $b = $zat_aktif_resep[$j];
        $key1 = "$a|$b";
        $key2 = "$b|$a";

        if (isset($interaksi[$key1])) {
            $hasil_interaksi[] = [
                'zat_aktif_1' => $a,
                'zat_aktif_2' => $b,
                'level' => $interaksi[$key1]
            ];
            echo "<p style='color:green'>✔ Interaksi ditemukan: $a + $b → Level {$interaksi[$key1]}</p>";
        } elseif (isset($interaksi[$key2])) {
            $hasil_interaksi[] = [
                'zat_aktif_1' => $b,
                'zat_aktif_2' => $a,
                'level' => $interaksi[$key2]
            ];
            echo "<p style='color:green'>✔ Interaksi ditemukan: $b + $a → Level {$interaksi[$key2]}</p>";
        } else {
            echo "<p style='color:gray'>- Tidak ada interaksi: $a + $b</p>";
        }
    }
}

// 6. Output akhir
echo "<h3>=== HASIL INTERAKSI FINAL ===</h3>";
echo "<pre>";
echo json_encode($hasil_interaksi, JSON_PRETTY_PRINT);
echo "</pre>";
