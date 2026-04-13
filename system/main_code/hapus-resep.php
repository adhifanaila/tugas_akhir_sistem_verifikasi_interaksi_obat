<?php
session_start();

// Cek apakah user login sebagai dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: login.html");
    exit;
}

// Cek apakah parameter id ada
if (!isset($_GET['id'])) {
    die("ID resep tidak ditemukan.");
}

$id = intval($_GET['id']);

// Koneksi ke database
$mysqli = new mysqli("localhost", "root", "", "interaction_cheker");

if ($mysqli->connect_errno) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Hapus item resep terlebih dahulu (karena relasi foreign key)
$mysqli->query("DELETE FROM prescription_items WHERE prescription_id = $id");

// Hapus resep utama
$mysqli->query("DELETE FROM prescriptions WHERE id = $id");

header("Location: riwayat-resep.php");
exit;
?>
