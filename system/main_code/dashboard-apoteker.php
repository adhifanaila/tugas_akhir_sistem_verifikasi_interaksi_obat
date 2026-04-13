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

// Ambil resep hari ini
$sql_resep = "
    SELECT r.id AS prescription_id, r.created_at, p.name AS patient_name, d.name AS doctor_name
    FROM prescriptions r
    JOIN patients p ON r.patient_id = p.id
    JOIN doctors d ON r.doctor_id = d.id
    WHERE DATE(r.created_at) = CURDATE()
    ORDER BY r.created_at DESC
";

$res_resep = $conn->query($sql_resep);

// Ambil semua item resep hari ini
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
        $types = str_repeat('i', count($ids));
        $sql_items = "
            SELECT pi.prescription_id, mo.nama_obat
            FROM prescription_items pi
            JOIN master_nama_obat mo ON pi.drug_id = mo.id
            WHERE pi.prescription_id IN ($placeholders)
        ";

        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param($types, ...$ids);
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Farmasi</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }

    /* Background Animation */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                  radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                  radial-gradient(circle at 40% 80%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
      animation: backgroundMove 20s ease-in-out infinite;
      z-index: -1;
    }

    @keyframes backgroundMove {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.8; }
    }

    /* Navigation */
    nav {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 1.2rem 2rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .nav-left {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .nav-left a {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-decoration: none;
      font-weight: 700;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .nav-left a:hover {
      background: rgba(102, 126, 234, 0.1);
      transform: translateX(-5px);
    }

    .nav-right {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .profile-btn,
    .logout-btn {
      font-weight: 600;
      text-decoration: none;
      padding: 0.8rem 1.5rem;
      border-radius: 12px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      font-size: 0.95rem;
    }

    .profile-btn {
      background: linear-gradient(135deg, #4fc3f7, #29b6f6);
      color: white;
      box-shadow: 0 4px 15px rgba(79, 195, 247, 0.4);
    }

    .profile-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(79, 195, 247, 0.6);
    }

    .logout-btn {
      background: linear-gradient(135deg, #ef5350, #e53935);
      color: white;
      box-shadow: 0 4px 15px rgba(239, 83, 80, 0.4);
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(239, 83, 80, 0.6);
    }

    /* Main Container */
    .main-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      max-width: 1200px;
      margin: 2rem auto;
      padding: 3rem;
      border-radius: 24px;
      box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideInUp 0.8s ease-out;
      position: relative;
      overflow: hidden;
    }

    .main-container::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.08) 0%, transparent 70%);
      animation: rotate 20s linear infinite;
    }

    /* Header Welcome */
    .welcome-header {
      text-align: center;
      margin-bottom: 3rem;
      position: relative;
      z-index: 2;
    }

    .welcome-header::before {
      content: '💊';
      font-size: 4rem;
      display: block;
      margin-bottom: 1rem;
      animation: bounce 2s ease-in-out infinite;
    }

    .welcome-header h2 {
      font-size: 2.5rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }

    .welcome-subtitle {
      color: #666;
      font-size: 1.1rem;
      font-weight: 500;
    }

    /* Action Section */
    .action-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      position: relative;
      z-index: 2;
    }

    .section-title {
      font-size: 1.8rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .section-icon {
      font-size: 2rem;
      animation: pulse 2s ease-in-out infinite;
    }

    .btn-riwayat {
      background: linear-gradient(135deg, #4fc3f7, #29b6f6);
      color: white;
      font-weight: 600;
      padding: 1rem 2rem;
      border: none;
      border-radius: 15px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.8rem;
      transition: all 0.3s ease;
      font-size: 1rem;
      box-shadow: 0 8px 25px rgba(79, 195, 247, 0.4);
      position: relative;
      overflow: hidden;
    }

    .btn-riwayat::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .btn-riwayat:hover::before {
      left: 100%;
    }

    .btn-riwayat:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(79, 195, 247, 0.6);
    }

    /* Table Styling */
    .table-container {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.3);
      position: relative;
      z-index: 2;
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
      font-size: 0.95rem;
    }

    thead th {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 1.2rem;
      text-align: left;
      font-weight: 700;
      border: none;
      position: relative;
    }

    thead th:first-child {
      border-radius: 15px 0 0 0;
    }

    thead th:last-child {
      border-radius: 0 15px 0 0;
    }

    tbody td {
      padding: 1.2rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    tbody tr {
      transition: all 0.3s ease;
    }

    tbody tr:hover {
      background: rgba(102, 126, 234, 0.05);
      transform: translateX(5px);
    }

    tbody tr:nth-child(even) {
      background: rgba(0, 0, 0, 0.02);
    }

    .btn-detail {
      background: linear-gradient(135deg, #ff7043, #e64a19);
      color: white;
      padding: 0.7rem 1.5rem;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 15px rgba(255, 112, 67, 0.4);
      position: relative;
      overflow: hidden;
    }

    .btn-detail::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .btn-detail:hover::before {
      left: 100%;
    }

    .btn-detail:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 112, 67, 0.6);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      position: relative;
      z-index: 2;
    }

    .empty-state::before {
      content: '📋';
      font-size: 5rem;
      display: block;
      margin-bottom: 1.5rem;
      opacity: 0.7;
      animation: bounce 2s ease-in-out infinite;
    }

    .empty-state h3 {
      font-size: 1.5rem;
      color: #666;
      margin-bottom: 1rem;
    }

    .empty-state p {
      color: #888;
      font-size: 1.1rem;
    }

    /* Animations */
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes bounce {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .main-container {
        margin: 1rem;
        padding: 2rem;
      }

      .stats-container {
        grid-template-columns: 1fr;
      }

      .action-section {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
      }

      nav {
        padding: 1rem;
        flex-direction: column;
        gap: 1rem;
      }

      .nav-right {
        width: 100%;
        justify-content: center;
      }

      table {
        font-size: 0.85rem;
      }

      thead th,
      tbody td {
        padding: 0.8rem;
      }

      .welcome-header h2 {
        font-size: 2rem;
      }

      .table-container {
        overflow-x: auto;
      }
    }

    /* Loading Animation */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(102, 126, 234, 0.3);
      border-radius: 50%;
      border-top-color: #667eea;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

  <nav>
    <div class="nav-left">
      <a href="dashboard-farmasi.php">🏠 Dashboard Farmasi</a>
    </div>
    <div class="nav-right">
      <a href="profile-apoteker.php" class="profile-btn">👤 Profile</a>
      <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
  </nav>

  <div class="main-container">
    <div class="welcome-header">
      <h2>Selamat Datang, <?= htmlspecialchars($nama_apoteker); ?></h2>
      <p class="welcome-subtitle">Sistem Manajemen Farmasi & Verifikasi Interaksi Obat</p>
    </div>

    <div class="action-section">
      <h3 class="section-title">
        <span class="section-icon">💊</span>
        Resep Dokter Hari Ini
      </h3>
      <a href="riwayat-resep.php" class="btn-riwayat">
        📄 Lihat Riwayat Resep
      </a>
    </div>

    <?php if (empty($resep_data)): ?>
      <div class="empty-state">
        <h3>Belum Ada Resep Hari Ini</h3>
        <p>Tidak ada resep yang ditulis hari ini. Resep baru akan muncul di sini.</p>
      </div>
    <?php else: ?>
      <div class="table-container">
        <table>
          <thead>
          <tr>
            <th>📅 Tanggal Resep</th>
            <th>👨‍⚕️ Nama Dokter</th>
            <th>👤 Nama Pasien</th>
            <th>💊 Nama Obat</th>
            <th>⚡ Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resep_data as $index => $row): ?>
            <tr style="animation-delay: <?= $index * 0.1 ?>s">
              <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['created_at']))) ?></td>
              <td><?= htmlspecialchars($row['doctor_name']) ?></td>
              <td><?= htmlspecialchars($row['patient_name']) ?></td>
              <td><?= htmlspecialchars(implode(', ', $items_by_prescription[$row['prescription_id']] ?? [])) ?></td>
              <td>
                <a href="detail-resep-farmasi.php?prescription_id=<?= $row['prescription_id'] ?>" class="btn-detail">
                  🔍 Detail
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Add entrance animations
    window.addEventListener('load', function() {
      const cards = document.querySelectorAll('.stat-card');
      cards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.2) + 's';
        card.style.animation = 'slideInUp 0.6s ease-out both';
      });

      const rows = document.querySelectorAll('tbody tr');
      rows.forEach((row, index) => {
        row.style.animationDelay = (index * 0.1) + 's';
        row.style.animation = 'slideInUp 0.4s ease-out both';
      });
    });

    // Add smooth hover effects
    document.querySelectorAll('.btn-detail, .btn-riwayat, .profile-btn, .logout-btn').forEach(button => {
      button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px) scale(1.02)';
      });

      button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
      });
    });

    // Real-time clock update
    function updateTime() {
      const now = new Date();
      const timeElement = document.querySelector('.stat-card:nth-child(2) .stat-number');
      if (timeElement) {
        timeElement.textContent = now.toLocaleTimeString('id-ID', {
          hour: '2-digit',
          minute: '2-digit'
        });
      }
    }

    // Update time every minute
    setInterval(updateTime, 60000);

    // Add subtle parallax effect
    window.addEventListener('scroll', function() {
      const scrolled = window.pageYOffset;
      const parallax = document.querySelector('.main-container::before');
      if (parallax) {
        parallax.style.transform = `translateY(${scrolled * 0.1}px)`;
      }
    });
  </script>

</body>
</html>