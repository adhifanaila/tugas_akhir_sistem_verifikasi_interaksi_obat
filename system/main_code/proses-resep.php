<?php
session_start();

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Tampilkan session info
echo "<h3>🔍 Debug Session:</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'TIDAK ADA') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'TIDAK ADA') . "<br>";
echo "Doctor ID: " . ($_SESSION['doctor_id'] ?? 'TIDAK ADA') . "<br><br>";

// Pastikan user login sebagai dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    die("❌ Error: User bukan dokter atau belum login");
}

if (!isset($_SESSION['doctor_id'])) {
    die("❌ Gagal: doctor_id tidak ditemukan di session. Pastikan user adalah dokter yang valid.");
}

$doctor_id = $_SESSION['doctor_id'];

// Debug: Tampilkan data POST
echo "<h3>📝 Debug Data POST:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre><br>";

// Koneksi ke database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}

echo "✅ Koneksi database berhasil<br><br>";

// Validasi data POST
$required_fields = ['nama', 'tanggal_lahir', 'usia', 'alamat', 'diagnosis', 'tanggal', 'obat', 'dosis', 'aturanpakai'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        die("❌ Error: Field '$field' tidak boleh kosong");
    }
}

// Ambil data dari form
$nama = $_POST['nama'];
$tanggal_lahir = $_POST['tanggal_lahir'];
$usia = $_POST['usia'];
$alamat = $_POST['alamat'];
$diagnosis = $_POST['diagnosis'];
$tanggal_resep = $_POST['tanggal'];
$catatan_form = $_POST['catatan'] ?? '';
$obats = $_POST['obat'];
$dosis = $_POST['dosis'];
$aturanpakai = $_POST['aturanpakai'];

echo "✅ Data form berhasil diambil<br>";

// Validasi array obat
if (!is_array($obats) || count($obats) == 0) {
    die("❌ Error: Tidak ada obat yang dipilih");
}

if (count($obats) != count($dosis) || count($obats) != count($aturanpakai)) {
    die("❌ Error: Jumlah obat, dosis, dan aturan pakai tidak sesuai");
}

echo "✅ Validasi array obat berhasil<br>";

// Cek apakah pasien sudah ada berdasarkan nama dan tanggal lahir
$stmt = $conn->prepare("SELECT id FROM patients WHERE name = ? AND tanggal_lahir = ?");
if (!$stmt) {
    die("❌ Error prepare statement 1: " . $conn->error);
}

$stmt->bind_param("ss", $nama, $tanggal_lahir);
if (!$stmt->execute()) {
    die("❌ Error execute statement 1: " . $stmt->error);
}

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $patient_id = $row['id'];
    echo "✅ Pasien sudah ada dengan ID: $patient_id<br>";
} else {
    // Insert pasien baru jika belum ada
    $stmt_insert = $conn->prepare("INSERT INTO patients (name, tanggal_lahir, usia_pasien, alamat, diagnosis_pasien) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_insert) {
        die("❌ Error prepare insert patient: " . $conn->error);
    }
    
    $stmt_insert->bind_param("sssss", $nama, $tanggal_lahir, $usia, $alamat, $diagnosis);
    if (!$stmt_insert->execute()) {
        die("❌ Error insert patient: " . $stmt_insert->error);
    }
    
    $patient_id = $stmt_insert->insert_id;
    echo "✅ Pasien baru berhasil ditambahkan dengan ID: $patient_id<br>";
    $stmt_insert->close();
}
$stmt->close();

// Insert ke tabel prescriptions
$stmt = $conn->prepare("INSERT INTO prescriptions (doctor_id, patient_id, created_at, catatan) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("❌ Error prepare insert prescription: " . $conn->error);
}

$stmt->bind_param("iiss", $doctor_id, $patient_id, $tanggal_resep, $catatan_form);
if (!$stmt->execute()) {
    die("❌ Error insert prescription: " . $stmt->error);
}

$prescription_id = $stmt->insert_id;
echo "✅ Prescription berhasil disimpan dengan ID: $prescription_id<br>";
$stmt->close();

// Insert ke tabel prescription_items
$stmt = $conn->prepare("INSERT INTO prescription_items (prescription_id, drug_id, dosage, usage_instruction) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("❌ Error prepare insert prescription items: " . $conn->error);
}

$success_count = 0;
for ($i = 0; $i < count($obats); $i++) {
    $drug_id = $obats[$i];
    $dose = $dosis[$i];
    $usage = $aturanpakai[$i];
    
    echo "🔄 Menyimpan obat $i: Drug ID=$drug_id, Dose=$dose, Usage=$usage<br>";
    
    $stmt->bind_param("iiss", $prescription_id, $drug_id, $dose, $usage);
    if (!$stmt->execute()) {
        echo "❌ Error insert prescription item $i: " . $stmt->error . "<br>";
    } else {
        $success_count++;
        echo "✅ Obat $i berhasil disimpan<br>";
    }
}
$stmt->close();

echo "<br>📊 Summary: $success_count dari " . count($obats) . " obat berhasil disimpan<br><br>";

// Cek interaksi jika ada lebih dari satu obat
if (count($obats) > 1) {
    echo "🔄 Redirect ke cek interaksi...<br>";
    echo "<a href='cek-interaksi.php?prescription_id=$prescription_id'>Lanjut ke Cek Interaksi</a><br>";
    // Uncomment untuk auto redirect:
    // header("Location: cek-interaksi.php?prescription_id=" . $prescription_id);
} else {
    echo "🔄 Redirect ke detail resep...<br>";
    echo "<a href='detail-resep.php?prescription_id=$prescription_id'>Lihat Detail Resep</a><br>";
    // Uncomment untuk auto redirect:
    // header("Location: detail-resep.php?prescription_id=" . $prescription_id);
}

$conn->close();
echo "<br>✅ Proses selesai!";
?>

<!-- Debug Form untuk testing manual -->
<br><br>
<h3>🧪 Form Debug (Manual Testing)</h3>
<form method="POST" action="">
    <input type="text" name="nama" placeholder="Nama Pasien" required><br><br>
    <input type="date" name="tanggal_lahir" required><br><br>
    <input type="text" name="usia" placeholder="25 tahun" required><br><br>
    <input type="text" name="alamat" placeholder="Alamat" required><br><br>
    <textarea name="diagnosis" placeholder="Diagnosis" required></textarea><br><br>
    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required><br><br>
    <textarea name="catatan" placeholder="Catatan tambahan"></textarea><br><br>
    
    <select name="obat[]" required>
        <option value="1">Obat 1</option>
        <option value="2">Obat 2</option>
    </select>
    <input type="text" name="dosis[]" placeholder="Dosis 1" required><br><br>
    
    <select name="obat[]">
        <option value="">Pilih obat 2 (opsional)</option>
        <option value="1">Obat 1</option>
        <option value="2">Obat 2</option>
    </select>
    <input type="text" name="dosis[]" placeholder="Dosis 2">
    <input type="text" name="aturanpakai[]" placeholder="Aturan 2"><br><br>
    
    <input type="text" name="aturanpakai[]" placeholder="Aturan pakai 1" required><br><br>
    
    <button type="submit">Test Submit</button>
</form>