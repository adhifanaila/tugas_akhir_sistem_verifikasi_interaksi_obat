<?php

session_start();

// Pastikan user login sebagai dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}


// PERBAIKAN: Set timezone untuk memastikan tanggal sesuai lokasi Indonesia
date_default_timezone_set('Asia/Jakarta');

// Koneksi database
$mysqli = new mysqli("localhost", "root", "", "interaction_cheker");

// Cek koneksi
if ($mysqli->connect_errno) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Query untuk menampilkan resep hari ini sesuai dokter yang login
// Menggunakan JOIN dengan tabel doctors untuk mencocokkan user_id
$query = "
    SELECT r.id, pa.name AS nama_pasien, pa.usia_pasien, r.created_at
    FROM prescriptions r
    JOIN patients pa ON r.patient_id = pa.id
    JOIN doctors d ON r.doctor_id = d.id
    WHERE DATE(r.created_at) = CURDATE() 
    AND d.user_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();// PERBAIKAN: Gunakan DateTime untuk kontrol lebih baik
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
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Riwayat Resep Hari Ini</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* Navigation Styles */
        nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-left a {
            color: #1976d2;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .nav-left a:hover {
            background: #1976d2;
            color: white;
            transform: translateX(-3px);
        }

        .nav-title {
            color: #1976d2;
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        /* Header Section */
        .header-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23e3f2fd" opacity="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-icon {
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3);
        }

        .header-icon i {
            font-size: 2rem;
            color: white;
        }

        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.5rem;
        }

        .header-subtitle {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .date-badge {
            display: inline-block;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            border: 1px solid #90caf9;
        }

        /* Table Section */
        .table-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .table-header {
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            padding: 2rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-container {
            overflow-x: auto;
            max-height: 600px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        th, td {
            padding: 1.5rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1976d2;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tbody tr {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
            transform: scale(1.01);
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .patient-details h4 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .patient-details p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }

        .time-badge {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #a5d6a7;
        }

        .age-badge {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #f57c00;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #ffcc02;
        }

        .btn-detail {
            background: linear-gradient(135deg, #ff7043 0%, #ff8a65 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 112, 67, 0.3);
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
            transition: left 0.5s ease;
        }

        .btn-detail:hover::before {
            left: 100%;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 112, 67, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-icon {
            font-size: 4rem;
            color: #e0e0e0;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #999;
        }

        .empty-subtitle {
            font-size: 1rem;
            color: #666;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-section, .stat-card, .table-section {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .table-section { animation-delay: 0.4s; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
                margin: 1rem auto;
            }

            .header-section {
                padding: 2rem 1.5rem;
            }

            .header-title {
                font-size: 2rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .table-header {
                padding: 1.5rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            th, td {
                padding: 1rem 0.75rem;
                font-size: 0.9rem;
            }

            .patient-info {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }

            nav {
                padding: 1rem;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1976d2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <div class="nav-left">
                <a href="dashboard-dokter.php">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Dashboard
                </a>
            </div>
            <div class="nav-title">
                <i class="fas fa-user-md"></i>
                Portal Dokter
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-prescription-bottle-alt"></i>
                </div>
                <h1 class="header-title">Riwayat Resep Harian</h1>
                <p class="header-subtitle">Pantau dan kelola semua resep yang telah Anda buat hari ini</p>
                <div class="date-badge">
                    <i class="fas fa-calendar-day"></i>
                    <?= date('d F Y') ?>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-list-alt"></i>
                    Daftar Resep Hari Ini
                </div>
                <div style="opacity: 0.8;">
                    <?= $result->num_rows ?> resep ditemukan
                </div>
            </div>
            
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Data Pasien</th>
                                <th><i class="fas fa-birthday-cake"></i> Usia</th>
                                <th><i class="fas fa-cogs"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 0;
                            mysqli_data_seek($result, 0); // Reset result pointer
                            while ($row = $result->fetch_assoc()): 
                                $counter++;
                                $time = date('H:i', strtotime($row['created_at']));
                                $initials = strtoupper(substr($row['nama_pasien'], 0, 2));
                            ?>
                                <tr style="animation-delay: <?= $counter * 0.1 ?>s">
                                    <td>
                                        <div class="patient-info">
                                            <div class="patient-avatar">
                                                <?= $initials ?>
                                            </div>
                                            <div class="patient-details">
                                                <h4><?= htmlspecialchars($row['nama_pasien']) ?></h4>
                                                <p>ID: #<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="age-badge">
                                            <?= htmlspecialchars($row['usia_pasien']) ?> tahun
                                        </div>
                                    </td>
                                    <td>
                                        <a href="detail-resep.php?prescription_id=<?= $row['id'] ?>" class="btn-detail">
                                            <i class="fas fa-eye"></i>
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-prescription-bottle"></i>
                        </div>
                        <div class="empty-title">Belum Ada Resep Hari Ini</div>
                        <div class="empty-subtitle">Resep yang Anda buat akan muncul di sini</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading effect to buttons
            const detailButtons = document.querySelectorAll('.btn-detail');
            detailButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<div class="loading"></div> Memuat...';
                    this.style.pointerEvents = 'none';
                    
                    // Reset after navigation (in case it fails)
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 3000);
                });
            });

            // Add ripple effect to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-detail')) return;
                    
                    const ripple = document.createElement('div');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(25, 118, 210, 0.1);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                        z-index: 1;
                    `;
                    
                    this.style.position = 'relative';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Auto-refresh every 5 minutes
            setInterval(() => {
                window.location.reload();
            }, 300000);
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>