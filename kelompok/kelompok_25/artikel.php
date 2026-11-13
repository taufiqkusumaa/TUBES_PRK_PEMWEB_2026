<?php
// ARTIKEL (publik): daftar artikel hukum dengan pencarian, filter bidang, dan halaman.
// Ditulis ulang oleh Rafi: versi sebelumnya memakai variabel $pdo dan kolom "konten" yang tidak ada.
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/komponen_artikel.php';

$pdo = db();

$q         = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$bidang_id = (int) ($_GET['bidang'] ?? 0);
$hal       = max(1, (int) ($_GET['hal'] ?? 1));
$perHal    = 10;
$adaFilter = $q !== '' || $bidang_id > 0;

$kondisi = ["a.status = 'terbit'"];
$param   = [];
if ($q !== '') {
    $kondisi[] = '(a.judul LIKE ? OR a.ringkasan LIKE ? OR a.isi LIKE ?)';
    $like = '%' . $q . '%';
    array_push($param, $like, $like, $like);
}
if ($bidang_id > 0) {
    $kondisi[] = 'a.bidang_id = ?';
    $param[] = $bidang_id;
}
$where = implode(' AND ', $kondisi);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM artikel a WHERE $where");
$stmt->execute($param);
$total    = (int) $stmt->fetchColumn();
$totalHal = max(1, (int) ceil($total / $perHal));
$hal      = min($hal, $totalHal);
$offset   = ($hal - 1) * $perHal;

$stmt = $pdo->prepare(
    "SELECT a.id, a.judul, a.slug, a.ringkasan, SUBSTR(a.isi, 1, 400) AS cuplikan, LENGTH(a.isi) AS panjang,
            a.bidang_id, a.created_at, b.nama AS bidang
     FROM artikel a
     LEFT JOIN bidang_hukum b ON b.id = a.bidang_id
     WHERE $where
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT ? OFFSET ?"
);
foreach ($param as $i => $nilai) {
    $stmt->bindValue($i + 1, $nilai);
}
$stmt->bindValue(count($param) + 1, $perHal, PDO::PARAM_INT);
$stmt->bindValue(count($param) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

// Sorotan: artikel terbaru, hanya di halaman pertama tanpa filter
$sorotan = null;
if (!$adaFilter && $hal === 1 && count($daftar) > 1) {
    $sorotan = array_shift($daftar);
}

// Chip bidang beserta jumlah artikel terbit
$chip = $pdo->query(
    "SELECT b.id, b.nama, COUNT(a.id) AS jml
     FROM bidang_hukum b
     LEFT JOIN artikel a ON a.bidang_id = b.id AND a.status = 'terbit'
     GROUP BY b.id, b.nama
     ORDER BY b.id"
)->fetchAll();
$semuaTerbit = (int) $pdo->query("SELECT COUNT(*) FROM artikel WHERE status = 'terbit'")->fetchColumn();

$judul = 'Artikel Hukum';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= e(url('assets/css/halaman_dalam.css')) ?>">

<section class="hd-hero">
  <div class="container">
    <span class="hd-kicker">Artikel hukum</span>
    <h1>Pahami hakmu sebelum bertindak</h1>
    <p>Panduan singkat dan mudah dipahami tentang masalah hukum sehari-hari, ditulis untuk masyarakat umum.</p>
  </div>
</section>

<div class="container hd-isi">
  <div class="hd-panel">
    <form method="get" action="<?= e(url('artikel.php')) ?>">
      <?php if ($bidang_id > 0): ?><input type="hidden" name="bidang" value="<?= $bidang_id ?>"><?php endif; ?>
      <label class="form-label" for="a-q">Cari artikel</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input class="form-control" id="a-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Contoh: PHK, hak asuh, sertifikat tanah">
        <button class="btn btn-gold" type="submit">Cari</button>
      </div>
    </form>
    <div class="hd-chips">
      <a class="hd-chip <?= $bidang_id === 0 ? 'aktif' : '' ?>" href="<?= e(url('artikel.php' . ($q !== '' ? '?q=' . urlencode($q) : ''))) ?>">Semua <span class="jml"><?= $semuaTerbit ?></span></a>
      <?php foreach ($chip as $c): ?>
        <?php $tautan = 'artikel.php?' . http_build_query(array_filter(['bidang' => $c['id'], 'q' => $q], fn ($v) => $v !== '')); ?>
        <a class="hd-chip <?= (int) $c['id'] === $bidang_id ? 'aktif' : '' ?>" href="<?= e(url($tautan)) ?>">
          <i class="bi <?= art_ikon((int) $c['id']) ?>"></i> <?= e(preg_replace('/^Hukum\s+/', '', $c['nama'])) ?> <span class="jml"><?= (int) $c['jml'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="hd-info">
    <span>
      <?php if ($total === 0): ?>Tidak ada artikel<?php else: ?>Menampilkan <strong><?= count($daftar) + ($sorotan ? 1 : 0) ?></strong> dari <strong><?= $total ?></strong> artikel<?php endif; ?>
      <?php if ($totalHal > 1): ?> &middot; halaman <?= $hal ?> dari <?= $totalHal ?><?php endif; ?>
    </span>
    <?php if ($adaFilter): ?>
      <a href="<?= e(url('artikel.php')) ?>" class="text-decoration-none"><i class="bi bi-x-circle"></i> Hapus filter</a>
    <?php endif; ?>
  </div>

  <?php if ($total === 0): ?>
    <div class="hd-kosong">
      <i class="bi bi-journal-x"></i>
      <h3 class="h5 mt-2">Artikel tidak ditemukan</h3>
      <p><?= $adaFilter ? 'Coba kata kunci lain atau pilih bidang yang berbeda.' : 'Belum ada artikel yang diterbitkan. Silakan kembali lagi nanti.' ?></p>
      <?php if ($adaFilter): ?><a class="btn btn-navy" href="<?= e(url('artikel.php')) ?>">Lihat semua artikel</a><?php endif; ?>
    </div>
  <?php else: ?>
    <?php if ($sorotan) { echo art_kartu($sorotan, true); } ?>
    <?php if ($daftar): ?>
      <div class="ar-grid">
        <?php foreach ($daftar as $a) { echo art_kartu($a); } ?>
      </div>
    <?php endif; ?>
    <div class="mt-4"><?= art_pagination($hal, $totalHal, 'artikel.php', ['q' => $q, 'bidang' => $bidang_id ?: '']) ?></div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
