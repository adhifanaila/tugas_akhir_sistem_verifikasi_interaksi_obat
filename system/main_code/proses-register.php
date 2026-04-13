<?php
// Tampilkan error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Koneksi ke database
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 2. Ambil data dari form
$role = $_POST['role'];
$nama = $_POST['nama'];
$email = $_POST['email'];
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// 3. Cek apakah username atau email sudah terdaftar
$sql_cek = "SELECT * FROM users 
            JOIN roles ON users.role_id = roles.id
            WHERE username = ? OR EXISTS (
                SELECT 1 FROM admins WHERE email = ? AND users.id = admins.user_id
                UNION
                SELECT 1 FROM doctors WHERE email = ? AND users.id = doctors.user_id
                UNION
                SELECT 1 FROM pharmacists WHERE email = ? AND users.id = pharmacists.user_id
            )";

$stmt_cek = $conn->prepare($sql_cek);
$stmt_cek->bind_param("ssss", $username, $email, $email, $email);
$stmt_cek->execute();
$result_cek = $stmt_cek->get_result();

if ($result_cek->num_rows > 0) {
    echo "<script>
            alert('Username atau Email sudah terdaftar.');
            window.history.back();
          </script>";
    exit;
}

// 4. Ambil role_id
$sql_role = "SELECT id FROM roles WHERE role_name = ?";
$stmt_role = $conn->prepare($sql_role);
$stmt_role->bind_param("s", $role);
$stmt_role->execute();
$result_role = $stmt_role->get_result();

if ($result_role->num_rows > 0) {
    $row = $result_role->fetch_assoc();
    $role_id = $row['id'];
} else {
    die("Role tidak ditemukan.");
}

// 5. Simpan ke tabel users
$sql_user = "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("ssi", $username, $password, $role_id);

if ($stmt_user->execute()) {
    $user_id = $stmt_user->insert_id;

    // 6. Simpan ke tabel role masing-masing
    if ($role === 'dokter') {
        $stmt = $conn->prepare("INSERT INTO doctors (user_id, name, email) VALUES (?, ?, ?)");
    } elseif ($role === 'apoteker') {
        $stmt = $conn->prepare("INSERT INTO pharmacists (user_id, name, email) VALUES (?, ?, ?)");
    } elseif ($role === 'admin') {
        $stmt = $conn->prepare("INSERT INTO admins (user_id, name, email) VALUES (?, ?, ?)");
    }

    $stmt->bind_param("iss", $user_id, $nama, $email);
    $stmt->execute();

    echo "<script>
            alert('Registrasi berhasil!');
            window.location.href = 'register-akun.html';
          </script>";
} else {
    echo "<script>
            alert('Gagal menyimpan data.');
            window.history.back();
          </script>";
}

$conn->close();
?>
