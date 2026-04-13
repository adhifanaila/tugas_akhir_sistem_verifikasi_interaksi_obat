<?php
$host = "localhost";      // Server database (bisa juga 127.0.0.1)
$user = "root";           // Username default XAMPP
$password = "";           // Password default XAMPP kosong
$database = "interaction_cheker";  // Nama database kamu

$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>
