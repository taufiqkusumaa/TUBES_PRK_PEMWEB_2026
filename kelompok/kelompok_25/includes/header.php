<?php
// Header bersama. Cara pakai di halaman mana pun:
//   $judul = 'Judul Halaman';
//   require_once __DIR__ . '/../config/app.php';
//   require_once __DIR__ . '/../includes/header.php';
$judul = $judul ?? APP_NAME;
$user  = current_user();
$profilSaya = $user ? auth_profil_saya((int) $user['id']) : [];

// Menu mana yang sedang dibuka? Dibaca dari lokasi file halaman, jadi artikel.php (publik)
// tidak tertukar dengan admin/artikel.php. Halaman boleh menimpanya: $menuAktif = 'faq';
$menuAktif = $menuAktif ?? (function (): string {
    $akar = realpath(__DIR__ . '/..');
    $file = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    if (!$akar || !$file || stripos($file, $akar) !== 0) {
        return '';
    }
    $rel = str_replace('\\', '/', ltrim(substr($file, strlen($akar)), '\\/'));
    return [
        'index.php'          => 'beranda',
        'pengacara.php'      => 'pengacara',
        'artikel.php'        => 'artikel',
        'artikel_detail.php' => 'artikel',
        'faq.php'            => 'faq',
        'tentang.php'        => 'tentang',
    ][$rel] ?? '';
})();
// Halaman polos (masuk, daftar, dst.): tanpa navbar dan footer. Set $tanpaKerangka = true; sebelum memanggil header.
$tanpaKerangka = $tanpaKerangka ?? false;
$menuUtama = [
    'beranda'   => ['index.php', 'Beranda'],
    'pengacara' => ['pengacara.php', 'Pengacara'],
    'artikel'   => ['artikel.php', 'Artikel'],
    'faq'       => ['faq.php', 'FAQ'],
    'tentang'   => ['tentang.php', 'Tentang Kami'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> | <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/profil.css') ?>" rel="stylesheet">
  <link href="<?= url('assets/css/footer.css') ?>" rel="stylesheet">
</head>
<body<?= $tanpaKerangka ? ' class="halaman-polos"' : '' ?>>
<?php if (!$tanpaKerangka): ?>
<nav class="navbar navbar-expand-lg navbar-rh sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= url('index.php') ?>"><i class="bi bi-bank2"></i> Ruang Hukum</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <?php foreach ($menuUtama as $mnKunci => [$mnBerkas, $mnNama]): ?>
          <li class="nav-item"><a class="nav-link<?= $menuAktif === $mnKunci ? ' active' : '' ?>" href="<?= url($mnBerkas) ?>"<?= $menuAktif === $mnKunci ? ' aria-current="page"' : '' ?>><?= $mnNama ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex flex-column flex-lg-row gap-2 align-items-stretch align-items-lg-center">
        <?php if ($user): ?>
          <a class="btn btn-gold btn-sm" href="<?= url(dashboard_path($user['role'])) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <div class="dropdown profil-menu">
            <button class="btn profil-tombol" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="tombolProfil">
              <?= auth_avatar_html($profilSaya['foto'] ?? null, 'kecil') ?>
              <span class="profil-nama"><?= e(mb_strtoupper($user['nama'])) ?></span>
              <i class="bi bi-caret-down-fill"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end profil-panel" aria-labelledby="tombolProfil">
              <?= auth_avatar_html($profilSaya['foto'] ?? null, 'besar') ?>
              <div class="profil-panel-nama"><?= e(mb_strtoupper($user['nama'])) ?></div>
              <div class="profil-panel-info"><?= e(ucfirst($user['role'])) ?><?= !empty($user['username']) ? ' &middot; @' . e($user['username']) : '' ?></div>
              <div class="profil-panel-login">Login Terakhir :<br><?= !empty($profilSaya['last_login_at']) ? e(auth_tgl_panjang($profilSaya['last_login_at'])) : 'Baru saja' ?></div>
              <div class="profil-panel-aksi">
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalFoto">Foto Profil</button>
                <a class="btn btn-outline-light btn-sm" href="<?= url('auth/logout.php') ?>">Keluar</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="<?= url('auth/login.php') ?>">Masuk</a>
          <a class="btn btn-gold btn-sm" href="<?= url('auth/register.php') ?>">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<?php $pesan = ambil_flash(); if ($pesan): ?>
<div class="toast-rh-wrap" aria-live="polite">
  <?php foreach ($pesan as $f): ?>
    <div class="toast-rh toast-<?= e($f['tipe']) ?>" role="alert">
      <i class="bi <?= ['success' => 'bi-check-circle-fill', 'danger' => 'bi-x-circle-fill', 'warning' => 'bi-exclamation-triangle-fill'][$f['tipe']] ?? 'bi-info-circle-fill' ?>"></i>
      <div class="pesan"><?= e($f['pesan']) ?></div>
      <button type="button" class="btn-close" aria-label="Tutup"></button>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($user): ?>
<div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="judulModalFoto" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-rh p-4">
      <h4 class="h5 mb-3" id="judulModalFoto">Foto Profil</h4>
      <div class="text-center mb-3"><?= auth_avatar_html($profilSaya['foto'] ?? null, 'besar', true) ?></div>
      <form method="post" action="<?= url('auth/foto_profil.php') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="unggah">
        <input type="hidden" name="kembali" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
        <label class="form-label" for="berkasFoto">Pilih foto (JPG, PNG, atau WebP, maks 2 MB)</label>
        <input class="form-control mb-3" type="file" id="berkasFoto" name="foto" accept="image/jpeg,image/png,image/webp" required data-pesan-wajib="Pilih foto terlebih dahulu.">
        <div class="d-flex flex-wrap justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-gold"><i class="bi bi-upload"></i> Simpan foto</button>
        </div>
      </form>
      <?php if (!empty($profilSaya['foto'])): ?>
        <form method="post" action="<?= url('auth/foto_profil.php') ?>" class="mt-3 text-center">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="kembali" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <button type="submit" class="btn btn-link text-danger btn-sm">Hapus foto saat ini</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<main>
