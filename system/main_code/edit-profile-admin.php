<?php
session_start();
require_once 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Ambil data admin saat halaman dimuat
$query = "SELECT * FROM admins WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Tangani form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if ($name && $email) {
        $update = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE user_id = ?");
        $update->bind_param('ssi', $name, $email, $user_id);

        if ($update->execute()) {
            $success_message = "Profil berhasil diperbarui.";
            $admin['name'] = $name;
            $admin['email'] = $email;
        } else {
            $error_message = "Gagal memperbarui profil.";
        }
    } else {
        $error_message = "Nama dan Email tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Profil Admin</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .register-container h2 {
      margin-bottom: 1.5rem; /* atau 24px, bisa disesuaikan */
    }
  </style>

</head>
<body>

  <div class="register-container">
    <h2>Edit Profil Admin</h2>

    <?php if ($success_message): ?>
      <p style="color: green;"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if ($error_message): ?>
      <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form method="POST" action="edit-profile-admin.php">
      <div class="form-group">
        <label for="name">Nama Lengkap</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
      </div>

      <button type="submit" class="form-button">Simpan Perubahan</button>
    </form>

    <a href="profile-admin.php" class="back-link">⬅ Kembali ke Profil</a>
  </div>

</body>
</html>
