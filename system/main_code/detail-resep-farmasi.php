<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'apoteker') {
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

// Ambil info pasien
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
    die("Data pasien tidak ditemukan.");
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

// Ambil semua drug_id
$stmt = $conn->prepare("SELECT drug_id FROM prescription_items WHERE prescription_id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$res = $stmt->get_result();
$drug_ids = [];
while ($row = $res->fetch_assoc()) {
    $drug_ids[] = $row['drug_id'];
}
$stmt->close();

$interaction_found = false;

// Ambil ingredient_id dari drug_id
if (count($drug_ids) >= 2) {
    $placeholders = implode(',', array_fill(0, count($drug_ids), '?'));
    $types = str_repeat('i', count($drug_ids));
    $stmt = $conn->prepare("SELECT DISTINCT drug_id, ingredient_id FROM drug_ingredients WHERE drug_id IN ($placeholders)");
    $stmt->bind_param($types, ...$drug_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $ingredient_to_drugs = []; // [ingredient_id => [drug_id, ...]]
    while ($row = $result->fetch_assoc()) {
        $ingredient_to_drugs[$row['ingredient_id']][] = $row['drug_id'];
    }
    $stmt->close();

    // Ambil nama zat aktif dari ingredient_id
    $ingredient_ids = array_keys($ingredient_to_drugs);
    $ingredient_map = [];
    if (count($ingredient_ids)) {
        $ph = implode(',', array_fill(0, count($ingredient_ids), '?'));
        $types = str_repeat('i', count($ingredient_ids));
        $stmt = $conn->prepare("SELECT id, zat_aktif FROM master_zat_aktif WHERE id IN ($ph)");
        $stmt->bind_param($types, ...$ingredient_ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ingredient_map[$row['id']] = strtolower($row['zat_aktif']);
        }
        $stmt->close();
    }

    // Ganti paracetamol jadi acetaminophen
    $stmt = $conn->prepare("SELECT id FROM master_zat_aktif WHERE LOWER(zat_aktif) = 'acetaminophen'");
    $stmt->execute();
    $res = $stmt->get_result();
    $acetaminophen_id = null;
    if ($row = $res->fetch_assoc()) {
        $acetaminophen_id = $row['id'];
    }
    $stmt->close();

    $updated_ingredients = [];
    foreach ($ingredient_map as $id => $name) {
        if ($name === 'paracetamol' && $acetaminophen_id !== null) {
            if (!isset($ingredient_to_drugs[$acetaminophen_id])) {
                $ingredient_to_drugs[$acetaminophen_id] = [];
            }
            $ingredient_to_drugs[$acetaminophen_id] = array_merge($ingredient_to_drugs[$acetaminophen_id], $ingredient_to_drugs[$id]);
            unset($ingredient_to_drugs[$id]);
        }
    }

    $ingredient_ids = array_keys($ingredient_to_drugs);

    // Cek interaksi antar zat aktif
    if (count($ingredient_ids) >= 2) {
        for ($i = 0; $i < count($ingredient_ids); $i++) {
            for ($j = $i + 1; $j < count($ingredient_ids); $j++) {
                $id1 = $ingredient_ids[$i];
                $id2 = $ingredient_ids[$j];

                $stmt = $conn->prepare("SELECT id_1, id_2 FROM data_interaksi_indexed WHERE (id_1 = ? AND id_2 = ?) OR (id_1 = ? AND id_2 = ?)");
                $stmt->bind_param("iiii", $id1, $id2, $id2, $id1);
                $stmt->execute();
                $check = $stmt->get_result();
                if ($check->num_rows > 0) {
                    $interaction_found = true;
                    $stmt->close();
                    break 2;
                }
                $stmt->close();
            }
        }
    }
}

// Dropdown semua obat
$drug_options = $conn->query("SELECT id, nama_obat FROM master_nama_obat ORDER BY nama_obat ASC");
$drug_option_html = '';
$drug_options->data_seek(0);
while ($drug = $drug_options->fetch_assoc()) {
    $drug_option_html .= '<option value="' . $drug['id'] . '">' . htmlspecialchars($drug['nama_obat']) . '</option>';
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Detail Resep Obat - Farmasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <style>
    body {
      background-color: #f7f9fc;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 960px;
    }
    .card {
      border-radius: 14px;
    }
    .card-header {
      background: linear-gradient(90deg, #1976d2, #2196f3);
      color: white;
      padding: 1rem 1.5rem;
      border-top-left-radius: 14px;
      border-top-right-radius: 14px;
    }
    .card-body h5 {
      font-weight: bold;
      margin-top: 1rem;
      color: #1976d2;
    }
    .table th {
      background-color: #f0f4f8;
    }
    .btn-primary {
      background-color: #1976d2;
      border: none;
    }
    .btn-primary:hover {
      background-color: #1565c0;
    }
    .note-box {
      background-color: #e3f2fd;
      border-left: 6px solid #2196f3;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-top: 1.25rem;
      color: #0d47a1;
      font-size: 1.1rem;
      line-height: 1.6;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      display: flex;
      align-items: center;
      gap: 12px;
      justify-content: center;
    }
    .note-box .icon {
      font-size: 1.5rem;
    }
    .danger-box {
      background-color: #ffebee;
      border-left: 6px solid #d32f2f;
      color: #b71c1c;
    }
  </style>
</head>
<body>

<div class="container my-5">
  <a href="dashboard-apoteker.php" class="btn btn-primary mb-3">← Kembali</a>

  <div class="card shadow-sm">
    <div class="card-header">
      <h4 class="mb-0">📋 Detail Resep Pasien</h4>
    </div>
    <div class="card-body">

      <h5>Informasi Pasien</h5>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-striped table-hover align-middle">
          <tbody>
            <tr><th>Nama</th><td><?= htmlspecialchars($patient['name']) ?></td></tr>
            <tr><th>Tanggal Lahir</th><td><?= htmlspecialchars($patient['tanggal_lahir']) ?></td></tr>
            <tr><th>Usia</th><td><?= htmlspecialchars($patient['usia_pasien']) ?></td></tr>
            <tr><th>Alamat</th><td><?= htmlspecialchars($patient['alamat']) ?></td></tr>
            <tr><th>Diagnosis</th><td><?= htmlspecialchars($patient['diagnosis_pasien']) ?></td></tr>
            <tr><th>Tanggal Resep</th><td><?= htmlspecialchars($patient['created_at']) ?></td></tr>
          </tbody>
        </table>
      </div>

      <h5>Daftar Obat</h5>
      <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle text-center">
          <thead><tr><th>Nama Obat</th><th>Dosis</th><th>Aturan Pakai</th></tr></thead>
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
      </div>

      <?php if (!empty(trim($patient['resep_catatan']))): ?>
        <h5>Catatan</h5>
        <div class="note-box">
          <?= nl2br(htmlspecialchars($patient['resep_catatan'])) ?>
        </div>
      <?php endif; ?>

      <h5>Status Interaksi Obat</h5>
      <div class="note-box <?= $interaction_found ? 'danger-box' : '' ?>">
        <?php if ($interaction_found): ?>
          <span class="icon">&#9888;</span> <strong>Resep ini mengandung interaksi berbahaya antar obat.</strong> Hubungi dokter untuk penanganan lebih lanjut.
        <?php else: ?>
          <span class="icon">&#10003;</span> <strong>Resep aman dari indikasi interaksi antar zat aktif.</strong>
        <?php endif; ?>
      </div>
    </div>
  </div>  

  <h5 class="mt-4">Tambahkan Obat Lain untuk Cek Interaksi</h5>
  <form method="POST" action="cek-interaksi.php">
    <div id="obat-container">
      <div class="row mb-2 obat-row align-items-center">
        <div class="col-md-10">
          <select name="obat_tambahan[]" class="form-select select2-obat" required>
            <option value="">Pilih Obat...</option>
            <?= $drug_option_html ?>
          </select>
        </div>
        <div class="col-md-2 text-end">
          <button type="button" class="btn btn-outline-danger btn-sm remove-obat">❌</button>
        </div>
      </div>
    </div>
    <div class="d-grid mb-3">
      <button type="button" id="tambah-obat-btn" class="btn btn-outline-secondary">➕ Tambah Obat Lagi</button>
    </div>
    <input type="hidden" name="prescription_id" value="<?= $prescription_id ?>">
    <button type="submit" class="btn btn-primary w-100">Cek Interaksi</button>
  </form>
</div>

<!-- Template Dropdown -->
<script id="dropdown-template" type="text/template">
  <div class="row mb-2 obat-row align-items-center">
    <div class="col-md-10">
      <select name="obat_tambahan[]" class="form-select select2-obat" required>
        <option value="">Pilih Obat...</option>
        <?= $drug_option_html ?>
      </select>
    </div>
    <div class="col-md-2 text-end">
      <button type="button" class="btn btn-outline-danger btn-sm remove-obat">❌</button>
    </div>
  </div>
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  function refreshSelect2() {
    $('.select2-obat').select2({ width: '100%' });
  }

  refreshSelect2();

  const obatContainer = document.getElementById("obat-container");
  const tambahBtn = document.getElementById("tambah-obat-btn");

  tambahBtn.addEventListener("click", function () {
    const template = document.getElementById("dropdown-template").innerHTML;
    const wrapper = document.createElement("div");
    wrapper.innerHTML = template;
    obatContainer.appendChild(wrapper.firstElementChild);
    refreshSelect2();
  });

  obatContainer.addEventListener("click", function(e) {
    if (e.target.classList.contains("remove-obat")) {
      const rows = document.querySelectorAll(".obat-row");
      if (rows.length > 1) {
        e.target.closest(".obat-row").remove();
      }
    }
  });
});
</script>

</body>
</html>
