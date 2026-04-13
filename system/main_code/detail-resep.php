<?php
session_start();

// Pastikan user login sebagai dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if (!isset($_GET['prescription_id'])) {
    die("Prescription ID tidak ditemukan.");
}
$prescription_id = intval($_GET['prescription_id']);

// Ambil informasi resep dan pasien
$query = "
    SELECT p.*, pr.created_at, pr.catatan AS resep_catatan
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    WHERE pr.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Data tidak ditemukan.");
}

// Ambil item resep
$query = "
    SELECT pi.*, m.nama_obat
    FROM prescription_items pi
    JOIN master_nama_obat m ON pi.drug_id = m.id
    WHERE pi.prescription_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$res_items = $stmt->get_result();
$items = [];
while ($row = $res_items->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Cek status interaksi obat
$drug_ids = array_column($items, 'drug_id');
$interaction_found = false;

if (count($drug_ids) >= 2) {
    // Ambil ingredient_id dari semua obat
    $placeholders = implode(',', array_fill(0, count($drug_ids), '?'));
    $types = str_repeat('i', count($drug_ids));
    $stmt = $conn->prepare("SELECT DISTINCT ingredient_id FROM drug_ingredients WHERE drug_id IN ($placeholders)");
    $stmt->bind_param($types, ...$drug_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $ingredient_ids = [];
    while ($row = $result->fetch_assoc()) {
        $ingredient_ids[] = $row['ingredient_id'];
    }
    $stmt->close();

    // Normalisasi paracetamol → acetaminophen
    $normalized_ids = [];
    $acetaminophen_id = null;
    foreach ($ingredient_ids as $id) {
        $stmt = $conn->prepare("SELECT id, LOWER(zat_aktif) AS zat FROM master_zat_aktif WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $zat = $res->fetch_assoc();
        $stmt->close();

        if ($zat['zat'] === 'paracetamol') {
            if ($acetaminophen_id === null) {
                $stmt = $conn->prepare("SELECT id FROM master_zat_aktif WHERE LOWER(zat_aktif) = 'acetaminophen'");
                $stmt->execute();
                $res = $stmt->get_result();
                $acet = $res->fetch_assoc();
                $stmt->close();
                $acetaminophen_id = $acet['id'] ?? $id;
            }
            $normalized_ids[] = $acetaminophen_id;
        } else {
            $normalized_ids[] = $id;
        }
    }
    $ingredient_ids = array_unique($normalized_ids);

    // Cek kombinasi interaksi antar zat aktif
    if (count($ingredient_ids) >= 2) {
        $stmt = $conn->prepare("SELECT id_1, id_2 FROM data_interaksi_indexed WHERE (id_1 = ? AND id_2 = ?) OR (id_1 = ? AND id_2 = ?)");
        for ($i = 0; $i < count($ingredient_ids); $i++) {
            for ($j = $i + 1; $j < count($ingredient_ids); $j++) {
                $id1 = $ingredient_ids[$i];
                $id2 = $ingredient_ids[$j];
                $stmt->bind_param("iiii", $id1, $id2, $id2, $id1);
                $stmt->execute();
                $check = $stmt->get_result();
                if ($check->num_rows > 0) {
                    $interaction_found = true;
                    break 2;
                }
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Resep Pasien</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f7f9fc;
      font-family: 'Segoe UI', sans-serif;
    }
    .container { max-width: 900px; }
    .card { border-radius: 14px; }
    .card-header {
      background: linear-gradient(90deg, #1976d2, #2196f3);
      color: white;
      padding: 1rem 1.5rem;
      border-top-left-radius: 14px;
      border-top-right-radius: 14px;
    }
    .card-body h5 { font-weight: bold; margin-top: 1rem; color: #1976d2; }
    .table th { background-color: #f0f4f8; }
    .btn-warning { font-weight: 600; color: #fff; background-color: #ff9800; border: none; }
    .btn-warning:hover { background-color: #fb8c00; }
    .btn-primary { background-color: #1976d2; border: none; }
    .btn-primary:hover { background-color: #1565c0; }
    .note-box {
      border-left: 6px solid;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-top: 1.25rem;
      font-size: 1.1rem;
      line-height: 1.6;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      display: flex;
      align-items: center;
      gap: 12px;
      justify-content: center;
    }
    .note-box .icon { font-size: 1.5rem; }
  </style>
</head>
<body>
  <div class="container my-5">
    <a href="dokter-riwayat.php" class="btn btn-primary mb-3">← Kembali</a>

    <div class="card shadow-sm">
      <div class="card-header">
        <h4 class="mb-0">📋 Detail Resep Pasien</h4>
      </div>
      <div class="card-body">

        <h5>Informasi Pasien</h5>
        <table class="table table-bordered table-striped table-hover align-middle mb-4">
          <tbody>
            <tr><th style="width: 200px;">Nama</th><td><?= htmlspecialchars($patient['name']) ?></td></tr>
            <tr><th>Tanggal Lahir</th><td><?= htmlspecialchars($patient['tanggal_lahir']) ?></td></tr>
            <tr><th>Usia</th><td><?= htmlspecialchars($patient['usia_pasien']) ?></td></tr>
            <tr><th>Alamat</th><td><?= htmlspecialchars($patient['alamat']) ?></td></tr>
            <tr><th>Diagnosis</th><td><?= htmlspecialchars($patient['diagnosis_pasien']) ?></td></tr>
            <tr><th>Tanggal Resep</th><td><?= htmlspecialchars($patient['created_at']) ?></td></tr>
          </tbody>
        </table>

        <h5>Daftar Obat</h5>
        <table class="table table-bordered align-middle text-center">
          <thead>
            <tr><th>Nama Obat</th><th>Dosis</th><th>Aturan Pakai</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['nama_obat']) ?></td>
                <td><?= htmlspecialchars($item['dosage']) ?></td>
                <td><?= htmlspecialchars($item['usage_instruction']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php if (!empty(trim($patient['resep_catatan']))): ?>
          <h5 class="mt-4">🗒️ Catatan</h5>
          <div class="note-box" style="background-color: #e3f2fd; border-left-color: #2196f3; color: #0d47a1;">
            <?= nl2br(htmlspecialchars($patient['resep_catatan'])) ?>
          </div>
        <?php endif; ?>

        <h5 class="mt-4">🔍 Status Interaksi Obat</h5>
        <div class="note-box"
          style="background-color: <?= $interaction_found ? '#ffebee' : '#e3f2fd' ?>;
                 border-left-color: <?= $interaction_found ? '#e53935' : '#2196f3' ?>;
                 color: <?= $interaction_found ? '#b71c1c' : '#0d47a1' ?>;">
          <?php if ($interaction_found): ?>
            <span class="icon">⚠️</span>
            <strong>Resep ini mengandung interaksi <u>berbahaya</u> antar obat.</strong> Mohon periksa ulang komposisi resep.
          <?php else: ?>
            <span class="icon">✅</span>
            <strong>Resep aman dari interaksi antar zat aktif.</strong>
          <?php endif; ?>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <a href="edit-resep.php?prescription_id=<?= $prescription_id ?>" class="btn btn-warning">✏️ Edit Resep</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
