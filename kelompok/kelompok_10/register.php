<?php
declare(strict_types=1);
require_once __DIR__ . '/config/auth.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $errorMessage = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
    } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
        $errorMessage = 'Username harus 3–50 karakter: huruf kecil, angka, titik, atau tanda hubung.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errorMessage = 'Format email tidak valid.';
    } elseif (strlen($password) < 3) {
        $errorMessage = 'Kata sandi minimal 3 karakter.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Konfirmasi kata sandi tidak sama.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
            $stmt->execute(['username' => $username, 'email' => $email]);
            if ($stmt->fetch()) {
                $errorMessage = 'Username atau email sudah digunakan akun lain.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = db()->prepare('
                    INSERT INTO users (full_name, username, email, password_hash, role, is_active)
                    VALUES (:name, :username, :email, :hash, "customer", 1)
                ');
                $stmtInsert->execute([
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'hash' => $hash,
                ]);

                $newId = (int) db()->lastInsertId();
                $_SESSION['user'] = [
                    'id' => $newId,
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'role' => 'customer',
                    'is_active' => true,
                ];

                flash('success', "Selamat datang, {$name}! Akun Pembeli Anda berhasil dibuat.");
                redirect('dashboard.php');
            }
        } catch (PDOException $e) {
            error_log('Register error: ' . $e->getMessage());
            $errorMessage = 'Gagal membuat akun pembeli. Coba beberapa saat lagi.';
        }
    }
}

$pageTitle = 'Daftar Akun Pembeli';
$activePage = 'register';
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
                    <small style="font-size: 11px; color: #BBF7D0; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">Portal Belanja Pembeli</small>
                </div>
            </div>

            <div class="auth-hero-content" style="margin: 0; padding: 0;">
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 9999px; background: rgba(255,255,255,0.15); color: #BBF7D0; font-size: 11.5px; font-weight: 750; border: 1px solid rgba(255,255,255,0.25); margin-bottom: 16px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #22C55E;"></span>
                    Akses Toko &amp; Belanja Mandiri
                </span>

                <h1 style="font-family: 'Playfair Display', Georgia, serif; font-size: 30px; font-weight: 800; line-height: 1.25; color: #fff; margin: 0 0 14px;">
                    Belanja Mudah, Stok Transparan Real-Time.
                </h1>

                <p style="color: rgba(255,255,255,0.88); font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
                    Daftar gratis sebagai pelanggan untuk memantau stok sembako, minuman, dan snack segar serta arsip nota belanja pribadi.
                </p>

                <!-- Single Clean Minimalist Badge Card -->
                <div style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22); border-radius: 16px; padding: 18px 20px; backdrop-filter: blur(8px);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 11px; font-weight: 750; color: #BBF7D0; text-transform: uppercase; letter-spacing: .06em;">Benefit Member</span>
                        <span style="background: #22C55E; color: #fff; font-size: 10.5px; padding: 2px 8px; border-radius: 9999px; font-weight: 800;">Gratis</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,0.2); display: grid; place-items: center; font-size: 18px; flex-shrink: 0;">
                            🛍️
                        </div>
                        <div>
                            <strong style="color: #fff; font-size: 13.5px; display: block;">Riwayat Struk &amp; Cek Stok</strong>
                            <small style="color: #BBF7D0; font-size: 11.5px;">Akses instan tanpa antri di kasir</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="font-size: 12px; color: #BBF7D0; display: flex; align-items: center; justify-content: space-between; padding-top: 18px; border-top: 1px solid rgba(255, 255, 255, 0.2); margin-top: 28px;">
            <span>&check; Pendaftaran Bebas Biaya</span>
            <span>&check; Privasi Terenkripsi</span>
        </div>
    </div>

    <!-- Kolom Kanan: Form Pendaftaran -->
    <div class="auth-form-side">
        <div class="auth-form-header">
            <p class="eyebrow" style="color: #0F5132; font-weight: 800; letter-spacing: .08em;">DAFTAR AKUN BARU</p>
            <h2 style="font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 800; color: #0F172A; margin: 4px 0 8px;">Buat Akun Pembeli</h2>
            <p style="color: #64748B; font-size: 13.5px; margin: 0;">Lengkapi formulir di bawah ini untuk menjadi pelanggan toko.</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice notice-error" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="name" style="font-size: 13px; font-weight: 750; color: #1E293B;">Nama Lengkap</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" maxlength="100" placeholder="Contoh: Rina Melati" required autofocus class="auth-input-field">
                </div>
            </div>

            <div class="form-group">
                <label for="username" style="font-size: 13px; font-weight: 750; color: #1E293B;">Username</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
                    </span>
                    <input id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" maxlength="50" placeholder="huruf kecil, angka (contoh: rina123)" required class="auth-input-field">
                </div>
            </div>

            <div class="form-group">
                <label for="email" style="font-size: 13px; font-weight: 750; color: #1E293B;">Email Aktif</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </span>
                    <input id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" maxlength="150" placeholder="nama@email.com" required class="auth-input-field">
                </div>
            </div>

            <div class="form-group">
                <label for="password" style="font-size: 13px; font-weight: 750; color: #1E293B;">Kata Sandi</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="password" name="password" type="password" placeholder="Minimal 3 karakter" required class="auth-input-field">
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" style="font-size: 13px; font-weight: 750; color: #1E293B;">Konfirmasi Kata Sandi</label>
                <div class="auth-input-wrapper">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Ulangi kata sandi di atas" required class="auth-input-field">
                </div>
            </div>

            <button type="submit" class="button button-dark" style="width: 100%; min-height: 50px; font-size: 15px; font-weight: 800; border-radius: var(--radius-full); margin-top: 14px; justify-content: center; background: #0F5132; box-shadow: 0 4px 14px rgba(15, 81, 50, 0.25);">
                <span>Daftar Akun Sekarang</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--line); text-align: center; font-size: 13px; color: var(--ink-muted);">
            Sudah memiliki akun? 
            <a href="<?= e(app_url('login.php')) ?>" style="color: #0F5132; font-weight: 750; text-decoration: none;">
                Masuk di Sini &rarr;
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
