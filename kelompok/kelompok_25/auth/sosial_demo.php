<?php
// SIMULASI layar persetujuan Google/LinkedIn/Facebook untuk demo tanpa kunci OAuth.
// Hanya aktif bila 'demo_sosial' => true dan penyedia belum diisi client_id/client_secret.
// Aman: tidak pernah menautkan ke akun yang sudah ada, hanya membuat akun klien baru atau masuk ke akun hasil simulasi.
require_once __DIR__ . '/../config/app.php';
if (is_logged_in()) { redirect(dashboard_path(current_user()['role'])); }
if (!auth_skema_siap()) { auth_halaman_skema_belum_siap(); }

$kunci  = (string) ($_GET['p'] ?? $_POST['p'] ?? '');
$daftar = auth_sosial_daftar();
if (!isset($daftar[$kunci]) || $daftar[$kunci]['mode'] !== 'demo') {
    flash('danger', 'Masuk dengan media sosial ini belum diaktifkan.');
    redirect('auth/login.php');
}
$nama = $daftar[$kunci]['nama'];
$galat = [];
$in = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in['nama']  = trim((string) ($_POST['nama'] ?? ''));
    $in['email'] = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!csrf_valid()) $galat[] = 'Sesi formulir tidak valid. Muat ulang halaman.';
    if (mb_strlen($in['nama']) < 3) $galat['nama'] = 'Nama minimal 3 huruf.';
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $galat['email'] = 'Format email tidak valid.';
    if (!$galat) {
        auth_sosial_proses($kunci, [
            'uid' => 'demo-' . hash('sha256', $in['email']),
            'email' => $in['email'], 'nama' => $in['nama'],
            'email_terverifikasi' => false,      // simulasi tidak pernah menjamin email, jadi tidak pernah menautkan akun lama
        ]);
    }
}

$judul = 'Simulasi ' . $nama;
$tanpaKerangka = true;   // halaman penuh tanpa navbar/footer situs
require_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/auth.css')) ?>">
<div class="auth-polos">
  <?= auth_logo() ?>
  <div class="auth-kartu-kecil">
    <div class="demo-banner"><i class="bi bi-info-circle-fill"></i> <span><strong>Mode simulasi.</strong> Halaman ini meniru layar persetujuan <?= e($nama) ?> untuk keperluan demo. Tidak terhubung ke <?= e($nama) ?> yang asli.</span></div>
    <div class="text-center my-3"><span class="logo-sosial sosial-<?= e($kunci) ?>"><?php if ($kunci === 'google'): ?><img class="ikon-google" src="<?= e(url('assets/img/google-g.svg')) ?>" alt="" width="28" height="28"><?php else: ?><i class="bi <?= e($daftar[$kunci]['ikon']) ?>"></i><?php endif; ?></span></div>
    <h1 class="h4 text-center mb-1">Masuk dengan <?= e($nama) ?></h1>
    <p class="text-secondary text-center small mb-4">Ruang Hukum akan menerima nama dan alamat emailmu.</p>
    <?php if (isset($galat[0])): ?><div class="alert alert-danger"><?= e($galat[0]) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="p" value="<?= e($kunci) ?>">
      <div class="mb-3">
        <label class="form-label" for="nama">Nama</label>
        <input class="form-control <?= isset($galat['nama']) ? 'is-invalid' : '' ?>" id="nama" name="nama" value="<?= e($in['nama']) ?>" required maxlength="100">
        <?php if (isset($galat['nama'])): ?><div class="invalid-feedback d-block"><?= e($galat['nama']) ?></div><?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="form-label" for="email">Email <?= e($nama) ?></label>
        <input type="email" class="form-control <?= isset($galat['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($in['email']) ?>" required maxlength="120">
        <?php if (isset($galat['email'])): ?><div class="invalid-feedback d-block"><?= e($galat['email']) ?></div><?php endif; ?>
      </div>
      <button class="btn btn-navy w-100 py-2" type="submit">Lanjutkan</button>
      <a class="btn btn-link w-100 mt-2" href="<?= e(url('auth/login.php')) ?>">Batal</a>
    </form>
  </div>
  <?= auth_kembali() ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
