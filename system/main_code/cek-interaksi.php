<?php
session_start();
$start = microtime(true); // ⬅️ MULAI HITUNG WAKTU

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'dokter' && $_SESSION['role'] !== 'apoteker')) {
    header("Location: login.html");
    exit;
}

// Ambil prescription_id dari GET atau POST
$prescription_id = 0;
if (isset($_GET['prescription_id'])) {
    $prescription_id = intval($_GET['prescription_id']);
} elseif (isset($_POST['prescription_id'])) {
    $prescription_id = intval($_POST['prescription_id']);
} else {
    die("❌ prescription_id tidak ditemukan.");
}

// Ambil obat tambahan dari form (jika ada)
$obat_tambahan = isset($_POST['obat_tambahan']) ? array_map('intval', $_POST['obat_tambahan']) : [];

$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil drug_id dari resep
$stmt = $conn->prepare("SELECT drug_id FROM prescription_items WHERE prescription_id = ?");
$stmt->bind_param("i", $prescription_id);
$stmt->execute();
$res = $stmt->get_result();
$drug_ids = [];
while ($row = $res->fetch_assoc()) {
    $drug_ids[] = $row['drug_id'];
}
$stmt->close();

// Gabungkan dengan obat tambahan
$drug_ids = array_merge($drug_ids, $obat_tambahan);
$drug_ids = array_unique($drug_ids);

if (count($drug_ids) < 2) {
    echo "Minimal harus ada 2 obat untuk pengecekan interaksi.";
    exit;
}

// Ambil ingredient_id dari semua drug_id
$placeholders1 = implode(',', array_fill(0, count($drug_ids), '?'));
$types1 = str_repeat('i', count($drug_ids));
$stmt = $conn->prepare("SELECT DISTINCT drug_id, ingredient_id FROM drug_ingredients WHERE drug_id IN ($placeholders1)");
$stmt->bind_param($types1, ...$drug_ids);
$stmt->execute();
$result = $stmt->get_result();

$drug_ingredients = []; // [drug_id => [ingredient_id, ...]]
$ingredient_to_drugs = []; // [ingredient_id => [drug_id, ...]]
while ($row = $result->fetch_assoc()) {
    $drug_ingredients[$row['drug_id']][] = $row['ingredient_id'];
    $ingredient_to_drugs[$row['ingredient_id']][] = $row['drug_id'];
}
$stmt->close();

// Ganti paracetamol dengan acetaminophen
foreach ($ingredient_to_drugs as $ingredient_id => $drug_list) {
    $stmt = $conn->prepare("SELECT zat_aktif FROM master_zat_aktif WHERE id = ?");
    $stmt->bind_param("i", $ingredient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $zat = $res->fetch_assoc();
    $stmt->close();

    if ($zat && strtolower($zat['zat_aktif']) === 'paracetamol') {
        $stmt = $conn->prepare("SELECT id FROM master_zat_aktif WHERE LOWER(zat_aktif) = 'acetaminophen'");
        $stmt->execute();
        $res = $stmt->get_result();
        $acetaminophen = $res->fetch_assoc();
        $stmt->close();

        if ($acetaminophen) {
            $acetaminophen_id = $acetaminophen['id'];

            if (!isset($ingredient_to_drugs[$acetaminophen_id])) {
                $ingredient_to_drugs[$acetaminophen_id] = [];
            }
            $ingredient_to_drugs[$acetaminophen_id] = array_merge($ingredient_to_drugs[$acetaminophen_id], $drug_list);
            unset($ingredient_to_drugs[$ingredient_id]);
        }
    }
}

$ingredient_ids = array_keys($ingredient_to_drugs);
if (count($ingredient_ids) < 2) {
    echo "Minimal harus ada 2 zat aktif.";
    exit;
}

// Cek interaksi antar kombinasi zat aktif
$interactions = [];
$stmt = $conn->prepare("SELECT id_1, id_2, level FROM data_interaksi_indexed WHERE (id_1 = ? AND id_2 = ?) OR (id_1 = ? AND id_2 = ?)");
for ($i = 0; $i < count($ingredient_ids); $i++) {
    for ($j = $i + 1; $j < count($ingredient_ids); $j++) {
        $id1 = $ingredient_ids[$i];
        $id2 = $ingredient_ids[$j];
        $stmt->bind_param("iiii", $id1, $id2, $id2, $id1);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $interactions[] = $row;
        }
    }
}
$stmt->close();

