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

$id     = $_POST['id'];
$role   = $_POST['role'];
$name   = $_POST['name'];
$email  = $_POST['email'];
$new_password = $_POST['new_password'] ?? ''; // opsional

// Validasi ID dan role
if (!$id || !$role || !$name || !$email) {
    die("Data tidak lengkap.");
}

// Update nama dan email
if ($role === 'dokter') {
    $sql = "UPDATE doctors SET name = ?, email = ? WHERE user_id = ?";
} elseif ($role === 'apoteker') {
    $sql = "UPDATE pharmacists SET name = ?, email = ? WHERE user_id = ?";
} elseif ($role === 'admin') {
    $sql = "UPDATE admins SET name = ?, email = ? WHERE user_id = ?";
} else {
    die("Role tidak valid.");
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $name, $email, $id);
$success = $stmt->execute();

if (!$success) {
    die("Gagal update data profil: " . $stmt->error);
}

// Reset password jika diisi
if (!empty($new_password)) {
    $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt_pw = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_pw->bind_param("si", $hashed_pw, $id);
    if (!$stmt_pw->execute()) {
        die("Gagal update password: " . $stmt_pw->error);
    }
}

$conn->close();
header("Location: admin-database-akun.php");
exit;
