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

$user_id = $_SESSION['user_id'];

// Ambil nama apoteker
$sql = "SELECT name FROM pharmacists WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$apoteker = $result->fetch_assoc();
$nama_apoteker = $apoteker['name'] ?? 'Apoteker';

// Ambil parameter pencarian
$search_patient = isset($_GET['search_patient']) ? trim($_GET['search_patient']) : '';

// Query dengan kondisi pencarian
$sql_resep = "
    SELECT r.id AS prescription_id, r.created_at, p.name AS patient_name, d.name AS doctor_name
    FROM prescriptions r
    JOIN patients p ON r.patient_id = p.id
    JOIN doctors d ON r.doctor_id = d.id
    WHERE r.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
";

// Tambahkan kondisi pencarian jika ada
$params = [];
$types = "";
if (!empty($search_patient)) {
    $sql_resep .= " AND p.name LIKE ?";
    $params[] = "%" . $search_patient . "%";
    $types .= "s";
}

$sql_resep .= " ORDER BY r.created_at DESC";

$stmt_resep = $conn->prepare($sql_resep);
if (!empty($params)) {
    $stmt_resep->bind_param($types, ...$params);
}
$stmt_resep->execute();
$res_resep = $stmt_resep->get_result();

$items_by_prescription = [];
$resep_data = [];

if ($res_resep && $res_resep->num_rows > 0) {
    $ids = [];
    while ($row = $res_resep->fetch_assoc()) {
        $ids[] = $row['prescription_id'];
        $resep_data[] = $row;
    }

    if (count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types_items = str_repeat('i', count($ids));
        $sql_items = "
            SELECT pi.prescription_id, mo.nama_obat
            FROM prescription_items pi
            JOIN master_nama_obat mo ON pi.drug_id = mo.id
            WHERE pi.prescription_id IN ($placeholders)
        ";

        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param($types_items, ...$ids);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($item = $result_items->fetch_assoc()) {
            $items_by_prescription[$item['prescription_id']][] = $item['nama_obat'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Riwayat Resep</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f1f5f9;
      margin: 0;
      padding: 0;
    }

    nav {
      background: linear-gradient(90deg, #1976d2, #2196f3);
      padding: 1rem 2rem;
      color: white;
      font-weight: bold;
      border-radius: 0 0 12px 12px;
    }

    .main-container {
      max-width: 1100px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    h2 {
      text-align: center;
      color: #1976d2;
      margin-bottom: 30px;
    }

    .top-action-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .btn-back {
      background-color: #1976d2;
      color: white;
      font-weight: 600;
      padding: 10px 20px;
      border: none;
      border-radius: 12px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: background-color 0.3s ease;
      font-size: 0.95rem;
    }

    .btn-back:hover {
      background-color: #1565c0;
    }

    .search-form {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .search-input {
      padding: 10px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 0.95rem;
      width: 250px;
      transition: border-color 0.3s ease;
    }

    .search-input:focus {
      outline: none;
      border-color: #1976d2;
    }

    .btn-search {
      background-color: #4caf50;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s ease;
      font-size: 0.95rem;
    }

    .btn-search:hover {
      background-color: #45a049;
    }

    .btn-clear {
      background-color: #ff7043;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s ease;
      font-size: 0.95rem;
      text-decoration: none;
      display: inline-block;
    }

    .btn-clear:hover {
      background-color: #f4511e;
    }

    .search-info {
      background-color: #e3f2fd;
      border-left: 4px solid #1976d2;
      padding: 10px 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      font-size: 0.9rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 0.95rem;
    }

    thead th {
      background-color: #1976d2;
      color: white;
      padding: 12px;
      text-align: left;
    }

    tbody td {
      padding: 12px;
      border-bottom: 1px solid #e0e0e0;
    }

    tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tbody tr:hover {
      background-color: #f0f8ff;
    }

    .btn-detail {
      background-color: #ff7043;
      color: white;
      padding: 6px 12px;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    .btn-detail:hover {
      background-color: #f4511e;
    }

    .no-data {
      text-align: center;
      color: #666;
      font-style: italic;
      padding: 40px 20px;
    }

    @media (max-width: 768px) {
      .top-action-row {
        flex-direction: column;
        align-items: stretch;
      }
      
      .search-form {
        flex-direction: column;
        gap: 10px;
      }
      
      .search-input {
        width: 100%;
      }
      
      .main-container {
        margin: 20px 10px;
        padding: 20px;
      }
      
      table {
        font-size: 0.85rem;
      }
      
      thead th, tbody td {
        padding: 8px;
      }
    }
  </style>
</head>
<body>
  <nav>
    <a href="dashboard-apoteker.php" class="btn-back">← Kembali</a>
  </nav>
  
  <div class="main-container">
    <h2>Riwayat Resep 5 Tahun Terakhir</h2>

    <div class="top-action-row">
      <div></div>
      <form class="search-form" method="GET">
        <input 
          type="text" 
          name="search_patient" 
          class="search-input" 
          placeholder="Cari berdasarkan nama pasien..." 
          value="<?= htmlspecialchars($search_patient) ?>"
        >
        <button type="submit" class="btn-search">🔍 Cari</button>
        <?php if (!empty($search_patient)): ?>
          <a href="?" class="btn-clear">✕ Hapus</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if (!empty($search_patient)): ?>
      <div class="search-info">
        Menampilkan hasil pencarian untuk: "<strong><?= htmlspecialchars($search_patient) ?></strong>" 
        (<?= count($resep_data) ?> resep ditemukan)
      </div>
    <?php endif; ?>

    <?php if (empty($resep_data)): ?>
      <div class="no-data">
        <?php if (!empty($search_patient)): ?>
          Tidak ada resep ditemukan untuk pencarian "<?= htmlspecialchars($search_patient) ?>".
        <?php else: ?>
          Tidak ada data resep ditemukan.
        <?php endif; ?>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Tanggal Resep</th>
            <th>Nama Dokter</th>
            <th>Nama Pasien</th>
            <th>Nama Obat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resep_data as $row): ?>
            <tr>
              <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['created_at']))) ?></td>
              <td><?= htmlspecialchars($row['doctor_name']) ?></td>
              <td>
                <?php 
                $patient_name = htmlspecialchars($row['patient_name']);
                // Highlight kata pencarian jika ada
                if (!empty($search_patient)) {
                    $patient_name = preg_replace(
                        '/(' . preg_quote($search_patient, '/') . ')/i', 
                        '<mark style="background-color: #ffeb3b;">$1</mark>', 
                        $patient_name
                    );
                }
                echo $patient_name;
                ?>
              </td>
              <td><?= htmlspecialchars(implode(', ', $items_by_prescription[$row['prescription_id']] ?? [])) ?></td>
              <td><a href="detail-resep-farmasi.php?prescription_id=<?= $row['prescription_id'] ?>" class="btn-detail">Detail</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>
</html>