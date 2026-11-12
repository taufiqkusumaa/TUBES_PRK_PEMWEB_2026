<?php
// kelompok/kelompok_13/app/Views/pelanggan.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user']);
$currentUser =$_SESSION['user'] ?? null;
$userRole = strtolower($currentUser['role'] ?? '');
$mode =$_GET['mode'] ?? ($isLoggedIn ? ($userRole === 'admin' ? 'users' : 'profile') : 'login');

// =========================================================================
// KONDISI 1: JIKA BELUM LOGIN (HALAMAN AUTH MANDIRI: LOGIN / REGISTER)
// =========================================================================
if (!$isLoggedIn):
    $pageTitle = ($mode === 'register') ? 'Pendaftaran Akun Baru' : 'Masuk ke Sistem';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Machinify</title>
    <script>
        (function() {
            const saved = localStorage.getItem('bengkel_theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2.5rem 1rem;
            background-color: var(--bg-main);
            transition: background-color 0.2s ease;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background-color: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2.25rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
            letter-spacing: 0.04em;
            margin-bottom: 0.5rem;
        }

        .auth-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 20px;
            margin-bottom: 0.35rem;
        }

        .auth-subtitle {
            color: var(--text-secondary);
            font-size: 13px;
        }

        .form-label-custom {
            display: block;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.35rem;
        }

        .theme-switch-prominent {
            background-color: var(--surface);
            border: 1.5px solid var(--border-color);
            color: var(--text-primary);
            font-size: 12.5px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .theme-switch-prominent:hover {
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }

        .auth-link {
            color: var(--accent-blue);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <!-- Tombol Switch Tema Jelas & Kontras -->
        <div class="position-absolute top-0 end-0 p-3">
            <button type="button" class="theme-switch-prominent" id="btnAuthTheme">
                <i class="bi bi-sun" id="authThemeIcon"></i>
                <span id="authThemeLabel">Mode Terang</span>
            </button>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <a href="#" class="auth-brand">
                    <span class="brand-dot"></span>
                    <span>Machinify</span>
                </a>
                <?php if ($mode === 'register'): ?>
                    <div class="auth-title">Buat Akun Baru</div>
                    <div class="auth-subtitle">Daftarkan akun untuk reservasi dan pelacakan servis</div>
                <?php else: ?>
                    <div class="auth-title">Selamat Datang Kembali</div>
                    <div class="auth-subtitle">Masukkan email dan kata sandi akun Anda</div>
                <?php endif; ?>
            </div>

            <!-- Alert Notifikasi -->
            <div id="authAlert" class="alert d-none small py-2 px-3 mb-3" role="alert"></div>

            <?php if ($mode === 'register'): ?>
                <!-- FORM REGISTER -->
                <form id="formRegister">
                    <div class="mb-3">
                        <label class="form-label-custom">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control input-enterprise" required placeholder="Nama lengkap Anda">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Alamat Email</label>
                        <input type="email" name="email" class="form-control input-enterprise font-mono" required placeholder="nama@domain.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Nomor WhatsApp / HP</label>
                        <input type="text" name="no_telp" class="form-control input-enterprise font-mono" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Tipe Akun</label>
                        <select name="role" class="form-select input-enterprise">
                            <option value="customer" selected>Customer (Pelanggan Bengkel)</option>
                            <option value="teknisi">Teknisi (Mekanik Lapangan)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Kata Sandi</label>
                        <input type="password" name="password" class="form-control input-enterprise" required placeholder="Minimal 6 karakter">
                    </div>

                    <button type="submit" class="btn btn-royal w-100 py-2 fw-semibold" id="btnSubmitAuth">
                        Daftar Akun Sekarang
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                    <span style="color: var(--text-secondary); font-size: 13px;">Sudah memiliki akun?</span>
                    <a href="index.php?page=pelanggan&mode=login" class="auth-link small ms-1">
                        Masuk di sini
                    </a>
                </div>

            <?php else: ?>
                <!-- FORM LOGIN -->
                <form id="formLogin">
                    <div class="mb-3">
                        <label class="form-label-custom">Alamat Email</label>
                        <input type="email" name="email" class="form-control input-enterprise font-mono" required placeholder="nama@domain.com">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Kata Sandi</label>
                        <input type="password" name="password" class="form-control input-enterprise" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-royal w-100 py-2 fw-semibold" id="btnSubmitAuth">
                        Masuk ke Akun
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                    <span style="color: var(--text-secondary); font-size: 13px;">Belum memiliki akun?</span>
                    <a href="index.php?page=pelanggan&mode=register" class="auth-link small ms-1">
                        Daftar akun servis
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="small mt-4 text-center" style="color: var(--text-muted);">
            &copy; <?= date('Y') ?> Machinify &bull; Kelompok 13 TI Unila
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/pelanggan.js"></script>
    <script>
        const btnAuthTheme = document.getElementById('btnAuthTheme');
        const authIcon = document.getElementById('authThemeIcon');
        const authLabel = document.getElementById('authThemeLabel');

        function syncAuthTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('bengkel_theme', theme);
            if (theme === 'dark') {
                authIcon.className = 'bi bi-moon-stars text-warning';
                authLabel.textContent = 'Mode Gelap';
            } else {
                authIcon.className = 'bi bi-sun text-primary';
                authLabel.textContent = 'Mode Terang';
            }
        }

        syncAuthTheme(localStorage.getItem('bengkel_theme') || 'light');

        btnAuthTheme.addEventListener('click', () => {
            const cur = document.documentElement.getAttribute('data-theme');
            syncAuthTheme(cur === 'dark' ? 'light' : 'dark');
        });
    </script>
</body>
</html>
<?php
exit;
endif;

// =========================================================================
// KONDISI 2: JIKA SUDAH LOGIN (MENGGUNAKAN TEMPLATE HEADER & SIDEBAR)
// =========================================================================
$pageTitle = ($mode === 'profile') ? 'Pengaturan Profil Pengguna' : 'Manajemen Pengguna Sistem';
require_once __DIR__ . '/layouts/header.php';
require_once __DIR__ . '/layouts/sidebar.php';
?>

<div class="no-print">
    <?php if ($mode === 'profile'): ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
            <div>
                <h5 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.01em;">Pengaturan Akun & Profil</h5>
                <div style="color: var(--text-secondary); font-size: 13px;">Kelola informasi profil pribadi dan keamanan kata sandi Anda.</div>
            </div>
            <?php if ($userRole === 'admin'): ?>
                <a href="index.php?page=pelanggan&mode=users" class="btn-surface text-decoration-none">
                    <i class="bi bi-people me-1"></i> Buka Manajemen Pengguna
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="surface-card p-4">
                    <div class="fw-semibold mb-3 pb-2 border-bottom" style="font-size: 14px; color: var(--text-primary); border-color: var(--border-color) !important;">
                        Informasi Data Diri
                    </div>

                    <div id="profileAlert" class="alert d-none small py-2 px-3 mb-3" role="alert"></div>

                    <form id="formProfile">
                        <div class="mb-3">
                            <label class="form-label-custom">Alamat Email (Permanen)</label>
                            <input type="email" id="profEmail" class="form-control input-enterprise font-mono" readonly style="opacity: 0.75; cursor: not-allowed;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Nama Lengkap</label>
                            <input type="text" name="nama" id="profNama" class="form-control input-enterprise" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">Nomor WhatsApp / HP</label>
                            <input type="text" name="no_telp" id="profTelp" class="form-control input-enterprise font-mono" placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="mb-4">
                            <label class="form-label-custom">Ubah Kata Sandi</label>
                            <input type="password" name="password" class="form-control input-enterprise" placeholder="Biarkan kosong jika tidak ingin mengubah sandi">
                            <div class="small mt-1" style="color: var(--text-muted);">Hanya isi jika Anda berencana mengganti kata sandi lama.</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-royal" id="btnSaveProfile">
                                <i class="bi bi-check2 me-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="surface-card p-4 mb-3">
                    <div class="fw-semibold mb-3 pb-2 border-bottom" style="font-size: 14px; color: var(--text-primary); border-color: var(--border-color) !important;">
                        Ringkasan Hak Akses
                    </div>
                    <div class="mb-3">
                        <span class="form-label-custom mb-1">Peran Sesi Aktif</span>
                        <span class="role-pill fs-6"><?= strtoupper(htmlspecialchars($currentUser['role'] ?? 'CUSTOMER')) ?></span>
                    </div>
                    <div class="mb-3">
                        <span class="form-label-custom mb-1">Status Akun</span>
                        <span class="status-badge status-success"><span class="dot"></span>Akun Aktif</span>
                    </div>
                    <p class="small mb-0" style="color: var(--text-secondary);">
                        Hak akses mengatur batasan modul yang dapat Anda buka di panel samping.
                    </p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
            <div>
                <h5 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.01em;">Manajemen Pengguna & Teknisi</h5>
                <div style="color: var(--text-secondary); font-size: 13px;">Kelola akun pelanggan dan status aktivasi akun teknisi.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn-surface" id="btnRefreshUsers">
                    <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang
                </button>
            </div>
        </div>

        <div class="surface-card overflow-hidden">
            <div class="p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom" style="border-color: var(--border-color) !important;">
                <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">Daftar Akun Terdaftar</div>
                <div class="d-flex flex-wrap gap-2">
                    <select class="input-enterprise" id="filterRole" style="width: 140px;">
                        <option value="">Semua Peran</option>
                        <option value="admin">Admin</option>
                        <option value="teknisi">Teknisi</option>
                        <option value="customer">Customer</option>
                    </select>
                    <input type="text" class="input-enterprise" id="searchUser" placeholder="Cari nama atau email..." style="width: 220px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-enterprise">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Nama Lengkap</th>
                            <th>Email Akun</th>
                            <th>No. Telepon</th>
                            <th>Peran Akses</th>
                            <th>Status Akun</th>
                            <th class="text-end" style="width: 150px;">Aksi / Kontrol</th>
                        </tr>
                    </thead>
                    <tbody id="tableUsersBody">
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color: var(--text-muted);">Memuat data pengguna...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/pelanggan.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>