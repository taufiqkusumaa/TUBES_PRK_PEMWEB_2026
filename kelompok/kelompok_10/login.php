<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errorMessage = '';
$hasUsers = false;
try {
    $hasUsers = (bool) db()->query('SELECT id FROM users LIMIT 1')->fetch();
} catch (PDOException $exception) {
    error_log('SisaSense login database check failed: ' . $exception->getMessage());
    $errorMessage = 'Database belum siap. Impor database/sisa_sense.sql dan periksa konfigurasi koneksi.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage === '') {
    require_valid_csrf();
    $identifier = is_string($_POST['identifier'] ?? null) ? trim($_POST['identifier']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $statement = db()->prepare(
        'SELECT id, full_name, username, email, password_hash, role, is_active
         FROM users WHERE username = :username OR email = :email LIMIT 1'
    );
    $statement->execute(['username' => $identifier, 'email' => $identifier]);
    $user = $statement->fetch();

    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'is_active' => true,
        ];
        flash('success', 'Selamat datang kembali, ' . $user['full_name'] . ' (' . user_role_label($_SESSION['user']) . ').');
        redirect('dashboard.php');
    }

    $errorMessage = 'Username/email atau kata sandi tidak sesuai.';
}

$flashMessage = take_flash();
$pageTitle = 'Masuk Akun';
$activePage = 'login';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-split-wrapper">
    <!-- Kolom Kiri: Minimalist Agro Brand ala Ecavo & Majoo -->
    <div class="auth-hero-side" style="background: linear-gradient(135deg, #0F5132 0%, #166534 50%, #064E3B 100%); color: #fff; padding: 48px 40px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div class="auth-hero-brand" style="display: flex; align-items: center; gap: 12px; margin-bottom: 36px;">
                <span class="brand-mark" style="background: #fff; color: #0F5132; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.15); width: 44px; height: 44px; font-weight: 800; font-size: 22px; display: grid; place-items: center;">S</span>
                <div>
                    <span style="display: block; font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 800; color: #fff; line-height: 1.1;">SisaSense</span>
                    <small style="font-size: 11px; color: #BBF7D0; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">Agro Staples &amp; POS UMKM</small>
                </div>
            </div>

            <div class="auth-hero-content" style="margin: 0; padding: 0;">
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 9999px; background: rgba(255,255,255,0.15); color: #BBF7D0; font-size: 11.5px; font-weight: 750; border: 1px solid rgba(255,255,255,0.25); margin-bottom: 16px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #22C55E;"></span>
                    Aplikasi Wirausaha Pangan Modern
                </span>

                <h1 style="font-family: 'Playfair Display', Georgia, serif; font-size: 30px; font-weight: 800; line-height: 1.25; color: #fff; margin: 0 0 14px;">
                    Kelola Bisnis Pangan &amp; Kasir Jadi Maju.
                </h1>

                <p style="color: rgba(255,255,255,0.88); font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                    Platform cerdas untuk alokasi persediaan stok metode FEFO, transaksi kasir kilat, serta kepastian produk pangan UMKM.
                </p>

                <!-- Single Clean Minimalist Badge Card -->
                <div style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22); border-radius: 16px; padding: 18px 20px; backdrop-filter: blur(8px);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 11px; font-weight: 750; color: #BBF7D0; text-transform: uppercase; letter-spacing: .06em;">Katalog Komoditas Riil</span>
                        <span style="background: #22C55E; color: #fff; font-size: 10.5px; padding: 2px 8px; border-radius: 9999px; font-weight: 800;">24 Produk</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.2); display: grid; place-items: center; font-size: 18px; flex-shrink: 0;">
                            🌿
                        </div>
                        <div>
                            <strong style="color: #fff; font-size: 13.5px; display: block;">Minyak, Madu, Beras &amp; Snack</strong>
                            <small style="color: #BBF7D0; font-size: 11.5px;">Foto riil autentik &bull; Kedaluwarsa terpantau</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="font-size: 12px; color: #BBF7D0; display: flex; align-items: center; justify-content: space-between; padding-top: 18px; border-top: 1px solid rgba(255, 255, 255, 0.2); margin-top: 28px;">
            <span>&check; FEFO Terintegrasi</span>
            <span>&check; Struk Thermal POS</span>
        </div>
    </div>

    <!-- Kolom Kanan: Form Masuk Akun Bersih & Elegan -->
    <div class="auth-form-side">
        <div class="auth-form-header">
            <p class="eyebrow" style="color: #0F5132; font-weight: 800; letter-spacing: .08em;">PORTAL MASUK SISTEM</p>
            <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 800; color: #0F172A; margin: 4px 0 8px;">Selamat Datang Kembali</h2>
            <p style="color: #64748B; font-size: 13.5px; margin: 0;">Masukkan username atau email Anda untuk melanjutkan.</p>
        </div>

        <?php if ($flashMessage): ?>
            <div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="identifier" style="font-size: 13px; font-weight: 750; color: #1E293B;">Username atau Email</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input id="identifier" name="identifier" value="<?= e($_POST['identifier'] ?? '') ?>" maxlength="150" autocomplete="username" placeholder="Masukkan username atau email akun" required autofocus class="auth-input-field">
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" style="margin-bottom: 0; font-size: 13px; font-weight: 750; color: #1E293B;">Kata Sandi</label>
                </div>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi akun" required class="auth-input-field" style="padding-right: 44px;">
                    <button type="button" id="togglePasswordBtn" class="auth-password-toggle" aria-label="Tampilkan atau sembunyikan kata sandi" title="Lihat kata sandi">
                        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; margin-top: 4px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 0; font-weight: normal; color: #475569;">
                    <input type="checkbox" name="remember" value="1" style="width: 16px; height: 16px; accent-color: #0F5132;">
                    Ingat sesi saya
                </label>
                <span class="muted" style="font-size: 12px; color: #64748B;">Sesi terenkripsi &amp; aman</span>
            </div>

            <button type="submit" class="button button-dark" style="width: 100%; min-height: 50px; font-size: 15px; font-weight: 800; border-radius: var(--radius-full); margin-top: 14px; justify-content: center; background: #0F5132; box-shadow: 0 4px 14px rgba(15, 81, 50, 0.25);">
                <span>Masuk ke SisaSense</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>

        <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); text-align: center; font-size: 13.5px; color: var(--ink-muted);">
            Belum memiliki akun pelanggan? 
            <a href="<?= e(app_url('register.php')) ?>" style="color: #0F5132; font-weight: 750; text-decoration: none;">
                Daftar Sebagai Pembeli &rarr;
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwdInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            if (isPassword) {
                eyeIcon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>';
                toggleBtn.setAttribute('title', 'Sembunyikan kata sandi');
            } else {
                eyeIcon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
                toggleBtn.setAttribute('title', 'Lihat kata sandi');
            }
        });
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
