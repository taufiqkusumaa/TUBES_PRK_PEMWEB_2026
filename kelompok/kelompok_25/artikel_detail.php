<?php
// ARTIKEL (publik): halaman baca satu artikel. Alamat: artikel_detail.php?slug=...
// Ditulis ulang oleh Rafi: versi sebelumnya memakai variabel $pdo dan kolom "konten" yang tidak ada.
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/komponen_artikel.php';

$pdo  = db();
$user = current_user();
$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    redirect('artikel.php');
}

// Admin boleh melihat draft sebagai pratinjau; pengunjung lain hanya artikel terbit.
$bolehDraft = $user && $user['role'] === 'admin';
$stmt = $pdo->prepare(
    "SELECT a.id, a.judul, a.slug, a.ringkasan, a.isi, a.status, a.bidang_id, a.created_at,
            b.nama AS bidang, u.nama AS penulis
     FROM artikel a
     LEFT JOIN bidang_hukum b ON b.id = a.bidang_id
     LEFT JOIN users u ON u.id = a.penulis_id
     WHERE a.slug = ?" . ($bolehDraft ? '' : " AND a.status = 'terbit'")
);
$stmt->execute([$slug]);
$a = $stmt->fetch();

if (!$a) {
    flash('warning','Artikel tidak ditemukan atau belum diterbitkan.');
    redirect('artikel.php');
}

// Artikel terkait: bidang yang sama dulu, lalu yang terbaru
$terkait = [];
$ambil = $pdo->prepare(
    "SELECT a.id, a.judul, a.slug, a.ringkasan, SUBSTR(a.isi, 1, 400) AS cuplikan, LENGTH(a.isi) AS panjang,
            a.bidang_id, a.created_at, b.nama AS bidang
     FROM artikel a LEFT JOIN bidang_hukum b ON b.id = a.bidang_id
     WHERE a.status = 'terbit' AND a.id <> ? AND a.bidang_id " . ($a['bidang_id'] ? '= ?' : 'IS NOT NULL') . "
     ORDER BY a.created_at DESC, a.id DESC LIMIT 3"
);
$ambil->execute($a['bidang_id'] ? [$a['id'], $a['bidang_id']] : [$a['id']]);
$terkait = $ambil->fetchAll();
if (count($terkait) < 3) {
    $ids = array_merge([(int) $a['id']], array_map(fn ($r) => (int) $r['id'], $terkait));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $lain = $pdo->prepare(
        "SELECT a.id, a.judul, a.slug, a.ringkasan, SUBSTR(a.isi, 1, 400) AS cuplikan, LENGTH(a.isi) AS panjang,
                a.bidang_id, a.created_at, b.nama AS bidang
         FROM artikel a LEFT JOIN bidang_hukum b ON b.id = a.bidang_id
         WHERE a.status = 'terbit' AND a.id NOT IN ($in)
         ORDER BY a.created_at DESC, a.id DESC LIMIT " . (3 - count($terkait))
    );
    $lain->execute($ids);
    $terkait = array_merge($terkait, $lain->fetchAll());
}

$bidang_id = (int) ($a['bidang_id'] ?? 0);
$judul = $a['judul'];
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/halaman_dalam.css')) ?>">

<section class="hd-hero ar-dhero">
  <div class="container">
    <div class="ar-crumb">
      <a href="<?= e(url('index.php')) ?>">Beranda</a> &rsaquo; <a href="<?= e(url('artikel.php')) ?>">Artikel</a>
    </div>
    <?php if ($a['bidang']): ?>
      <a class="ar-pil" href="<?= e(url('artikel.php?bidang=' . $bidang_id)) ?>"><i class="bi <?= art_ikon($bidang_id) ?>"></i> <?= e($a['bidang']) ?></a>
    <?php endif; ?>
    <h1><?= e($a['judul']) ?></h1>
    <div class="ar-dmeta">
      <span><i class="bi bi-person-circle"></i><?= e($a['penulis'] ?? 'Tim Ruang Hukum') ?></span>
      <span><i class="bi bi-calendar3"></i><?= e(tgl($a['created_at'])) ?></span>
      <span><i class="bi bi-clock"></i><?= art_waktu_baca(mb_strlen($a['isi'])) ?> menit baca</span>
    </div>
  </div>
</section>

<div class="container hd-isi">
  <div class="ar-layout">
    <article>
      <?php if ($a['status'] !== 'terbit'): ?>
        <div class="ar-draft"><i class="bi bi-eye"></i> Pratinjau: artikel ini masih <strong>draft</strong> dan belum terlihat oleh pengunjung.</div>
      <?php endif; ?>
      <div class="ar-baca">
        <?= art_render($a['isi']) ?>
        <div class="ar-disclaimer">
          <i class="bi bi-info-circle"></i>
          <div>Artikel ini berisi informasi hukum umum dan bukan nasihat hukum untuk kasus tertentu. Aturan dapat berubah dan setiap perkara punya fakta yang berbeda, jadi sebaiknya konsultasikan masalahmu dengan pengacara.</div>
        </div>
      </div>
    </article>

    <aside class="ar-sisi">
      <div class="ar-cta">
        <h3>Punya masalah serupa?</h3>
        <p>Ceritakan kasusmu dan dapatkan arahan dari pengacara terverifikasi<?= $a['bidang'] ? ' di ' . e($a['bidang']) : '' ?>.</p>
        <?php $tujuan = 'klien/konsultasi_baru.php' . ($bidang_id ? '?bidang=' . $bidang_id : ''); ?>
        <?php if ($user && $user['role'] === 'klien'): ?>
          <a class="btn btn-gold w-100" href="<?= e(url($tujuan)) ?>">Ajukan Konsultasi</a>
        <?php elseif (!$user): ?>
          <a class="btn btn-gold w-100" href="<?= e(url('auth/register.php')) ?>">Daftar &amp; Konsultasi</a>
        <?php else: ?>
          <a class="btn btn-gold w-100" href="<?= e(url('pengacara.php' . ($bidang_id ? '?bidang=' . $bidang_id : ''))) ?>">Lihat Pengacara</a>
        <?php endif; ?>
      </div>
      <?php if ($terkait): ?>
        <div class="ar-kotak">
          <h3>Baca juga</h3>
          <?php foreach ($terkait as $t): ?>
            <a href="<?= e(url('artikel_detail.php?slug=' . rawurlencode($t['slug']))) ?>">
              <?= e($t['judul']) ?>
              <small><?= e($t['bidang'] ?? 'Umum') ?> &middot; <?= art_waktu_baca((int) $t['panjang']) ?> menit baca</small>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a class="btn btn-outline-secondary" href="<?= e(url('artikel.php')) ?>"><i class="bi bi-arrow-left"></i> Semua artikel</a>
    </aside>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
