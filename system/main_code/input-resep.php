<?php
session_start();
include 'koneksi.php'; // Pastikan koneksi ke database sudah ada

// Ambil data dari form
$doctor_id = $_SESSION['user_id']; // Mengambil ID dokter dari session
$patient_name = $_POST['nama']; // Mengambil nama pasien dari form
$tanggal = $_POST['tanggal']; // Mengambil tanggal resep dari form
$catatan = $_POST['catatan']; // Catatan resep

// Insert data resep ke tabel prescriptions
$queryPrescription = "INSERT INTO prescriptions (doctor_id, patient_id, created_at, catatan) VALUES ('$doctor_id', '$patient_id', '$tanggal', '$catatan')";
$insertPrescription = mysqli_query($conn, $queryPrescription);

if ($insertPrescription) {
    $prescription_id = mysqli_insert_id($conn); // Ambil ID resep yang baru saja dimasukkan
    
    // Ambil data obat, dosis, dan aturan pakai dari form
    $obat = $_POST['obat'];
    $dosis = $_POST['dosis'];
    $aturanpakai = $_POST['aturanpakai'];

    // Insert setiap item resep ke tabel prescription_items
    foreach ($obat as $index => $drug_id) {
        $dosage = $dosis[$index];
        $usage_instruction = $aturanpakai[$index];
        
        $queryItem = "INSERT INTO prescription_items (prescription_id, drug_id, dosage, usage_instruction) VALUES ('$prescription_id', '$drug_id', '$dosage', '$usage_instruction')";
        mysqli_query($conn, $queryItem);
    }

    // Pengecekan interaksi obat
    checkDrugInteractions($prescription_id);
    
    echo "Resep berhasil disimpan!";
} else {
    echo "Gagal menyimpan resep: " . mysqli_error($conn);
}

// Fungsi pengecekan interaksi obat
function checkDrugInteractions($prescription_id) {
    global $conn;
    
    // Ambil data obat yang ada di resep
    $query = "SELECT * FROM prescription_items WHERE prescription_id = '$prescription_id'";
    $result = mysqli_query($conn, $query);

    $drugs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $drugs[] = $row['drug_id'];
    }

    // Cek interaksi antar obat
    for ($i = 0; $i < count($drugs); $i++) {
        for ($j = $i + 1; $j < count($drugs); $j++) {
            $drug1 = $drugs[$i];
            $drug2 = $drugs[$j];

            $checkInteraction = "SELECT * FROM drug_interactions WHERE (drug_id_1 = '$drug1' AND drug_id_2 = '$drug2') OR (drug_id_1 = '$drug2' AND drug_id_2 = '$drug1')";
            $interactionResult = mysqli_query($conn, $checkInteraction);
            if (mysqli_num_rows($interactionResult) > 0) {
                $interaction = mysqli_fetch_assoc($interactionResult);
                echo "Interaksi obat ditemukan antara obat ID $drug1 dan $drug2. Level interaksi: " . $interaction['interaction_level'] . "<br>";
            }
        }
    }
}
?>