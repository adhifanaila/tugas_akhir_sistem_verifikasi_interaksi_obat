<?php
session_start();

// Cek apakah user sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f1f5f9;
      color: #333;
    }

    nav {
      background: linear-gradient(90deg, #1976d2, #2196f3);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      border-radius: 0 0 16px 16px;
    }

    .nav-left {
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
    }

    .nav-right {
      display: flex;
      gap: 1rem;
    }

    .nav-btn {
      background-color: #4fc3f7;
      color: white;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: 0.3s ease;
    }

    .nav-btn:hover {
      background-color: #29b6f6;
    }

    .logout-btn {
      background-color: #ef5350;
    }

    .logout-btn:hover {
      background-color: #e53935;
    }

    .main-container {
      max-width: 600px;
      margin: 60px auto;
      padding: 40px;
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
      text-align: center;
    }

    .main-container h1 {
      font-size: 2.2rem;
      color: #1976d2;
      margin-bottom: 2.5rem;
    }

    .admin-buttons {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      align-items: center;
    }

    .admin-btn {
      background-color: #1976d2;
      color: white;
      text-decoration: none;
      padding: 1.2rem 1rem;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    .admin-btn:hover {
      background-color: #1565c0;
      transform: translateY(-4px);
    }

    .admin-btn:active {
      background-color: #0d47a1;
      transform: translateY(2px);
    }

    @media (max-width: 600px) {
      .nav-left {
        font-size: 1.2rem;
      }
      .main-container {
        margin: 30px 16px;
        padding: 24px;
      }
    }
  </style>
</head>
<body>

  <nav>
    <div class="nav-left">🏠 Dashboard Admin</div>
    <div class="nav-right">
      <a href="profile-admin.php" class="nav-btn">Profile</a>
      <a href="logout.php" class="nav-btn logout-btn">Logout</a>
    </div>
  </nav>

  <div class="main-container">
    <h1>Panel Administrasi</h1>

    <div class="admin-buttons">
      <a href="register-akun.html" class="admin-btn">📝 Register Akun Aplikasi</a>
      <a href="admin-database-akun.php" class="admin-btn">📁 Database Akun Aplikasi</a>
      <a href="import-obat.php" class="admin-btn">💊 Update Data Obat</a>
    </div>
  </div>

</body>
</html>
