<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

// PERBAIKAN: Set timezone untuk memastikan tanggal sesuai lokasi Indonesia
date_default_timezone_set('Asia/Jakarta');

// Ambil data nama obat dari database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$nama_obat_result = $conn->query("SELECT id, nama_obat FROM master_nama_obat");
$nama_obat_options = "";
while ($row = $nama_obat_result->fetch_assoc()) {
    $nama_obat_options .= "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['nama_obat']) . "</option>";
}
$conn->close();

// PERBAIKAN: Gunakan DateTime untuk kontrol lebih baik
try {
    $datetime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $tanggal_hari_ini = $datetime->format('Y-m-d');
    $tanggal_lengkap = $datetime->format('d F Y'); // Untuk display
    $waktu_sekarang = $datetime->format('H:i:s'); // Jika diperlukan
} catch (Exception $e) {
    // Fallback jika ada error
    $tanggal_hari_ini = date('Y-m-d');
    $tanggal_lengkap = date('d F Y');
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Input Resep Dokter</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
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
      padding: 1rem 2rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .nav-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      max-width: 1200px;
      margin: 0 auto;
    }

    .nav-left a {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      text-decoration: none;
      font-weight: 700;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .nav-left a:hover {
      background: rgba(102, 126, 234, 0.1);
      transform: translateX(-5px);
    }

    /* Form Container */
    .form-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      max-width: 1000px;
      margin: 2rem auto;
      padding: 3rem;
      border-radius: 24px;
      box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideInUp 0.8s ease-out;
      position: relative;
      overflow: hidden;
    }

    .form-container::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
      animation: rotate 15s linear infinite;
    }

    .form-header {
      text-align: center;
      margin-bottom: 3rem;
      position: relative;
      z-index: 2;
    }

    .form-header::before {
      content: '📝';
      font-size: 3rem;
      display: block;
      margin-bottom: 1rem;
      animation: bounce 2s ease-in-out infinite;
    }

    .form-container h1 {
      font-size: 2.2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }

    .form-subtitle {
      color: #666;
      font-size: 1rem;
      font-weight: 500;
    }

    /* Section Headers */
    .section-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 3rem 0 2rem 0;
      padding-bottom: 1rem;
      border-bottom: 2px solid rgba(102, 126, 234, 0.2);
      position: relative;
      z-index: 2;
    }

    .section-icon {
      font-size: 2rem;
      animation: pulse 2s ease-in-out infinite;
    }

    .section-title {
      font-size: 1.6rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Form Groups */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .form-group {
      position: relative;
      z-index: 2;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #333;
      font-size: 0.95rem;
    }

    .input-wrapper {
      position: relative;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 1rem;
      font-size: 1rem;
      background: rgba(255, 255, 255, 0.8);
      border: 2px solid #e1e5e9;
      border-radius: 12px;
      transition: all 0.3s ease;
      outline: none;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      border-color: #667eea;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
      transform: translateY(-2px);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    /* Medication Row Styling */
    .obat-row {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border: 2px solid rgba(102, 126, 234, 0.2);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 2rem;
      position: relative;
      transition: all 0.3s ease;
      animation: slideInRight 0.6s ease-out;
      z-index: 2;
    }

    .obat-row:hover {
      box-shadow: 0 15px 40px rgba(102, 126, 234, 0.15);
      transform: translateY(-5px);
    }

    .obat-row::before {
      content: '💊';
      position: absolute;
      top: 1rem;
      left: 1rem;
      font-size: 1.5rem;
      opacity: 0.7;
    }

    .remove-row {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      border: none;
      border-radius: 50%;
      color: white;
      width: 35px;
      height: 35px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    .remove-row:hover {
      transform: scale(1.1) rotate(90deg);
      box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
    }

    .obat-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr;
      gap: 1.5rem;
      margin-top: 2rem;
    }

    /* Buttons */
    .btn {
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      z-index: 2;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      width: 100%;
      margin-top: 2rem;
    }

    .btn-secondary {
      background: linear-gradient(135deg, #4fc3f7, #29b6f6);
      color: white;
      width: 100%;
      margin: 1rem 0;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .btn:hover::before {
      left: 100%;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }

    .btn:active {
      transform: translateY(0);
    }

    /* Select2 Customization */
    .select2-container--default .select2-selection--single {
      background: rgba(255, 255, 255, 0.8) !important;
      border: 2px solid #e1e5e9 !important;
      border-radius: 12px !important;
      height: 54px !important;
      padding: 0 1rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 50px !important;
      padding-left: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 50px !important;
      right: 1rem !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
      border-color: #667eea !important;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
    }

    /* Progress Indicator */
    .progress-indicator {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
      position: relative;
      z-index: 2;
    }

    .progress-step {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: rgba(102, 126, 234, 0.1);
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
      color: #667eea;
    }

    .progress-step.active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
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

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
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
      .form-container {
        margin: 1rem;
        padding: 2rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }

      .obat-grid {
        grid-template-columns: 1fr;
      }

      .progress-indicator {
        flex-direction: column;
        gap: 0.5rem;
      }

      .nav-container {
        padding: 0 1rem;
      }
    }

    /* Loading Animation */
    .loading {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid rgba(102, 126, 234, 0.3);
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
  <!-- Loading Animation -->
  <div class="loading" id="loading">
    <div class="loading-spinner"></div>
  </div>

  <nav>
    <div class="nav-container">
      <div class="nav-left">
        <a href="dashboard-dokter.php">⬅️ Kembali ke Dashboard</a>
      </div>
    </div>
  </nav>

  <div class="form-container">
    <div class="form-header">
      <h1>Input Resep Obat</h1>
      <p class="form-subtitle">Buat resep baru dengan sistem verifikasi interaksi otomatis</p>
    </div>

    <div class="progress-indicator">
      <div class="progress-step active">
        <span>👤</span> Info Pasien
      </div>
      <div class="progress-step" id="step2">
        <span>💊</span> Obat-obatan
      </div>
      <div class="progress-step" id="step3">
        <span>✅</span> Verifikasi
      </div>
    </div>

    <form action="proses-resep.php" method="POST" id="prescriptionForm">
      <!-- Patient Information Section -->
      <div class="section-header">
        <span class="section-icon">👤</span>
        <h2 class="section-title">Informasi Pasien</h2>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="nama">Nama Lengkap Pasien</label>
          <div class="input-wrapper">
            <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap pasien" />
          </div>
        </div>

        <div class="form-group">
          <label for="tanggal_lahir">Tanggal Lahir</label>
          <div class="input-wrapper">
            <input type="date" id="tanggal_lahir" name="tanggal_lahir" required />
          </div>
        </div>

        <div class="form-group">
          <label for="usia">Usia (Otomatis)</label>
          <div class="input-wrapper">
            <input type="text" id="usia" name="usia" readonly placeholder="Akan terisi otomatis" />
          </div>
        </div>

        <div class="form-group">
          <label for="alamat">Alamat Lengkap</label>
          <div class="input-wrapper">
            <input type="text" id="alamat" name="alamat" required placeholder="Alamat lengkap pasien" />
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="diagnosis">Diagnosis & Catatan Klinis</label>
        <div class="input-wrapper">
          <textarea id="diagnosis" name="diagnosis" required placeholder="Masukkan diagnosis, gejala, dan catatan klinis lainnya..."></textarea>
        </div>
      </div>

      <!-- Prescription Section -->
      <div class="section-header">
        <span class="section-icon">💊</span>
        <h2 class="section-title">Resep Obat-obatan</h2>
      </div>

      <div class="form-group">
        <label for="tanggal">Tanggal Resep</label>
        <div class="input-wrapper">
          <input type="date" id="tanggal" name="tanggal" value="<?= $tanggal_hari_ini ?>" required />
        </div>
      </div>

      <div id="obat-container">
        <div class="obat-row">
          <button type="button" class="remove-row">&times;</button>
          <div class="obat-grid">
            <div class="form-group">
              <label>Nama Obat</label>
              <select name="obat[]" class="select-obat" required>
                <option value="">Pilih Obat</option>
                <?= $nama_obat_options ?>
              </select>
            </div>
            <div class="form-group">
              <label>Dosis</label>
              <input type="text" name="dosis[]" required placeholder="500mg, 2 tablet, dll" />
            </div>
            <div class="form-group">
              <label>Aturan Pakai</label>
              <input type="text" name="aturanpakai[]" required placeholder="3x sehari sesudah makan" />
            </div>
          </div>
        </div>
      </div>

      <button type="button" class="btn btn-secondary" id="add-row">
        ➕ Tambah Obat Lain
      </button>

      <div class="form-group">
        <label for="catatan">Catatan & Instruksi Tambahan</label>
        <div class="input-wrapper">
          <textarea id="catatan" name="catatan" placeholder="Catatan khusus untuk pasien, efek samping yang perlu diperhatikan, dll..."></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" id="submitBtn">
        🔍 Submit dan Verifikasi Interaksi
      </button>
    </form>
  </div>

  <script>
    $(document).ready(function () {
      // Drug selection template
      const selectTemplate = `
        <div class="obat-row">
          <button type="button" class="remove-row">&times;</button>
          <div class="obat-grid">
            <div class="form-group">
              <label>Nama Obat</label>
              <select name="obat[]" class="select-obat" required>
                <option value="">Pilih Obat</option>
                <?= $nama_obat_options ?>
              </select>
            </div>
            <div class="form-group">
              <label>Dosis</label>
              <input type="text" name="dosis[]" required placeholder="500mg, 2 tablet, dll" />
            </div>
            <div class="form-group">
              <label>Aturan Pakai</label>
              <input type="text" name="aturanpakai[]" required placeholder="3x sehari sesudah makan" />
            </div>
          </div>
        </div>
      `;

      // Initialize Select2
      $('.select-obat').select2({ 
        placeholder: "🔍 Cari dan pilih obat...", 
        allowClear: true,
        width: '100%'
      });

      // Add new medication row
      $('#add-row').click(function () {
        const newRow = $(selectTemplate);
        $('#obat-container').append(newRow);
        
        // Animate new row
        newRow.hide().slideDown(300);
        
        // Initialize Select2 for new row
        newRow.find('select.select-obat').select2({ 
          placeholder: "🔍 Cari dan pilih obat...", 
          allowClear: true,
          width: '100%'
        });

        // Update progress
        updateProgress();
      });

      // Remove medication row
      $('#obat-container').on('click', '.remove-row', function () {
        const row = $(this).closest('.obat-row');
        if ($('.obat-row').length > 1) {
          row.slideUp(300, function() {
            $(this).remove();
            updateProgress();
          });
        } else {
          alert('⚠️ Minimal harus ada satu obat dalam resep!');
        }
      });

      // Calculate age automatically
      $('#tanggal_lahir').on('change', function () {
        const birthDate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        let months = today.getMonth() - birthDate.getMonth();
        
        if (months < 0 || (months === 0 && today.getDate() < birthDate.getDate())) {
          age--;
          months += 12;
        }
        
        if (months < 0) months = 0;
        
        let ageText = age + ' tahun';
        if (months > 0) {
          ageText += ' ' + months + ' bulan';
        }
        
        $('#usia').val(ageText);
        updateProgress();
      });

      // Form validation and progress tracking
      function updateProgress() {
        const patientFilled = $('#nama').val() && $('#tanggal_lahir').val() && $('#alamat').val() && $('#diagnosis').val();
        const medicationFilled = $('.obat-row select').first().val() && $('.obat-row input[name="dosis[]"]').first().val();
        
        $('#step2').toggleClass('active', patientFilled);
        $('#step3').toggleClass('active', patientFilled && medicationFilled);
      }

      // Real-time validation
      $('input[required], textarea[required], select[required]').on('input change', updateProgress);

      // Form submission with loading
      $('#prescriptionForm').on('submit', function(e) {
        e.preventDefault();
        
        // Show loading
        $('#loading').show();
        $('#submitBtn').html('⏳ Memproses Resep...');
        $('#submitBtn').prop('disabled', true);
        
        // Simulate processing time (remove in production)
        setTimeout(() => {
          this.submit();
        }, 1500);
      });

      // Add hover effects to form elements
      $('.form-group input, .form-group textarea, .form-group select').hover(
        function() {
          $(this).css('transform', 'translateY(-1px)');
        },
        function() {
          $(this).css('transform', 'translateY(0)');
        }
      );

      // Initialize progress on load
      updateProgress();
    });

    // Add entrance animations
    window.addEventListener('load', function() {
      $('.form-group').each(function(index) {
        $(this).css({
          'animation-delay': (index * 0.1) + 's',
          'animation': 'slideInUp 0.6s ease-out both'
        });
      });
    });
  </script>
</body>
</html>