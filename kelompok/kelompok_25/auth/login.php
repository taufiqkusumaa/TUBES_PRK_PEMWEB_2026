<?php
require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    redirect(dashboard_path(current_user()['role']));
}
if (!auth_skema_siap()) {
    auth_halaman_skema_belum_siap();
}

$error = '';
$identitas = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim(mb_substr((string) ($_POST['identitas'] ?? ''), 0, 120));
    $pass  = (string) ($_POST['password'] ?? '');
    $ingat = isset($_POST['ingat']);

    if (!csrf_valid()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif ($identitas === '' || $pass === '') {
        $error = 'Isi username atau email, dan kata sandi.';
    } elseif (auth_terkunci($identitas)) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . (int) auth_config()['gagal_menit'] . ' menit, atau gunakan "Lupa kata sandi".';
    } else {
        $u = auth_cari_pengguna($identitas);
        $benar = password_verify($pass, $u['password'] ?? AUTH_HASH_PALSU);   // selalu dijalankan agar waktunya sama
        if (!$u || !$benar) {
            auth_gagal_catat($identitas);
            $error = 'Username/email atau kata sandi salah.';
        } elseif ($pesan = auth_pesan_status($u)) {
            $error = $pesan;
        } else {
            auth_gagal_hapus($identitas);
            auth_masuk($u, $ingat);
            flash('success', 'Selamat datang, ' . $u['nama'] . '!');
            redirect(dashboard_path($u['role']));
        }
    }
}

$judul = 'Masuk';
$tanpaKerangka = true;   // halaman penuh tanpa navbar/footer situs
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
<div class="auth-layar">
  <?php auth_panel('Konsultasi hukum yang lebih dekat dan terpercaya.', [
      ['bi-patch-check', 'Pengacara terverifikasi admin'],
      ['bi-shield-lock', 'Percakapan tersimpan aman dan rahasia'],
      ['bi-chat-square-text', 'Jawaban jelas, langsung dari praktisi'],
  ]); ?>

  <div class="auth-sisi">
    <div class="auth-atas"><?= auth_logo('d-lg-none') ?><?= auth_kembali() ?></div>

      <section class="auth-isi">
        <h1 class="auth-judul">Masuk</h1>
        <p class="text-secondary mb-4">Lanjutkan konsultasi hukummu.</p>

        <?php if ($error): ?><div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div><?php endif; ?>

        <form method="post" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="identitas">Username atau Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input class="form-control" id="identitas" name="identitas" value="<?= e($identitas) ?>" autocomplete="username"
                     maxlength="120" required autofocus data-pesan-wajib="Isi username atau email.">
            </div>
          </div>
          <div class="mb-3">
            <?php auth_input_sandi('password', 'password', 'Kata Sandi', ['autocomplete' => 'current-password']); ?>
          </div>
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="ingat" name="ingat" value="1" <?= isset($_POST['ingat']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="ingat">Ingat akun saya</label>
            </div>
            <a class="small" href="<?= e(url('auth/lupa_sandi.php')) ?>">Lupa kata sandi?</a>
          </div>
          <button class="btn btn-navy w-100 py-2" type="submit" data-memproses="Memproses...">Masuk</button>
        </form>

        <?= auth_sosial_blok('Atau masuk dengan') ?>

        <p class="text-center mt-4 mb-0 small">Belum punya akun? <a href="<?= e(url('auth/register.php')) ?>">Daftar</a></p>
      </section>
  </div>
</div>
<script src="<?= e(url('assets/js/auth.js')) ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
