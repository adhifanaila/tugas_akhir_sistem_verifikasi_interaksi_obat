<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

$doctor_id = $_SESSION['user_id'];

if (!isset($_GET['prescription_id'])) {
    die("❌ prescription_id tidak ditemukan.");
}

$prescription_id = intval($_GET['prescription_id']);

$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$stmt = $conn->prepare("
    SELECT l.*, 
           m1.nama_obat AS old_obat, 
           m2.nama_obat AS new_obat
    FROM prescription_log l
    JOIN prescriptions p ON l.prescription_id = p.id
    LEFT JOIN master_nama_obat m1 ON l.old_drug_id = m1.id
    LEFT JOIN master_nama_obat m2 ON l.new_drug_id = m2.id
    WHERE l.prescription_id = ? AND p.doctor_id = ?
    ORDER BY l.changed_at DESC
");
$stmt->bind_param("ii", $prescription_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Log Perubahan Resep</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3>📜 Riwayat Perubahan Resep (Anda)</h3>
  <a href="detail-resep.php?prescription_id=<?= $prescription_id ?>" class="btn btn-primary my-3">⬅ Kembali ke Detail Resep</a>

  <?php if (count($logs) === 0): ?>
    <div class="alert alert-info">Anda belum melakukan perubahan terhadap resep ini.</div>
  <?php else: ?>
    <table class="table table-bordered table-hover">
      <thead class="table-light">
        <tr>
          <th>Waktu Perubahan</th>
          <th>Obat Lama</th>
          <th>Dosis Lama</th>
          <th>Aturan Pakai Lama</th>
          <th>Obat Baru</th>
          <th>Dosis Baru</th>
          <th>Aturan Pakai Baru</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?= htmlspecialchars($log['changed_at']) ?></td>
            <td><?= htmlspecialchars($log['old_obat'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['old_dosage']) ?? '-' ?></td>
            <td><?= htmlspecialchars($log['old_usage']) ?? '-' ?></td>
            <td><?= htmlspecialchars($log['new_obat'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['new_dosage']) ?? '-' ?></td>
            <td><?= htmlspecialchars($log['new_usage']) ?? '-' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
</body>
</html>