// Ambil nama zat aktif
$all_ids = array_unique(array_merge(array_column($interactions, 'id_1'), array_column($interactions, 'id_2')));
$names = [];
if (count($all_ids)) {
    $placeholders2 = implode(',', array_fill(0, count($all_ids), '?'));
    $types2 = str_repeat('i', count($all_ids));
    $stmt = $conn->prepare("SELECT id, zat_aktif FROM master_zat_aktif WHERE id IN ($placeholders2)");
    $stmt->bind_param($types2, ...$all_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $names[$row['id']] = $row['zat_aktif'];
    }
    $stmt->close();
}

// Ambil nama obat
$placeholders3 = implode(',', array_fill(0, count($drug_ids), '?'));
$types3 = str_repeat('i', count($drug_ids));
$drug_name_stmt = $conn->prepare("SELECT id, nama_obat FROM master_nama_obat WHERE id IN ($placeholders3)");
$drug_name_stmt->bind_param($types3, ...$drug_ids);
$drug_name_stmt->execute();
$drug_name_result = $drug_name_stmt->get_result();
$drug_names = [];
while ($row = $drug_name_result->fetch_assoc()) {
    $drug_names[$row['id']] = $row['nama_obat'];
}
$drug_name_stmt->close();

$conn->close();
$dashboard_url = ($_SESSION['role'] === 'dokter') ? 'dashboard-dokter.php' : 'dashboard-apoteker.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Cek Interaksi Obat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header {
            background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 40px 30px;
        }

        .result-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        .safe-result {
            border-left: 6px solid #4CAF50;
            background: linear-gradient(135deg, #E8F5E8 0%, #F1F8E9 100%);
        }

        .danger-result {
            border-left: 6px solid #F44336;
            background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
        }

        .result-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }

        .safe-result .result-icon {
            color: #4CAF50;
        }

        .danger-result .result-icon {
            color: #F44336;
        }

        .result-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .safe-result .result-title {
            color: #2E7D32;
        }

        .danger-result .result-title {
            color: #C62828;
        }

        .result-description {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .interaction-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        .interaction-table th {
            background: linear-gradient(135deg, #1976d2 0%, #1565C0 100%);
            color: white;
            padding: 18px 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .interaction-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
        }

        .interaction-table tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }

        .level-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .level-minor {
            background-color: #FFF3E0;
            color: #F57C00;
            border: 1px solid #FFE0B2;
        }

        .level-moderate {
            background-color: #FFF8E1;
            color: #F9A825;
            border: 1px solid #FFECB3;
        }

        .level-major {
            background-color: #FFEBEE;
            color: #E53935;
            border: 1px solid #FFCDD2;
        }

        .drug-list {
            background: linear-gradient(135deg, #FFF9C4 0%, #F0F4C3 100%);
            border-radius: 12px;
            padding: 25px;
            margin-top: 25px;
            border-left: 6px solid #FBC02D;
        }

        .drug-list h3 {
            color: #F57F17;
            margin-bottom: 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .drug-list ul {
            list-style: none;
            padding: 0;
        }

        .drug-list li {
            background: white;
            margin: 8px 0;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s ease;
        }

        .drug-list li:hover {
            transform: translateX(5px);
        }

        .drug-list li::before {
            content: '💊';
            font-size: 1.2rem;
        }

        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1976d2 0%, #1565C0 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(25, 118, 210, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #FF7043 0%, #F4511E 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 112, 67, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 112, 67, 0.4);
        }

        .execution-time {
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
            border: 1px solid #90CAF9;
        }

        .execution-time i {
            color: #1976d2;
            margin-right: 8px;
            font-size: 1.2rem;
        }

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

        .result-card, .drug-list, .execution-time {
            animation: fadeInUp 0.6s ease-out;
        }

        .interaction-table {
            animation: fadeInUp 0.8s ease-out;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 16px;
            }

            .header {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .content {
                padding: 20px;
            }

            .result-card {
                padding: 20px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }

            .interaction-table {
                font-size: 0.9rem;
            }

            .interaction-table th,
            .interaction-table td {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-microscope"></i> Hasil Pengecekan Interaksi</h1>
        </div>
        
        <div class="content">
            <?php if (count($interactions) > 0): ?>
                <div class="result-card danger-result">
                    <i class="fas fa-exclamation-triangle result-icon"></i>
                    <div class="result-title">Peringatan Interaksi Terdeteksi!</div>
                    <div class="result-description">
                        Sistem telah mendeteksi <?= count($interactions) ?> interaksi potensial antara zat aktif dalam resep ini. 
                        Harap tinjau dengan cermat sebelum memberikan obat kepada pasien.
                    </div>
                    
                    <table class="interaction-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-flask"></i> Zat Aktif Pertama</th>
                                <th><i class="fas fa-flask"></i> Zat Aktif Kedua</th>
                                <th><i class="fas fa-exclamation-circle"></i> Tingkat Bahaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interactions as $interaksi): ?>
                                <tr>
                                    <td><?= htmlspecialchars($names[$interaksi['id_1']] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($names[$interaksi['id_2']] ?? '-') ?></td>
                                    <td>
                                        <span class="level-badge level-<?= strtolower($interaksi['level']) ?>">
                                            <?= ucfirst($interaksi['level']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="drug-list">
                        <h3><i class="fas fa-pills"></i> Obat yang Mengandung Zat Aktif Berinteraksi</h3>
                        <ul>
                            <?php
                            $shown = [];
                            foreach ($interactions as $interaksi) {
                                foreach ([$interaksi['id_1'], $interaksi['id_2']] as $zat_id) {
                                    if (!isset($ingredient_to_drugs[$zat_id])) continue;
                                    foreach ($ingredient_to_drugs[$zat_id] as $drug_id) {
                                        if (!isset($drug_names[$drug_id])) continue;
                                        $obat_label = $drug_names[$drug_id] . " (mengandung " . ($names[$zat_id] ?? '-') . ")";
                                        if (!in_array($obat_label, $shown)) {
                                            echo "<li>" . htmlspecialchars($obat_label) . "</li>";
                                            $shown[] = $obat_label;
                                        }
                                    }
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="result-card safe-result">
                    <i class="fas fa-check-circle result-icon"></i>
                    <div class="result-title">Resep Aman Digunakan</div>
                    <div class="result-description">
                        Sistem tidak menemukan interaksi berbahaya antara zat aktif dalam resep ini. 
                        Kombinasi obat telah diverifikasi aman untuk dikonsumsi pasien.
                    </div>
                </div>
            <?php endif; ?>

            <div class="buttons">
                <a href="<?= $dashboard_url ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Dashboard
                </a>
                <?php if ($_SESSION['role'] === 'dokter'): ?>
                    <a href="edit-resep.php?prescription_id=<?= $prescription_id ?>" class="btn btn-secondary">
                        <i class="fas fa-edit"></i>
                        Edit Resep
                    </a>
                <?php endif; ?>
            </div>

            <div class="execution-time">
                <?php
                $end = microtime(true);
                $execution_time = $end - $start;
                ?>
                <i class="fas fa-stopwatch"></i>
                <strong>Waktu Pemrosesan:</strong> <?= round($execution_time, 4) ?> detik
            </div>
        </div>
    </div>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate table rows on load
            const tableRows = document.querySelectorAll('.interaction-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
                row.classList.add('animate-fade-in');
            });

            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = button.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255,255,255,0.4);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    button.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add CSS animation for table rows
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .animate-fade-in {
                animation: fadeInUp 0.6s ease-out forwards;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>