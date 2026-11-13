<?php
// kelompok/kelompok_12/admin/login.php
session_start();

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Prepared statement untuk mencegah SQL Injection
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifikasi admin dan password hash
        if ($admin && password_verify($password, $admin['password'])) {
            // Set session saat login berhasil
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Harap isi semua kolom!';
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Admin - SehatIn</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/login.css?v=1">
</head>

<body class="login-page">

    <div class="login-card">

        <h2>🌿 SehatIn</h2>

        <p>Masuk ke halaman administrator</p>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <label>Username</label>
            <input
                type="text"
                name="username"
                placeholder="Masukkan username"
                required
            >

            <label>Password</label>
            <input
                type="password"
                name="password"
                placeholder="Masukkan password"
                required
            >

            <button type="submit">
                Login
            </button>

        </form>

        <p style="text-align:center; margin-top:20px;">
            <a href="../index.php"
               style="color:#198754; text-decoration:none; font-size:13px;">
                Kembali ke Beranda
            </a>
        </p>

    </div>

</body>
</html>
