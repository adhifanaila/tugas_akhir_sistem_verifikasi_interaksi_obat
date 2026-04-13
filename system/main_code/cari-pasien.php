<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "interaction_cheker";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$search = $_GET['q'] ?? '';
$sql = "SELECT id, name, tanggal_lahir, alamat FROM patients WHERE name LIKE ?";
$stmt = $conn->prepare($sql);
$likeSearch = "%$search%";
$stmt->bind_param("s", $likeSearch);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'text' => $row['name'],
        'tanggal_lahir' => $row['tanggal_lahir'],
        'alamat' => $row['alamat']
    ];
}
echo json_encode(['results' => $data]);

$stmt->close();
$conn->close();
?>