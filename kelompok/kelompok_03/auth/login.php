<?php

require_once __DIR__ . '/../includes/init.php';

// Jika sudah login, langsung alihkan ke dashboard masing-masing role (admin atau pasien)
if (isset($_SESSION['user_id'])) {
    $redirect = $_SESSION['role'] === 'admin' ? '../admin/admin.php' : '../pasien/pasien.php';
    header("Location: $redirect");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, nama, email, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Alihkan ke dashboard admin jika role admin, atau ke portal pasien (pasien.php) jika role pasien
                $redirect = $user['role'] === 'admin' ? '../admin/admin.php' : '../pasien/pasien.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            error_log('[Login] ' . $e->getMessage());
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
    <title>Login - Sistem Antrian Klinik</title>
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
            <a href="register.php" class="tombol-kecil">Daftar</a>
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

                    <!-- Sisi Kanan: Card Form Login -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-body p-4 p-md-5">
                                <h3 class="fw-bold mb-1">Masuk</h3>
                                <p class="text-muted small mb-4">Gunakan akun yang sudah terdaftar</p>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-medium">Email</label>
                                        <input type="email" class="form-control py-2" id="email" name="email" 
                                               placeholder="nama@email.com"
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="password" class="form-label fw-medium">Password</label>
                                        <input type="password" class="form-control py-2" id="password" name="password" 
                                               placeholder="Masukkan password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                                </form>

                               <div class="text-center mt-4 mb-0">
                                    <p class="mb-2 text-muted small">
                                        Belum punya akun? <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--primary);">Daftar di sini</a>
                                    </p>
                                    <a href="javascript:void(0)" onclick="document.getElementById('needHelpDialog').showModal()" class="text-muted small text-decoration-none fw-semibold">
                                        Need Help?
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <dialog id="needHelpDialog" class="border-0 rounded-4 shadow p-4" style="max-width: 420px; width: 90%;">
        <h5 class="fw-bold mb-2" style="font-family: 'Bricolage Grotesque', sans-serif; color: var(--text-main);">
            Bantuan Akses Akun
        </h5>
        <p class="text-muted mb-4">
            Password atau akun bermasalah? Harap lapor ke petugas loket/admin <strong>Klinik FK Unila</strong> untuk reset akun.
        </p>
        <button type="button" onclick="document.getElementById('needHelpDialog').close()" class="btn btn-secondary px-4">
            Close
        </button>
    </dialog>
</body>
</html>