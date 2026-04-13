<?php
$start_time = microtime(true);
echo "=== DEBUGGING AKTIF ===\n";

// Fungsi membersihkan nama obat dari dosis dan spasi
function normalize_obat_name($nama) {
    $nama = strtolower(trim($nama));
    $nama = preg_replace('/\s*\d+(\.\d+)?\s*(mg|ml|mcg|g|gr|iu|units)?\b/i', '', $nama);
    $nama = preg_replace('/\s+/', ' ', $nama);
    return trim($nama);
}

// Load data normalisasi
$normalisasi = [];
if (($handle = fopen("data_normalisasi.csv", "r")) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 2) continue;
        $nama_obat = normalize_obat_name($data[0]);
        $zat_aktif = array_map('strtolower', array_map('trim', explode(',', $data[1])));
        foreach ($zat_aktif as $zat) {
            if (!isset($normalisasi[$nama_obat])) {
                $normalisasi[$nama_obat] = [];
            }
            if (!in_array($zat, $normalisasi[$nama_obat])) {
                $normalisasi[$nama_obat][] = $zat;
            }
        }
    }
    fclose($handle);
}

// Load data interaksi
$interaksi = [];
if (($handle = fopen("data_interaksi.csv", "r")) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 3) {
            echo "[!] Baris CSV tidak valid: " . implode(", ", $data) . "\n";
            continue;
        }
        $a = strtolower(trim($data[0]));
        $b = strtolower(trim($data[1]));
        $level = trim($data[2]);
        $key1 = "$a|$b";
        $key2 = "$b|$a";
        $interaksi[$key1] = $level;
        $interaksi[$key2] = $level;
    }
    fclose($handle);
}

// Load input resep dari file JSON
$input = json_decode(file_get_contents("resep_tinggi_20250814_180218i.json"), true);

// Ambil semua nama obat dari semua resep
$obat_input = [];
foreach ($input as $resep) {
    foreach ($resep["obat"] as $obat) {
        $nama_bersih = normalize_obat_name($obat);
        $obat_input[] = $nama_bersih;
    }
}

// Ambil zat aktif dari obat
$zat_aktif_resep = [];
foreach ($obat_input as $obat) {
    if (isset($normalisasi[$obat])) {
        foreach ($normalisasi[$obat] as $zat) {
            $zat_aktif_resep[] = strtolower($zat);
        }
    } else {
        echo "[!] Obat tidak ditemukan di data normalisasi: $obat\n";
    }
}
$zat_aktif_resep = array_values(array_unique($zat_aktif_resep));

// Cek interaksi antar zat aktif
$hasil_interaksi = [];
$cek_duplikat = [];
for ($i = 0; $i < count($zat_aktif_resep); $i++) {
    for ($j = $i + 1; $j < count($zat_aktif_resep); $j++) {
        $a = $zat_aktif_resep[$i];
        $b = $zat_aktif_resep[$j];
        $key1 = "$a|$b";
        if (isset($interaksi[$key1]) && !in_array($key1, $cek_duplikat)) {
            $hasil_interaksi[] = [
                'zat_aktif_1' => $a,
                'zat_aktif_2' => $b,
                'level' => $interaksi[$key1]
            ];
            $cek_duplikat[] = $key1;
        }
    }
}

// Output hasil
echo "\n=== HASIL INTERAKSI FINAL ===\n";
foreach ($hasil_interaksi as $interaksi_item) {
    // Uncomment kalau ingin print interaksi satu-satu
    // echo "{$interaksi_item['zat_aktif_1']} + {$interaksi_item['zat_aktif_2']} --> Level {$interaksi_item['level']}\n";
}
echo "\n=== JUMLAH INTERAKSI: " . count($hasil_interaksi) . " ===\n";

// Monitoring eksekusi dan memori
$end_time = microtime(true);
$duration = $end_time - $start_time;
echo "\n=== EXECUTION TIME: " . number_format($duration, 6) . " seconds ===\n";

$peak_memory = memory_get_peak_usage(true);
$rss_memory = memory_get_usage(true);
$peak_memory_mb = $peak_memory / 1048576;
$rss_memory_mb = $rss_memory / 1048576;

echo "Peak memory (heap)  : " . number_format($peak_memory_mb, 2) . " MB\n";
echo "RSS memory (sistem) : " . number_format($rss_memory_mb, 2) . " MB\n";

// Logging ke CSV
$fp = fopen("benchmark_log.csv", "a");
if (filesize("benchmark_log.csv") === 0) {
    fputcsv($fp, ["script", "duration_seconds"]);
}
fputcsv($fp, ["php", $duration]);
fclose($fp);

// ✅ Simpan juga ke logs/php.json
if (!is_dir("logs")) {
    mkdir("logs", 0777, true);
}
$json_log = [
    "script" => "php_import_file",
    "jumlah_interaksi" => count($hasil_interaksi),
    "waktu_eksekusi" => round($duration, 4),
    "heap_memory_mb" => round($peak_memory_mb, 2),
    "rss_memory_mb" => round($rss_memory_mb, 2)
];
file_put_contents("logs/php.json", json_encode($json_log, JSON_PRETTY_PRINT));
?>
