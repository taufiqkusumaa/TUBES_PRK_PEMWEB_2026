<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../assets/style.css">
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $pesan = "Email dan password wajib diisi!";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, nama, email, password, role
             FROM users
             WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["nama"] = $user["nama"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === "petani") {
                header("Location: ../petani/dashboard.php");
                exit;
            } 

        } else {
            $pesan = "Email atau password salah!";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login BUYPANEN</title>
    
    <!-- Memanggil CSS Global agar form kembali ke tengah dan berbentuk kartu putih -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">

    <div class="login-container">
        <!-- Judul Atas -->
        <h1>Login BUYPANEN</h1>
        <h2>Login Petani</h2>

        <!-- Area Pesan Error/Notifikasi -->
        <?php if (!empty($pesan)): ?>
            <div class="login-message">
                <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <!-- Form Login (Kartu Putih) -->
        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" placeholder="contoh@buypanen.com" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <!-- Area Link Bawah -->
        <div class="login-register">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>

            <div style="margin-top: 20px;">
                <a href="../index.php" style="color: #718078; text-decoration: none; font-size: 14px; font-weight: 500;">
                    &larr; Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

</body>
</html>
