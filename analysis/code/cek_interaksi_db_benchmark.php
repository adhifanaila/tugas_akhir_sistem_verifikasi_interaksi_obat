<?php
// Koneksi DB
$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Load resep dari JSON
$json = file_get_contents("resep_tinggi_20250814_180218.json");
$resep_data = json_decode($json, true);

// Ambil nama obat unik
$nama_obat = [];
foreach ($resep_data as $resep) {
    foreach ($resep['obat'] as $obat) {
        $nama_obat[] = strtolower(trim($obat));
    }
}
$nama_obat = array_unique($nama_obat);
echo "\n[INFO] Total nama obat unik input : " . count($nama_obat);

// Ambil drug_id dari nama obat
$placeholders = implode(',', array_fill(0, count($nama_obat), '?'));
$stmt = $conn->prepare("SELECT id FROM master_nama_obat WHERE LOWER(nama_obat) IN ($placeholders)");
$stmt->bind_param(str_repeat('s', count($nama_obat)), ...$nama_obat);
$stmt->execute();
$res = $stmt->get_result();
$drug_ids = [];
while ($row = $res->fetch_assoc()) {
    $drug_ids[] = $row['id'];
}
$stmt->close();

// Ambil ingredient_id dari semua drug_id
$ingredient_ids = [];
if (count($drug_ids)) {
    $placeholders2 = implode(',', array_fill(0, count($drug_ids), '?'));
    $stmt = $conn->prepare("SELECT DISTINCT ingredient_id FROM drug_ingredients WHERE drug_id IN ($placeholders2)");
    $stmt->bind_param(str_repeat('i', count($drug_ids)), ...$drug_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ingredient_ids[] = $row['ingredient_id'];
    }
    $stmt->close();
}

// Normalisasi paracetamol jadi acetaminophen
$normalized_ids = [];
foreach ($ingredient_ids as $id) {
    $stmt = $conn->prepare("SELECT LOWER(zat_aktif) as zat_aktif FROM master_zat_aktif WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $zat = $res->fetch_assoc();
    $stmt->close();

    if ($zat && $zat['zat_aktif'] === 'paracetamol') {
        // Cari id acetaminophen
        $stmt = $conn->prepare("SELECT id FROM master_zat_aktif WHERE LOWER(zat_aktif) = 'acetaminophen'");
        $stmt->execute();
        $res2 = $stmt->get_result();
        $acetaminophen = $res2->fetch_assoc();
        $stmt->close();

        if ($acetaminophen) {
            $normalized_ids[] = $acetaminophen['id'];
            continue;
        }
    }
    $normalized_ids[] = $id;
}
$ingredient_ids = array_unique($normalized_ids);
echo "\n[INFO] Total zat aktif ditemukan   : " . count($ingredient_ids);

if (count($ingredient_ids) < 2) {
    die("Minimal 2 zat aktif diperlukan.\n");
}

$start_time = microtime(true); // ⏱️ MULAI HITUNG EKSEKUSI

// Ambil semua kombinasi unik
$interactions_serialized = [];
$interactions_result = [];
$total_comb = 0;

$stmt = $conn->prepare("SELECT id_1, id_2, level FROM data_interaksi_indexed WHERE (id_1 = ? AND id_2 = ?) OR (id_1 = ? AND id_2 = ?)");
for ($i = 0; $i < count($ingredient_ids); $i++) {
    for ($j = $i + 1; $j < count($ingredient_ids); $j++) {
        $id1 = $ingredient_ids[$i];
        $id2 = $ingredient_ids[$j];
        $stmt->bind_param("iiii", $id1, $id2, $id2, $id1);
        $stmt->execute();
        $res = $stmt->get_result();
        $total_comb++;

        while ($row = $res->fetch_assoc()) {
            // Simpan hanya pasangan unik
            $pair_key = serialize([$row['id_1'], $row['id_2']]);
            if (!isset($interactions_serialized[$pair_key])) {
                $interactions_serialized[$pair_key] = true;
                $interactions_result[] = $row;
            }
        }
    }
}
$stmt->close();
echo "\n[INFO] Total kombinasi dicek      : $total_comb";

// Ambil nama zat aktif
$all_ids = array_unique(array_merge(
    array_column($interactions_result, 'id_1'),
    array_column($interactions_result, 'id_2')
));
$names = [];
$missing_ids = [];

if (count($all_ids)) {
    $placeholders3 = implode(',', array_fill(0, count($all_ids), '?'));
    $stmt = $conn->prepare("SELECT id, zat_aktif FROM master_zat_aktif WHERE id IN ($placeholders3)");
    $stmt->bind_param(str_repeat('i', count($all_ids)), ...$all_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $names[$row['id']] = $row['zat_aktif'];
    }
    $stmt->close();

    // Cek yang hilang
    foreach ($all_ids as $id_check) {
        if (!isset($names[$id_check])) {
            $missing_ids[] = $id_check;
        }
    }
}
echo "\n[INFO] Total zat aktif hilang     : " . count($missing_ids);
if (count($missing_ids)) {
    echo "\n[WARNING] ID tidak ditemukan di master_zat_aktif:\n";
    foreach ($missing_ids as $mid) {
        echo "- ID $mid\n";
    }
}

$end_time = microtime(true); // ⏱️ SELESAI HITUNG EKSEKUSI

// Output
echo "\n=== DATABASE LOOKUP RESULT ===\n";
foreach ($interactions_result as $row) {
    $zat1 = $names[$row['id_1']] ?? "[ID {$row['id_1']} tidak ditemukan]";
    $zat2 = $names[$row['id_2']] ?? "[ID {$row['id_2']} tidak ditemukan]";
    echo "$zat1 + $zat2 --> Level {$row['level']}\n";
}

echo "\nTotal interaksi unik: " . count($interactions_result);
echo "\nDurasi eksekusi     : " . round($end_time - $start_time, 6) . " detik";
echo "\nHeap memory (peak)  : " . number_format(memory_get_peak_usage(true) / 1048576, 2) . " MB";
echo "\nRSS memory (sistem) : " . number_format(memory_get_usage(true) / 1048576, 2) . " MB";

// Simpan ke log (opsional)
file_put_contents("logs/database_lookup.json", json_encode([
    "script" => "php_database_lookup",
    "jumlah_obat_unik" => count($nama_obat),
    "jumlah_zat_aktif" => count($ingredient_ids),
    "jumlah_kombinasi" => $total_comb,
    "jumlah_interaksi" => count($interactions_result),
    "zat_aktif_tidak_ditemukan" => $missing_ids,
    "waktu_eksekusi" => round($end_time - $start_time, 6),
    "heap_memory_mb" => round(memory_get_peak_usage(true) / 1048576, 2),
    "rss_memory_mb" => round(memory_get_usage(true) / 1048576, 2),
], JSON_PRETTY_PRINT));

$conn->close();
