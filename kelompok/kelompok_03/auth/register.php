<?php

require_once __DIR__ . '/../includes/init.php';

// Jika pengguna sudah login, langsung alihkan sesuai role (admin atau pasien)
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] === 'admin' ? '../admin/dokter_list.php' : '../pasien/pasien.php';
    header("Location: $redirect");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($nama) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Password dan konfirmasi password tidak sama.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nama, $email, $passwordHash, 'pasien']);
                $success = 'Registrasi berhasil. Silakan login.';
            }
        } catch (PDOException $e) {
            error_log('[Register] ' . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistem Antrian Klinik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="halaman-auth bg-light">
    <header class="topbar">
        <a href="../index.php" class="brand fw-bold">Klinik FK Unila</a>
        <nav aria-label="Menu utama">
            <a href="../index.php">Jadwal</a>
            <a href="login.php" class="tombol-kecil">Masuk</a>
        </nav>
    </header>
    <div class="container py-5">
        <div class="row justify-content-center align-items-center" style="min-height: calc(100vh - 140px);">
            <div class="col-lg-9">
                <div class="row g-5 align-items-start">
                    
                    <!-- Sisi Kiri: Informasi & Branding -->
                    <div class="col-md-6 pt-1">
                        <span class="badge rounded-pill px-3 py-2 mb-3" style="background-color: #ccfbf1; color: var(--utama-gelap); font-weight: 600;">
                            Sistem Antrian Klinik
                        </span>
                        <h1 class="fw-bold mb-2" style="font-family: 'Bricolage Grotesque', sans-serif; color: var(--text-main); line-height: 1.2;">
                            Layanan Antrian Online Klinik FK Unila
                        </h1>
                        <p class="text-muted mb-2">
                            Masuk untuk mengambil nomor antrian poli, memantau jadwal dokter praktik, dan melihat estimasi giliran pemeriksaan Anda secara real-time.
                        </p>
                        <div class="d-flex flex-column gap-1 small text-muted">
                            <div>Cek jadwal &amp; kuota dokter hari ini</div>
                            <div>Ambil nomor antrian tanpa perlu antre lama di lokasi</div>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Card Form Register -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-body p-4 p-md-5">
                                <h3 class="fw-bold mb-1">Daftar Akun</h3>
                                <p class="text-muted small mb-4">Lengkapi data diri untuk membuat akun pasien</p>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="nama" class="form-label fw-medium">Nama Lengkap</label>
                                        <input type="text" class="form-control py-2" id="nama" name="nama" 
                                               placeholder="Masukkan nama lengkap"
                                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-medium">Email</label>
                                        <input type="email" class="form-control py-2" id="email" name="email" 
                                               placeholder="nama@email.com"
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-medium">Password</label>
                                        <input type="password" class="form-control py-2" id="password" name="password" 
                                               placeholder="Minimal 6 karakter" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="password_confirm" class="form-label fw-medium">Konfirmasi Password</label>
                                        <input type="password" class="form-control py-2" id="password_confirm" name="password_confirm" 
                                               placeholder="Masukkan password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-2">Register</button>
                                </form>

                                <div class="text-center mt-4 mb-0">
                                    <p class="mb-0 text-muted small">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--primary);">Login di sini</a></p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>