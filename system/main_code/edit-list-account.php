<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID tidak ditemukan.");
}

$sql = "
    SELECT u.username, r.role_name,
        d.name AS doctor_name, d.email AS doctor_email,
        p.name AS pharmacist_name, p.email AS pharmacist_email,
        a.name AS admin_name, a.email AS admin_email
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN doctors d ON u.id = d.user_id
    LEFT JOIN pharmacists p ON u.id = p.user_id
    LEFT JOIN admins a ON u.id = a.user_id
    WHERE u.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$role = strtolower($data['role_name']); // lowercase agar mudah dicocokkan

switch ($role) {
    case 'dokter':
        $name = $data['doctor_name'] ?? '';
        $email = $data['doctor_email'] ?? '';
        break;
    case 'apoteker':
        $name = $data['pharmacist_name'] ?? '';
        $email = $data['pharmacist_email'] ?? '';
        break;
    case 'admin':
        $name = $data['admin_name'] ?? '';
        $email = $data['admin_email'] ?? '';
        break;
    default:
        $name = '';
        $email = '';
        break;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Akun</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f4fb;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            background-color: white;
            margin: 3rem auto;
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #1976d2;
            margin-bottom: 2rem;
        }

        form label {
            display: block;
            margin-top: 1rem;
            font-weight: bold;
            color: #333;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            margin-top: 0.3rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
        }

        button[type="submit"] {
            margin-top: 2rem;
            padding: 0.8rem 1.5rem;
            background-color: #1976d2;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #1565c0;
        }

        .back-link {
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            color: #1976d2;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Akun - <?= ucfirst($role) ?></h2>
    <form method="POST" action="update_account.php">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="role" value="<?= $role ?>">

        <label>Username:</label>
        <input type="text" value="<?= htmlspecialchars($data['username']) ?>" disabled>

        <label>Nama:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label>Password Baru (Opsional):</label>
        <input type="password" name="new_password" placeholder="Biarkan kosong jika tidak diubah">

        <button type="submit">💾 Simpan Perubahan</button>
    </form>
    <a href="admin-database-akun.php" class="back-link">⬅ Kembali ke Database</a>
</div>
</body>
</html>
