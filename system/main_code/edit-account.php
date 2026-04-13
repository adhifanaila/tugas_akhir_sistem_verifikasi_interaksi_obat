<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

$id = $_GET['id']; // Ambil ID dari query string

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Akun tidak ditemukan.";
    exit;
}

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Akun</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <nav>
    <div class="nav-container">
      <div class="nav-left">
        <a href="admin-dashboard.php" class="profile-icon">⬅ Kembali</a>
      </div>
      <div class="nav-right">
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </div>
  </nav>

  <main class="main-container">
    <h1>Edit Profil Akun</h1>

    <form method="POST" action="update-account.php">
      <input type="hidden" name="id" value="<?= $user['id'] ?>">

      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= $user['username'] ?>" required />

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= $user['email'] ?>" required />

      <label for="role">Role</label>
      <select name="role" id="role" required>
        <option value="dokter" <?= $user['role_id'] == 1 ? 'selected' : '' ?>>Dokter</option>
        <option value="apoteker" <?= $user['role_id'] == 2 ? 'selected' : '' ?>>Apoteker</option>
        <option value="admin" <?= $user['role_id'] == 3 ? 'selected' : '' ?>>Admin</option>
      </select>

      <button type="submit">Simpan Perubahan</button>
    </form>
  </main>
</body>
</html>
