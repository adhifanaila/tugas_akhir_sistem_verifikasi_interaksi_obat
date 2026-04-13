<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
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

$user_id = $_SESSION['user_id'];
$updateSuccess = null;
$passwordUpdateSuccess = null;

// Update data dokter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name'], $_POST['phone'], $_POST['email'])) {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $nip = $_POST['nip'] ?? '';
        $str = $_POST['str_number'] ?? '';

        $stmt = $conn->prepare("UPDATE doctors SET name=?, phone=?, email=?, nip=?, str_number=? WHERE user_id=?");
        $stmt->bind_param("sssssi", $name, $phone, $email, $nip, $str, $user_id);
        $updateSuccess = $stmt->execute();
    }

    // Update password
    if (isset($_POST['old_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password === $confirm_password) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (password_verify($old_password, $user['password'])) {
                $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_new, $user_id);
                $passwordUpdateSuccess = $stmt->execute();
            } else {
                $passwordUpdateSuccess = false;
            }
        } else {
            $passwordUpdateSuccess = false;
        }
    }
}

// Ambil data dokter
$stmt = $conn->prepare("SELECT name, phone, email, nip, str_number FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profil Dokter</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f4f7fb;
      margin: 0;
      padding: 0;
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
    }

    .main-container {
      max-width: 600px;
      margin: 3rem auto;
      background: white;
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    }

    h2 {
      text-align: center;
      color: #1976d2;
      margin-bottom: 1.8rem;
    }

    form {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    form label {
      align-self: center; /* dari sebelumnya: flex-start */
      font-weight: 600;
      margin-bottom: 6px;
      color: #333;
      text-align: center;  /* tambahan untuk memusatkan teks */
    }


    form input {
      width: 100%;
      max-width: 500px;
      padding: 12px 14px;
      margin-bottom: 18px;
      border: 1.5px solid #ccc;
      border-radius: 8px;
      background: #f9f9f9;
      font-size: 1rem;
    }

    form input:focus {
      border-color: #1976d2;
      outline: none;
      background: #fff;
    }

    .btn-submit {
      width: 100%;
      max-width: 500px;
      background-color: #1976d2;
      color: white;
      border: none;
      padding: 14px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-submit:hover {
      background-color: #145ea8;
    }

    .message {
      text-align: center;
      padding: 12px;
      border-radius: 8px;
      font-weight: 500;
      margin-bottom: 1rem;
    }

    .success {
      background-color: #e8f5e9;
      color: #256029;
      border: 1px solid #c8e6c9;
    }

    .error {
      background-color: #fdecea;
      color: #b71c1c;
      border: 1px solid #f5c6cb;
    }

    hr {
      margin: 2rem 0;
      border: none;
      border-top: 1px solid #ddd;
    }
  </style>
</head>
<body>

  <nav>
    <div><a href="profile.php">⬅ Kembali</a></div>
  </nav>

  <div class="main-container">
    <h2>Edit Profil Dokter</h2>

    <?php if ($updateSuccess === true): ?>
      <div class="message success">✅ Data profil berhasil diperbarui.</div>
    <?php elseif ($updateSuccess === false): ?>
      <div class="message error">❌ Gagal memperbarui data.</div>
    <?php endif; ?>

    <form method="POST">
      <label for="name">Nama</label>
      <input type="text" name="name" id="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>" required>

      <label for="phone">Telepon</label>
      <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($data['phone'] ?? '') ?>">

      <label for="email">Email Pribadi</label>
      <input type="email" name="email" id="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" required>

      <label for="nip">NIP</label>
      <input type="text" name="nip" id="nip" value="<?= htmlspecialchars($data['nip'] ?? '') ?>">

      <label for="str_number">Nomor STR</label>
      <input type="text" name="str_number" id="str_number" value="<?= htmlspecialchars($data['str_number'] ?? '') ?>">

      <button type="submit" class="btn-submit">💾 Simpan Perubahan</button>
  </div>

</body>
</html>
