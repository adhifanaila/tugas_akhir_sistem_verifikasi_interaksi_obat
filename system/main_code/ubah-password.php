<?php
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
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

// Ambil user_id dari sesi
$user_id = $_SESSION['user_id'];

// Ambil role_id dari database
$role_id = null;
$stmt_role = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
if (!$stmt_role) {
    die("Prepare failed: " . $conn->error);
}
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$result_role = $stmt_role->get_result();
if ($row = $result_role->fetch_assoc()) {
    $role_id = (int)$row['role_id'];
} else {
    // Jika user tidak ditemukan, logout dan paksa login ulang
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}
$stmt_role->close();

// Mapping role_id ke nama file dashboard
$role_map = [
    1 => 'dokter',
    2 => 'apoteker',
    3 => 'admin'
];
$role_name = isset($role_map[$role_id]) ? $role_map[$role_id] : 'user';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan trim input
    $old_pass = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    $new_pass = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_pass = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if ($old_pass === '' || $new_pass === '' || $confirm_pass === '') {
        $errors[] = "Semua kolom wajib diisi.";
    } elseif (strlen($new_pass) < 8) {
        $errors[] = "Password baru minimal 8 karakter.";
    } elseif ($new_pass !== $confirm_pass) {
        $errors[] = "Password baru dan konfirmasi tidak cocok.";
    } else {
        // Ambil password lama dari DB
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        if (!$stmt) {
            $errors[] = "Terjadi kesalahan internal.";
        } else {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($old_pass, $user['password'])) {
                $errors[] = "Password lama salah.";
            } else {
                // Hash password baru dan simpan
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                if (!$update) {
                    $errors[] = "Terjadi kesalahan saat mempersiapkan query.";
                } else {
                    $update->bind_param("si", $hashed, $user_id);
                    if ($update->execute()) {
                        // Regenerasi session ID untuk mencegah fixation
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user_id; // pastikan tetap ada

                        $success = "✅ Password berhasil diubah. Anda akan diarahkan ke dashboard.";
                        // Redirect setelah delay singkat (JS) dan fallback PHP
                        header("Refresh:3; url=dashboard-" . htmlspecialchars($role_name) . ".php");
                    } else {
                        $errors[] = "❌ Gagal mengubah password.";
                    }
                    $update->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ubah Password</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; }
    .card { max-width: 500px; margin: auto; }
  </style>
</head>
<body class="bg-light">
<div class="container my-5">
  <h2 class="text-center mb-4">🔒 Ubah Password</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $error): ?>
        <div>• <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="text-center mb-3">
      <small>Kalau tidak otomatis, klik manual: <a href="dashboard-<?= htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') ?>.php">Dashboard</a></small>
    </div>
    <script>
      setTimeout(() => {
        window.location.href = "dashboard-<?= htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') ?>.php";
      }, 3000);
    </script>
  <?php endif; ?>

  <form method="POST" class="card p-4 shadow-sm bg-white" novalidate>
    <div class="mb-3">
      <label for="old_password" class="form-label">Password Lama</label>
      <input type="password" class="form-control" id="old_password" name="old_password" required autocomplete="current-password" aria-label="Password Lama">
    </div>
    <div class="mb-3">
      <label for="new_password" class="form-label">Password Baru</label>
      <input type="password" class="form-control" id="new_password" name="new_password" required autocomplete="new-password" aria-label="Password Baru">
      <div class="form-text">Minimal 8 karakter.</div>
    </div>
    <div class="mb-3">
      <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
      <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password" aria-label="Konfirmasi Password Baru">
    </div>
    <div class="d-grid gap-2 mt-4">
      <button type="submit" class="btn btn-success btn-lg rounded-pill shadow-sm">
        💾 Simpan Perubahan
      </button>
    </div>
  </form>
</div>
</body>
</html>
