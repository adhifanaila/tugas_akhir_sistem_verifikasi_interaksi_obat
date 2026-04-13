<?php
session_start();

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Konfigurasi database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset untuk mendukung karakter khusus
$conn->set_charset("utf8");

function setMessage($message, $type) {
    $_SESSION['import_message'] = $message;
    $_SESSION['import_type'] = $type;
}

function redirectBack() {
    header("Location: import-obat.php");
    exit;
}

// Validasi file upload
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    setMessage("Error dalam upload file. Silakan coba lagi.", "danger");
    redirectBack();
}

$uploadedFile = $_FILES['csv_file'];

// Validasi tipe file
$fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
if ($fileExtension !== 'csv') {
    setMessage("File harus berformat CSV (.csv)", "danger");
    redirectBack();
}

// Validasi ukuran file (maksimal 10MB)
if ($uploadedFile['size'] > 10 * 1024 * 1024) {
    setMessage("Ukuran file terlalu besar. Maksimal 10MB.", "danger");
    redirectBack();
}

try {
    // Mulai transaksi
    $conn->autocommit(false);
    
    $csvFile = fopen($uploadedFile['tmp_name'], 'r');
    if (!$csvFile) {
        throw new Exception("Gagal membaca file CSV");
    }

    // Skip header jika ada
    $header = fgetcsv($csvFile);
    
    // Validasi header
    if (!$header || count($header) < 2) {
        throw new Exception("Format CSV tidak valid. Pastikan ada kolom Nama_Obat dan Zat_Aktif");
    }

    // Prepare statements untuk efisiensi
    $stmt_check_obat = $conn->prepare("SELECT id FROM master_nama_obat WHERE nama_obat = ?");
    $stmt_insert_obat = $conn->prepare("INSERT INTO master_nama_obat (nama_obat) VALUES (?)");
    $stmt_check_zat = $conn->prepare("SELECT id FROM master_zat_aktif WHERE zat_aktif = ?");
    $stmt_insert_zat = $conn->prepare("INSERT INTO master_zat_aktif (zat_aktif) VALUES (?)");
    $stmt_check_ingredient = $conn->prepare("SELECT id FROM drug_ingredients WHERE drug_id = ? AND ingredient_id = ?");
    $stmt_insert_ingredient = $conn->prepare("INSERT INTO drug_ingredients (drug_id, ingredient_id) VALUES (?, ?)");

    $totalRows = 0;
    $successRows = 0;
    $skippedRows = 0;
    $errors = [];

    while (($data = fgetcsv($csvFile)) !== FALSE) {
        $totalRows++;
        
        // Skip baris kosong
        if (empty($data[0]) && empty($data[1])) {
            continue;
        }

        // Validasi data
        if (count($data) < 2 || empty(trim($data[0]))) {
            $errors[] = "Baris $totalRows: Data tidak lengkap";
            $skippedRows++;
            continue;
        }

        $nama_obat = trim($data[0]);
        $zat_aktif_raw = trim($data[1]);

        // Pisahkan multiple zat aktif (dipisah dengan koma)
        $zat_aktif_array = array_map('trim', explode(',', $zat_aktif_raw));
        $zat_aktif_array = array_filter($zat_aktif_array); // Hapus element kosong

        if (empty($zat_aktif_array)) {
            $errors[] = "Baris $totalRows: Zat aktif tidak ditemukan untuk $nama_obat";
            $skippedRows++;
            continue;
        }

        try {
            // 1. Cek/Insert nama obat
            $stmt_check_obat->bind_param("s", $nama_obat);
            $stmt_check_obat->execute();
            $result_obat = $stmt_check_obat->get_result();
            
            if ($result_obat->num_rows > 0) {
                $obat_id = $result_obat->fetch_assoc()['id'];
            } else {
                $stmt_insert_obat->bind_param("s", $nama_obat);
                if (!$stmt_insert_obat->execute()) {
                    throw new Exception("Gagal menambah obat: $nama_obat");
                }
                $obat_id = $conn->insert_id;
            }

            // 2. Proses setiap zat aktif
            $ingredient_added = 0;
            foreach ($zat_aktif_array as $zat_aktif) {
                if (empty($zat_aktif)) continue;
                
                // Cek/Insert zat aktif
                $stmt_check_zat->bind_param("s", $zat_aktif);
                $stmt_check_zat->execute();
                $result_zat = $stmt_check_zat->get_result();
                
                if ($result_zat->num_rows > 0) {
                    $zat_id = $result_zat->fetch_assoc()['id'];
                } else {
                    $stmt_insert_zat->bind_param("s", $zat_aktif);
                    if (!$stmt_insert_zat->execute()) {
                        throw new Exception("Gagal menambah zat aktif: $zat_aktif");
                    }
                    $zat_id = $conn->insert_id;
                }

                // 3. Cek/Insert drug_ingredients (hindari duplikat)
                $stmt_check_ingredient->bind_param("ii", $obat_id, $zat_id);
                $stmt_check_ingredient->execute();
                $result_ingredient = $stmt_check_ingredient->get_result();
                
                if ($result_ingredient->num_rows == 0) {
                    $stmt_insert_ingredient->bind_param("ii", $obat_id, $zat_id);
                    if ($stmt_insert_ingredient->execute()) {
                        $ingredient_added++;
                    }
                }
            }

            if ($ingredient_added > 0) {
                $successRows++;
            } else {
                $skippedRows++;
                $errors[] = "Baris $totalRows: $nama_obat sudah ada dengan zat aktif yang sama";
            }

        } catch (Exception $e) {
            $errors[] = "Baris $totalRows: " . $e->getMessage();
            $skippedRows++;
        }
    }

    fclose($csvFile);

    // Commit transaksi
    $conn->commit();

    // Prepare success message
    $message = "Import berhasil!<br>";
    $message .= "• Total baris diproses: $totalRows<br>";
    $message .= "• Berhasil: $successRows<br>";
    $message .= "• Dilewati/Error: $skippedRows";

    if (!empty($errors) && count($errors) <= 10) {
        $message .= "<br><br>Detail error:<br>";
        foreach (array_slice($errors, 0, 10) as $error) {
            $message .= "• $error<br>";
        }
        if (count($errors) > 10) {
            $message .= "• ... dan " . (count($errors) - 10) . " error lainnya";
        }
    }

    setMessage($message, $successRows > 0 ? "success" : "warning");

} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    setMessage("Error: " . $e->getMessage(), "danger");
} finally {
    // Tutup prepared statements
    if (isset($stmt_check_obat)) $stmt_check_obat->close();
    if (isset($stmt_insert_obat)) $stmt_insert_obat->close();
    if (isset($stmt_check_zat)) $stmt_check_zat->close();
    if (isset($stmt_insert_zat)) $stmt_insert_zat->close();
    if (isset($stmt_check_ingredient)) $stmt_check_ingredient->close();
    if (isset($stmt_insert_ingredient)) $stmt_insert_ingredient->close();
    
    $conn->close();
}

redirectBack();
?>