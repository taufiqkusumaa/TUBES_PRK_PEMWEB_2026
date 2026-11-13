<?php
session_start();
require_once '../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validasi input di sisi server (Mencegah input kosong)
    $npm = isset($_POST['npm']) ? trim($_POST['npm']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($npm) || empty($password)) {
        $pesan = "<p class='msg-error'>NPM dan Password wajib diisi!</p>";
    } else {
        // 2. Sebutkan nama kolom secara spesifik (Hindari SELECT *)
        $stmt = $pdo->prepare("SELECT id_user, nama_lengkap, password, role FROM users WHERE npm = ?");
        $stmt->execute([$npm]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // 3. Panggil session_regenerate_id(true) untuk mencegah Session Fixation
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../index.php");
            }
            exit();
        } else {
            $pesan = "<p class='msg-error'>NPM atau Password salah!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KabarSi FISIP</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f2eb;
            color: #141414;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background-color: #141414;
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #ffffff;
            font-weight: 800;
            font-size: 17px;
            letter-spacing: -0.3px;
        }

        .brand-badge {
            background-color: #ff5000;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 6px;
            border-radius: 3px;
        }

        .brand span {
            color: #ff5000;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .nav-links a:hover {
            color: #ff5000;
        }

        /* Area Utama */
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Kotak Form Putih */
        .auth-card {
            background-color: #ffffff;
            border: 1px solid #141414;
            border-radius: 6px;
            padding: 32px;
            width: 100%;
            max-width: 400px;
        }

        .kicker {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .auth-card h2 {
            color: #141414;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            color: #525252;
            margin-bottom: 22px;
            line-height: 1.5;
        }

        /* Input */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            color: #141414;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            background-color: #ffffff;
            border: 1px solid #c7c2b8;
            border-radius: 4px;
            font-size: 14px;
            color: #141414;
            outline: none;
        }

        .form-group input:focus {
            border-color: #141414;
        }

        /* Tombol */
        .btn-submit {
            width: 100%;
            background-color: #ff5000;
            color: #ffffff;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 6px;
        }

        .btn-submit:hover {
            background-color: #e04600;
        }

        /* Teks Bawah */
        .link-text {
            text-align: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #ece8df;
            font-size: 13px;
            color: #525252;
        }

        .link-text a {
            color: #ff5000;
            font-weight: 700;
            text-decoration: none;
        }

        .link-text a:hover {
            text-decoration: underline;
        }

        /* Pesan Notifikasi */
        .msg-error {
            background-color: #fef2f2;
            color: #b91c1c;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 16px;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <a href="../index.php" class="brand">
            <span class="brand-badge">KS</span>
            KabarSi <span>FISIP</span>
        </a>
        <nav class="nav-links">
            <a href="../index.php">Beranda</a>
            <a href="register.php">Daftar</a>
        </nav>
    </header>

    <main class="main-container">
        <div class="auth-card">
            <p class="kicker">Portal Resmi FISIP UNILA</p>
            <h2>Login Akun</h2>
            <p class="subtitle">Masuk menggunakan NPM dan password yang sudah terdaftar.</p>

            <?= $pesan; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>NPM</label>
                    <input type="text" name="npm" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">Login</button>
            </form>

            <p class="link-text">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
        </div>
    </main>
</body>
</html>