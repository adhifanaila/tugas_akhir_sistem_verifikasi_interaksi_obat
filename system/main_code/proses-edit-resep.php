<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$prescription_id = intval($_POST['prescription_id']);
$nama = $_POST['nama'];
$tanggal_lahir = $_POST['tanggal_lahir'];
$usia = $_POST['usia'];
$alamat = $_POST['alamat'];
$diagnosis = $_POST['diagnosis'];
$catatan = $_POST['catatan'];

// 1. Update data pasien
$update_pasien = $conn->prepare("UPDATE patients p 
    JOIN prescriptions pr ON p.id = pr.patient_id
    SET p.name=?, p.tanggal_lahir=?, p.usia_pasien=?, p.alamat=?, p.diagnosis_pasien=?
    WHERE pr.id=?");
$update_pasien->bind_param("ssissi", $nama, $tanggal_lahir, $usia, $alamat, $diagnosis, $prescription_id);
$update_pasien->execute();
$update_pasien->close();

// 2. Update catatan resep
$update_resep = $conn->prepare("UPDATE prescriptions SET catatan=? WHERE id=?");
$update_resep->bind_param("si", $catatan, $prescription_id);
$update_resep->execute();
$update_resep->close();

// 3. Ambil data lama resep untuk perbandingan
$existing_items = [];
$res = $conn->prepare("SELECT id, drug_id, dosage, usage_instruction FROM prescription_items WHERE prescription_id=?");
$res->bind_param("i", $prescription_id);
$res->execute();
$result = $res->get_result();
while ($row = $result->fetch_assoc()) {
    $existing_items[$row['id']] = $row;
}
$res->close();

// 4. Loop dan proses obat baru
$item_ids = $_POST['item_id'];
$obat_ids = $_POST['obat'];
$dosis_list = $_POST['dosis'];
$aturanpakai_list = $_POST['aturanpakai'];

$submitted_ids = [];

for ($i = 0; $i < count($item_ids); $i++) {
    $item_id = $item_ids[$i];
    $drug_id = intval($obat_ids[$i]);
    $dosis = $dosis_list[$i];
    $aturan = $aturanpakai_list[$i];

    if ($item_id === "new") {
        // Tambah obat baru
        $stmt = $conn->prepare("INSERT INTO prescription_items (prescription_id, drug_id, dosage, usage_instruction) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $prescription_id, $drug_id, $dosis, $aturan);
        $stmt->execute();
        $stmt->close();
    } else {
        $item_id = intval($item_id);
        $submitted_ids[] = $item_id;

        $lama = $existing_items[$item_id];
        $ada_perubahan = (
            $lama['drug_id'] != $drug_id ||
            $lama['dosage'] != $dosis ||
            $lama['usage_instruction'] != $aturan
        );

        if ($ada_perubahan) {
            // Update item
            $stmt = $conn->prepare("UPDATE prescription_items SET drug_id=?, dosage=?, usage_instruction=? WHERE id=?");
            $stmt->bind_param("issi", $drug_id, $dosis, $aturan, $item_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// 5. Deteksi dan hapus obat yang dihapus dari form
foreach ($existing_items as $id => $data) {
    if (!in_array($id, $submitted_ids)) {
        // Hapus item
        $stmt = $conn->prepare("DELETE FROM prescription_items WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect ke halaman cek interaksi
header("Location: cek-interaksi.php?prescription_id=" . $prescription_id);
exit;
?>
