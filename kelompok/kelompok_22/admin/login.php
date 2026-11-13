<?php
session_start();
require_once '../config/koneksi.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("SELECT id_admin, username, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verifikasi password hash
        if (password_verify($password, $row['password'])) {
            $_SESSION['id_admin'] = $row['id_admin'];
            $_SESSION['username'] = $row['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - KlikObat</title>
    <link rel="stylesheet" href="../frontend/assets/css/style.css">
<link rel="stylesheet" href="../frontend/assets/css/admin.css"> <!-- Sesuaikan jika ada CSS -->
</head>
<body>
    <div class="login-container" style="max-width: 400px; margin: 80px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2>Login Admin KlikObat</h2>
        <?php if (!empty($error)): ?>
            <div style="color: red; margin-bottom: 10px;"><?= $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div style="margin-bottom: 15px;">
                <label>Username:</label><br>
                <input type="text" name="username" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Password:</label><br>
                <input type="password" name="password" required style="width: 100%; padding: 8px;">
            </div>
            <button type="submit" style="width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px;">Masuk</button>
        </form>
    </div>
</body>
</html>