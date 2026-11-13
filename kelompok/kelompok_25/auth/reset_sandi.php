<?php
// Halaman dari tautan "Lupa kata sandi": isi kata sandi baru. Token sekali pakai.
require_once __DIR__ . '/../config/app.php';
if (is_logged_in()) { redirect(dashboard_path(current_user()['role'])); }
if (!auth_skema_siap()) { auth_halaman_skema_belum_siap(); }

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$r = auth_reset_cari($token);
$galat = [];

if ($r && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');
    if (!csrf_valid()) {
        $galat['_'] = 'Sesi formulir tidak valid. Muat ulang halaman.';
    }
    if ($m = auth_cek_sandi($pass)) { $galat['password'] = $m; }
    if ($pass !== $pass2) { $galat['password2'] = 'Konfirmasi kata sandi tidak sama.'; }

    if (!$galat) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int) $r['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?')->execute([auth_sekarang(), (int) $r['reset_id']]);
        $pdo->commit();
        auth_ingat_cabut_semua((int) $r['user_id']);      // keluarkan dari semua perangkat yang "diingat"
        auth_gagal_hapus((string) ($r['username'] ?? $r['email']));
        flash('success', 'Kata sandi berhasil diganti. Silakan masuk dengan kata sandi baru.');
        redirect('auth/login.php');
    }
}

$judul = 'Atur Ulang Kata Sandi';
$tanpaKerangka = true;   // halaman penuh tanpa navbar/footer situs
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
<div class="auth-polos">
  <?= auth_logo() ?>
  <div class="auth-kartu-kecil">
    <?php if (!$r): ?>
      <div class="text-center mb-3"><span class="ikon-besar gagal"><i class="bi bi-x-octagon"></i></span></div>
      <h1 class="h4 text-center">Tautan tidak berlaku</h1>
      <p class="text-secondary text-center">Tautan ini sudah dipakai, kedaluwarsa, atau tidak benar. Minta tautan baru.</p>
      <a class="btn btn-navy w-100" href="<?= e(url('auth/lupa_sandi.php')) ?>">Minta tautan baru</a>
    <?php else: ?>
      <h1 class="h4 mb-1">Atur kata sandi baru</h1>
      <p class="text-secondary mb-4">Untuk akun <strong><?= e($r['username'] ? '@' . $r['username'] : $r['email']) ?></strong>.</p>
      <?php if (isset($galat['_'])): ?><div class="alert alert-danger py-2" role="alert"><?= e($galat['_']) ?></div><?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="mb-3">
          <?php auth_input_sandi('password', 'password', 'Kata Sandi Baru', ['autocomplete' => 'new-password', 'minlength' => 8, 'galat' => $galat['password'] ?? '']); ?>
          <div class="meter-sandi" data-meter-untuk="password"><div class="batang"><span></span></div><small class="teks">Minimal 8 karakter, memuat huruf dan angka.</small></div>
        </div>
        <div class="mb-4"><?php auth_input_sandi('password2', 'password2', 'Konfirmasi Kata Sandi', ['autocomplete' => 'new-password', 'galat' => $galat['password2'] ?? '']); ?></div>
        <button class="btn btn-navy w-100 py-2" type="submit" data-memproses="Menyimpan...">Simpan kata sandi</button>
      </form>
    <?php endif; ?>
  </div>
  <?= auth_kembali() ?>
</div>
<script src="<?= e(url('assets/js/auth.js')) ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
