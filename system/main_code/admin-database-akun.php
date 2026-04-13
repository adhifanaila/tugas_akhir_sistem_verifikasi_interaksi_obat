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

$sql = "
    SELECT u.id AS user_id, u.username, r.role_name,
        d.name AS doctor_name, d.email AS doctor_email,
        p.name AS pharmacist_name, p.email AS pharmacist_email,
        a.name AS admin_name, a.email AS admin_email
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN doctors d ON u.id = d.user_id
    LEFT JOIN pharmacists p ON u.id = p.user_id
    LEFT JOIN admins a ON u.id = a.user_id
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Database Akun Pengguna</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f4fb;
            margin: 0;
            padding: 0;
        }

        nav {
            background-color: #1976d2;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .nav-container a {
            text-decoration: none;
            color: white;
        }

        .main-container {
            max-width: 1000px;
            margin: 3rem auto;
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #1976d2;
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 0.8rem 1rem;
            text-align: left;
        }

        th {
            background-color: #1976d2;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f7f9fc;
        }

        tr:hover {
            background-color: #e3f2fd;
        }

        a.action-link {
            text-decoration: none;
            color: #1976d2;
            font-weight: bold;
        }

        a.action-link:hover {
            text-decoration: underline;
        }

        .action-icons {
            display: flex;
            gap: 0.5rem;
        }

        .action-icons a {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }
    </style>
</head>
<body>
<nav>
    <div class="nav-container">
        <a href="dashboard-admin.php">⬅ Kembali</a>
    </div>
</nav>

<main class="main-container">
    <h1>Database Akun Pengguna</h1>

    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Nama Lengkap</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()):
            if ($row['role_name'] === 'dokter') {
                $name = $row['doctor_name'] ?? '-';
                $email = $row['doctor_email'] ?? '-';
            } elseif ($row['role_name'] === 'apoteker') {
                $name = $row['pharmacist_name'] ?? '-';
                $email = $row['pharmacist_email'] ?? '-';
            } elseif ($row['role_name'] === 'admin') {
                $name = $row['admin_name'] ?? '-';
                $email = $row['admin_email'] ?? '-';
            } else {
                $name = '-';
                $email = '-';
            }
        ?>
            <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($email) ?></td>
                <td><?= ucfirst($row['role_name']) ?></td>
                <td><?= htmlspecialchars($name) ?></td>
                <td class="action-icons">
                    <a href="edit-list-account.php?id=<?= $row['user_id'] ?>" class="action-link">✏️ Edit</a>
                    <a href="delete_account.php?id=<?= $row['user_id'] ?>" class="action-link" onclick="return confirm('Yakin ingin hapus akun ini?')">🗑️ Hapus</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</main>
</body>
</html>
