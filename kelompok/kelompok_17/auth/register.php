<?php
session_start();
require_once '../config/database.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil input menggunakan isset() dan trim()
    $nama_lengkap_input = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $npm_input = isset($_POST['npm']) ? trim($_POST['npm']) : '';
    $password_input = isset($_POST['password']) ? $_POST['password'] : '';

    // 2. Validasi input kosong (Server-side validation)
    if (empty($nama_lengkap_input) || empty($npm_input) || empty($password_input)) {
        $pesan = "<p class='msg-error'>Semua kolom (Nama, NPM, dan Password) wajib diisi!</p>";
    } else {
        // 3. Sanitasi dan Enkripsi jika data sudah lengkap
        $nama_lengkap = htmlspecialchars($nama_lengkap_input);
        $npm = htmlspecialchars($npm_input);
        $password = password_hash($password_input, PASSWORD_DEFAULT);
        $role = 'mahasiswa';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, npm, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama_lengkap, $npm, $password, $role]);
            $pesan = "<p class='msg-success'>Registrasi berhasil! Silakan login.</p>";
        } catch (PDOException $e) {
            $pesan = "<p class='msg-error'>Error: NPM sudah terdaftar atau terjadi kesalahan.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - KabarSi FISIP</title>
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
        .register-card {
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

        .register-card h2 {
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

        .msg-success {
            background-color: #f0fdf4;
            color: #15803d;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 16px;
            border: 1px solid #86efac;
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
            <a href="login.php">Login</a>
        </nav>
    </header>

    <main class="main-container">
        <div class="register-card">
            <p class="kicker">Portal Resmi FISIP UNILA</p>
            <h2>Daftar Akun Mahasiswa</h2>
            <p class="subtitle">Gunakan NPM aktif untuk membuat akun aspirasi dan diskusi kampus.</p>

            <?= $pesan; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required>
                </div>
                <div class="form-group">
                    <label>NPM</label>
                    <input type="text" name="npm" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">Daftar</button>
            </form>

            <p class="link-text">Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </main>
</body>
</html>