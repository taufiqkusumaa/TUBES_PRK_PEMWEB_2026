<?php
// Lupa kata sandi: buat token sekali pakai (disimpan sebagai hash di tabel password_resets).
require_once __DIR__ . '/../config/app.php';
if (is_logged_in()) { redirect(dashboard_path(current_user()['role'])); }
if (!auth_skema_siap()) { auth_halaman_skema_belum_siap(); }

$error = '';
$terkirim = false;
$tautanDemo = '';
$identitas = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim(mb_substr((string) ($_POST['identitas'] ?? ''), 0, 120));
    if (!csrf_valid()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } elseif ($identitas === '') {
        $error = 'Isi username atau email akunmu.';
    } else {
        $u = auth_cari_pengguna($identitas);
        if ($u && !auth_reset_terlalu_sering((int) $u['id'])) {
            $tautan = auth_url_mutlak('auth/reset_sandi.php?token=' . auth_reset_buat((int) $u['id']));
            $terkirim = true;
            if (!empty(auth_config()['demo_tautan_reset'])) {
                $tautanDemo = $tautan;       // mode demo: belum ada server email, tampilkan di layar
            } else {
                auth_kirim_email_reset($u, $tautan);
            }
        } else {
            $terkirim = true;                // jawaban sama, agar akun yang tidak ada tidak bisa ditebak
        }
    }
}

$judul = 'Lupa Kata Sandi';
$tanpaKerangka = true;   // halaman penuh tanpa navbar/footer situs
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
<div class="auth-polos">
  <?= auth_logo() ?>
  <div class="auth-kartu-kecil">
    <?php if ($terkirim): ?>
      <div class="text-center mb-3"><span class="ikon-besar"><i class="bi bi-envelope-check"></i></span></div>
      <h1 class="h4 text-center">Periksa emailmu</h1>
      <p class="text-secondary text-center">Jika akun dengan data itu ada, tautan untuk mengatur ulang kata sandi sudah dikirim ke email terdaftar. Tautan berlaku <?= (int) auth_config()['reset_menit'] ?> menit.</p>
      <?php if ($tautanDemo): ?>
        <div class="demo-banner flex-column align-items-start" id="kotakTautanDemo">
          <div><i class="bi bi-info-circle-fill"></i> <strong>Mode demo.</strong> Belum ada server email, jadi tautan reset ditampilkan di sini:</div>
          <a class="btn btn-gold btn-sm mt-2" id="tautanReset" href="<?= e($tautanDemo) ?>">Atur ulang kata sandi</a>
        </div>
      <?php endif; ?>
      <a class="btn btn-outline-secondary w-100 mt-3" href="<?= e(url('auth/login.php')) ?>">Kembali ke halaman masuk</a>
    <?php else: ?>
      <h1 class="h4 mb-1">Lupa kata sandi?</h1>
      <p class="text-secondary mb-4">Masukkan username atau email akunmu. Kami akan membuat tautan untuk mengatur kata sandi baru.</p>
      <?php if ($error): ?><div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <label class="form-label" for="identitas">Username atau Email</label>
        <div class="input-group mb-4">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input class="form-control" id="identitas" name="identitas" value="<?= e($identitas) ?>" maxlength="120" required autofocus data-pesan-wajib="Isi username atau email akunmu.">
        </div>
        <button class="btn btn-navy w-100 py-2" type="submit">Kirim tautan reset</button>
      </form>
      <p class="text-center mt-4 mb-0 small"><a href="<?= e(url('auth/login.php')) ?>">&larr; Kembali ke halaman masuk</a></p>
    <?php endif; ?>
  </div>
  <?= auth_kembali() ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
