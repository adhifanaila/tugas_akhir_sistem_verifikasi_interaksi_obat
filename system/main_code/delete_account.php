<?php
// file: delete_account.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "interaction_cheker");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    die("ID tidak ditemukan.");
}

// Hapus data dari tabel role-specific
$conn->query("DELETE FROM doctors WHERE user_id = $user_id");
$conn->query("DELETE FROM pharmacists WHERE user_id = $user_id");
$conn->query("DELETE FROM admins WHERE user_id = $user_id");
// Hapus dari users
$conn->query("DELETE FROM users WHERE id = $user_id");

$conn->close();
header("Location: admin-database-akun.php");
exit;