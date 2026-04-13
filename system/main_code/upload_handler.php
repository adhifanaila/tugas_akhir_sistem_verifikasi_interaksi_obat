<?php
$host = "localhost";
$db = "interaction_cheker";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_FILES["csv_file"]["error"] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES["csv_file"]["tmp_name"];
    $targetTable = $_POST["target_table"];

    // Nonaktifkan foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Kosongkan isi tabel (gunakan DELETE, bukan TRUNCATE)
    $conn->query("DELETE FROM $targetTable");

    // Reset AUTO_INCREMENT jika ingin dari 1
    $conn->query("ALTER TABLE $targetTable AUTO_INCREMENT = 1");

    // Aktifkan kembali foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // Buka file CSV
    if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row === 0) { // lewati header
                $row++;
                continue;
            }

            // Sesuaikan query insert untuk masing-masing tabel
            switch ($targetTable) {
                case "master_zat_aktif":
                    $zat_aktif = $conn->real_escape_string($data[0]);
                    $conn->query("INSERT INTO master_zat_aktif (zat_aktif) VALUES ('$zat_aktif')");
                    break;
                case "master_nama_obat":
                    $nama_obat = $conn->real_escape_string($data[0]);
                    $conn->query("INSERT INTO master_nama_obat (nama_obat) VALUES ('$nama_obat')");
                    break;
                case "drug_ingredients":
                    $drug_id = (int)$data[0];
                    $ingredient_id = (int)$data[1];
                    $conn->query("INSERT INTO drug_ingredients (drug_id, ingredient_id) VALUES ($drug_id, $ingredient_id)");
                    break;
                case "data_interaksi_indexed":
                    $id_1 = (int)$data[0];
                    $id_2 = (int)$data[1];
                    $level = $conn->real_escape_string($data[2]);
                    $conn->query("INSERT INTO data_interaksi_indexed (id_1, id_2, level) VALUES ($id_1, $id_2, '$level')");
                    break;
                default:
                    echo "Tabel tidak dikenali.";
                    exit;
            }

            $row++;
        }
        fclose($handle);
        echo "✅ Berhasil mengimpor <b>" . ($row - 1) . "</b> baris ke tabel <b>$targetTable</b>.";
    } else {
        echo "❌ Gagal membuka file.";
    }
} else {
    echo "❌ Upload gagal. Error code: " . $_FILES["csv_file"]["error"];
}

$conn->close();
?>
