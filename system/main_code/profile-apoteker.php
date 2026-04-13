<?php
session_start();

// Cek login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'apoteker') {
    header("Location: login.html");
    exit;
}

// Koneksi database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Ambil data user dari tabel users
$sql_user = "SELECT username FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$data_user = $result_user->fetch_assoc();

// Ambil data tambahan dari tabel pharmacists
$sql_apoteker = "SELECT name, phone, email FROM pharmacists WHERE user_id = ?";
$stmt_apoteker = $conn->prepare($sql_apoteker);
$stmt_apoteker->bind_param("i", $user_id);
$stmt_apoteker->execute();
$result_apoteker = $stmt_apoteker->get_result();
$data_apoteker = $result_apoteker->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profil Apoteker</title>
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

    .btn-password {
    display: inline-block;
    margin-top: 1rem;
    background-color: #eceff1;
    color: #1976d2;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    border: 1.5px solid #cfd8dc;
    transition: background 0.3s;
  }

  .btn-password:hover {
    background-color: #e0e0e0;
    color: #145ea8;
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
  <nav>
    <div class="nav-left">
      <a href="dashboard-apoteker.php">⬅ Kembali</a>
    </div>
  </nav>

  <main class="main-container">
    <div class="profile-card">
      <h2>Profil Apoteker</h2>
      <table class="profile-table">
        <tr><th>Nama</th><td><?= htmlspecialchars($data_apoteker['name'] ?? '-') ?></td></tr>
        <tr><th>Username</th><td><?= htmlspecialchars($data_user['username'] ?? '-') ?></td></tr>
        <tr><th>Role</th><td>Apoteker</td></tr>
        <tr><th>No HP</th><td><?= htmlspecialchars($data_apoteker['phone'] ?? '-') ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($data_apoteker['email'] ?? '-') ?></td></tr>
      </table>
      <a href="edit-profile-apoteker.php" class="btn-edit-profile">✏️ Edit Profil</a>
      <a href="ubah-password.php" class="btn-password">🔒 Ubah Password</a>
    </div>
  </main>
</body>
</html>
