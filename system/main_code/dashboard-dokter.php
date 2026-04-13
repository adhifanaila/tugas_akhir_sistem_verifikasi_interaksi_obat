<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login dan role-nya adalah dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

// Koneksi ke database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil doctor_id dan nama dokter dari tabel doctors berdasarkan user_id
$user_id = $_SESSION['user_id'];
$sql = "SELECT id, name FROM doctors WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$nama = "Dokter";
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $_SESSION['doctor_id'] = $row['id']; // ✅ SIMPAN doctor_id YANG BENAR
    $nama = htmlspecialchars($row['name']);
} else {
    echo "<p style='color:red;'>❌ Gagal: Data dokter tidak ditemukan.</p>";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Dokter</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      color: #333;
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
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .nav-left {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .nav-left a {
      font-size: 1.4rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .nav-center {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(102, 126, 234, 0.1);
      padding: 0.5rem;
      border-radius: 15px;
    }

    .nav-center a {
      font-weight: 600;
      text-decoration: none;
      padding: 12px 20px;
      border-radius: 12px;
      color: #667eea;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .nav-center a::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      transition: left 0.3s ease;
      z-index: -1;
    }

    .nav-center a:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .nav-center a:hover::before {
      left: 0;
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .profile-btn, .logout-btn {
      font-weight: 600;
      text-decoration: none;
      padding: 12px 20px;
      border-radius: 12px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .profile-btn {
      background: linear-gradient(135deg, #4fc3f7, #29b6f6);
      color: white;
    }

    .profile-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(79, 195, 247, 0.4);
    }

    .logout-btn {
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
    }

    /* Main Content */
    .main-container {
      padding: 3rem 2rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
      margin-bottom: 2rem;
    }

    .welcome-section {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 3rem;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideInLeft 0.8s ease-out;
      position: relative;
      overflow: hidden;
    }

    .welcome-section::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
      animation: rotate 10s linear infinite;
    }

    .welcome-section h2 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 1rem;
      position: relative;
      z-index: 2;
    }

    .welcome-greeting {
      background: linear-gradient(135deg, #2e7d32, #4caf50);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .doctor-name {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      position: relative;
    }

    .doctor-name::after {
      content: '👨‍⚕️';
      margin-left: 10px;
      animation: wave 2s ease-in-out infinite;
    }

    .welcome-description {
      font-size: 1.1rem;
      color: #666;
      line-height: 1.6;
      position: relative;
      z-index: 2;
    }

    .stats-section {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 2rem;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideInRight 0.8s ease-out;
    }

    .stats-title {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .stat-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      margin-bottom: 1rem;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      border-radius: 12px;
      transition: transform 0.3s ease;
    }

    .stat-item:hover {
      transform: translateX(5px);
    }

    .stat-label {
      font-weight: 600;
      color: #667eea;
    }

    .stat-value {
      font-weight: 700;
      font-size: 1.2rem;
      color: #764ba2;
    }

    /* Quick Actions */
    .quick-actions {
      margin-top: 2rem;
    }

    .quick-actions h3 {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .action-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 2rem;
      max-width: 800px;
      margin: 0 auto;
    }

    .action-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .action-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
      transition: left 0.3s ease;
    }

    .action-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
    }

    .action-card:hover::before {
      left: 0;
    }

    .card-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      display: block;
      animation: bounce 2s ease-in-out infinite;
    }

    .card-title {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: #333;
      position: relative;
      z-index: 2;
    }

    .card-description {
      color: #666;
      font-size: 0.9rem;
      position: relative;
      z-index: 2;
    }

    /* Time Display */
    .time-display {
      position: fixed;
      top: 50%;
      right: 2rem;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(20px);
      padding: 1.5rem;
      border-radius: 20px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      z-index: 100;
    }

    .current-time {
      font-size: 1.2rem;
      font-weight: 700;
      color: #667eea;
      margin-bottom: 0.5rem;
    }

    .current-date {
      font-size: 0.9rem;
      color: #666;
    }

    /* Animations */
    @keyframes slideInLeft {
      from { opacity: 0; transform: translateX(-50px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes slideInRight {
      from { opacity: 0; transform: translateX(50px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @keyframes wave {
      0%, 100% { transform: rotate(0deg); }
      25% { transform: rotate(-10deg); }
      75% { transform: rotate(10deg); }
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      nav {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
      }

      .nav-center {
        order: 2;
        width: 100%;
        justify-content: center;
      }

      .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }

      .main-container {
        padding: 2rem 1rem;
      }

      .welcome-section {
        padding: 2rem;
      }

      .welcome-section h2 {
        font-size: 1.8rem;
      }

      .time-display {
        position: static;
        transform: none;
        margin: 2rem auto;
        width: fit-content;
      }

      .action-cards {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<?php

// Cek apakah user sudah login dan role-nya adalah dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

// Koneksi ke database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil doctor_id dan nama dokter dari tabel doctors berdasarkan user_id
$user_id = $_SESSION['user_id'];
$sql = "SELECT id, name FROM doctors WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$nama = "Dokter";
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $_SESSION['doctor_id'] = $row['id']; // ✅ SIMPAN doctor_id YANG BENAR
    $nama = htmlspecialchars($row['name']);
} else {
    echo "<p style='color:red;'>❌ Gagal: Data dokter tidak ditemukan.</p>";
    exit;
}

$conn->close();
?>
  <nav>
    <div class="nav-left">
      <a href="dashboard-dokter.php">🏠 Dashboard Dokter</a>
    </div>
    <div class="nav-center">
      <a href="dokter-input.php">📝 Input Resep</a>
      <a href="dokter-riwayat.php">📋 Riwayat Resep</a>
    </div>
    <div class="nav-right">
      <a href="profile.php" class="profile-btn">👤 Profile</a>
      <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
  </nav>

  <main class="main-container">
    <div class="dashboard-grid">
      <div class="welcome-section">
        <h2>
          <span class="welcome-greeting">Selamat datang,</span><br>
          <span class="doctor-name">dr. <?= $nama ?></span>
        </h2>
        <p class="welcome-description">
          Sistem Verifikasi Resep Obat siap membantu Anda dalam mengelola keamanan resep pasien dengan aman dan efisien. 
          Gunakan menu navigasi untuk mengakses fitur-fitur yang tersedia.
        </p>
      </div>
    </div>

    <div class="quick-actions">
      <h3>🚀 Aksi Cepat</h3>
      <div class="action-cards">
        <div class="action-card" onclick="location.href='dokter-input.php'">
          <span class="card-icon">📝</span>
          <h4 class="card-title">Buat Resep Baru</h4>
          <p class="card-description">Mulai membuat resep untuk pasien dengan sistem verifikasi otomatis</p>
        </div>
        
        <div class="action-card" onclick="location.href='dokter-riwayat.php'">
          <span class="card-icon">📋</span>
          <h4 class="card-title">Lihat Riwayat</h4>
          <p class="card-description">Tinjau semua resep yang telah dibuat dan status verifikasinya</p>
        </div>
      </div>
    </div>
  </main>

  <div class="time-display">
    <div class="current-time" id="currentTime">--:--:--</div>
    <div class="current-date" id="currentDate">-- --- ----</div>
  </div>

  <script>
    // Update time display
    function updateTime() {
      const now = new Date();
      const timeOptions = { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false 
      };
      const dateOptions = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      };
      
      document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID', timeOptions);
      document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', dateOptions);
    }

    // Update time every second
    updateTime();
    setInterval(updateTime, 1000);

    // Add hover effects to navigation
    document.querySelectorAll('.nav-center a, .profile-btn, .logout-btn').forEach(link => {
      link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
      });
      
      link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
    });

    // Add click animation to action cards
    document.querySelectorAll('.action-card').forEach(card => {
      card.addEventListener('click', function() {
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = 'translateY(-10px)';
        }, 100);
      });
    });

    // Simulate real-time stats updates - REMOVED
    // function updateStats() { ... }
    // setInterval(updateStats, 30000); - REMOVED

    // Add welcome animation
    window.addEventListener('load', function() {
      document.querySelector('.welcome-section h2').style.animation = 'slideInLeft 1s ease-out 0.3s both';
      document.querySelector('.welcome-description').style.animation = 'slideInLeft 1s ease-out 0.6s both';
    });

    // Add particle effect on navigation click
    document.querySelectorAll('nav a').forEach(link => {
      link.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const particle = document.createElement('div');
        particle.style.cssText = `
          position: absolute;
          left: ${x}px;
          top: ${y}px;
          width: 6px;
          height: 6px;
          background: white;
          border-radius: 50%;
          pointer-events: none;
          animation: particleFloat 1s ease-out forwards;
        `;
        
        this.appendChild(particle);
        setTimeout(() => particle.remove(), 1000);
      });
    });

    // Add particle animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes particleFloat {
        0% {
          opacity: 1;
          transform: translate(-50%, -50%) scale(1);
        }
        100% {
          opacity: 0;
          transform: translate(-50%, -70px) scale(0);
        }
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>