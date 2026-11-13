<?php
// MODUL PENGACARA (Nday): profil pengacara
require_once __DIR__ . '/../config/app.php';
require_role('pengacara');
 
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
 
$uid = (int) current_user()['id'];
$pdo = db();
 
if (empty($_SESSION['csrf_pengacara'])) {
    $_SESSION['csrf_pengacara'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_pengacara'];
 
// Data profil saat ini
$st = $pdo->prepare('SELECT p.bio, p.kota, p.pengalaman_tahun, p.no_izin, b.nama AS bidang
                     FROM profil_pengacara p
                     LEFT JOIN bidang_hukum b ON b.id = p.bidang_id
                     WHERE p.user_id = ?');
$st->execute([$uid]);
$p = $st->fetch();
 
$errors = [];
 
if (!$p) {
    $errors[] = 'Data profilmu belum dibuat. Hubungi admin.';
    $p = ['bio' => '', 'kota' => '', 'pengalaman_tahun' => 0, 'no_izin' => '', 'bidang' => null];
}
 
$form = [
    'bio'              => (string) $p['bio'],
    'kota'             => (string) $p['kota'],
    'pengalaman_tahun' => (string) $p['pengalaman_tahun'],
    'no_izin'          => (string) $p['no_izin'],
];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    $form['bio']              = trim((string) ($_POST['bio'] ?? ''));
    $form['kota']             = trim((string) ($_POST['kota'] ?? ''));
    $form['pengalaman_tahun'] = trim((string) ($_POST['pengalaman_tahun'] ?? ''));
    $form['no_izin']          = trim((string) ($_POST['no_izin'] ?? ''));
 
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'Sesi tidak valid, muat ulang halaman.';
    }
    if (mb_strlen($form['bio']) > 2000) {
        $errors[] = 'Bio maksimal 2000 karakter.';
    }
    if ($form['kota'] === '' || mb_strlen($form['kota']) > 60) {
        $errors[] = 'Kota wajib diisi (maksimal 60 karakter).';
    }
    if (!ctype_digit($form['pengalaman_tahun']) || (int) $form['pengalaman_tahun'] > 60) {
        $errors[] = 'Pengalaman harus berupa angka 0 sampai 60 tahun.';
    }
    if ($form['no_izin'] === '' || mb_strlen($form['no_izin']) > 60) {
        $errors[] = 'Nomor izin wajib diisi (maksimal 60 karakter).';
    }
 
    if (!$errors) {
        try {
            $pdo->prepare('UPDATE profil_pengacara
                           SET bio = ?, kota = ?, pengalaman_tahun = ?, no_izin = ?
                           WHERE user_id = ?')
                ->execute([
                    $form['bio'] === '' ? null : $form['bio'],
                    $form['kota'],
                    (int) $form['pengalaman_tahun'],
                    $form['no_izin'],
                    $uid,
                ]);
            header('Location: profil.php?ok=1');
            exit;
        } catch (Throwable $ex) {
            $errors[] = 'Gagal menyimpan profil. Coba lagi.';
        }
    }
}
 
$judul = 'Profil Saya';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container page-head">
  <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">&larr; Kembali</a>
  <h2 class="mb-3">Profil saya</h2>
 
  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success">Profil berhasil disimpan.</div>
  <?php endif; ?>
  <?php foreach ($errors as $er): ?>
    <div class="alert alert-danger"><?= e($er) ?></div>
  <?php endforeach; ?>
 
  <form method="post" action="profil.php" class="card-rh p-3" style="max-width: 720px;">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
 
    <div class="mb-3">
      <label class="form-label">Nama</label>
      <input type="text" class="form-control" value="<?= e(current_user()['nama']) ?>" disabled>
    </div>
 
    <div class="mb-3">
      <label class="form-label">Bidang hukum</label>
      <input type="text" class="form-control" value="<?= e($p['bidang'] ?? '-') ?>" disabled>
      <div class="form-text">Bidang hanya bisa diubah oleh admin.</div>
    </div>
 
    <div class="mb-3">
      <label class="form-label" for="kota">Kota</label>
      <input type="text" id="kota" name="kota" class="form-control" maxlength="60"
             value="<?= e($form['kota']) ?>" required>
    </div>
 
    <div class="mb-3">
      <label class="form-label" for="pengalaman_tahun">Pengalaman (tahun)</label>
      <input type="number" id="pengalaman_tahun" name="pengalaman_tahun" class="form-control"
             min="0" max="60" value="<?= e($form['pengalaman_tahun']) ?>" required>
    </div>
 
    <div class="mb-3">
      <label class="form-label" for="no_izin">Nomor izin</label>
      <input type="text" id="no_izin" name="no_izin" class="form-control" maxlength="60"
             value="<?= e($form['no_izin']) ?>" required>
    </div>
 
    <div class="mb-3">
      <label class="form-label" for="bio">Bio</label>
      <textarea id="bio" name="bio" class="form-control" rows="6" maxlength="2000"><?= e($form['bio']) ?></textarea>
    </div>
 
    <button type="submit" class="btn btn-primary">Simpan profil</button>
  </form>
</div>
 
<script>
// Pesan validasi browser dalam bahasa Indonesia
document.querySelectorAll('form input, form textarea').forEach(function (el) {
  el.addEventListener('invalid', function () {
    var v = el.validity;
    if (v.valueMissing) {
      el.setCustomValidity('Kolom ini wajib diisi.');
    } else if (v.rangeOverflow) {
      el.setCustomValidity('Nilai tidak boleh lebih dari ' + el.max + '.');
    } else if (v.rangeUnderflow) {
      el.setCustomValidity('Nilai tidak boleh kurang dari ' + el.min + '.');
    } else if (v.tooLong) {
      el.setCustomValidity('Teks terlalu panjang (maksimal ' + el.maxLength + ' karakter).');
    } else if (v.badInput) {
      el.setCustomValidity('Masukkan angka yang valid.');
    }
  });
  el.addEventListener('input', function () {
    el.setCustomValidity('');
  });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
 