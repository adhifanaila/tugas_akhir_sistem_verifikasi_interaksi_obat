<?php
session_start();
require_once 'koneksi.php';

// Cek login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data admin dari DB
$query = "SELECT * FROM admins WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
} else {
    echo "Data admin tidak ditemukan.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profil Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f7fb;
    }

    nav {
      background: linear-gradient(to right, #1976d2, #2196f3);
      padding: 1rem 2rem;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    nav a {
      color: white;
      text-decoration: none;
      font-weight: 600;
      font-size: 16px;
    }

    .logout-btn {
      background-color: #e53935;
      padding: 6px 14px;
      border-radius: 6px;
      font-weight: 600;
    }

    .main-container {
      max-width: 800px;
      margin: 2rem auto;
      background: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    }

    .profile-card h2 {
      font-size: 1.8rem;
      margin-bottom: 1.5rem;
      color: #1976d2;
    }

    .profile-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 16px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    }

    .profile-table th {
      background-color: #f0f4f8;
      text-align: left;
      padding: 12px 16px;
      width: 35%;
      color: #333;
      font-weight: 600;
      border-bottom: 1px solid #e0e0e0;
    }

    .profile-table td {
      padding: 12px 16px;
      border-bottom: 1px solid #e0e0e0;
      color: #444;
      background-color: #fff;
    }

    .profile-table tr:last-child th,
    .profile-table tr:last-child td {
      border-bottom: none;
    }

    .btn-edit-profile {
      display: inline-block;
      margin-top: 2rem;
      background-color: #ffa726;
      color: white;
      padding: 10px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s;
    }

    .btn-edit-profile:hover {
      background-color: #fb8c00;
    }

    @media (max-width: 600px) {
      .main-container {
        margin: 1rem;
        padding: 1.5rem;
      }

      .profile-table th,
      .profile-table td {
        display: block;
        width: 100%;
        text-align: left;
      }

      .profile-table th {
        background-color: #f9fafc;
        font-weight: bold;
        padding-top: 16px;
      }

      .profile-table td {
        padding-bottom: 16px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <nav>
    <div class="nav-left">
      <a href="dashboard-admin.php">⬅ Back</a>
    </div>
    <div class="nav-right">
      <a href="logout.php" class="logout-btn">Logout</a>
    </div>
  </nav>

  <!-- Konten -->
  <main class="main-container">
    <div class="profile-card">
      <h2>🧑‍💼 Profil Admin</h2>
      <table class="profile-table">
        <tr><th>Nama Lengkap</th><td><?= htmlspecialchars($admin['name'] ?? '-') ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($admin['email'] ?? '-') ?></td></tr>
        <tr><th>Telepon</th><td><?= htmlspecialchars($admin['phone'] ?? '-') ?></td></tr>
      </table>

      <a href="edit-profile-admin.php" class="btn-edit-profile">✏️ Edit Profil</a>
    </div>
  </main>
</body>
</html>
