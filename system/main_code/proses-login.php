<?php
session_start();

// 1. Koneksi ke database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 2. Ambil input dari form
$username = $_POST['username'];
$password = $_POST['password'];

// 3. Ambil data user berdasarkan username
$sql = "SELECT users.id AS user_id, users.password, roles.role_name
        FROM users
        JOIN roles ON users.role_id = roles.id
        WHERE users.username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// 4. Cek apakah user ditemukan
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // 5. Verifikasi password
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role_name'];

        // 6. Redirect berdasarkan role
        switch ($user['role_name']) {
            case 'dokter':
                header("Location: dashboard-dokter.php");
                exit;
            case 'apoteker':
                header("Location: dashboard-apoteker.php");
                exit;
            case 'admin':
                header("Location: dashboard-admin.php");
                exit;
            default:
                echo "<script>alert('Role tidak dikenal.'); window.location.href='login.html';</script>";
                exit;
        }
    } else {
        echo "<script>alert('Password salah!'); window.location.href='login.html';</script>";
    }
} else {
    echo "<script>alert('Akun tidak ditemukan!'); window.location.href='login.html';</script>";
}

$conn->close();
?>
