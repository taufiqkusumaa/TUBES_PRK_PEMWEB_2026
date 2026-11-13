<?php
// ==========================================
// 1. KONFIGURASI ERROR
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================
// 2. MEMULAI SESSION
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// 3. KONEKSI DATABASE
// ==========================================
require_once __DIR__ . '/config/koneksi.php';

// Pastikan koneksi tersedia
if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    die("Koneksi database tidak tersedia. Periksa config/koneksi.php");
}

// ==========================================
// 4. CEK SESSION LOGIN
// ==========================================
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// ==========================================
// 5. INISIALISASI VARIABEL
// ==========================================
$error = '';
$username = '';

// ==========================================
// 6. PROSES LOGIN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi!";

    } else {

        try {

            // Ambil data pengguna berdasarkan username
            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT * FROM users WHERE username = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            // ==========================================
            // 7. VALIDASI USERNAME DAN PASSWORD
            // ==========================================
            // Mendukung hash Bcrypt (password_hash PHP) maupun SHA2-256 (MySQL)
            $is_valid_password = false;
            if ($user && isset($user['password'])) {
                $is_valid_password = password_verify($password, $user['password'])
                    || hash('sha256', $password) === $user['password'];
            }

            if ($is_valid_password) {

                // Role yang diperbolehkan
                $allowed_roles = [
                    'admin',
                    'peternak',
                    'dokter'
                ];

                $role = strtolower(trim($user['role'] ?? ''));

                if (!in_array($role, $allowed_roles, true)) {

                    $error = "Role pengguna tidak valid.";

                } elseif (
                    !isset($user['nama_lengkap']) ||
                    $user['nama_lengkap'] === ''
                ) {

                    $error = "Data nama lengkap pengguna belum tersedia.";

                } else {

                    // ==========================================
                    // 8. MEMBUAT SESSION LOGIN
                    // ==========================================
                    session_regenerate_id(true);

                    // Deteksi nama kolom ID
                    $user_id = $user['id_user']
                        ?? $user['id']
                        ?? null;

                    // Simpan format array bertingkat
                    $_SESSION['user'] = [
                        'id'           => $user_id,
                        'username'     => $user['username'],
                        'nama_lengkap' => $user['nama_lengkap'],
                        'role'         => $role
                    ];

                    // Simpan juga format langsung agar cocok dengan index.php / modul lain
                    $_SESSION['id_user']      = $user_id;
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role']         = $role;

                    // ==========================================
                    // 9. REDIRECT KE DASHBOARD
                    // ==========================================
                    header("Location: index.php");
                    exit;
                }

            } else {

                $error = "Username atau password salah!";

            }

        } catch (mysqli_sql_exception $e) {

            error_log("Login database error: " . $e->getMessage());
            $error = "Terjadi kesalahan database. Periksa konfigurasi dan struktur tabel users.";

        } catch (Throwable $e) {

            error_log("Login system error: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan periksa konfigurasi aplikasi.";

        }
    }
}

$pesan_logout = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $pesan_logout = 'Anda berhasil logout. Silakan login kembali.';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Log Ternak K04</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url; ?>/assets/css/theme.css?v=1">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background: var(--bs-light);
            min-height: 100vh;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: var(--bs-border-radius);
        }

        .login-icon {
            color: var(--brand);
        }

        .btn-login {
            background-color: var(--brand);
            border: none;
        }

        .btn-login:hover {
            background-color: var(--brand-dark);
        }
    </style>
</head>

<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">

    <div class="card login-card shadow-sm p-4">

        <!-- JUDUL LOGIN -->
        <div class="text-center mb-4">
            <i class="bi bi-box-seam login-icon"></i>
            <h3 class="fw-bold text-success mt-2">
                Log Ternak K04
            </h3>
            <p class="text-muted">
                Silakan login ke akun Anda
            </p>
        </div>

        <!-- PESAN LOGOUT -->
        <?php if ($pesan_logout !== ''): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($pesan_logout, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- PESAN ERROR -->
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form method="POST" action="">

            <!-- USERNAME -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Username
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                        autocomplete="username"
                        required
                        autofocus>
                </div>
            </div>

            <!-- PASSWORD -->
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Password
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required>
                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <!-- TOMBOL LOGIN -->
            <button
                type="submit"
                class="btn btn-success btn-login w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Login
            </button>

        </form>

        <!-- FOOTER -->
        <div class="text-center mt-4">
            <small class="text-muted">
                &copy; 2026 Praktikum Pemrograman Web
                <br>
                Kelompok 04
            </small>
        </div>

    </div>

</div>

<!-- JAVASCRIPT -->
<script>
function togglePassword() {
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (password.type === 'password') {
        password.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        password.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>