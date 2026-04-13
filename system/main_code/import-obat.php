<?php
session_start();

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .back-btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: #0d6efd;
            margin-bottom: 1rem;
        }

        .back-btn:hover {
            text-decoration: underline;
            color: #084298;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }

        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin: 1rem 0;
        }

        .format-example {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: monospace;
            font-size: 0.875rem;
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9ff;
        }

        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <a href="dashboard-admin.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-upload me-2"></i>
                        Import Data Obat dari CSV
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Info Box -->
                    <div class="info-box">
                        <h6><i class="fas fa-info-circle me-2"></i>Informasi Format CSV:</h6>
                        <ul class="mb-0">
                            <li>File harus berformat CSV (.csv)</li>
                            <li>Kolom pertama: <strong>Nama_Obat</strong></li>
                            <li>Kolom kedua: <strong>Zat_Aktif</strong></li>
                            <li>Gunakan koma (,) sebagai pemisah untuk multiple zat aktif</li>
                            <li>Sistem akan otomatis mengecek duplikasi data</li>
                        </ul>
                    </div>

                    <!-- Format Example -->
                    <div class="mb-4">
                        <h6>Contoh Format CSV:</h6>
                        <div class="format-example">
Nama_Obat,Zat_Aktif<br>
PEHACAIN,LIDOCAINE HYDROCHLORIDE,EPINEPHRINE<br>
BUSCOPAN,HYOSCINE BUTYLBROMIDE<br>
DUVADILAN,ISOXSUPRINE HYDROCHLORIDE
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form action="proses-import-obat.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-area mb-3" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-2">Drag & drop file CSV di sini atau klik untuk memilih file</p>
                            <input type="file" name="csv_file" id="csv_file" class="form-control" 
                                   accept=".csv" required style="display: none;">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('csv_file').click()">
                                <i class="fas fa-folder-open me-2"></i>Pilih File CSV
                            </button>
                        </div>

                        <div id="fileInfo" class="alert alert-info" style="display: none;">
                            <i class="fas fa-file-csv me-2"></i>
                            <span id="fileName"></span>
                            <span id="fileSize" class="text-muted"></span>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-upload me-2"></i>Import Data Obat
                            </button>
                        </div>
                    </form>

                    <?php if (isset($_SESSION['import_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['import_type'] ?> mt-3" role="alert">
                            <i class="fas fa-<?= $_SESSION['import_type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                            <?= $_SESSION['import_message'] ?>
                        </div>
                        <?php 
                        unset($_SESSION['import_message']);
                        unset($_SESSION['import_type']);
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('csv_file');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');

    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect();
        }
    });

    // File input change
    fileInput.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('Silakan pilih file CSV (.csv)');
                fileInput.value = '';
                return;
            }

            // Show file info
            fileName.textContent = file.name;
            fileSize.textContent = ` (${(file.size / 1024).toFixed(1)} KB)`;
            fileInfo.style.display = 'block';
            submitBtn.disabled = false;
        }
    }

    // Click to select file
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
});
</script>
</body>
</html>