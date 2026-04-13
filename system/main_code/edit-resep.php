<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

if (!isset($_GET['prescription_id'])) {
    die("❌ prescription_id tidak ditemukan.");
}

$prescription_id = intval($_GET['prescription_id']);

// Koneksi
$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data pasien & resep
$query = "SELECT p.*, pr.catatan, pr.created_at FROM prescriptions pr
          JOIN patients p ON pr.patient_id = p.id
          WHERE pr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Data resep tidak ditemukan.");
}

// Ambil daftar obat
$stmt = $conn->prepare("SELECT pi.*, mo.nama_obat 
                        FROM prescription_items pi 
                        JOIN master_nama_obat mo ON pi.drug_id = mo.id
                        WHERE pi.prescription_id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$items_result = $stmt->get_result();
$obat_items = [];
while ($row = $items_result->fetch_assoc()) {
    $obat_items[] = $row;
}
$stmt->close();

// Ambil semua nama obat untuk dropdown
$nama_obat_result = $conn->query("SELECT id, nama_obat FROM master_nama_obat");
$nama_obat_options = [];
while ($row = $nama_obat_result->fetch_assoc()) {
    $nama_obat_options[$row['id']] = $row['nama_obat'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Resep</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2>Edit Resep Pasien</h2>
  <form action="proses-edit-resep.php" method="POST">
    <input type="hidden" name="prescription_id" value="<?= $prescription_id ?>">
    <input type="hidden" id="deleted_item_ids" name="deleted_item_ids" value="">

    <div class="mb-3">
      <label>Nama Pasien</label>
      <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($data['name']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Tanggal Lahir</label>
      <input type="date" class="form-control" name="tanggal_lahir" value="<?= $data['tanggal_lahir'] ?>" required>
    </div>
    <div class="mb-3">
      <label>Usia</label>
      <input type="text" class="form-control" name="usia" value="<?= $data['usia_pasien'] ?>" required>
    </div>
    <div class="mb-3">
      <label>Alamat</label>
      <input type="text" class="form-control" name="alamat" value="<?= htmlspecialchars($data['alamat']) ?>" required>
    </div>
    <div class="mb-3">
      <label>Diagnosis</label>
      <input type="text" class="form-control" name="diagnosis" value="<?= htmlspecialchars($data['diagnosis_pasien']) ?>" required>
    </div>

    <hr>
    <h5>Daftar Obat</h5>
    <div id="obat-container">
      <?php foreach ($obat_items as $index => $item): ?>
        <div class="border p-3 mb-3 obat-row" id="obat-row-<?= $index ?>">
          <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
          <div class="mb-2">
            <label>Nama Obat</label>
            <select name="obat[]" class="form-select select-obat" required>
              <?php foreach ($nama_obat_options as $id => $nama): ?>
                <option value="<?= $id ?>" <?= $item['drug_id'] == $id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($nama) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label>Dosis</label>
            <input type="text" class="form-control" name="dosis[]" value="<?= htmlspecialchars($item['dosage']) ?>" required>
          </div>
          <div class="mb-2">
            <label>Aturan Pakai</label>
            <input type="text" class="form-control" name="aturanpakai[]" value="<?= htmlspecialchars($item['usage_instruction']) ?>" required>
          </div>
          <button type="button" class="btn btn-danger btn-sm remove-obat" data-item-id="<?= $item['id'] ?>">✖ Hapus Obat</button>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="button" class="btn btn-outline-secondary mb-3" id="tambah-obat">+ Tambah Obat</button>

    <div class="mb-3">
      <label>Catatan Tambahan</label>
      <textarea name="catatan" class="form-control"><?= htmlspecialchars($data['catatan']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Simpan & Cek Interaksi</button>
  </form>
</div>

<script>
  $(document).ready(function () {
    const namaObatOptions = `<?php foreach ($nama_obat_options as $id => $nama): ?>
      <option value="<?= $id ?>"><?= htmlspecialchars($nama) ?></option>
    <?php endforeach; ?>`;

    let deletedItems = [];

    $('.select-obat').select2({ placeholder: "Pilih Obat", allowClear: true });

    $('#tambah-obat').click(function () {
      $('#obat-container').append(`
        <div class="border p-3 mb-3">
          <input type="hidden" name="item_id[]" value="new">
          <div class="mb-2">
            <label>Nama Obat</label>
            <select name="obat[]" class="form-select select-obat" required>
              ${namaObatOptions}
            </select>
          </div>
          <div class="mb-2">
            <label>Dosis</label>
            <input type="text" class="form-control" name="dosis[]" required>
          </div>
          <div class="mb-2">
            <label>Aturan Pakai</label>
            <input type="text" class="form-control" name="aturanpakai[]" required>
          </div>
          <button type="button" class="btn btn-danger btn-sm remove-obat">✖ Hapus Obat</button>
        </div>
      `);
      $('.select-obat').select2({ placeholder: "Pilih Obat", allowClear: true });
    });

    $(document).on('click', '.remove-obat', function () {
      const itemId = $(this).data('item-id');
      if (itemId && itemId !== 'new') {
        deletedItems.push(itemId);
        $('#deleted_item_ids').val(deletedItems.join(','));
      }
      $(this).closest('.obat-row').remove();
    });
  });
</script>
</body>
</html>
