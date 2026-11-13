<?php
// Kepala halaman + menu tab untuk semua halaman admin.
// Pemakaian (SETELAH require header.php):  admin_kepala('pengguna', 'Manajemen Pengguna', 'Atur akun...');
require_once __DIR__ . '/komponen_artikel.php';

function admin_kepala(string $aktif, string $judulHalaman, string $keterangan = ''): void
{
    $menunggu = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'pengacara' AND status = 'menunggu'")->fetchColumn();
    $menu = [
        'dashboard'  => ['admin/dashboard.php', 'bi-speedometer2', 'Ringkasan'],
        'verifikasi' => ['admin/verifikasi.php', 'bi-patch-check', 'Verifikasi'],
        'pengguna'   => ['admin/pengguna.php', 'bi-people', 'Pengguna'],
        'konsultasi' => ['admin/konsultasi.php', 'bi-chat-square-text', 'Konsultasi'],
        'artikel'    => ['admin/artikel.php', 'bi-journal-text', 'Artikel'],
        'faq'        => ['admin/faq.php', 'bi-question-circle', 'FAQ'],
        'bidang'     => ['admin/bidang.php', 'bi-bookmarks', 'Bidang Hukum'],
    ];
    ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
    <div class="ad-judul">
      <h1><?= e($judulHalaman) ?></h1>
      <?php if ($keterangan !== ''): ?><p><?= e($keterangan) ?></p><?php endif; ?>
    </div>
    <nav class="ad-nav" aria-label="Menu admin">
      <?php foreach ($menu as $kunci => [$alamat, $ikon, $nama]): ?>
        <a href="<?= e(url($alamat)) ?>" class="<?= $kunci === $aktif ? 'aktif' : '' ?>">
          <i class="bi <?= $ikon ?>"></i> <?= e($nama) ?>
          <?php if ($kunci === 'verifikasi' && $menunggu > 0): ?><span class="jml"><?= $menunggu ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php
}

/** Pill status/peran yang berwarna. Contoh: <?= admin_pil('aktif') ?> */
function admin_pil(string $nilai): string
{
    $label = ['aktif' => 'Aktif', 'menunggu' => 'Menunggu', 'nonaktif' => 'Nonaktif', 'ditolak' => 'Ditolak',
              'terbit' => 'Terbit', 'draft' => 'Draft', 'admin' => 'Admin', 'pengacara' => 'Pengacara', 'klien' => 'Klien'];
    return '<span class="ad-pil pil-' . e($nilai) . '">' . e($label[$nilai] ?? ucfirst($nilai)) . '</span>';
}

/** Pill status konsultasi memakai kelas bawaan style.css (badge-menunggu, badge-dijawab, ...). */
function admin_pil_konsultasi(string $status): string
{
    return '<span class="badge-status badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

/** Alamat kembali setelah aksi: hanya membawa parameter filter yang dikenal. */
function admin_kembali(string $dasar, array $sumber, array $kunci): string
{
    $q = [];
    foreach ($kunci as $k) {
        $v = trim((string) ($sumber[$k] ?? ''));
        if ($v !== '' && $v !== '0') {
            $q[$k] = mb_substr($v, 0, 80);
        }
    }
    return $dasar . ($q ? '?' . http_build_query($q) : '');
}
